<?php
require 'config/database.php';

echo "--- ALL MATERIALS ---\n";
$stmt = $pdo->query("SELECT id_material, nombre, costo_primario FROM maestro_materiales LIMIT 50");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode($row) . "\n";
}

echo "\n--- TANQUES ---\n";
$stmt = $pdo->query("SELECT * FROM spot_tanques");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode($row) . "\n";
}
?>