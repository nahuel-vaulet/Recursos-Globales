<?php
require_once 'config/database.php';

echo "<h2>Iniciando Migración de Base de Datos - Mejoras Cuadrillas</h2>";

try {
    $pdo->beginTransaction();

    // 1. Table: herramientas
    echo "Verificando tabla 'herramientas'...<br>";
    $pdo->exec("CREATE TABLE IF NOT EXISTS herramientas (
        id_herramienta INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        descripcion TEXT,
        numero_serie VARCHAR(50),
        marca VARCHAR(50),
        modelo VARCHAR(50),
        precio_reposicion DECIMAL(10,2) DEFAULT 0.00,
        estado ENUM('Disponible', 'Asignada', 'Reparación', 'Baja') DEFAULT 'Disponible',
        id_cuadrilla_asignada INT NULL,
        id_personal_asignado INT NULL,
        fecha_compra DATE,
        fecha_calibracion DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Tabla 'herramientas' OK.<br>";

    // 2. Table: tipos_trabajo
    echo "Verificando tabla 'tipos_trabajo'...<br>";
    $pdo->exec("CREATE TABLE IF NOT EXISTS tipos_trabajo (
        id_tipo INT AUTO_INCREMENT PRIMARY KEY,
        descripcion VARCHAR(100) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Tabla 'tipos_trabajo' OK.<br>";

    // Populate default types
    $defaults = ['Veredas', 'Hidráulica', 'Medidores', 'Calzada', 'Emergencias', 'Poda', 'Zanjeo'];
    $stmtType = $pdo->prepare("INSERT IGNORE INTO tipos_trabajo (descripcion) VALUES (?)");
    foreach ($defaults as $type) {
        $stmtType->execute([$type]);
    }
    echo "Tipos de trabajo por defecto insertados.<br>";

    // 3. Table: cuadrilla_tipos_trabajo (Many-to-Many)
    echo "Verificando tabla 'cuadrilla_tipos_trabajo'...<br>";
    $pdo->exec("CREATE TABLE IF NOT EXISTS cuadrilla_tipos_trabajo (
        id_cuadrilla INT NOT NULL,
        id_tipo INT NOT NULL,
        PRIMARY KEY (id_cuadrilla, id_tipo),
        FOREIGN KEY (id_tipo) REFERENCES tipos_trabajo(id_tipo) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Tabla 'cuadrilla_tipos_trabajo' OK.<br>";

    $pdo->commit();
    echo "<h3 style='color:green'>Migración Completada Exitosamente.</h3>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<h3 style='color:red'>Error en la Migración: " . $e->getMessage() . "</h3>";
}
?>