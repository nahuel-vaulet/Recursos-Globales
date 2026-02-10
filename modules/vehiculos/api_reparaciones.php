<?php
/**
 * API para CRUD de reparaciones de vehículos
 * Usado vía AJAX desde form.php y view.php
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';

verificarSesion();

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Acción no especificada']);
    exit;
}

try {
    switch ($input['action']) {
        case 'add':
            if (empty($input['id_vehiculo']) || empty($input['fecha']) || empty($input['descripcion'])) {
                echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios']);
                exit;
            }

            $stmt = $pdo->prepare("
                INSERT INTO vehiculos_reparaciones 
                (id_vehiculo, fecha, descripcion, realizado_por, costo, moneda, tiempo_horas, codigos_repuestos, proveedor_repuestos)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                intval($input['id_vehiculo']),
                $input['fecha'],
                trim($input['descripcion']),
                trim($input['realizado_por'] ?? '') ?: null,
                !empty($input['costo']) ? floatval($input['costo']) : null,
                $input['moneda'] ?? 'ARS',
                !empty($input['tiempo_horas']) ? floatval($input['tiempo_horas']) : null,
                trim($input['codigos_repuestos'] ?? '') ?: null,
                trim($input['proveedor_repuestos'] ?? '') ?: null,
            ]);

            $newId = $pdo->lastInsertId();
            registrarAccion('CREAR', 'vehiculos_reparaciones', "Reparación agregada", $newId);

            echo json_encode(['success' => true, 'id' => $newId]);
            break;

        case 'delete':
            if (empty($input['id_reparacion'])) {
                echo json_encode(['success' => false, 'message' => 'ID de reparación faltante']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM vehiculos_reparaciones WHERE id_reparacion = ?");
            $stmt->execute([intval($input['id_reparacion'])]);

            registrarAccion('ELIMINAR', 'vehiculos_reparaciones', "Reparación eliminada", $input['id_reparacion']);

            echo json_encode(['success' => true]);
            break;

        case 'list':
            if (empty($input['id_vehiculo'])) {
                echo json_encode(['success' => false, 'message' => 'ID de vehículo faltante']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT * FROM vehiculos_reparaciones WHERE id_vehiculo = ? ORDER BY fecha DESC");
            $stmt->execute([intval($input['id_vehiculo'])]);
            $reparaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $reparaciones]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error DB: ' . $e->getMessage()]);
}
?>