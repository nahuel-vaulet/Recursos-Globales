<?php
require_once 'config/database.php';
try {
    echo "--- MAESTRO MATERIALES (FUEL RELATED) ---\n";
    $stmt = $pdo->query("SELECT id_material, nombre, codigo, unidad_medida FROM maestro_materiales WHERE nombre LIKE '%combustible%' OR nombre LIKE '%nafta%' OR nombre LIKE '%gasoil%' OR nombre LIKE '%diesel%' OR id_material IN (999, 1000, 1001)");
    $mats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($mats);

    echo "\n--- SPOT_REMITO_ITEMS SCHEMA ---\n";
    $stmt2 = $pdo->query("DESCRIBE spot_remito_items");
    print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));

    echo "\n--- MOVIMIENTOS SCHEMA ---\n";
    $stmt3 = $pdo->query("DESCRIBE movimientos");
    print_r($stmt3->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>