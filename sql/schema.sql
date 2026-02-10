-- ============================================
-- SISTEMA DE GESTIÓN DE STOCK
-- Script de creación de base de datos
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS stock_management 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE stock_management;

-- ============================================
-- TABLA: usuarios
-- ============================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'supervisor', 'operador') NOT NULL DEFAULT 'operador',
    estado TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- TABLA: materiales
-- ============================================
CREATE TABLE IF NOT EXISTS materiales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(150) NOT NULL,
    codigo VARCHAR(50) UNIQUE,
    unidad_medida VARCHAR(30) NOT NULL,
    stock_actual DECIMAL(12,2) NOT NULL DEFAULT 0,
    stock_minimo DECIMAL(12,2) NOT NULL DEFAULT 0,
    categoria VARCHAR(100),
    estado TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- TABLA: cuadrillas
-- ============================================
CREATE TABLE IF NOT EXISTS cuadrillas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    zona_trabajo VARCHAR(150),
    responsable VARCHAR(100),
    estado TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- TABLA: movimientos
-- ============================================
CREATE TABLE IF NOT EXISTS movimientos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    material_id INT NOT NULL,
    cuadrilla_id INT,
    usuario_id INT NOT NULL,
    tipo ENUM('entrada', 'salida') NOT NULL,
    cantidad DECIMAL(12,2) NOT NULL,
    observaciones TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (material_id) REFERENCES materiales(id) ON DELETE RESTRICT,
    FOREIGN KEY (cuadrilla_id) REFERENCES cuadrillas(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_fecha (fecha),
    INDEX idx_material (material_id),
    INDEX idx_cuadrilla (cuadrilla_id)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: auditoria
-- ============================================
CREATE TABLE IF NOT EXISTS auditoria (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT,
    accion VARCHAR(50) NOT NULL,
    tabla VARCHAR(50) NOT NULL,
    registro_id INT,
    valor_anterior JSON,
    valor_nuevo JSON,
    ip_address VARCHAR(45),
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_fecha (fecha),
    INDEX idx_tabla (tabla)
) ENGINE=InnoDB;

-- ============================================
-- DATOS DE PRUEBA
-- ============================================

-- Usuario admin por defecto (password: admin123)
-- INSERT IGNORE para evitar errores de duplicados si se corre varias veces
INSERT IGNORE INTO usuarios (nombre, email, password_hash, rol) VALUES 
('Administrador', 'admin@stock.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Supervisor Demo', 'supervisor@stock.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'supervisor'),
('Operador Demo', 'operador@stock.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'operador');

-- Cuadrillas de ejemplo
INSERT IGNORE INTO cuadrillas (nombre, zona_trabajo, responsable) VALUES 
('Cuadrilla Norte', 'Zona Norte - Sector A', 'Juan Pérez'),
('Cuadrilla Sur', 'Zona Sur - Sector B', 'María García'),
('Cuadrilla Centro', 'Zona Centro - Sector C', 'Carlos López'),
('Cuadrilla Este', 'Zona Este - Sector D', 'Ana Martínez');

-- Materiales de ejemplo
INSERT IGNORE INTO materiales (nombre, codigo, unidad_medida, stock_actual, stock_minimo, categoria) VALUES 
('Cemento Portland', 'CEM-001', 'Bolsa 50kg', 150, 50, 'Construcción'),
('Arena Gruesa', 'ARE-001', 'm³', 75, 30, 'Áridos'),
('Hierro Corrugado 12mm', 'HIE-012', 'Varilla', 500, 100, 'Hierros'),
('Ladrillos Huecos', 'LAD-001', 'Unidad', 2500, 500, 'Mampostería'),
('Caños PVC 110mm', 'PVC-110', 'Metro', 45, 20, 'Plomería'),
('Cable 2.5mm', 'CAB-025', 'Metro', 800, 200, 'Electricidad'),
('Pintura Látex Blanco', 'PIN-001', 'Litro', 25, 10, 'Pinturería'),
('Membrana Asfáltica', 'MEM-001', 'm²', 15, 20, 'Aislación');

-- Movimientos de ejemplo
INSERT IGNORE INTO movimientos (material_id, cuadrilla_id, usuario_id, tipo, cantidad, observaciones) VALUES 
(1, 1, 1, 'entrada', 100, 'Compra inicial'),
(1, 1, 2, 'salida', 20, 'Obra calle 25'),
(2, 2, 1, 'entrada', 50, 'Reposición'),
(3, 3, 2, 'salida', 50, 'Columnas edificio'),
(4, 1, 3, 'salida', 200, 'Muro perimetral');

SET FOREIGN_KEY_CHECKS = 1;