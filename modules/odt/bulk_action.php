<?php
/**
 * [!] ARCH: Handler para acciones masivas en ODTs
 * [✓] AUDIT: Recepción de JSON, validación y ejecución por lotes
 */
require_once '../../includes/auth.php';

// [✓] AUDIT: Verificar permisos y sesión
// Jefe de Cuadrilla no tiene permiso 'odt' pero necesita usar este endpoint para gestión básica
// session_start(); // YA INICIADA EN auth.php
$rol = $_SESSION['usuario_rol'] ?? '';
$esJefe = in_array($rol, ['JefeCuadrilla', 'Jefe de Cuadrilla']);

// [DEBUG] Log attempt
file_put_contents('debug_bulk.txt', date('Y-m-d H:i:s') . " - User: " . ($_SESSION['usuario_nombre'] ?? 'Guest') . " | Rol: '$rol' | HasPerm: " . (tienePermiso('odt') ? 'YES' : 'NO') . " | EsJefe: " . ($esJefe ? 'YES' : 'NO') . "\n", FILE_APPEND);

if (!verificarSesion(false) || (!tienePermiso('odt') && !$esJefe)) {
    file_put_contents('debug_bulk.txt', " -> DENIED\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => "Acceso denegado. Rol: '$rol'"]);
    exit();
}

// Recibir datos JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// [DEBUG] Log received data
file_put_contents('debug_bulk.txt', " -> Received: " . $input . "\n", FILE_APPEND);

if (!$data || empty($data['ids'])) {
    file_put_contents('debug_bulk.txt', " -> ERROR: No IDs\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Datos insuficientes']);
    exit();
}

$ids = $data['ids'];

// Validation for simple status change
if (!isset($data['action']) || $data['action'] !== 'assign_squad') {
    if (empty($data['estado'])) {
        echo json_encode(['success' => false, 'message' => 'Estado requerido']);
        exit();
    }
    $nuevoEstado = $data['estado'];
    $validEstados = ['Sin Programar', 'Programación Solicitada', 'Programado', 'Ejecución', 'Ejecutado', 'Precertificada', 'Finalizado', 'Re-programar', 'Aprobado por inspector', 'Retrabajo', 'Postergado'];

    if (!in_array($nuevoEstado, $validEstados)) {
        echo json_encode(['success' => false, 'message' => 'Estado inválido']);
        exit();
    }
}

try {
    $pdo->beginTransaction();

    // Detectar tipo de acción
    // Detectar tipo de acción UNIFICADA
    if (isset($data['action']) && $data['action'] === 'unified_update') {
        $ids = $data['ids'];
        $opsPerformed = 0;

        file_put_contents('debug_bulk.txt', " -> Processing Unified Update for " . count($ids) . " IDs\n", FILE_APPEND);

        // 1. ASIGNACIÓN DE CUADRILLA (Si se envió)
        if (!empty($data['cuadrilla']) && !empty($data['fecha'])) {
            file_put_contents('debug_bulk.txt', " -> Assigning Squad: " . $data['cuadrilla'] . "\n", FILE_APPEND);

            $cuadrilla = $data['cuadrilla'];
            $fecha = $data['fecha'];

            // Prepare statements
            $stmtInsert = $pdo->prepare("INSERT INTO programacion_semanal (id_odt, id_cuadrilla, fecha_programada, turno, estado_programacion) VALUES (?, ?, ?, NULL, 'Tildado_Admin')");

            // Logica vencimiento si pasa a programado
            $stmtUpdateProg = $pdo->prepare("
                UPDATE odt_maestro o
                LEFT JOIN tipos_trabajos t ON o.id_tipologia = t.id_tipologia
                SET 
                    o.estado_gestion = 'Programado',
                    o.fecha_vencimiento = CASE 
                        WHEN t.tiempo_limite_dias > 0 THEN DATE_ADD(?, INTERVAL t.tiempo_limite_dias DAY)
                        ELSE o.fecha_vencimiento 
                    END
                WHERE o.id_odt = ?
            ");

            foreach ($ids as $id) {
                // Insertar programación
                $res = $stmtInsert->execute([$id, $cuadrilla, $fecha]);
                if (!$res) {
                    file_put_contents('debug_bulk.txt', " -> Error inserting schedule for ID $id: " . implode(" ", $stmtInsert->errorInfo()) . "\n", FILE_APPEND);
                }

                // Si NO se va a cambiar el estado explícitamente después, aplicamos 'Programado' por defecto al asignar
                if (empty($data['estado'])) {
                    $stmtUpdateProg->execute([$fecha, $id]);
                }
            }
            $opsPerformed++;
        } else {
            file_put_contents('debug_bulk.txt', " -> No Squad Assignment (empty fields)\n", FILE_APPEND);
        }

        // 2. CAMBIO DE ESTADO (Si se envió)
        if (!empty($data['estado'])) {
            $nuevoEstado = $data['estado'];
            file_put_contents('debug_bulk.txt', " -> Changing Status to: $nuevoEstado\n", FILE_APPEND);

            // Validación básica de estado
            $validEstados = ['Sin Programar', 'Programación Solicitada', 'Programado', 'Ejecución', 'Ejecutado', 'Precertificada', 'Finalizado', 'Re-programar', 'Aprobado por inspector', 'Retrabajo', 'Postergado'];
            if (in_array($nuevoEstado, $validEstados)) {
                $placeholders = str_repeat('?,', count($ids) - 1) . '?';

                if ($nuevoEstado === 'Programado') {
                    // Update con calculo de vencimiento
                    $sql = "UPDATE odt_maestro o
                        LEFT JOIN tipos_trabajos t ON o.id_tipologia = t.id_tipologia
                        SET o.estado_gestion = ?,
                            o.fecha_vencimiento = CASE 
                                WHEN t.tiempo_limite_dias > 0 THEN DATE_ADD(CURRENT_DATE, INTERVAL t.tiempo_limite_dias DAY)
                                ELSE o.fecha_vencimiento 
                            END
                        WHERE o.id_odt IN ($placeholders)";
                } else {
                    $sql = "UPDATE odt_maestro SET estado_gestion = ? WHERE id_odt IN ($placeholders)";
                }

                $stmt = $pdo->prepare($sql);
                $params = array_merge([$nuevoEstado], $ids);
                $resUpdates = $stmt->execute($params);

                if (!$resUpdates) {
                    file_put_contents('debug_bulk.txt', " -> Error updating status: " . implode(" ", $stmt->errorInfo()) . "\n", FILE_APPEND);
                } else {
                    file_put_contents('debug_bulk.txt', " -> Updated " . $stmt->rowCount() . " rows\n", FILE_APPEND);
                }

                $opsPerformed++;
            }
        } else {
            file_put_contents('debug_bulk.txt', " -> No Status Change (empty field)\n", FILE_APPEND);
        }

        if ($opsPerformed > 0) {
            $pdo->commit();
            file_put_contents('debug_bulk.txt', " -> COMMIT SUCCESS\n", FILE_APPEND);
            echo json_encode(['success' => true, 'message' => 'Acciones aplicadas correctamente']);
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'No se seleccionó ninguna acción (Cuadrilla o Estado)']);
            $pdo->rollBack();
            file_put_contents('debug_bulk.txt', " -> ROLLBACK (No ops)\n", FILE_APPEND);
            exit();
        }
    }

    // Fallback para legacy calls (si quedan)
    if (isset($data['action']) && $data['action'] === 'assign_squad') {
        // ... (Legacy code could be here, but we replaced it)
        echo json_encode(['success' => false, 'message' => 'API deprecada, use unified_update']);
        $pdo->rollBack();
        exit();
    }

    // Default: Change Status Logic (Legacy fallback for updateOdtStatus)
    if (isset($data['estado']) && !isset($data['action'])) {
        $nuevoEstado = $data['estado'];

        $placeholders = str_repeat('?,', count($ids) - 1) . '?';

        if ($nuevoEstado === 'Programado') {
            $sql = "UPDATE odt_maestro o
                LEFT JOIN tipos_trabajos t ON o.id_tipologia = t.id_tipologia
                SET o.estado_gestion = ?,
                    o.fecha_vencimiento = CASE 
                        WHEN t.tiempo_limite_dias > 0 THEN DATE_ADD(CURRENT_DATE, INTERVAL t.tiempo_limite_dias DAY)
                        ELSE o.fecha_vencimiento 
                    END
                WHERE o.id_odt IN ($placeholders)";
        } else {
            $sql = "UPDATE odt_maestro SET estado_gestion = ? WHERE id_odt IN ($placeholders)";
        }

        $stmt = $pdo->prepare($sql);
        $params = array_merge([$nuevoEstado], $ids);
        $stmt->execute($params);

        $pdo->commit();
        echo json_encode(['success' => true, 'count' => $stmt->rowCount()]);
        exit();
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>