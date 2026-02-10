-- Add tipo_combustible to vehiculos
ALTER TABLE vehiculos ADD COLUMN tipo_combustible VARCHAR(20) DEFAULT 'Diesel';

-- Add id_cuadrilla to allow multiple vehicles per squad
ALTER TABLE vehiculos ADD COLUMN id_cuadrilla INT DEFAULT NULL;
ALTER TABLE vehiculos ADD FOREIGN KEY (id_cuadrilla) REFERENCES cuadrillas(id_cuadrilla) ON DELETE SET NULL;
