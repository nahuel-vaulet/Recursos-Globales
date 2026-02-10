<?php
/**
 * Módulo Herramientas - API Sanciones
 * [!] ARQUITECTURA: Endpoints AJAX para gestión de sanciones
 */
require_once '../../config/database.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

verificarSesion();

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'aplicar':
            $id_sancion = intval($input['id_sancion']);

            $pdo->prepare("UPDATE herramientas_sanciones SET estado = 'Aplicada' WHERE id_sancion = ?")
                ->execute([$id_sancion]);

            registrarAccion('EDITAR', 'herramientas_sanciones', "Sanción aplicada", $id_sancion);

            echo json_encode(['success' => true, 'message' => 'Sanción aplicada']);
            break;

        case 'anular':
            $id_sancion = intval($input['id_sancion']);

            $pdo->prepare("UPDATE herramientas_sanciones SET estado = 'Anulada' WHERE id_sancion = ?")
                ->execute([$id_sancion]);

            registrarAccion('EDITAR', 'herramientas_sanciones', "Sanción anulada", $id_sancion);

            echo json_encode(['success' => true, 'message' => 'Sanción anulada']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>