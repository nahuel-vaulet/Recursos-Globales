<?php
require_once 'config/database.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS odt_fotos (
        id_foto INT AUTO_INCREMENT PRIMARY KEY,
        id_odt INT NOT NULL,
        ruta_archivo VARCHAR(255) NOT NULL,
        tipo_foto VARCHAR(50) DEFAULT 'Avance',
        fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        subido_por INT NULL,
        INDEX(id_odt)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Tabla 'odt_fotos' creada/verificada correctamente.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>