-- =============================================
-- ACTUALIZACIÓN: Campos extendidos para Vehículos
-- Ejecutar en phpMyAdmin o MySQL CLI
-- =============================================

USE erp_global;

-- Agregar campos de Control Diario
ALTER TABLE vehiculos
    ADD COLUMN IF NOT EXISTS nivel_aceite ENUM('OK', 'Bajo', 'Crítico') DEFAULT 'OK',
    ADD COLUMN IF NOT EXISTS nivel_combustible ENUM('Lleno', 'Medio', 'Bajo', 'Reserva') DEFAULT 'Medio',
    ADD COLUMN IF NOT EXISTS estado_frenos ENUM('OK', 'Desgastados', 'Cambiar') DEFAULT 'OK';

-- Agregar campos de Mantenimiento
ALTER TABLE vehiculos
    ADD COLUMN IF NOT EXISTS km_actual INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS proximo_service_km INT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS fecha_ultimo_inventario DATE DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS costo_reposicion DECIMAL(12,2) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS observaciones TEXT DEFAULT NULL;

-- Actualizar ENUM de tipo para incluir más opciones
ALTER TABLE vehiculos 
    MODIFY COLUMN tipo ENUM('Camioneta', 'Utilitario', 'Camión', 'Moto', 'Retropala', 'Generador', 'Otro') DEFAULT 'Camioneta';

SELECT 'Actualización de vehículos completada' as mensaje;
