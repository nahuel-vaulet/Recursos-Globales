<?php
/**
 * Migración Vehículos V2
 * Ejecutar desde navegador: http://localhost/APP-Prueba/migration_vehiculos_v2.php
 * ELIMINAR DESPUÉS DE EJECUTAR
 */
echo "<h2>Migración Vehículos V2</h2><pre>";

try {
    // Try port 3306 first, then 3307
    $connected = false;
    foreach ([3306, 3307] as $port) {
        try {
            $pdo = new PDO("mysql:host=localhost;port=$port;dbname=erp_global;charset=utf8mb4", "root", "", [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            echo "✅ Conectado en puerto $port\n";
            $connected = true;
            break;
        } catch (PDOException $e) {
            echo "⚠️ Puerto $port falló, probando siguiente...\n";
        }
    }
    if (!$connected)
        die("❌ No se pudo conectar a MySQL");

    // ─── 1. Columnas nuevas en vehiculos ───
    $alterSql = "
        ALTER TABLE vehiculos
            ADD COLUMN IF NOT EXISTS foto_estado VARCHAR(255) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS seguro_nombre VARCHAR(150) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS seguro_telefono VARCHAR(50) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS seguro_grua_telefono VARCHAR(50) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS seguro_cobertura VARCHAR(50) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS seguro_franquicia DECIMAL(12,2) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS seguro_valor DECIMAL(12,2) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS seguro_poliza_pdf VARCHAR(255) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS gestya_instalado TINYINT(1) DEFAULT 0,
            ADD COLUMN IF NOT EXISTS gestya_fecha_instalacion DATE DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS gestya_lugar VARCHAR(200) DEFAULT NULL
    ";
    $pdo->exec($alterSql);
    echo "✅ Columnas de seguro, foto y Gestya agregadas a vehiculos\n";

    // ─── 2. Tabla vehiculos_mantenimiento ───
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS vehiculos_mantenimiento (
            id_mantenimiento INT AUTO_INCREMENT PRIMARY KEY,
            id_vehiculo INT NOT NULL,
            tipo VARCHAR(80) NOT NULL,
            codigo VARCHAR(80) DEFAULT NULL,
            marca VARCHAR(100) DEFAULT NULL,
            equivalencia VARCHAR(200) DEFAULT NULL,
            tipo_aceite VARCHAR(100) DEFAULT NULL,
            cantidad DECIMAL(6,2) DEFAULT NULL,
            precio_usd DECIMAL(10,2) DEFAULT NULL,
            precio_ars DECIMAL(12,2) DEFAULT NULL,
            fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_vehiculo (id_vehiculo),
            FOREIGN KEY (id_vehiculo) REFERENCES vehiculos(id_vehiculo) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅ Tabla vehiculos_mantenimiento creada\n";

    // ─── 3. Tabla vehiculos_reparaciones ───
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS vehiculos_reparaciones (
            id_reparacion INT AUTO_INCREMENT PRIMARY KEY,
            id_vehiculo INT NOT NULL,
            fecha DATE NOT NULL,
            descripcion TEXT,
            realizado_por VARCHAR(150) DEFAULT NULL,
            costo DECIMAL(12,2) DEFAULT NULL,
            moneda VARCHAR(5) DEFAULT 'ARS',
            tiempo_horas DECIMAL(6,1) DEFAULT NULL,
            codigos_repuestos TEXT DEFAULT NULL,
            proveedor_repuestos VARCHAR(200) DEFAULT NULL,
            fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_vehiculo_rep (id_vehiculo),
            FOREIGN KEY (id_vehiculo) REFERENCES vehiculos(id_vehiculo) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅ Tabla vehiculos_reparaciones creada\n";

    echo "\n🎉 Migración completada. ELIMINA ESTE ARCHIVO.\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
echo "</pre>";
?>