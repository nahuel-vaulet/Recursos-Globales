-- =====================================================
-- Migración #8: Tipos de Operación Adicionales
-- Fecha: 2026-01-29
-- Descripción: Agrega tipos de devolución al ENUM
-- =====================================================

-- Modificar ENUM para incluir nuevos tipos de movimiento
ALTER TABLE movimientos 
MODIFY COLUMN tipo_movimiento ENUM(
    'Compra_Material',
    'Recepcion_ASSA_Oficina',
    'Entrega_Oficina_Cuadrilla',
    'Consumo_Cuadrilla_Obra',
    'Devolucion_ASSA',
    'Devolucion_Compra'
) NOT NULL;

-- Verificación
SELECT COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'erp_global' 
  AND TABLE_NAME = 'movimientos' 
  AND COLUMN_NAME = 'tipo_movimiento';
