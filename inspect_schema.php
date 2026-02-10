<?php
require_once 'config/database.php';

try {
    $stmt = $pdo->query("DESCRIBE odt_maestro");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Columns in odt_maestro:\n";
    foreach ($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }

    echo "\n-------------------\n";

    $stmt = $pdo->query("DESCRIBE tipos_trabajos");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Columns in tipos_trabajos:\n";
    foreach ($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>