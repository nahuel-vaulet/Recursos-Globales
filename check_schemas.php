<?php
require_once 'config/database.php';

function describeTable($pdo, $table)
{
    echo "--- TABLE: $table ---\n";
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c) {
            echo $c['Field'] . " | " . $c['Type'] . "\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

describeTable($pdo, 'tipos_trabajos');
describeTable($pdo, 'tipos_trabajo'); // My new one
?>