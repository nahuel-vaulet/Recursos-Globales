-- =========================================================
-- MÓDULO DE TAREAS Y CALENDARIO
-- Fecha: 2026-02-02
-- =========================================================

-- 1. Tabla de Definiciones (Plantillas de Recurrencia)
CREATE TABLE IF NOT EXISTS tareas_definicion (
    id_definicion INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT,
    prioridad ENUM('Normal', 'Urgente') DEFAULT 'Normal',
    tipo_recurrencia ENUM('Unica', 'Diaria', 'Semanal', 'Mensual') DEFAULT 'Unica',
    -- Parametro: 
    -- Para Semanal: '1' (Lunes) a '7' (Domingo) o string 'Monday'
    -- Para Mensual: '1' a '31' (Día del mes)
    parametro_recurrencia VARCHAR(50) DEFAULT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE DEFAULT NULL,
    ultimo_generado DATE DEFAULT NULL, -- Control para no duplicar generación
    id_creador INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_creador) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
);

-- 2. Tabla de Instancias (Tareas Reales en Calendario)
CREATE TABLE IF NOT EXISTS tareas_instancia (
    id_tarea INT AUTO_INCREMENT PRIMARY KEY,
    id_definicion INT NOT NULL,
    titulo VARCHAR(150), -- Se clona por si cambia la definición futura
    descripcion TEXT,    -- Se permite editar la descripción de la instancia específica
    fecha_vencimiento DATE NOT NULL,
    fecha_completada DATETIME DEFAULT NULL,
    estado ENUM('Pendiente', 'Completada', 'Cancelada') DEFAULT 'Pendiente',
    prioridad ENUM('Normal', 'Urgente') DEFAULT 'Normal',
    id_responsable INT DEFAULT NULL, -- Quién la completó o a quién se asignó la instancia específica
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_definicion) REFERENCES tareas_definicion(id_definicion) ON DELETE CASCADE,
    FOREIGN KEY (id_responsable) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
);

-- Indices para búsqueda rápida en dashboard
CREATE INDEX idx_tarea_fecha ON tareas_instancia(fecha_vencimiento);
CREATE INDEX idx_tarea_estado ON tareas_instancia(estado);
CREATE INDEX idx_tarea_prioridad ON tareas_instancia(prioridad);
