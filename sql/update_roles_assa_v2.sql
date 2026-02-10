-- ARCH: Actualización de ENUM de roles para soportar todos los tipos de usuario activos
-- AUDIT: Asegurar consistencia con los selectores del frontend
ALTER TABLE usuarios 
MODIFY COLUMN rol ENUM(
    'Gerente', 
    'Coordinador ASSA', 
    'Administrativo', 
    'Administrativo ASSA', 
    'Inspector ASSA', 
    'JefeCuadrilla'
) NOT NULL;
