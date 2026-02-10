<?php
/**
 * [!] ARCH: Endpoint para guardar ODT (CREATE/UPDATE)
 * [→] EDITAR: Validaciones y campos según necesidad
 * [✓] AUDIT: Valida nro_odt_assa único, registra en auditoría
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// [✓] AUDIT: Verificar sesión
verificarSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// [→] EDITAR: Obtener datos del formulario
$id = $_POST['id_odt'] ?? null;
$nro_odt_assa = trim($_POST['nro_odt_assa'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$id_tipologia = !empty($_POST['id_tipologia']) ? $_POST['id_tipologia'] : null;
$prioridad = $_POST['prioridad'] ?? 'Normal';
$estado_gestion = $_POST['estado_gestion'] ?? 'Sin Programar';
$fecha_inicio_plazo = !empty($_POST['fecha_inicio_plazo']) ? $_POST['fecha_inicio_plazo'] : null;
$fecha_vencimiento = !empty($_POST['fecha_vencimiento']) ? $_POST['fecha_vencimiento'] : null;
$avance = trim($_POST['avance'] ?? '');
$inspector = trim($_POST['inspector'] ?? '');

// [✓] ARCH: Lógica de Negocio RBAC
// [✓] ARCH: Lógica de Negocio RBAC
// El Inspector ASSA no puede elegir el estado inicial, siempre es 'Sin Programar'
// Pero SÍ puede modificar el estado de ODTs ya existentes.
if ($_SESSION['usuario_rol'] === 'Inspector ASSA' && empty($id)) {
    $estado_gestion = 'Sin Programar';
    // // AUDIT: Log de forzado de integridad
}

// [✓] AUDIT: Validación de campos obligatorios
if (empty($nro_odt_assa) || empty($direccion)) {
    $_SESSION['error'] = 'Número ODT y Dirección son obligatorios.';
    header('Location: form.php' . ($id ? "?id=$id" : ''));
    exit;
}

// [✓] AUDIT: Validar que nro_odt_assa sea único
// [✓] AUDIT: Validar que nro_odt_assa sea único
try {
    $checkSql = "SELECT id_odt FROM odt_maestro WHERE nro_odt_assa = ?";
    if ($id) {
        $checkSql .= " AND id_odt != ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$nro_odt_assa, $id]);
    } else {
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$nro_odt_assa]);
    }

    if ($checkStmt->fetch()) {
        $_SESSION['error'] = 'El número de ODT ya existe en el sistema.';
        header('Location: form.php' . ($id ? "?id=$id" : ''));
        exit;
    }

    if ($id) {
        // [✓] AUDIT: UPDATE con WHERE específico
        $sql = "UPDATE odt_maestro SET 
                nro_odt_assa = ?, direccion = ?, id_tipologia = ?,
                prioridad = ?, estado_gestion = ?,
                fecha_inicio_plazo = ?, fecha_vencimiento = ?,
                avance = ?, inspector = ?
                WHERE id_odt = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nro_odt_assa,
            $direccion,
            $id_tipologia,
            $prioridad,
            $estado_gestion,
            $fecha_inicio_plazo,
            $fecha_vencimiento,
            $avance,
            $inspector,
            $id
        ]);

        // [✓] AUDIT: Registrar edición
        registrarAccion('EDITAR', 'odt_maestro', "ODT editada: $nro_odt_assa", $id);

    } else {
        // [✓] AUDIT: INSERT
        $sql = "INSERT INTO odt_maestro 
                (nro_odt_assa, direccion, id_tipologia, prioridad, estado_gestion,
                 fecha_inicio_plazo, fecha_vencimiento, avance, inspector)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nro_odt_assa,
            $direccion,
            $id_tipologia,
            $prioridad,
            $estado_gestion,
            $fecha_inicio_plazo,
            $fecha_vencimiento,
            $avance,
            $inspector
        ]);

        $newId = $pdo->lastInsertId();
    }

    // [New] Manejo de Fotos (Shared Logic)
    $uploadDir = '../../uploads/odt_photos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // ID a usar para las fotos
    $targetOdtId = $id ? $id : ($newId ?? null);

    if ($targetOdtId) {
        // Procesar subidas usando la función helper definida arriba o aquí
        // Definimos la función helper localmente si no existe, o usamos lógica inline
        // Para limpieza, usaremos lógica inline o una closure si fuera PHP moderno, 
        // pero mantendremos la función definida anteriormente fuera del try/catch si es posible.
        // Como replace_file_content edita un bloque, definiremos la función helper al principio del archivo en otro paso si es necesario,
        // pero por ahora la incluiremos aquí para asegurar que exista.

        if (!function_exists('procesarFotoGuardar')) {
            function procesarFotoGuardar($fileInputName, $tipo, $odtId, $pdo, $uploadDir)
            {
                if (!empty($_FILES[$fileInputName]['name'])) {
                    if (is_array($_FILES[$fileInputName]['name'])) {
                        foreach ($_FILES[$fileInputName]['name'] as $key => $name) {
                            if ($_FILES[$fileInputName]['error'][$key] === UPLOAD_ERR_OK) {
                                $tmpName = $_FILES[$fileInputName]['tmp_name'][$key];
                                $fileName = time() . '_' . uniqid() . '_' . basename($name);
                                $targetPath = $uploadDir . $fileName;
                                if (move_uploaded_file($tmpName, $targetPath)) {
                                    $stmt = $pdo->prepare("INSERT INTO odt_fotos (id_odt, tipo_foto, ruta_archivo) VALUES (?, ?, ?)");
                                    $stmt->execute([$odtId, $tipo, 'uploads/odt_photos/' . $fileName]);
                                }
                            }
                        }
                    } else {
                        if ($_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
                            $tmpName = $_FILES[$fileInputName]['tmp_name'];
                            $fileName = time() . '_' . uniqid() . '_' . basename($_FILES[$fileInputName]['name']);
                            $targetPath = $uploadDir . $fileName;
                            if (move_uploaded_file($tmpName, $targetPath)) {
                                $stmt = $pdo->prepare("INSERT INTO odt_fotos (id_odt, tipo_foto, ruta_archivo) VALUES (?, ?, ?)");
                                $stmt->execute([$odtId, $tipo, 'uploads/odt_photos/' . $fileName]);
                            }
                        }
                    }
                }
            }
        }

        procesarFotoGuardar('foto_odt', 'ODT', $targetOdtId, $pdo, $uploadDir);
        // Compatibilidad: procesar tanto foto_trabajo (viejo) como fotos_trabajo (nuevo)
        procesarFotoGuardar('foto_trabajo', 'TRABAJO', $targetOdtId, $pdo, $uploadDir);
        procesarFotoGuardar('fotos_trabajo', 'TRABAJO', $targetOdtId, $pdo, $uploadDir);
        procesarFotoGuardar('fotos_extras', 'EXTRA', $targetOdtId, $pdo, $uploadDir);

        if ($id) {
            registrarAccion('EDITAR', 'odt_fotos', "Fotos actualizadas para ODT: $nro_odt_assa", $id);
        } else {
            registrarAccion('CREAR', 'odt_fotos', "Fotos agregadas para ODT: $nro_odt_assa", $targetOdtId);
        }
    }

    // [!] ARCH: Guardar Ítems de Trabajo (delete + re-insert)
    $targetOdtId = $id ? $id : ($newId ?? null);
    if ($targetOdtId && isset($_POST['items'])) {
        // Borrar ítems anteriores
        $pdo->prepare("DELETE FROM odt_items WHERE id_odt = ?")->execute([$targetOdtId]);

        // Insertar nuevos ítems
        $stmtItem = $pdo->prepare("INSERT INTO odt_items 
            (id_odt, descripcion_item, seleccionado, medida_1, medida_2, medida_3, unidad) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");

        foreach ($_POST['items'] as $item) {
            $descripcion = trim($item['descripcion'] ?? '');
            if (empty($descripcion))
                continue;

            $seleccionado = isset($item['seleccionado']) ? 1 : 0;
            $medida1 = !empty($item['medida_1']) ? floatval($item['medida_1']) : null;
            $medida2 = !empty($item['medida_2']) ? floatval($item['medida_2']) : null;
            $medida3 = !empty($item['medida_3']) ? floatval($item['medida_3']) : null;
            $unidad = $item['unidad'] ?? 'm';

            $stmtItem->execute([
                $targetOdtId,
                $descripcion,
                $seleccionado,
                $medida1,
                $medida2,
                $medida3,
                $unidad
            ]);
        }
    }

    // [!] ARCH: Guardar Materiales Utilizados (con ajuste de stock de cuadrilla)
    $targetOdtId = $id ? $id : ($newId ?? null);
    $idCuadrillaAsignada = $_POST['id_cuadrilla_asignada'] ?? null;

    if ($targetOdtId && $idCuadrillaAsignada && isset($_POST['materiales'])) {
        // 1. Leer cantidades previas para calcular delta neto
        $stmtPrev = $pdo->prepare("SELECT id_material, SUM(cantidad) as cant_previa FROM odt_materiales WHERE id_odt = ? GROUP BY id_material");
        $stmtPrev->execute([$targetOdtId]);
        $previas = [];
        foreach ($stmtPrev->fetchAll(PDO::FETCH_ASSOC) as $p) {
            $previas[$p['id_material']] = floatval($p['cant_previa']);
        }

        // 2. Borrar registros anteriores
        $pdo->prepare("DELETE FROM odt_materiales WHERE id_odt = ?")->execute([$targetOdtId]);

        // 3. Insertar nuevos y calcular deltas
        $stmtInsertMat = $pdo->prepare("INSERT INTO odt_materiales (id_odt, id_material, cantidad) VALUES (?, ?, ?)");
        $stmtUpdateStock = $pdo->prepare("UPDATE stock_cuadrilla SET cantidad = cantidad - ? WHERE id_cuadrilla = ? AND id_material = ?");
        $stmtMovimiento = $pdo->prepare("INSERT INTO movimientos (tipo_movimiento, id_material, cantidad, id_cuadrilla, id_odt, usuario_despacho) VALUES ('Consumo_Cuadrilla_Obra', ?, ?, ?, ?, ?)");

        $nuevas = [];
        foreach ($_POST['materiales'] as $mat) {
            $idMat = intval($mat['id_material'] ?? 0);
            $cant = floatval($mat['cantidad'] ?? 0);
            if ($idMat <= 0 || $cant <= 0)
                continue;

            // Insertar en odt_materiales
            $stmtInsertMat->execute([$targetOdtId, $idMat, $cant]);

            // Acumular por material (puede haber duplicados)
            $nuevas[$idMat] = ($nuevas[$idMat] ?? 0) + $cant;
        }

        // 4. Ajustar stock solo por la diferencia neta
        $userId = $_SESSION['usuario_id'] ?? null;
        foreach ($nuevas as $idMat => $cantNueva) {
            $cantPrevia = $previas[$idMat] ?? 0;
            $delta = $cantNueva - $cantPrevia; // positivo = más consumo, negativo = devolución

            if ($delta != 0) {
                // Descontar delta del stock (si delta negativo, devuelve al stock)
                $stmtUpdateStock->execute([$delta, $idCuadrillaAsignada, $idMat]);

                // Registrar movimiento (solo si hay consumo nuevo neto)
                if ($delta > 0) {
                    $stmtMovimiento->execute([$idMat, $delta, $idCuadrillaAsignada, $targetOdtId, $userId]);
                }
            }

            // Quitar de previas para detectar materiales eliminados
            unset($previas[$idMat]);
        }

        // 5. Devolver stock de materiales que fueron removidos completamente
        foreach ($previas as $idMat => $cantPrevia) {
            if ($cantPrevia > 0) {
                // Devolver al stock
                $pdo->prepare("UPDATE stock_cuadrilla SET cantidad = cantidad + ? WHERE id_cuadrilla = ? AND id_material = ?")
                    ->execute([$cantPrevia, $idCuadrillaAsignada, $idMat]);
            }
        }
    }

    header('Location: index.php?msg=saved');



} catch (PDOException $e) {
    // [!] ARCH: No exponer errores SQL
    error_log("Error en odt/save.php: " . $e->getMessage());
    $_SESSION['error'] = 'Error al guardar la ODT.';
    header('Location: form.php' . ($id ? "?id=$id" : ''));
}

exit;
?>