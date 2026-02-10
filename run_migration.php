<?php
require_once 'config/database.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sql = file_get_contents('sql/migration_fuel_column.sql');

    // Split by semicolon to execute multiple statements (basic parser)
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            try {
                $pdo->exec($stmt);
                echo "Executed: " . substr($stmt, 0, 50) . "...\n";
            } catch (Exception $e) {
                echo "Error executing: " . substr($stmt, 0, 50) . "... -> " . $e->getMessage() . "\n";
            }
        }
    }
    echo "Migration completed.\n";

} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage();
}
?>