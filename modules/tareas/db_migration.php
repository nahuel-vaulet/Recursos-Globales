<?php
require_once '../../config/database.php';

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS tareas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT DEFAULT 1,
        titulo VARCHAR(200) NOT NULL,
        descripcion TEXT,
        fecha_limite DATE,
        prioridad ENUM('Alta', 'Media', 'Baja') DEFAULT 'Media',
        estado ENUM('Pendiente', 'En progreso', 'Completada', 'Cancelada') DEFAULT 'Pendiente',
        categoria VARCHAR(50) DEFAULT 'Otros',
        recordatorio_especial BOOLEAN DEFAULT FALSE,
        fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
        responsable VARCHAR(100) DEFAULT 'Cache',
        fecha_completada DATETIME DEFAULT NULL,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql);
    echo "Tabla 'tareas' creada o verificada correctamente.";
} catch (PDOException $e) {
    echo "Error al crear la tabla: " . $e->getMessage();
}
?>