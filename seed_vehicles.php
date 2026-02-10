<?php
require_once 'config/database.php';

try {
    $pdo->beginTransaction();

    // 1. Create Vehicles
    echo "Creating vehicles...\n";
    $pdo->exec("INSERT INTO vehiculos (marca, modelo, patente, estado, tipo) VALUES ('Toyota', 'Hilux', 'AA123BB', 'Operativo', 'Camioneta')");
    $id1 = $pdo->lastInsertId();

    $pdo->exec("INSERT INTO vehiculos (marca, modelo, patente, estado, tipo) VALUES ('Ford', 'Ranger', 'CC987DD', 'Operativo', 'Camioneta')");
    $id2 = $pdo->lastInsertId();

    // 2. Assign to Squads
    echo "Assigning vehicles to squads...\n";
    $pdo->exec("UPDATE cuadrillas SET id_vehiculo_asignado = $id1 WHERE id_cuadrilla = 1");
    $pdo->exec("UPDATE cuadrillas SET id_vehiculo_asignado = $id2 WHERE id_cuadrilla = 2");

    // 3. Create a Dispatch for Today (so they see fuel > 0)
    // Need a tank first
    $tank = $pdo->query("SELECT id_tanque FROM combustibles_tanques LIMIT 1")->fetchColumn();
    if ($tank) {
        echo "Creating sample dispatch...\n";
        $stmt = $pdo->prepare("INSERT INTO combustibles_despachos (id_tanque, id_vehiculo, fecha_hora, litros, usuario_despacho, usuario_conductor) VALUES (?, ?, NOW(), 45.5, 1, 'Test Driver')");
        $stmt->execute([$tank, $id1]);

        $stmt2 = $pdo->prepare("INSERT INTO combustibles_despachos (id_tanque, id_vehiculo, fecha_hora, litros, usuario_despacho, usuario_conductor) VALUES (?, ?, NOW(), 20.0, 1, 'Test Driver 2')");
        $stmt2->execute([$tank, $id2]);
    } else {
        echo "No tanks found, skipping dispatch creation.\n";
    }

    $pdo->commit();
    echo "Seeding complete. Vehicles assigned.\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}
?>