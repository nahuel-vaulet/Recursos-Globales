-- Seed Data for Testing Stock & Movements
USE erp_global;

-- 1. Insert Tipologias
INSERT INTO tipologias (nombre, codigo_trabajo) VALUES 
('Reparación de Vereda', 'VER-001'),
('Fuga de Agua', 'FUG-002'),
('Conexión Nueva', 'CON-003')
ON DUPLICATE KEY UPDATE nombre=nombre;

-- 2. Insert Cuadrillas
INSERT INTO cuadrillas (nombre_cuadrilla, tipo_especialidad, estado_operativo) VALUES 
('Cuadrilla Norte (Juan/Pedro)', 'Veredas', 'Activa'),
('Cuadrilla Sur (Carlos/Luis)', 'Hidráulica', 'Activa')
ON DUPLICATE KEY UPDATE nombre_cuadrilla=nombre_cuadrilla;

-- 3. Insert ODTs
INSERT INTO odt_maestro (nro_odt_assa, direccion, id_tipologia, estado_gestion, prioridad) VALUES 
('ODT-9901', 'Av. Santa Fe 1234', 1, 'Programado', 'Normal'),
('ODT-9902', 'Calle Lavalle 550', 2, 'Ejecutado', 'Urgente')
ON DUPLICATE KEY UPDATE nro_odt_assa=nro_odt_assa;
