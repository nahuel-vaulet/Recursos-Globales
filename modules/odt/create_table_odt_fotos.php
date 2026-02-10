<?php
require_once '../../config/database.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS odt_fotos (
        id_foto INT AUTO_INCREMENT PRIMARY KEY,
        id_odt INT NOT NULL,
        tipo VARCHAR(50) NOT NULL,
        ruta VARCHAR(255) NOT NULL,
        fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (id_odt)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql);
    echo "Table 'odt_fotos' created successfully (or already exists).";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>