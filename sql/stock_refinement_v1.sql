-- Refinements for Stock Fuel Module

-- 1. Updates for Fuel Transfers tracking
ALTER TABLE `spot_traspasos_combustible` 
ADD COLUMN `precio_unitario` DECIMAL(12,2) AFTER `litros_cargados`,
ADD COLUMN `importe_total` DECIMAL(12,2) AFTER `precio_unitario`,
ADD COLUMN `es_alerta` TINYINT(1) DEFAULT 0 AFTER `estado_verificacion`,
ADD COLUMN `observaciones_alerta` TEXT AFTER `es_alerta`;

-- 2. Average consumption for vehicles (L/100km)
ALTER TABLE `vehiculos` 
ADD COLUMN `consumo_promedio` DECIMAL(10,2) DEFAULT 15.00;

-- 3. Ensure Fuel Material exists
INSERT IGNORE INTO `maestro_materiales` (id_material, nombre, codigo, unidad_medida, costo_primario)
VALUES (999, 'GASOIL / COMBUSTIBLE', 'COMB-001', 'Litros', 1200.00);

-- 4. Initial Tank if empty
INSERT IGNORE INTO `spot_tanques` (id_tanque, nombre, capacidad_total, stock_actual)
VALUES (1, 'TANQUE CENTRAL 01', 5000.00, 2500.00);
