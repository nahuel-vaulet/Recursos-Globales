-- Add missing columns to proveedores table
ALTER TABLE proveedores ADD COLUMN direccion VARCHAR(255) NULL AFTER cuit;
ALTER TABLE proveedores ADD COLUMN telefono VARCHAR(50) NULL AFTER direccion;
ALTER TABLE proveedores ADD COLUMN email VARCHAR(100) NULL AFTER telefono;
ALTER TABLE proveedores ADD COLUMN nombre_contacto VARCHAR(100) NULL AFTER email;
