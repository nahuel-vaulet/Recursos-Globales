<?php
require_once 'config/database.php';
try {
    $tanks = $pdo->query("SELECT * FROM combustibles_tanques")->fetchAll(PDO::FETCH_ASSOC);
    $materials = $pdo->query("SELECT id_material, nombre FROM maestro_materiales WHERE nombre LIKE '%combustible%' OR nombre LIKE '%nafta%' OR nombre LIKE '%gasoil%' OR nombre LIKE '%diesel%'")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['tanks' => $tanks, 'materials' => $materials]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>