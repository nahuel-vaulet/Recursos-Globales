-- Migration Compras Module
-- Tablas para gestión de compras integradas al ERP

SET FOREIGN_KEY_CHECKS=0;

-- 1. Tabla Solicitudes de Compra
CREATE TABLE IF NOT EXISTS `compras_solicitudes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `urgencia` enum('baja','media','alta','critica') NOT NULL DEFAULT 'baja',
  `unidad_negocio` varchar(100) DEFAULT NULL,
  `ubicacion` varchar(150) DEFAULT NULL,
  `estado` enum('borrador','enviada','en_revision','aprobada','rechazada','convertida_odc') NOT NULL DEFAULT 'borrador',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Items de Solicitud
CREATE TABLE IF NOT EXISTS `compras_items_solicitud` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_solicitud` int(11) NOT NULL,
  `item` varchar(200) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `unidad` varchar(50) DEFAULT 'unidades',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_solicitud`) REFERENCES `compras_solicitudes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Ordenes de Compra
CREATE TABLE IF NOT EXISTS `compras_ordenes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nro_orden` varchar(50) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `estado` enum('emitida','enviada','confirmada','entregada','cancelada') NOT NULL DEFAULT 'emitida',
  `monto_total` decimal(12,2) DEFAULT 0.00,
  `fecha_entrega_pactada` date DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nro_orden` (`nro_orden`),
  FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Items de Orden
CREATE TABLE IF NOT EXISTS `compras_items_orden` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_orden` int(11) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_orden`) REFERENCES `compras_ordenes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS=1;

-- Seed Data (Example)
-- INSERT INTO `compras_solicitudes` ...
