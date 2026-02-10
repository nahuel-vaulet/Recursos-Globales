-- Fix password hashes for Gerente and Administrativo users
-- Passwords: Gerente = 999999, Administrativo = 666666

USE erp_global;

-- Update Gerente password (999999)
UPDATE usuarios 
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE email = 'gerente@erp.com';

-- Update Administrativo password (666666)  
UPDATE usuarios 
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE email = 'admin@erp.com';

-- Verify
SELECT id_usuario, nombre, email, rol, LEFT(password_hash, 30) as hash_preview FROM usuarios WHERE estado = 1;
