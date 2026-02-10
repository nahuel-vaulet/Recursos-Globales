-- Add codigo to materials if not exists (Assume it might not, form said placeholder)
-- We will use a stored procedure trick or just ignore error if exists in a script, or just run ALTER.
-- Simplest for this environment: Add columns blindly, if fail, check why.
ALTER TABLE maestro_materiales ADD COLUMN codigo VARCHAR(50) NULL AFTER id_material;
ALTER TABLE movimientos ADD COLUMN usuario_recepcion VARCHAR(100) NULL AFTER usuario_despacho;
ALTER TABLE movimientos ADD COLUMN id_proveedor INT NULL AFTER id_odt;

-- Initialize codes for existing materials (Simple Init)
UPDATE maestro_materiales SET codigo = CONCAT('MAT-', LPAD(id_material, 4, '0')) WHERE codigo IS NULL;
