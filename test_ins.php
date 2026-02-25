<?php
require_once 'config/database.php';
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO movimientos (nro_documento, tipo_movimiento, id_material, cantidad, id_cuadrilla, usuario_despacho, fecha_hora) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    // Testing with id_material 999
    $stmt->execute(['TEST-ID', 'Entrega_Oficina_Cuadrilla', 999, 1.0, 1, 1]);
    $pdo->rollBack();
    echo json_encode(['status' => 'success', 'message' => 'Test insertion worked']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>