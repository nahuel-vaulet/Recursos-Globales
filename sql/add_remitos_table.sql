-- =========================================================
-- Script: add_remitos_table.sql
-- Descripción: Crea tablas para sistema de remitos
-- Fecha: 2026-01-30
-- =========================================================

-- Tabla principal de remitos
CREATE TABLE IF NOT EXISTS remitos (
    id_remito INT AUTO_INCREMENT PRIMARY KEY,
    numero_remito VARCHAR(20) NOT NULL UNIQUE,
    fecha_emision DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_cuadrilla INT,
    tipo_remito ENUM('Entrega_Cuadrilla', 'Devolucion') DEFAULT 'Entrega_Cuadrilla',
    observaciones TEXT,
    usuario_emision VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cuadrilla) REFERENCES cuadrillas(id_cuadrilla) ON DELETE SET NULL
);

-- Tabla de detalle de remitos (líneas de materiales)
CREATE TABLE IF NOT EXISTS remitos_detalle (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_remito INT NOT NULL,
    id_material INT NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    id_movimiento INT,
    FOREIGN KEY (id_remito) REFERENCES remitos(id_remito) ON DELETE CASCADE,
    FOREIGN KEY (id_material) REFERENCES maestro_materiales(id_material) ON DELETE CASCADE,
    FOREIGN KEY (id_movimiento) REFERENCES movimientos(id_movimiento) ON DELETE SET NULL
);

-- Índice para búsqueda rápida por número de remito
CREATE INDEX idx_remitos_numero ON remitos(numero_remito);

-- Índice para búsqueda por cuadrilla
CREATE INDEX idx_remitos_cuadrilla ON remitos(id_cuadrilla);
