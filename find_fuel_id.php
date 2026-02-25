<?php
require_once 'config/database.php';
try {
    $stmt = $pdo->query("SELECT id_material, nombre_material, unidad_medida FROM maestro_materiales WHERE nombre_material LIKE '%combustible%' OR nombre_material LIKE '%nafta%' OR nombre_material LIKE '%gasoil%' OR nombre_material LIKE '%diesel%'");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['results' => $results]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>