-- ============================================================================
-- MÓDULO: PARTES DIARIOS Y CONSUMO
-- Descripción: Registro de ejecución de ODTs, tiempos, consumo de materiales
--              y seguimiento de trabajos realizados por las cuadrillas.
-- 
-- @author Sistema ERP - Recursos Globales
-- @version 1.0
-- ============================================================================

-- ============================================================================
-- TABLA PRINCIPAL: Partes Diarios
-- Registra cada parte de trabajo ejecutado por una cuadrilla
-- ============================================================================
CREATE TABLE IF NOT EXISTS partes_diarios (
    id_parte INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Vínculo con ODT
    id_odt INT NOT NULL,
    id_programacion INT NULL,
    
    -- Cuadrilla que ejecuta
    id_cuadrilla INT NOT NULL,
    
    -- Control de Jornada
    fecha_ejecucion DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    tiempo_ejecucion_real INT GENERATED ALWAYS AS (
        TIMESTAMPDIFF(MINUTE, CONCAT(fecha_ejecucion, ' ', hora_inicio), CONCAT(fecha_ejecucion, ' ', hora_fin))
    ) STORED COMMENT 'Tiempo en minutos calculado automáticamente',
    
    -- Desglose de Trabajo
    id_tipologia INT NOT NULL COMMENT 'Tipo de trabajo realizado',
    largo DECIMAL(10,2) NULL DEFAULT 0 COMMENT 'Metros',
    ancho DECIMAL(10,2) NULL DEFAULT 0 COMMENT 'Metros',
    profundidad DECIMAL(10,2) NULL DEFAULT 0 COMMENT 'Metros (para M3)',
    volumen_calculado DECIMAL(10,3) GENERATED ALWAYS AS (
        CASE 
            WHEN profundidad > 0 THEN largo * ancho * profundidad
            ELSE largo * ancho
        END
    ) STORED COMMENT 'M3 o M2 según profundidad',
    unidad_volumen VARCHAR(5) GENERATED ALWAYS AS (
        CASE 
            WHEN profundidad > 0 THEN 'M3'
            ELSE 'M2'
        END
    ) VIRTUAL,
    
    -- Vehículo utilizado
    id_vehiculo INT NULL,
    km_inicial INT NULL,
    km_final INT NULL,
    
    -- Observaciones
    observaciones TEXT NULL,
    
    -- Estado del parte
    estado ENUM('Borrador', 'Enviado', 'Aprobado', 'Rechazado') DEFAULT 'Borrador',
    
    -- Auditoría
    usuario_creacion INT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    CONSTRAINT fk_parte_odt FOREIGN KEY (id_odt) REFERENCES ODT_Maestro(id_odt),
    CONSTRAINT fk_parte_cuadrilla FOREIGN KEY (id_cuadrilla) REFERENCES cuadrillas(id_cuadrilla),
    CONSTRAINT fk_parte_tipologia FOREIGN KEY (id_tipologia) REFERENCES tipologias(id_tipologia),
    CONSTRAINT fk_parte_vehiculo FOREIGN KEY (id_vehiculo) REFERENCES vehiculos(id_vehiculo),
    
    -- Índices
    INDEX idx_fecha_ejecucion (fecha_ejecucion),
    INDEX idx_cuadrilla (id_cuadrilla),
    INDEX idx_odt (id_odt),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLA: Consumo de Materiales por Parte
-- Registra los materiales consumidos en cada parte diario
-- ============================================================================
CREATE TABLE IF NOT EXISTS partes_materiales (
    id_parte_material INT AUTO_INCREMENT PRIMARY KEY,
    id_parte INT NOT NULL,
    id_material INT NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    
    -- Auditoría
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    CONSTRAINT fk_pm_parte FOREIGN KEY (id_parte) REFERENCES partes_diarios(id_parte) ON DELETE CASCADE,
    CONSTRAINT fk_pm_material FOREIGN KEY (id_material) REFERENCES maestro_materiales(id_material),
    
    -- Evitar duplicados
    UNIQUE KEY uk_parte_material (id_parte, id_material)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLA: Personal Interviniente por Parte
-- Registra qué operarios participaron en cada parte
-- ============================================================================
CREATE TABLE IF NOT EXISTS partes_personal (
    id_parte_personal INT AUTO_INCREMENT PRIMARY KEY,
    id_parte INT NOT NULL,
    id_personal INT NOT NULL,
    
    -- Auditoría
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    CONSTRAINT fk_pp_parte FOREIGN KEY (id_parte) REFERENCES partes_diarios(id_parte) ON DELETE CASCADE,
    CONSTRAINT fk_pp_personal FOREIGN KEY (id_personal) REFERENCES personal(id_personal),
    
    -- Evitar duplicados
    UNIQUE KEY uk_parte_personal (id_parte, id_personal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLA: Fotos de Evidencia por Parte
-- Registra las 3 fotos obligatorias (Inicio, Proceso, Fin)
-- ============================================================================
CREATE TABLE IF NOT EXISTS partes_fotos (
    id_foto INT AUTO_INCREMENT PRIMARY KEY,
    id_parte INT NOT NULL,
    tipo_foto ENUM('Inicio', 'Proceso', 'Fin') NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    
    -- Metadatos
    latitud DECIMAL(10,8) NULL COMMENT 'Coordenada GPS',
    longitud DECIMAL(11,8) NULL COMMENT 'Coordenada GPS',
    fecha_captura DATETIME NULL,
    
    -- Auditoría
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    CONSTRAINT fk_pf_parte FOREIGN KEY (id_parte) REFERENCES partes_diarios(id_parte) ON DELETE CASCADE,
    
    -- Una foto por tipo por parte
    UNIQUE KEY uk_parte_tipo (id_parte, tipo_foto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TRIGGER: Actualizar Stock de Cuadrilla al guardar material consumido
-- Resta automáticamente del stock_cuadrilla
-- ============================================================================
DELIMITER //

CREATE TRIGGER IF NOT EXISTS tr_descontar_stock_cuadrilla
AFTER INSERT ON partes_materiales
FOR EACH ROW
BEGIN
    DECLARE v_id_cuadrilla INT;
    
    -- Obtener la cuadrilla del parte
    SELECT id_cuadrilla INTO v_id_cuadrilla 
    FROM partes_diarios 
    WHERE id_parte = NEW.id_parte;
    
    -- Descontar del stock de la cuadrilla
    UPDATE stock_cuadrilla 
    SET cantidad = cantidad - NEW.cantidad,
        updated_at = NOW()
    WHERE id_cuadrilla = v_id_cuadrilla 
      AND id_material = NEW.id_material;
END//

DELIMITER ;


-- ============================================================================
-- TRIGGER: Revertir Stock si se elimina material del parte
-- ============================================================================
DELIMITER //

CREATE TRIGGER IF NOT EXISTS tr_revertir_stock_cuadrilla
AFTER DELETE ON partes_materiales
FOR EACH ROW
BEGIN
    DECLARE v_id_cuadrilla INT;
    
    -- Obtener la cuadrilla del parte (si aún existe)
    SELECT id_cuadrilla INTO v_id_cuadrilla 
    FROM partes_diarios 
    WHERE id_parte = OLD.id_parte;
    
    -- Devolver al stock de la cuadrilla
    IF v_id_cuadrilla IS NOT NULL THEN
        UPDATE stock_cuadrilla 
        SET cantidad = cantidad + OLD.cantidad,
            updated_at = NOW()
        WHERE id_cuadrilla = v_id_cuadrilla 
          AND id_material = OLD.id_material;
    END IF;
END//

DELIMITER ;


-- ============================================================================
-- VISTA: Resumen de Partes con datos completos
-- ============================================================================
CREATE OR REPLACE VIEW v_partes_completos AS
SELECT 
    pd.id_parte,
    pd.fecha_ejecucion,
    pd.hora_inicio,
    pd.hora_fin,
    pd.tiempo_ejecucion_real,
    pd.volumen_calculado,
    pd.unidad_volumen,
    pd.estado,
    pd.observaciones,
    
    -- ODT
    o.id_odt,
    o.nro_odt_assa,
    o.direccion AS odt_direccion,
    o.estado_gestion AS odt_estado,
    
    -- Cuadrilla
    c.id_cuadrilla,
    c.nombre_cuadrilla,
    
    -- Tipología
    t.id_tipologia,
    t.nombre AS tipologia_nombre,
    t.codigo_trabajo,
    
    -- Vehículo
    v.id_vehiculo,
    v.patente AS vehiculo_patente,
    
    -- Contadores
    (SELECT COUNT(*) FROM partes_personal pp WHERE pp.id_parte = pd.id_parte) AS cant_personal,
    (SELECT COUNT(*) FROM partes_materiales pm WHERE pm.id_parte = pd.id_parte) AS cant_materiales,
    (SELECT COUNT(*) FROM partes_fotos pf WHERE pf.id_parte = pd.id_parte) AS cant_fotos
    
FROM partes_diarios pd
LEFT JOIN ODT_Maestro o ON pd.id_odt = o.id_odt
LEFT JOIN cuadrillas c ON pd.id_cuadrilla = c.id_cuadrilla
LEFT JOIN tipologias t ON pd.id_tipologia = t.id_tipologia
LEFT JOIN vehiculos v ON pd.id_vehiculo = v.id_vehiculo;


-- ============================================================================
-- Crear directorio para fotos (se ejecuta desde PHP)
-- Las fotos se guardarán en: uploads/partes/{id_parte}/
-- ============================================================================

SELECT 'Estructura de tablas para Partes Diarios creada exitosamente' AS mensaje;
