<?php
require_once 'config/database.php';

try {
    echo "Iniciando migración de Módulo de Combustibles...\n";

    // 1. Tabla: combustibles_tanques
    $sql_tanques = "CREATE TABLE IF NOT EXISTS combustibles_tanques (
        id_tanque INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        tipo_combustible VARCHAR(50) NOT NULL DEFAULT 'Diesel',
        capacidad_maxima DECIMAL(10,2) NOT NULL,
        stock_actual DECIMAL(10,2) DEFAULT 0.00,
        ubicacion VARCHAR(100) DEFAULT 'Base Central',
        estado ENUM('Activo', 'Inactivo', 'Mantenimiento') DEFAULT 'Activo',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql_tanques);
    echo "- Tabla 'combustibles_tanques' verificada/creada.\n";

    // 2. Tabla: combustibles_cargas (Entradas)
    $sql_cargas = "CREATE TABLE IF NOT EXISTS combustibles_cargas (
        id_carga INT AUTO_INCREMENT PRIMARY KEY,
        id_tanque INT NOT NULL,
        fecha_hora DATETIME NOT NULL,
        litros DECIMAL(10,2) NOT NULL,
        precio_unitario DECIMAL(10,2) DEFAULT 0.00,
        proveedor VARCHAR(100),
        nro_factura VARCHAR(50),
        comprobante_path VARCHAR(255),
        usuario_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_tanque) REFERENCES combustibles_tanques(id_tanque)
    )";
    $pdo->exec($sql_cargas);
    echo "- Tabla 'combustibles_cargas' verificada/creada.\n";

    // 3. Tabla: combustibles_despachos (Salidas)
    $sql_despachos = "CREATE TABLE IF NOT EXISTS combustibles_despachos (
        id_despacho INT AUTO_INCREMENT PRIMARY KEY,
        id_tanque INT NOT NULL,
        id_vehiculo INT NOT NULL,
        fecha_hora DATETIME NOT NULL,
        litros DECIMAL(10,2) NOT NULL,
        odometro_actual DECIMAL(10,1) NOT NULL,
        usuario_despacho INT,
        usuario_conductor VARCHAR(100),
        destino_obra VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_tanque) REFERENCES combustibles_tanques(id_tanque),
        FOREIGN KEY (id_vehiculo) REFERENCES vehiculos(id_vehiculo)
    )";
    $pdo->exec($sql_despachos);
    echo "- Tabla 'combustibles_despachos' verificada/creada.\n";

    // Seed Data: Tanques por defecto si no existen
    $check_tanques = $pdo->query("SELECT COUNT(*) FROM combustibles_tanques")->fetchColumn();
    if ($check_tanques == 0) {
        $pdo->exec("INSERT INTO combustibles_tanques (nombre, tipo_combustible, capacidad_maxima, stock_actual) VALUES 
            ('Tanque Principal (Gasoil)', 'Diesel', 5000.00, 0.00),
            ('Tanque Auxiliar (Nafta)', 'Nafta', 1000.00, 0.00)
        ");
        echo "- Se han insertado tanques por defecto.\n";
    }

    echo "Migración completada con éxito.\n";

} catch (PDOException $e) {
    die("Error en migración: " . $e->getMessage() . "\n");
}
?>