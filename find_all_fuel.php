<?php
require_once 'config/database.php';
try {
    $stmt = $pdo->query("SELECT id_material, nombre, codigo FROM maestro_materiales WHERE nombre LIKE '%combustible%' OR nombre LIKE '%nafta%' OR nombre LIKE '%gasoil%' OR nombre LIKE '%diesel%' OR nombre LIKE '%bencina%'");
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['all_fuel_materials' => $all]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>