-- ============================================
-- TABLA: tipos_trabajos (antes: tipologias)
-- Catálogo de tipos de trabajo ASSA/RG
-- ============================================

USE erp_global;

-- Eliminar tabla si existe para recrear
DROP TABLE IF EXISTS tipos_trabajos;

-- ============================================
-- CREACIÓN DE LA TABLA
-- ============================================
CREATE TABLE tipos_trabajos (
    id_tipologia INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(150) NOT NULL COMMENT 'Nombre del tipo de trabajo',
    codigo_trabajo VARCHAR(30) NOT NULL UNIQUE COMMENT 'Código interno ASSA/RG (ej: 3.1, 22.5)',
    tiempo_limite_dias INT DEFAULT NULL COMMENT 'Plazo máximo para ejecución en días',
    unidad_medida ENUM('M2', 'M3', 'ML', 'U') NOT NULL DEFAULT 'U' COMMENT 'Unidad de medida del trabajo',
    descripcion_larga TEXT COMMENT 'Descripción detallada del trabajo',
    descripcion_breve VARCHAR(255) COMMENT 'Descripción corta para listados',
    precio_unitario DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Precio por unidad de medida (OM 2026)',
    estado TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_codigo (codigo_trabajo),
    INDEX idx_unidad (unidad_medida),
    INDEX idx_estado (estado)
) ENGINE=InnoDB 
  DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Catálogo de tipos de trabajo para obras ASSA/RG';

-- ============================================
-- DATOS INICIALES (Catálogo ASSA/RG)
-- Extraídos de la documentación oficial
-- ============================================

INSERT INTO tipos_trabajos (codigo_trabajo, nombre, descripcion_breve, descripcion_larga, unidad_medida, precio_unitario) VALUES

-- REPARACIONES DE VEREDAS
('3.1', 'Reparación de veredas comunes', 'Rep. veredas comunes', 'Reparación de veredas comunes en zona urbana', 'M2', 109181.88),
('3.2', 'Reparación de veredas con concreto', 'Rep. veredas concreto', 'Reparación de veredas utilizando concreto especial', 'M2', 105181.88),

-- FUGAS Y REPARACIONES DE AGUA
('22.1', 'Reparación de fugas de agua en vereda', 'Rep. fugas agua vereda', 'Reparación de fugas de agua detectadas en zona de vereda', 'U', 271024.83),
('22.2', 'Reparación de fugas de agua en calzada', 'Rep. fugas agua calzada', 'Reparación de fugas de agua detectadas en zona de calzada', 'U', 98374.18),
('22.5', 'Fuga de caño distribuidor en calzada', 'Fuga caño distribuidor', 'Reparación de fuga en caño distribuidor ubicado en calzada', 'U', NULL),

-- CONEXIONES DE AGUA
('20.1', 'Conexión nueva de agua calzada', 'Conexión nueva calzada', 'Instalación de nueva conexión de agua en calzada', 'U', NULL),
('20.2', 'Conexión corta en calzada', 'Conexión corta calzada', 'Instalación de conexión corta de agua en calzada', 'U', NULL),
('20.4', 'Conexión larga de agua en vereda', 'Conexión larga vereda', 'Instalación de conexión larga de agua en zona de vereda', 'U', NULL),
('20.5', 'Conexión larga de agua en calzada', 'Conexión larga calzada', 'Instalación de conexión larga de agua en zona de calzada', 'U', NULL),

-- RENOVACIÓN DE CONEXIONES
('21.1', 'Renovación de conexiones corta de agua', 'Renov. conexiones corta', 'Renovación de conexiones cortas de servicio de agua', 'U', 277656.12),

-- EMPALMES Y LLAVES
('22.7', 'Renovación de llaves maestra', 'Renov. llaves maestra', 'Renovación de llaves maestras del sistema de agua', 'ML', 58201.13),

-- EMPALMES DE AGUA
('20.2b', 'Empalmes nuevos de agua calzada', 'Empalmes nuevos calzada', 'Instalación de empalmes nuevos de agua en calzada', 'U', 434445.77),

-- CLOACA
('22.1b', 'Reparación fugas de cloaca', 'Rep. fugas cloaca', 'Reparación de fugas detectadas en sistema de cloaca', 'U', NULL),

-- CONEXIONES DE CLOACA
('24.1', 'Conexiones corta vereda nueva de cloaca', 'Conex. corta vereda cloaca', 'Instalación de conexión corta de cloaca en vereda nueva', 'U', NULL),
('24.5', 'Conexiones larga en vereda hasta 2,5mt de prof', 'Conex. larga vereda 2.5m', 'Conexión larga en vereda con profundidad hasta 2.5 metros', 'U', NULL),
('25.1', 'Renovación de conexiones de cloaca', 'Renov. conexiones cloaca', 'Renovación completa de conexiones del sistema de cloaca', 'ML', 396749.80),

-- REDES
('18', 'Renovación de redes de cloaca', 'Renov. redes cloaca', 'Renovación de redes principales del sistema de cloaca', 'U', 424895.94),

-- HIDRANTERÍA Y VÁLVULAS
('24.1b', 'Conexiones corta hidrantes', 'Conexiones hidrantes', 'Instalación de conexiones cortas para hidrantes', 'U', NULL),
('24.2', 'Conexiones de válvulas', 'Conexiones válvulas', 'Instalación de conexiones para válvulas del sistema', 'U', NULL),

-- RENOVACIÓN DE REDES DE AGUA
('22.1c', 'Renovación de redes de agua', 'Renov. redes agua', 'Renovación de redes principales del sistema de agua', 'U', NULL),

-- MARCO Y TAPA
('43.2', 'Colocación de marco y tapa p/boca de registro (calzada)', 'Marco y tapa calzada', 'Colocación de marco y tapa para boca de registro en calzada', 'U', 52238.35),

-- INSTALACIONES
('43.3', 'Instalaciones medidores SC', 'Instalaciones medidores', 'Instalación de medidores de servicio continuo', 'U', 23743.17),

-- RENOVACIÓN MARCO Y TAPA
('43.4', 'Renovación Marco y Tapa', 'Renov. marco y tapa', 'Renovación de marco y tapa existente', 'ML', 52238.35),

-- RECAMBIOS
('43.8', 'Recambios de medidores', 'Recambio medidores', 'Recambio de medidores en servicio', 'U', 52238.35),

-- PLAYA DE SECADO
('PS-01', 'Playa de Secado - REHABILITACIÓN', 'Playa secado rehab.', 'Trabajos de rehabilitación en playa de secado', 'U', NULL),
('PS-02', 'Playas de secado - vereda + finalización', 'Playa secado vereda', 'Trabajos de vereda y finalización en playa de secado', 'U', NULL);

-- ============================================
-- VERIFICACIÓN
-- ============================================
SELECT 
    COUNT(*) as total_registros,
    SUM(CASE WHEN unidad_medida = 'M2' THEN 1 ELSE 0 END) as tipo_m2,
    SUM(CASE WHEN unidad_medida = 'U' THEN 1 ELSE 0 END) as tipo_unidad,
    SUM(CASE WHEN unidad_medida = 'ML' THEN 1 ELSE 0 END) as tipo_ml
FROM tipos_trabajos;
