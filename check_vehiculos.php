<?php
require_once 'config/database.php';
try {
    $stmt = $pdo->query("DESCRIBE vehiculos");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns: " . implode(", ", $columns) . "\n";

    if (in_array('id_cuadrilla', $columns)) {
        echo "HAS_ID_CUADRILLA: YES\n";
    } else {
        echo "HAS_ID_CUADRILLA: NO\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>