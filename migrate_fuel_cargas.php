<?php
require_once 'config/database.php';

try {
    $pdo->exec("ALTER TABLE combustibles_cargas ADD COLUMN id_cuadrilla INT DEFAULT NULL AFTER id_tanque");
    $pdo->exec("ALTER TABLE combustibles_cargas ADD COLUMN id_vehiculo INT DEFAULT NULL AFTER id_cuadrilla");
    $pdo->exec("ALTER TABLE combustibles_cargas ADD COLUMN conductor VARCHAR(100) DEFAULT NULL AFTER id_vehiculo");

    echo "Columns added successfully to combustibles_cargas.\n";
} catch (PDOException $e) {
    echo "Error adding columns (might already exist): " . $e->getMessage() . "\n";
}
?>