-- =============================================
-- ACTUALIZACIÓN: Módulo Personal Extendido
-- Ejecutar en phpMyAdmin o MySQL CLI
-- =============================================

USE erp_global;

-- =============================================
-- 1. TABLA CUADRILLAS - Campos adicionales
-- =============================================

ALTER TABLE cuadrillas
    ADD COLUMN IF NOT EXISTS id_vehiculo_asignado INT NULL AFTER tipo_especialidad,
    ADD COLUMN IF NOT EXISTS id_celular_asignado VARCHAR(50) NULL AFTER id_vehiculo_asignado,
    ADD COLUMN IF NOT EXISTS url_grupo_whatsapp VARCHAR(255) NULL AFTER zona_asignada;

-- Actualizar ENUM de tipo_especialidad si es necesario
ALTER TABLE cuadrillas 
    MODIFY COLUMN tipo_especialidad VARCHAR(50) NULL;

-- =============================================
-- 2. TABLA PERSONAL - Estructura completa
-- =============================================

-- Campos de Seguridad
ALTER TABLE personal
    ADD COLUMN IF NOT EXISTS id_kit_herramientas INT NULL AFTER id_cuadrilla,
    ADD COLUMN IF NOT EXISTS seguro_art VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS talle_ropa VARCHAR(20) NULL,
    ADD COLUMN IF NOT EXISTS talle_calzado VARCHAR(10) NULL,
    ADD COLUMN IF NOT EXISTS fecha_ultima_entrega_epp DATE NULL,
    ADD COLUMN IF NOT EXISTS vencimiento_carnet_conducir DATE NULL;

-- Campos de Salud
ALTER TABLE personal
    ADD COLUMN IF NOT EXISTS grupo_sanguineo VARCHAR(10) NULL,
    ADD COLUMN IF NOT EXISTS alergias_condiciones TEXT NULL,
    ADD COLUMN IF NOT EXISTS numero_emergencia VARCHAR(50) NULL;

-- Campos Administrativos
ALTER TABLE personal
    ADD COLUMN IF NOT EXISTS fecha_ingreso DATE NULL,
    ADD COLUMN IF NOT EXISTS cbu_alias VARCHAR(50) NULL,
    ADD COLUMN IF NOT EXISTS domicilio VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS link_legajo_digital VARCHAR(255) NULL;

-- Actualizar ENUM de rol para incluir más opciones
ALTER TABLE personal 
    MODIFY COLUMN rol ENUM('Oficial', 'Ayudante', 'Administrativo', 'Supervisor', 'Chofer') DEFAULT 'Ayudante';

-- =============================================
-- 3. TABLA ASISTENCIA - Campo horas_emergencia
-- =============================================

ALTER TABLE asistencia
    ADD COLUMN IF NOT EXISTS horas_emergencia DECIMAL(4,2) DEFAULT 0 AFTER estado_dia;

-- =============================================
-- 4. TABLA VEHICULOS (Nueva - Referenciada por Cuadrillas)
-- =============================================

CREATE TABLE IF NOT EXISTS vehiculos (
    id_vehiculo INT AUTO_INCREMENT PRIMARY KEY,
    patente VARCHAR(20) NOT NULL UNIQUE,
    marca VARCHAR(50),
    modelo VARCHAR(50),
    anio INT,
    tipo ENUM('Camioneta', 'Utilitario', 'Camión', 'Moto') DEFAULT 'Camioneta',
    vencimiento_vtv DATE,
    vencimiento_seguro DATE,
    estado ENUM('Operativo', 'En Taller', 'Baja') DEFAULT 'Operativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- 5. TABLA KITS DE HERRAMIENTAS (Nueva)
-- =============================================

CREATE TABLE IF NOT EXISTS kits_herramientas (
    id_kit INT AUTO_INCREMENT PRIMARY KEY,
    codigo_kit VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    fecha_entrega DATE,
    id_personal_asignado INT NULL,
    estado ENUM('Completo', 'Incompleto', 'Perdido') DEFAULT 'Completo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_personal_asignado) REFERENCES personal(id_personal) ON DELETE SET NULL
);

-- =============================================
-- 6. AGREGAR FOREIGN KEYS (si no existen)
-- =============================================

-- FK Cuadrillas -> Vehiculos
-- ALTER TABLE cuadrillas ADD CONSTRAINT fk_cuadrilla_vehiculo 
--     FOREIGN KEY (id_vehiculo_asignado) REFERENCES vehiculos(id_vehiculo) ON DELETE SET NULL;

-- FK Personal -> Kits
-- ALTER TABLE personal ADD CONSTRAINT fk_personal_kit 
--     FOREIGN KEY (id_kit_herramientas) REFERENCES kits_herramientas(id_kit) ON DELETE SET NULL;

-- =============================================
-- FIN DE LA ACTUALIZACIÓN
-- =============================================
SELECT 'Actualización completada exitosamente' as mensaje;
