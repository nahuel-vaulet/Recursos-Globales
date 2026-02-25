-- ERP Spot: Database Migration

CREATE TABLE IF NOT EXISTS `spot_tanques` (
    `id_tanque` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL,
    `capacidad_total` DECIMAL(12,2) NOT NULL,
    `stock_actual` DECIMAL(12,2) DEFAULT 0,
    `unidad_medida` VARCHAR(20) DEFAULT 'Litros',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `spot_precios_proveedores` (
    `id_precio` INT AUTO_INCREMENT PRIMARY KEY,
    `id_proveedor` INT NOT NULL,
    `tipo_combustible` VARCHAR(50) NOT NULL,
    `precio_litro` DECIMAL(12,2) NOT NULL,
    `fecha_vigencia` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (id_proveedor),
    INDEX (fecha_vigencia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `spot_remitos` (
    `id_remito` INT AUTO_INCREMENT PRIMARY KEY,
    `nro_remito` VARCHAR(50) UNIQUE NOT NULL,
    `tipo` ENUM('Material', 'Combustible') NOT NULL,
    `id_cuadrilla_origen` INT DEFAULT NULL,
    `id_cuadrilla_destino` INT DEFAULT NULL,
    `id_proveedor` INT DEFAULT NULL,
    `id_personal_entrega` INT NOT NULL,
    `id_personal_recepcion` INT NOT NULL,
    `destino_obra` VARCHAR(255) DEFAULT NULL,
    `fecha_emision` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `usuario_sistema_id` INT NOT NULL,
    INDEX (tipo),
    INDEX (fecha_emision)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `spot_remito_items` (
    `id_item` INT AUTO_INCREMENT PRIMARY KEY,
    `id_remito` INT NOT NULL,
    `id_material` INT DEFAULT NULL,
    `id_tanque` INT DEFAULT NULL,
    `cantidad` DECIMAL(12,2) NOT NULL,
    `precio_unitario` DECIMAL(12,2) DEFAULT NULL,
    `total` DECIMAL(12,2) DEFAULT NULL,
    FOREIGN KEY (`id_remito`) REFERENCES `spot_remitos`(`id_remito`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `spot_traspasos_combustible` (
    `id_traspaso` INT AUTO_INCREMENT PRIMARY KEY,
    `id_remito` INT NOT NULL,
    `id_tanque` INT NOT NULL,
    `id_vehiculo` INT NOT NULL,
    `km_ultimo` INT NOT NULL,
    `km_actual` INT NOT NULL,
    `km_diferencia` INT AS (km_actual - km_ultimo) STORED,
    `litros_estimados` DECIMAL(12,2) NOT NULL,
    `litros_cargados` DECIMAL(12,2) NOT NULL,
    `diferencia_verificacion` DECIMAL(12,2) AS (litros_cargados - litros_estimados) STORED,
    `estado_verificacion` ENUM('Verifica', 'Alerta', 'Error') DEFAULT 'Verifica',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_remito`) REFERENCES `spot_remitos`(`id_remito`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `spot_diagnosticos` (
    `id_diagnostico` INT AUTO_INCREMENT PRIMARY KEY,
    `error_id` VARCHAR(36) UNIQUE NOT NULL,
    `modulo` VARCHAR(50) NOT NULL,
    `accion` VARCHAR(100) NOT NULL,
    `parametros` TEXT,
    `mensaje_error` TEXT,
    `stack_trace` TEXT,
    `usuario_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
