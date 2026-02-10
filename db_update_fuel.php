<?php
require_once 'config/database.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Create Administracion_Gastos table
    $sql1 = "CREATE TABLE IF NOT EXISTS Administracion_Gastos (
        id_gasto INT AUTO_INCREMENT PRIMARY KEY,
        fecha DATE NOT NULL,
        id_proveedor INT DEFAULT NULL,
        categoria VARCHAR(50) NOT NULL,
        monto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        comprobante_path VARCHAR(255) DEFAULT NULL,
        detalle TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql1);
    echo "Table 'Administracion_Gastos' checked/created.\n";

    // 2. Modify vehiculos table
    // Check if columns exist first to avoid errors on repeated runs
    $columns = $pdo->query("SHOW COLUMNS FROM vehiculos")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('consumo_acumulado', $columns)) {
        $pdo->exec("ALTER TABLE vehiculos ADD COLUMN consumo_acumulado FLOAT DEFAULT 0");
        echo "Column 'consumo_acumulado' added to 'vehiculos'.\n";
    }

    if (!in_array('capacidad_tanque', $columns)) {
        $pdo->exec("ALTER TABLE vehiculos ADD COLUMN capacidad_tanque FLOAT DEFAULT 0");
        echo "Column 'capacidad_tanque' added to 'vehiculos'.\n";
    }

    if (!in_array('odometro_actual', $columns)) {
        $pdo->exec("ALTER TABLE vehiculos ADD COLUMN odometro_actual FLOAT DEFAULT 0");
        echo "Column 'odometro_actual' added to 'vehiculos'.\n";
    }

    // 3. Ensure remitos table exists (Basic check, if it doesn't exist, Create it based on StockMover inference)
    $sqlRemitos = "CREATE TABLE IF NOT EXISTS remitos (
        id_remito INT AUTO_INCREMENT PRIMARY KEY,
        numero_remito VARCHAR(50) NOT NULL UNIQUE,
        id_cuadrilla INT DEFAULT NULL,
        tipo_remito VARCHAR(50),
        usuario_emision VARCHAR(100),
        fecha_emision TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sqlRemitos);
    echo "Table 'remitos' checked/created.\n";

    $sqlRemitosDetalle = "CREATE TABLE IF NOT EXISTS remitos_detalle (
        id_detalle INT AUTO_INCREMENT PRIMARY KEY,
        id_remito INT NOT NULL,
        id_material INT NOT NULL,
        cantidad DECIMAL(10,2) NOT NULL,
        id_movimiento INT DEFAULT NULL,
        FOREIGN KEY (id_remito) REFERENCES remitos(id_remito) ON DELETE CASCADE
    )";
    $pdo->exec($sqlRemitosDetalle);
    echo "Table 'remitos_detalle' checked/created.\n";


    echo "Database updates applied successfully.\n";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage() . "\n");
}
?>