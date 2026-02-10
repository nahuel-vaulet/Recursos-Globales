<?php
/**
 * Migration: Add fecha_asignacion to herramientas
 * Run via browser: http://localhost/APP-Prueba/migration_fecha_asignacion.php
 * Delete after running.
 */
$connected = false;
$ports = [3306, 3307];
foreach ($ports as $port) {
    try {
        $pdo = new PDO("mysql:host=127.0.0.1;port=$port;dbname=erp_global;charset=utf8mb4", 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $connected = true;
        echo "Connected on port $port\n";
        break;
    } catch (PDOException $e) {
        // Try next port
    }
}

if (!$connected) {
    die("Could not connect to MySQL on any port. Make sure XAMPP MySQL is running.\n");
}

try {
    $cols = $pdo->query("SHOW COLUMNS FROM herramientas LIKE 'fecha_asignacion'")->fetchAll();
    if (count($cols) > 0) {
        echo "Column 'fecha_asignacion' already exists. Skipping.\n";
    } else {
        $pdo->exec("ALTER TABLE herramientas ADD COLUMN fecha_asignacion DATE DEFAULT NULL");
        echo "OK: Column 'fecha_asignacion' added to herramientas table.\n";
    }

    $updated = $pdo->exec("UPDATE herramientas SET fecha_asignacion = CURDATE() WHERE id_cuadrilla_asignada IS NOT NULL AND fecha_asignacion IS NULL");
    echo "Updated $updated existing assigned tools with today's date.\n";

    echo "Migration complete.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
