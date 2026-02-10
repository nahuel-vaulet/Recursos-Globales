<?php
require_once 'config/database.php';

try {
    $sql = file_get_contents('sql/migration_odt_console.sql');
    if (!$sql)
        throw new Exception("Error reading SQL file");

    $pdo->exec($sql);
    echo "Migration completed successfully.\n";

    // Check results
    echo "\nCHECKING odt_maestro:\n";
    $stmt = $pdo->query("DESCRIBE odt_maestro estado_gestion");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

    echo "\nCHECKING programacion_semanal:\n";
    $stmt = $pdo->query("DESCRIBE programacion_semanal");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
