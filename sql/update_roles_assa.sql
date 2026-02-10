-- =========================================================
-- UPDATE ROLES ASSA
-- =========================================================

-- ARCH: Update roles enum in usuarios table
-- AUDIT: Log changes in auditoria_acciones if possible

-- Modify the rol column to include new roles
ALTER TABLE usuarios 
MODIFY COLUMN rol ENUM(
    'Administrador', 
    'Supervisor', 
    'Operador', 
    'Inspector ASSA', 
    'Administrativo ASSA', 
    'Coordinador ASSA', 
    'Gerente'
) DEFAULT 'Operador';

-- Ensure Gerente role exists for the main admin
UPDATE usuarios SET rol = 'Gerente' WHERE rol = 'Administrador' OR email = 'admin@erp.com';

-- EDITAR: You can add seeds for specific users here if needed
-- INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES ('Inspector Juan', 'juan@assa.com', '...', 'Inspector ASSA');
