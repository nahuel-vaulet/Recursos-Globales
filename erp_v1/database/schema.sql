-- ============================================
-- SCHEMA.SQL - ERP Gestion de Stock v1
-- ============================================

-- Eliminar base de datos si existe (para reinstalacion limpia)
DROP DATABASE IF EXISTS erp_stock;

-- Crear base de datos
CREATE DATABASE erp_stock
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE erp_stock;

-- ============================================
-- TABLA: usuarios
-- ============================================
CREATE TABLE usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('administrador', 'supervisor', 'operador') NOT NULL DEFAULT 'operador',
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    ultimo_acceso DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_rol (rol),
    INDEX idx_estado (estado)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: materiales
-- ============================================
CREATE TABLE materiales (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT NULL,
    unidad_medida VARCHAR(50) NOT NULL,
    stock_actual INT NOT NULL DEFAULT 0,
    stock_minimo INT NOT NULL DEFAULT 0,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo),
    INDEX idx_nombre (nombre),
    INDEX idx_stock (stock_actual, stock_minimo)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: cuadrillas
-- ============================================
CREATE TABLE cuadrillas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    zona_trabajo VARCHAR(255) NULL,
    responsable VARCHAR(100) NULL,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nombre (nombre),
    INDEX idx_estado (estado)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: movimientos
-- ============================================
CREATE TABLE movimientos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    material_id INT UNSIGNED NOT NULL,
    cuadrilla_id INT UNSIGNED NULL,
    usuario_id INT UNSIGNED NOT NULL,
    tipo ENUM('entrada', 'salida') NOT NULL,
    cantidad INT NOT NULL,
    observaciones TEXT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (material_id) REFERENCES materiales(id) ON DELETE RESTRICT,
    FOREIGN KEY (cuadrilla_id) REFERENCES cuadrillas(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_material (material_id),
    INDEX idx_cuadrilla (cuadrilla_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_tipo (tipo),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: auditoria
-- ============================================
CREATE TABLE auditoria (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    accion ENUM('crear', 'editar', 'eliminar', 'login', 'logout') NOT NULL,
    tabla VARCHAR(50) NOT NULL,
    registro_id INT UNSIGNED NULL,
    valor_anterior JSON NULL,
    valor_nuevo JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_usuario (usuario_id),
    INDEX idx_accion (accion),
    INDEX idx_tabla (tabla),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB;

-- ============================================
-- DATOS INICIALES
-- ============================================

-- Usuarios (password: 123456)
INSERT INTO usuarios (nombre, email, password_hash, rol, estado) VALUES 
('Administrador General', 'admin@recursosgl.com', '$2y$10$xLRsVR5wEIZRGqFDdT1TeeLq6zBKUHvFxwp6PqFhfSLVjRsLvH.fS', 'administrador', 'activo'),
('Carlos Supervisor', 'supervisor@recursosgl.com', '$2y$10$xLRsVR5wEIZRGqFDdT1TeeLq6zBKUHvFxwp6PqFhfSLVjRsLvH.fS', 'supervisor', 'activo'),
('Maria Operadora', 'operador@recursosgl.com', '$2y$10$xLRsVR5wEIZRGqFDdT1TeeLq6zBKUHvFxwp6PqFhfSLVjRsLvH.fS', 'operador', 'activo'),
('Juan Inactivo', 'inactivo@recursosgl.com', '$2y$10$xLRsVR5wEIZRGqFDdT1TeeLq6zBKUHvFxwp6PqFhfSLVjRsLvH.fS', 'operador', 'inactivo');

-- Cuadrillas
INSERT INTO cuadrillas (nombre, zona_trabajo, responsable) VALUES 
('Cuadrilla Norte', 'Sector Norte - Obras Civiles', 'Juan Perez'),
('Cuadrilla Sur', 'Sector Sur - Mantenimiento', 'Maria Garcia'),
('Cuadrilla Central', 'Sector Central - Instalaciones', 'Carlos Lopez');

-- Materiales
INSERT INTO materiales (codigo, nombre, unidad_medida, stock_actual, stock_minimo) VALUES 
('MAT-001', 'Cemento Portland', 'Bolsa 50kg', 150, 50),
('MAT-002', 'Arena Gruesa', 'm3', 25, 10),
('MAT-003', 'Hierro 10mm', 'Barra 12m', 200, 100),
('MAT-004', 'Ladrillos Comunes', 'Unidad', 5000, 2000),
('MAT-005', 'Canos PVC 4 pulgadas', 'Tubo 4m', 45, 20),
('MAT-006', 'Cable 2.5mm', 'Metro', 500, 200),
('MAT-007', 'Pintura Latex Interior', 'Litro', 80, 30);

-- ============================================
-- VISTAS
-- ============================================

CREATE VIEW v_materiales_stock AS
SELECT 
    m.id,
    m.codigo,
    m.nombre,
    m.unidad_medida,
    m.stock_actual,
    m.stock_minimo,
    CASE 
        WHEN m.stock_actual <= m.stock_minimo THEN 'danger'
        WHEN m.stock_actual <= m.stock_minimo * 1.5 THEN 'warning'
        ELSE 'ok'
    END AS estado_stock,
    m.estado
FROM materiales m
WHERE m.estado = 'activo';

CREATE VIEW v_movimientos_detalle AS
SELECT 
    mo.id,
    mo.fecha,
    mo.tipo,
    mo.cantidad,
    mo.observaciones,
    ma.codigo AS material_codigo,
    ma.nombre AS material_nombre,
    ma.unidad_medida,
    c.nombre AS cuadrilla_nombre,
    u.nombre AS usuario_nombre
FROM movimientos mo
JOIN materiales ma ON mo.material_id = ma.id
LEFT JOIN cuadrillas c ON mo.cuadrilla_id = c.id
JOIN usuarios u ON mo.usuario_id = u.id
ORDER BY mo.fecha DESC;

-- ============================================
-- TRIGGER: Actualizar stock automaticamente
-- ============================================

DELIMITER //
CREATE TRIGGER tr_actualizar_stock
AFTER INSERT ON movimientos
FOR EACH ROW
BEGIN
    IF NEW.tipo = 'entrada' THEN
        UPDATE materiales SET stock_actual = stock_actual + NEW.cantidad WHERE id = NEW.material_id;
    ELSE
        UPDATE materiales SET stock_actual = stock_actual - NEW.cantidad WHERE id = NEW.material_id;
    END IF;
END//
DELIMITER ;
