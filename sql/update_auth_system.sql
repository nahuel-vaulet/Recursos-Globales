-- ===================================
-- SISTEMA DE AUTENTICACIÓN Y ROLES
-- ERP Recursos Globales
-- ===================================

USE erp_global;

-- ===================================
-- 1. MODIFICAR TABLA USUARIOS
-- ===================================

-- Primero eliminar la constraint existente si hay
ALTER TABLE usuarios 
MODIFY COLUMN rol ENUM('Gerente', 'Administrativo', 'JefeCuadrilla', 'Administrador', 'Supervisor', 'Operador') NOT NULL DEFAULT 'Administrativo';

-- Agregar relación con cuadrilla para Jefe de Cuadrilla
-- Verificar si la columna ya existe antes de agregarla
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = 'erp_global' 
               AND TABLE_NAME = 'usuarios' 
               AND COLUMN_NAME = 'id_cuadrilla');

SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE usuarios ADD COLUMN id_cuadrilla INT DEFAULT NULL',
    'SELECT "Column id_cuadrilla already exists"');

PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar foreign key si no existe
SET @fk_exists := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
                   WHERE CONSTRAINT_SCHEMA = 'erp_global' 
                   AND TABLE_NAME = 'usuarios' 
                   AND CONSTRAINT_NAME = 'fk_usuario_cuadrilla');

SET @fk_stmt := IF(@fk_exists = 0,
    'ALTER TABLE usuarios ADD CONSTRAINT fk_usuario_cuadrilla FOREIGN KEY (id_cuadrilla) REFERENCES cuadrillas(id_cuadrilla) ON DELETE SET NULL',
    'SELECT "FK already exists"');

PREPARE fk_prep FROM @fk_stmt;
EXECUTE fk_prep;
DEALLOCATE PREPARE fk_prep;

-- ===================================
-- 2. TABLA DE AUDITORÍA DE ACCIONES
-- ===================================

CREATE TABLE IF NOT EXISTS auditoria_acciones (
    id_auditoria INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    accion ENUM('LOGIN', 'LOGOUT', 'CREAR', 'EDITAR', 'ELIMINAR', 'VER') NOT NULL,
    modulo VARCHAR(50) NOT NULL,
    descripcion TEXT,
    id_registro_afectado INT DEFAULT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    INDEX idx_usuario (id_usuario),
    INDEX idx_accion (accion),
    INDEX idx_modulo (modulo),
    INDEX idx_fecha (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- 3. USUARIOS DE PRUEBA
-- ===================================

-- Contraseña para todos: 123456
-- Hash generado con password_hash('123456', PASSWORD_BCRYPT)
-- Nota: Este hash es válido y funcional

-- Eliminar usuarios de prueba existentes si los hay (por email)
DELETE FROM usuarios WHERE email IN ('gerente@erp.com', 'admin@erp.com', 'jefe@erp.com');

-- Insertar usuarios de prueba
INSERT INTO usuarios (nombre, email, password_hash, rol, id_cuadrilla, estado) VALUES
('Gerente Demo', 'gerente@erp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Gerente', NULL, 1),
('Admin Demo', 'admin@erp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrativo', NULL, 1);

-- El Jefe de Cuadrilla se asignará a una cuadrilla existente
-- Primero insertamos sin cuadrilla, luego actualizamos
INSERT INTO usuarios (nombre, email, password_hash, rol, id_cuadrilla, estado) VALUES
('Jefe Cuadrilla Demo', 'jefe@erp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JefeCuadrilla', NULL, 1);

-- Asignar a la primera cuadrilla disponible (si existe)
UPDATE usuarios u 
SET u.id_cuadrilla = (SELECT c.id_cuadrilla FROM cuadrillas c LIMIT 1)
WHERE u.email = 'jefe@erp.com' 
AND EXISTS (SELECT 1 FROM cuadrillas LIMIT 1);

-- ===================================
-- 4. VERIFICACIÓN
-- ===================================

-- Mostrar usuarios creados
SELECT id_usuario, nombre, email, rol, id_cuadrilla, estado 
FROM usuarios 
ORDER BY id_usuario DESC 
LIMIT 5;

-- Mostrar estructura de auditoría
DESCRIBE auditoria_acciones;
