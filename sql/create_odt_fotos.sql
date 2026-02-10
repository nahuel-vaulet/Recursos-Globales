-- ARCH: Tabla para almacenamiento de fotos de ODT
-- AUDIT: Soporte para múltiples fotos y tipos específicos
CREATE TABLE IF NOT EXISTS odt_fotos (
    id_foto INT AUTO_INCREMENT PRIMARY KEY,
    id_odt INT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    ruta VARCHAR(255) NOT NULL,
    nombre_original VARCHAR(255),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_odt) REFERENCES odt_maestro(id_odt) ON DELETE CASCADE
);
