<?php
header('Content-Type: application/json');
require_once '../../../../config/database.php';

$squad_id = $_GET['squad_id'] ?? null;

if (!$squad_id) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id_personal, nombre_apellido FROM personal WHERE id_cuadrilla = ? ORDER BY nombre_apellido ASC");
    $stmt->execute([$squad_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
