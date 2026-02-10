-- Database: erp_global
CREATE DATABASE IF NOT EXISTS erp_global CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE erp_global;

-- =========================================================
-- MÓDULO 1: DEFINICIONES Y MAESTROS
-- =========================================================

CREATE TABLE IF NOT EXISTS proveedores (
    id_proveedor INT AUTO_INCREMENT PRIMARY KEY,
    razon_social VARCHAR(100) NOT NULL,
    cuit VARCHAR(20),
    direccion VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS proveedores_contactos (
    id_contacto INT AUTO_INCREMENT PRIMARY KEY,
    id_proveedor INT NOT NULL,
    nombre_vendedor VARCHAR(100),
    telefono_contacto VARCHAR(50),
    email_vendedor VARCHAR(100),
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_proveedor) REFERENCES proveedores(id_proveedor) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tipologias (
    id_tipologia INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    codigo_trabajo VARCHAR(50) UNIQUE,
    tiempo_limite_dias INT DEFAULT 1,
    unidad_medida VARCHAR(20),
    descripcion_larga TEXT,
    descripcion_breve VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS maestro_materiales (
    id_material INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    unidad_medida VARCHAR(20),
    punto_pedido DECIMAL(10,2),
    id_contacto_primario INT,
    costo_primario DECIMAL(10,2),
    id_contacto_secundario INT,
    costo_secundario DECIMAL(10,2),
    fecha_ultima_cotizacion DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_contacto_primario) REFERENCES proveedores_contactos(id_contacto) ON DELETE SET NULL,
    FOREIGN KEY (id_contacto_secundario) REFERENCES proveedores_contactos(id_contacto) ON DELETE SET NULL
);

-- =========================================================
-- MÓDULO 2: GESTIÓN OPERATIVA
-- =========================================================

CREATE TABLE IF NOT EXISTS odt_maestro (
    id_odt INT AUTO_INCREMENT PRIMARY KEY,
    nro_odt_assa VARCHAR(50) NOT NULL UNIQUE,
    direccion VARCHAR(255),
    id_tipologia INT,
    prioridad ENUM('Normal', 'Urgente') DEFAULT 'Normal',
    estado_gestion ENUM('Sin Programar', 'Programado', 'Ejecutado', 'Finalizado') DEFAULT 'Sin Programar',
    fecha_inicio_plazo DATE,
    fecha_vencimiento DATE,
    avance TEXT,
    inspector VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_tipologia) REFERENCES tipologias(id_tipologia) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS cuadrillas (
    id_cuadrilla INT AUTO_INCREMENT PRIMARY KEY,
    nombre_cuadrilla VARCHAR(100) NOT NULL,
    tipo_especialidad VARCHAR(50),
    zona_asignada VARCHAR(100),
    estado_operativo ENUM('Activa', 'Mantenimiento', 'Baja') DEFAULT 'Activa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS programacion_semanal (
    id_programacion INT AUTO_INCREMENT PRIMARY KEY,
    id_odt INT NOT NULL,
    id_cuadrilla INT NOT NULL,
    fecha_programada DATE,
    estado_programacion ENUM('Tildado_Admin', 'Confirmado_ASSA') DEFAULT 'Tildado_Admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_odt) REFERENCES odt_maestro(id_odt) ON DELETE CASCADE,
    FOREIGN KEY (id_cuadrilla) REFERENCES cuadrillas(id_cuadrilla) ON DELETE CASCADE
);

-- =========================================================
-- MÓDULO 3: LOGÍSTICA
-- =========================================================

CREATE TABLE IF NOT EXISTS stock_saldos (
    id_saldo INT AUTO_INCREMENT PRIMARY KEY,
    id_material INT NOT NULL,
    stock_oficina DECIMAL(10,2) DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_material) REFERENCES maestro_materiales(id_material) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS stock_cuadrilla (
    id_stock_cuadrilla INT AUTO_INCREMENT PRIMARY KEY,
    id_cuadrilla INT NOT NULL,
    id_material INT NOT NULL,
    cantidad DECIMAL(10,2) DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cuadrilla) REFERENCES cuadrillas(id_cuadrilla) ON DELETE CASCADE,
    FOREIGN KEY (id_material) REFERENCES maestro_materiales(id_material) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS movimientos (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    nro_documento VARCHAR(50),
    tipo_movimiento ENUM('Recepcion_ASSA_Oficina', 'Entrega_Oficina_Cuadrilla', 'Consumo_Cuadrilla_Obra') NOT NULL,
    id_material INT NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    id_cuadrilla INT,
    id_odt INT,
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    usuario_despacho INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_material) REFERENCES maestro_materiales(id_material) ON DELETE CASCADE,
    FOREIGN KEY (id_cuadrilla) REFERENCES cuadrillas(id_cuadrilla) ON DELETE SET NULL,
    FOREIGN KEY (id_odt) REFERENCES odt_maestro(id_odt) ON DELETE SET NULL
);

-- =========================================================
-- MÓDULO 4: RRHH & USUARIOS
-- =========================================================

CREATE TABLE IF NOT EXISTS personal (
    id_personal INT AUTO_INCREMENT PRIMARY KEY,
    nombre_apellido VARCHAR(100) NOT NULL,
    dni VARCHAR(20) UNIQUE,
    rol ENUM('Oficial', 'Ayudante', 'Administrativo', 'Supervisor') DEFAULT 'Ayudante',
    id_cuadrilla INT,
    telefono_personal VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cuadrilla) REFERENCES cuadrillas(id_cuadrilla) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS asistencia (
    id_asistencia INT AUTO_INCREMENT PRIMARY KEY,
    id_personal INT NOT NULL,
    fecha DATE NOT NULL,
    hora_entrada TIME,
    hora_salida TIME,
    estado_dia ENUM('Presente', 'Falta Justificada', 'Injustificada', 'Dia Lluvia'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_personal) REFERENCES personal(id_personal) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    id_personal INT UNIQUE,
    nombre VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('Administrador', 'Supervisor', 'Operador') DEFAULT 'Operador',
    estado TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_personal) REFERENCES personal(id_personal) ON DELETE SET NULL
);

-- Insert dummy admin user (Password: admin123)
-- Hash generated via password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO usuarios (nombre, email, password_hash, rol) 
VALUES ('Administrador', 'admin@erp.com', '$2y$10$YourHashHere', 'Administrador')
ON DUPLICATE KEY UPDATE nombre=nombre;
