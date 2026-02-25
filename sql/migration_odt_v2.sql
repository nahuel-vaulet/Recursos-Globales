-- ============================================
-- MIGRACIÓN ODT v2 — Gestión Integral
-- Fecha: 2026-02-20
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Cambiar estado_gestion de ENUM a VARCHAR(50)
-- Primero verificamos si ya es VARCHAR
SET @is_enum = (
    SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'erp_global' AND TABLE_NAME = 'odt_maestro' AND COLUMN_NAME = 'estado_gestion'
);

ALTER TABLE odt_maestro MODIFY COLUMN estado_gestion VARCHAR(50) NOT NULL DEFAULT 'Nuevo';

-- 2. Migrar datos de estados viejos a nuevos
UPDATE odt_maestro SET estado_gestion = 'Nuevo' WHERE estado_gestion = 'Sin Programar';
UPDATE odt_maestro SET estado_gestion = 'Priorizado' WHERE estado_gestion = 'Programación Solicitada';
-- 'Programado' permanece igual
UPDATE odt_maestro SET estado_gestion = 'En ejecución' WHERE estado_gestion = 'Ejecución';
-- 'Ejecutado' permanece igual
UPDATE odt_maestro SET estado_gestion = 'Precertificar' WHERE estado_gestion = 'Precertificada';
UPDATE odt_maestro SET estado_gestion = 'Certificar' WHERE estado_gestion = 'Aprobado por inspector';
UPDATE odt_maestro SET estado_gestion = 'Retorno' WHERE estado_gestion = 'Retrabajo';
UPDATE odt_maestro SET estado_gestion = 'Reprogramar por visita fallida' WHERE estado_gestion = 'Postergado';
-- 'Finalizado' → lo mantenemos como 'Certificar' si aplica, sino lo dejamos

-- 3. Agregar nuevas columnas si no existen
-- urgente_flag
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'erp_global' AND TABLE_NAME = 'odt_maestro' AND COLUMN_NAME = 'urgente_flag');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE odt_maestro ADD COLUMN urgente_flag TINYINT(1) NOT NULL DEFAULT 0 AFTER prioridad', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- orden (orden de ejecución)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'erp_global' AND TABLE_NAME = 'odt_maestro' AND COLUMN_NAME = 'orden');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE odt_maestro ADD COLUMN orden INT DEFAULT NULL AFTER urgente_flag', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- fecha_asignacion
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'erp_global' AND TABLE_NAME = 'odt_maestro' AND COLUMN_NAME = 'fecha_asignacion');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE odt_maestro ADD COLUMN fecha_asignacion DATE DEFAULT NULL AFTER orden', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Migrar urgente_flag desde prioridad existente
UPDATE odt_maestro SET urgente_flag = 1 WHERE prioridad = 'Urgente';

-- Cambiar prioridad de ENUM a INT para niveles numéricos
ALTER TABLE odt_maestro MODIFY COLUMN prioridad INT NOT NULL DEFAULT 3;
-- Migrar: Urgente = 1 (max), Normal = 3
UPDATE odt_maestro SET prioridad = 1 WHERE urgente_flag = 1;
UPDATE odt_maestro SET prioridad = 3 WHERE urgente_flag = 0 AND prioridad NOT IN (1, 2, 3, 4, 5);

-- 4. Crear tabla odt_historial
CREATE TABLE IF NOT EXISTS odt_historial (
    id_historial INT AUTO_INCREMENT PRIMARY KEY,
    id_odt INT NOT NULL,
    estado_anterior VARCHAR(50),
    estado_nuevo VARCHAR(50) NOT NULL,
    id_usuario INT,
    observacion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_odt) REFERENCES odt_maestro(id_odt) ON DELETE CASCADE,
    INDEX idx_odt_historial_odt (id_odt),
    INDEX idx_odt_historial_fecha (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Índices de rendimiento para odt_maestro
-- Estado
CREATE INDEX IF NOT EXISTS idx_odt_estado ON odt_maestro(estado_gestion);

-- Cuadrilla + Fecha asignación (para calendario y cuadrillas)
-- Necesitamos la FK de cuadrilla en odt_maestro. Verificar si id_cuadrilla existe
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'erp_global' AND TABLE_NAME = 'odt_maestro' AND COLUMN_NAME = 'id_cuadrilla');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE odt_maestro ADD COLUMN id_cuadrilla INT DEFAULT NULL AFTER fecha_asignacion, ADD FOREIGN KEY fk_odt_cuadrilla (id_cuadrilla) REFERENCES cuadrillas(id_cuadrilla) ON DELETE SET NULL', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Migrar datos de programacion_semanal → odt_maestro.id_cuadrilla y fecha_asignacion
UPDATE odt_maestro o
INNER JOIN (
    SELECT ps.id_odt, ps.id_cuadrilla, ps.fecha_programada
    FROM programacion_semanal ps
    INNER JOIN (
        SELECT id_odt, MAX(id_programacion) as max_id 
        FROM programacion_semanal 
        GROUP BY id_odt
    ) latest ON ps.id_programacion = latest.max_id
) sub ON o.id_odt = sub.id_odt
SET o.id_cuadrilla = sub.id_cuadrilla,
    o.fecha_asignacion = sub.fecha_programada
WHERE o.id_cuadrilla IS NULL;

-- Índices adicionales
CREATE INDEX IF NOT EXISTS idx_odt_cuadrilla_fecha ON odt_maestro(id_cuadrilla, fecha_asignacion);
CREATE INDEX IF NOT EXISTS idx_odt_vencimiento ON odt_maestro(fecha_vencimiento);
CREATE INDEX IF NOT EXISTS idx_odt_prioridad_orden ON odt_maestro(prioridad, orden);

-- 6. Agregar updated_at si no existe
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'erp_global' AND TABLE_NAME = 'odt_maestro' AND COLUMN_NAME = 'updated_at');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE odt_maestro ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- SEED DATA DE PRUEBA
-- ============================================

-- Asegurar que existen cuadrillas de ejemplo
INSERT IGNORE INTO cuadrillas (id_cuadrilla, nombre_cuadrilla, tipo_especialidad, zona_asignada, estado_operativo) VALUES 
(1, 'Cuadrilla Alpha', 'Gas', 'Zona Norte', 'Activa'),
(2, 'Cuadrilla Beta', 'Agua', 'Zona Sur', 'Activa'),
(3, 'Cuadrilla Gamma', 'Electricidad', 'Zona Centro', 'Activa');

-- ODTs de prueba con nuevos estados
INSERT IGNORE INTO odt_maestro (nro_odt_assa, direccion, id_tipologia, prioridad, urgente_flag, estado_gestion, orden, fecha_asignacion, id_cuadrilla, fecha_inicio_plazo, fecha_vencimiento) VALUES
('ODT-2026-001', 'Av. San Martín 1500', NULL, 3, 0, 'Nuevo', NULL, NULL, NULL, '2026-02-15', '2026-03-01'),
('ODT-2026-002', 'Calle Belgrano 800', NULL, 3, 0, 'Inspeccionar', NULL, NULL, NULL, '2026-02-16', '2026-03-05'),
('ODT-2026-003', 'Mitre 2200', NULL, 3, 0, 'Inspeccionado', NULL, NULL, NULL, '2026-02-17', '2026-03-10'),
('ODT-2026-004', 'Rivadavia 450', NULL, 1, 1, 'Priorizado', NULL, NULL, NULL, '2026-02-18', '2026-02-25'),
('ODT-2026-005', 'Sarmiento 1100', NULL, 3, 0, 'Programado', NULL, NULL, 1, '2026-02-18', '2026-03-08'),
('ODT-2026-006', 'Moreno 600', NULL, 2, 0, 'Asignado', 1, '2026-02-20', 1, '2026-02-19', '2026-03-12'),
('ODT-2026-007', 'Lavalle 1800', NULL, 2, 0, 'Asignado', 2, '2026-02-20', 2, '2026-02-19', '2026-03-15'),
('ODT-2026-008', 'Corrientes 3200', NULL, 3, 0, 'En ejecución', 1, '2026-02-19', 1, '2026-02-17', '2026-03-04'),
('ODT-2026-009', 'Av. Libertador 5000', NULL, 1, 1, 'En ejecución', 1, '2026-02-19', 3, '2026-02-15', '2026-02-22'),
('ODT-2026-010', 'Santa Fe 900', NULL, 3, 0, 'Ejecutado', 1, '2026-02-18', 2, '2026-02-14', '2026-03-01'),
('ODT-2026-011', 'Juncal 700', NULL, 3, 0, 'Retorno', 1, '2026-02-17', 1, '2026-02-10', '2026-02-28'),
('ODT-2026-012', 'Alem 300', NULL, 3, 0, 'Auditar', 1, '2026-02-16', 3, '2026-02-08', '2026-02-26'),
('ODT-2026-013', 'Callao 1200', NULL, 3, 0, 'Precertificar', 1, '2026-02-15', 2, '2026-02-05', '2026-02-24'),
('ODT-2026-014', 'Córdoba 2500', NULL, 3, 0, 'Certificar', 1, '2026-02-14', 1, '2026-02-01', '2026-02-20'),
('ODT-2026-015', 'Tucumán 400', NULL, 2, 0, 'Asignado', 3, '2026-02-21', 3, '2026-02-20', '2026-03-20');
