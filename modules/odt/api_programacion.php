<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos del body
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id_odt']) || !isset($input['id_cuadrilla']) || !isset($input['fecha_programada']) || !isset($input['turno'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$id_odt = $input['id_odt'];
$id_cuadrilla = $input['id_cuadrilla'];
$fecha = $input['fecha_programada'];
$turno = $input['turno'];

try {
    $pdo->beginTransaction();

    // 1. Insertar o actualizar en programacion_semanal
    // (Asumimos una nueva entrada por cada programación)
    $stmt = $pdo->prepare("INSERT INTO programacion_semanal (id_odt, id_cuadrilla, fecha_programada, turno, estado_programacion) VALUES (?, ?, ?, ?, 'Tildado_Admin')");
    $stmt->execute([$id_odt, $id_cuadrilla, $fecha, $turno]);

    // 2. Actualizar estado de ODT
    $stmtUpdate = $pdo->prepare("UPDATE odt_maestro SET estado_gestion = 'Programado' WHERE id_odt = ?");
    $stmtUpdate->execute([$id_odt]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Programación guardada correctamente']);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en base de datos: ' . $e->getMessage()]);
}
