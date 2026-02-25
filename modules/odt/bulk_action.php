<?php
/**
 * [!] ARCH: Handler para acciones masivas en ODTs v2
 * Soporta: unified_update (estado + cuadrilla + orden), toggle_urgent
 * Registra historial de cambios vía ODTService
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../services/ODTService.php';
require_once '../../services/StateMachine.php';
require_once '../../services/ErrorService.php';

header('Content-Type: application/json; charset=utf-8');

$rol = $_SESSION['usuario_tipo'] ?? $_SESSION['usuario_rol'] ?? '';
$idUsuario = $_SESSION['usuario_id'] ?? 0;
$esJefe = in_array($rol, ['JefeCuadrilla', 'Jefe de Cuadrilla']);

if (!verificarSesion(false) || (!tienePermiso('odt') && !$esJefe)) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || empty($data['ids'])) {
    echo json_encode(['success' => false, 'message' => 'Datos insuficientes: IDs requeridos']);
    exit();
}

$ids = array_map('intval', $data['ids']);
$action = $data['action'] ?? '';
$odtService = new ODTService($pdo);

try {
    // ─── TOGGLE URGENTE ───
    if ($action === 'toggle_urgent') {
        $results = [];
        foreach ($ids as $id) {
            try {
                $odtService->toggleUrgente($id, $idUsuario);
                $results[] = ['id' => $id, 'ok' => true];
            } catch (\Exception $e) {
                $results[] = ['id' => $id, 'ok' => false, 'error' => $e->getMessage()];
            }
        }
        echo json_encode(['success' => true, 'results' => $results]);
        exit();
    }

    // ─── ELIMINACIÓN MASIVA ───
    if ($action === 'bulk_delete') {
        // Solo roles con permisos administrativos
        if ($esJefe) {
            echo json_encode(['success' => false, 'message' => 'No tenés permisos para eliminar ODTs.']);
            exit();
        }

        $deleted = 0;
        $errors = [];

        $pdo->beginTransaction();
        try {
            foreach ($ids as $id) {
                try {
                    // Limpiar dependencias antes de borrar el maestro
                    $pdo->prepare("DELETE FROM odt_fotos WHERE id_odt = ?")->execute([$id]);
                    $pdo->prepare("DELETE FROM odt_items WHERE id_odt = ?")->execute([$id]);
                    $pdo->prepare("DELETE FROM odt_materiales WHERE id_odt = ?")->execute([$id]);
                    $pdo->prepare("DELETE FROM odt_historial WHERE id_odt = ?")->execute([$id]);
                    $pdo->prepare("DELETE FROM odt_maestro WHERE id_odt = ?")->execute([$id]);
                    $deleted++;
                } catch (\PDOException $e) {
                    $errors[] = "ODT #{$id}: " . $e->getMessage();
                }
            }

            if (empty($errors)) {
                $pdo->commit();
            } else {
                $pdo->rollBack();
            }
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'Error critico: ' . $e->getMessage()]);
            exit();
        }

        $response = ['success' => true, 'message' => "{$deleted} ODT(s) eliminadas correctamente."];
        if (!empty($errors)) {
            $response['success'] = false;
            $response['message'] = 'Algunas ODTs no pudieron eliminarse.';
            $response['warnings'] = $errors;
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

    // ─── UNIFIED UPDATE (estado + cuadrilla) ───
    if ($action === 'unified_update') {
        $opsPerformed = 0;
        $errors = [];

        // 1. ASIGNACIÓN DE CUADRILLA
        if (!empty($data['cuadrilla']) && !empty($data['fecha'])) {
            $cuadrilla = (int) $data['cuadrilla'];
            $fecha = $data['fecha'];
            $orden = (int) ($data['orden'] ?? 1);

            foreach ($ids as $id) {
                try {
                    $odtService->asignar($id, $cuadrilla, $fecha, $orden, $idUsuario);
                    $orden++; // Auto-increment order for batch assignments
                } catch (\Exception $e) {
                    $errors[] = "ODT #{$id}: " . $e->getMessage();
                }
            }
            $opsPerformed++;
        }

        // 2. CAMBIO DE ESTADO (solo si NO se asignó cuadrilla, ya que asignar cambia a 'Asignado')
        if (!empty($data['estado']) && empty($data['cuadrilla'])) {
            $nuevoEstado = $data['estado'];

            if (!StateMachine::isValidState($nuevoEstado)) {
                echo json_encode(['success' => false, 'message' => "Estado inválido: {$nuevoEstado}"]);
                exit();
            }

            foreach ($ids as $id) {
                try {
                    $odtService->cambiarEstado($id, $nuevoEstado, $idUsuario);
                } catch (\InvalidArgumentException $e) {
                    // Transición inválida — no fatal, reportar
                    $errors[] = "ODT #{$id}: " . $e->getMessage();
                } catch (\Exception $e) {
                    $errors[] = "ODT #{$id}: Error - " . $e->getMessage();
                }
            }
            $opsPerformed++;
        }

        // Si se asignó cuadrilla Y se pidió un estado diferente de 'Asignado'
        if (!empty($data['cuadrilla']) && !empty($data['estado']) && $data['estado'] !== 'Asignado') {
            $nuevoEstado = $data['estado'];
            if (StateMachine::isValidState($nuevoEstado)) {
                foreach ($ids as $id) {
                    try {
                        $odtService->cambiarEstado($id, $nuevoEstado, $idUsuario);
                    } catch (\Exception $e) {
                        $errors[] = "ODT #{$id}: " . $e->getMessage();
                    }
                }
                $opsPerformed++;
            }
        }

        if ($opsPerformed > 0) {
            $response = ['success' => true, 'message' => 'Acciones aplicadas correctamente'];
            if (!empty($errors)) {
                $response['warnings'] = $errors;
            }
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se seleccionó ninguna acción (Cuadrilla o Estado)']);
        }
        exit();
    }

    // ─── LEGACY: Cambio de estado directo (sin action) ───
    if (!empty($data['estado']) && empty($action)) {
        $nuevoEstado = $data['estado'];

        if (!StateMachine::isValidState($nuevoEstado)) {
            echo json_encode(['success' => false, 'message' => "Estado inválido: {$nuevoEstado}"]);
            exit();
        }

        $errors = [];
        foreach ($ids as $id) {
            try {
                $odtService->cambiarEstado($id, $nuevoEstado, $idUsuario);
            } catch (\Exception $e) {
                $errors[] = "ODT #{$id}: " . $e->getMessage();
            }
        }

        $response = ['success' => true, 'count' => count($ids) - count($errors)];
        if (!empty($errors)) {
            $response['warnings'] = $errors;
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

    echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);

} catch (\Exception $e) {
    $error = ErrorService::capture($e, 'BulkActionController', 'bulk_action', $data);
    ErrorService::respondWithError($error);
}