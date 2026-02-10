-- =============================================================================
-- TABLA: Administracion_Gastos
-- Módulo: Fondos Fijos y Gastos Menores
-- Autor: Sistema ERP - Recursos Globales
-- Fecha: 2026-02-03
-- =============================================================================

CREATE TABLE IF NOT EXISTS Administracion_Gastos (
    id_gasto INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Monto del gasto (obligatorio)
    monto DECIMAL(10,2) NOT NULL,
    
    -- Tipo de gasto (categoría predefinida)
    tipo_gasto ENUM('Ferreteria', 'Comida', 'Peajes', 'Combustible_Emergencia', 'Insumos_Oficina', 'Otros') NOT NULL,
    
    -- Responsable (FK a personal)
    id_responsable INT NOT NULL,
    
    -- Comprobante (ruta del archivo)
    comprobante_path VARCHAR(500) NOT NULL,
    
    -- Fecha del gasto
    fecha_gasto DATE NOT NULL,
    
    -- Descripción opcional
    descripcion TEXT NULL,
    
    -- Estado del gasto
    estado ENUM('Pendiente', 'Rendido', 'Rechazado') DEFAULT 'Pendiente',
    
    -- ID de la rendición (para agrupar gastos rendidos)
    id_rendicion INT NULL,
    
    -- Metadatos de auditoría
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    usuario_creacion INT NULL,
    fecha_modificacion DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    CONSTRAINT fk_gasto_responsable FOREIGN KEY (id_responsable) REFERENCES personal(id_personal),
    
    -- Índices para búsquedas frecuentes
    INDEX idx_tipo_gasto (tipo_gasto),
    INDEX idx_responsable (id_responsable),
    INDEX idx_fecha (fecha_gasto),
    INDEX idx_estado (estado),
    INDEX idx_rendicion (id_rendicion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLA: Administracion_Rendiciones
-- Agrupa gastos rendidos para control y reposición
-- =============================================================================

CREATE TABLE IF NOT EXISTS Administracion_Rendiciones (
    id_rendicion INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Fecha de la rendición
    fecha_rendicion DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Monto total de la rendición
    monto_total DECIMAL(10,2) NOT NULL,
    
    -- Cantidad de comprobantes
    cantidad_comprobantes INT NOT NULL,
    
    -- Usuario que realizó la rendición
    usuario_rendicion INT NULL,
    
    -- Estado
    estado ENUM('Pendiente_Reposicion', 'Repuesto', 'Cancelado') DEFAULT 'Pendiente_Reposicion',
    
    -- Observaciones
    observaciones TEXT NULL,
    
    -- Metadatos
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLA: Administracion_Fondo_Fijo
-- Configuración del fondo fijo
-- =============================================================================

CREATE TABLE IF NOT EXISTS Administracion_Fondo_Fijo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Monto total del fondo fijo
    monto_fondo DECIMAL(10,2) NOT NULL DEFAULT 100000.00,
    
    -- Última reposición
    fecha_ultima_reposicion DATE NULL,
    
    -- Metadatos
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar configuración inicial del fondo fijo
INSERT INTO Administracion_Fondo_Fijo (monto_fondo) VALUES (100000.00)
ON DUPLICATE KEY UPDATE monto_fondo = monto_fondo;
