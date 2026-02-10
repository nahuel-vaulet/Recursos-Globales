<?php
require_once 'config/database.php';

try {
    // Make id_tanque nullable
    $pdo->exec("ALTER TABLE combustibles_cargas MODIFY COLUMN id_tanque INT DEFAULT NULL");

    // Add columns for destination logic
    $pdo->exec("ALTER TABLE combustibles_cargas ADD COLUMN destino_tipo ENUM('stock', 'vehiculo') NOT NULL DEFAULT 'stock' AFTER id_tanque");
    $pdo->exec("ALTER TABLE combustibles_cargas ADD COLUMN tipo_combustible VARCHAR(50) DEFAULT NULL AFTER destino_tipo");

    echo "Table combustibles_cargas updated successfully.\n";
} catch (PDOException $e) {
    echo "Error updating table: " . $e->getMessage() . "\n";
}
?>