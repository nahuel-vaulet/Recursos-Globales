<?php
/**
 * Módulo Herramientas - API para operaciones AJAX
 * [!] ARQUITECTURA: Endpoints para asignar, devolver y otras acciones
 * [✓] AUDITORÍA CRUD: Todas las operaciones registran movimientos
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

verificarSesion();

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'asignar':
            $id_herramienta = intval($input['id_herramienta']);
            $id_cuadrilla = intval($input['id_cuadrilla']);
            $observaciones = trim($input['observaciones'] ?? '');

            // Actualizar herramienta
            $stmt = $pdo->prepare("UPDATE herramientas SET id_cuadrilla_asignada = ?, estado = 'Asignada' WHERE id_herramienta = ?");
            $stmt->execute([$id_cuadrilla, $id_herramienta]);

            // Registrar movimiento
            $stmtMov = $pdo->prepare("INSERT INTO herramientas_movimientos (id_herramienta, tipo_movimiento, id_cuadrilla, observaciones, created_by) VALUES (?, 'Asignacion', ?, ?, ?)");
            $stmtMov->execute([$id_herramienta, $id_cuadrilla, $observaciones, $_SESSION['user_id'] ?? null]);

            registrarAccion('ASIGNAR', 'herramientas', "Herramienta asignada a cuadrilla #$id_cuadrilla", $id_herramienta);

            echo json_encode(['success' => true, 'message' => 'Herramienta asignada correctamente']);
            break;

        case 'devolver':
            $id_herramienta = intval($input['id_herramienta']);

            // Obtener cuadrilla actual para el registro
            $stmt = $pdo->prepare("SELECT id_cuadrilla_asignada FROM herramientas WHERE id_herramienta = ?");
            $stmt->execute([$id_herramienta]);
            $h = $stmt->fetch();
            $id_cuadrilla_anterior = $h['id_cuadrilla_asignada'];

            // Actualizar herramienta
            $stmt = $pdo->prepare("UPDATE herramientas SET id_cuadrilla_asignada = NULL, estado = 'Disponible' WHERE id_herramienta = ?");
            $stmt->execute([$id_herramienta]);

            // Registrar movimiento
            $stmtMov = $pdo->prepare("INSERT INTO herramientas_movimientos (id_herramienta, tipo_movimiento, id_cuadrilla, observaciones, created_by) VALUES (?, 'Devolucion', ?, 'Devolución al depósito', ?)");
            $stmtMov->execute([$id_herramienta, $id_cuadrilla_anterior, $_SESSION['user_id'] ?? null]);

            registrarAccion('DEVOLVER', 'herramientas', "Herramienta devuelta al depósito", $id_herramienta);

            echo json_encode(['success' => true, 'message' => 'Herramienta devuelta al depósito']);
            break;

        case 'reparacion':
            $id_herramienta = intval($input['id_herramienta']);
            $observaciones = trim($input['observaciones'] ?? '');

            $stmt = $pdo->prepare("UPDATE herramientas SET estado = 'Reparación' WHERE id_herramienta = ?");
            $stmt->execute([$id_herramienta]);

            $stmtMov = $pdo->prepare("INSERT INTO herramientas_movimientos (id_herramienta, tipo_movimiento, observaciones, created_by) VALUES (?, 'Reparacion', ?, ?)");
            $stmtMov->execute([$id_herramienta, $observaciones, $_SESSION['user_id'] ?? null]);

            echo json_encode(['success' => true, 'message' => 'Herramienta enviada a reparación']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>