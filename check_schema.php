<?php
require_once 'config/database.php';

function describeTable($pdo, $table)
{
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        echo "TABLE: $table\n";
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
            echo $col['Field'] . " - " . $col['Type'] . "\n";
        }
        echo "\n";
    } catch (Exception $e) {
        echo "TABLE $table NOT FOUND: " . $e->getMessage() . "\n\n";
    }
}

describeTable($pdo, 'odt_maestro');
describeTable($pdo, 'cuadrillas');
describeTable($pdo, 'programacion_semanal');
