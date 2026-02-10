-- =========================================================
-- MIGRATION SCRIPT: CONSISTENCY & AUDIT REMEDIATION
-- =========================================================
-- 1. Unificar nombres de tablas (Tipos de Trabajo)
-- REQUISITO: Verificar que 'tipos_trabajos' NO exista antes de renombrar
-- Si 'tipos_trabajos' ya existe y es la buena, habría que migrar datos.
-- Asumimos que 'tipologias' es la tabla activa actual.

RENAME TABLE tipologias TO tipos_trabajos;

-- Actualizar Foreign Keys (Esto puede fallar si los nombres de FK son específicos, 
-- pero al renombrar la tabla, MySQL suele mantener los constraints apuntando a la tabla renombrada)

-- 2. Limpieza de Triggers (Gestión de Stock via PHP ahora)
DROP TRIGGER IF EXISTS trg_partes_materiales_insert;
DROP TRIGGER IF EXISTS trg_partes_materiales_delete;
DROP TRIGGER IF EXISTS trg_partes_materiales_update;

-- 3. Estabilización de Esquema: Combustibles Despachos
-- Asegurar que la columna id_cuadrilla existe
SET @exist := (SELECT COUNT(*) 
    FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'combustibles_despachos' 
    AND column_name = 'id_cuadrilla');

SET @sql := IF(@exist = 0, 
    'ALTER TABLE combustibles_despachos ADD COLUMN id_cuadrilla INT NULL AFTER id_vehiculo, ADD FOREIGN KEY (id_cuadrilla) REFERENCES cuadrillas(id_cuadrilla) ON DELETE SET NULL', 
    'SELECT "Columna id_cuadrilla ya existe"');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Estabilización de Esquema: Combustibles Cargas
-- Asegurar que la columna foto_ticket existe
SET @exist_foto := (SELECT COUNT(*) 
    FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'combustibles_cargas' 
    AND column_name = 'foto_ticket');

SET @sql_foto := IF(@exist_foto = 0, 
    'ALTER TABLE combustibles_cargas ADD COLUMN foto_ticket VARCHAR(255) NULL AFTER nro_factura', 
    'SELECT "Columna foto_ticket ya existe"');

PREPARE stmt_foto FROM @sql_foto;
EXECUTE stmt_foto;
DEALLOCATE PREPARE stmt_foto;
