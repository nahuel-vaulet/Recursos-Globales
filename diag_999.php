<?php
require_once 'config/database.php';
try {
    $stmt = $pdo->prepare("SELECT * FROM maestro_materiales WHERE id_material = 999");
    $stmt->execute();
    $mat = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt2 = $pdo->query("SELECT id_material, nombre FROM maestro_materiales LIMIT 5");
    $all = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['mat_999' => $mat, 'top_5' => $all]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>