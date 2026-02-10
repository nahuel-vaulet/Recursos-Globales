<?php
require_once '../../../config/database.php';
header('Content-Type: application/json');

try {
    // 1. Fetch Squads
    $cuadrillas = $pdo->query("SELECT * FROM cuadrillas WHERE estado_operativo = 'Activa' ORDER BY nombre_cuadrilla")->fetchAll(PDO::FETCH_ASSOC);

    // 2. Fetch Personal (Drivers/Responsibles)
    $personal = $pdo->query("SELECT id_personal, nombre_apellido, id_cuadrilla FROM personal ORDER BY nombre_apellido")->fetchAll(PDO::FETCH_ASSOC);

    // 3. Fetch Vehicles
    $vehiculos = $pdo->query("SELECT id_vehiculo, marca, modelo, patente, id_cuadrilla FROM vehiculos ORDER BY marca, modelo")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'cuadrillas' => $cuadrillas,
        'personal' => $personal,
        'vehiculos' => $vehiculos
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>