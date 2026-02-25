<?php
require_once 'config/database.php';
try {
    echo "Testing insert for 1000...\n";
    $stmt = $pdo->prepare("INSERT INTO movimientos (nro_documento, tipo_movimiento, id_material, cantidad, id_cuadrilla, usuario_despacho, fecha_hora) VALUES (?, 'Entrega_Oficina_Cuadrilla', ?, ?, ?, ?, NOW())");
    $stmt->execute(['TEST-1000', 1000, 1.0, 1, 1]);
    echo "Success for 1000\n";

    echo "Testing insert for 1001...\n";
    $stmt->execute(['TEST-1001', 1001, 1.0, 1, 1]);
    echo "Success for 1001\n";

    echo "Testing insert for 999...\n";
    $stmt->execute(['TEST-999', 999, 1.0, 1, 1]);
    echo "Success for 999\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>