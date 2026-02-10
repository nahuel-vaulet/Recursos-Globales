-- Modificar ENUM en odt_maestro
ALTER TABLE odt_maestro 
MODIFY COLUMN estado_gestion ENUM('Sin Programar', 'Programación Solicitada', 'Programado', 'Ejecución', 'Ejecutado', 'Precertificada', 'Finalizado', 'Re-programar') NOT NULL DEFAULT 'Sin Programar';

-- Agregar columna turno en programacion_semanal
ALTER TABLE programacion_semanal
ADD COLUMN turno ENUM('Mañana', 'Tarde') NOT NULL DEFAULT 'Mañana' AFTER fecha_programada;
