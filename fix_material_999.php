<?php
require_once 'config/database.php';
try {
    $check = $pdo->query("SELECT COUNT(*) FROM maestro_materiales WHERE id_material = 999")->fetchColumn();
    if (!$check) {
        // Try to insert it. We need to be careful with column names.
        // Based on schema: id_material, codigo, nombre, descripcion, unidad_medida...
        $stmt = $pdo->prepare("INSERT INTO maestro_materiales (id_material, codigo, nombre, unidad_medida, costo_primario) VALUES (999, 'COMB-001', 'GASOIL / COMBUSTIBLE', 'Litros', 1200.00)");
        $stmt->execute();
        echo json_encode(['status' => 'success', 'message' => 'Material 999 created']);
    } else {
        echo json_encode(['status' => 'exists', 'message' => 'Material 999 already exists']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>