-- Database Schema for Modulo Compras

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------

-- Table: users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL, -- For simple auth
  `role` enum('solicitante','comprador','admin') NOT NULL DEFAULT 'solicitante',
  `department` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: providers
CREATE TABLE IF NOT EXISTS `providers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `tax_id` varchar(50) DEFAULT NULL,
  `contact_name` varchar(100) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `contract_type` enum('marco','spot','exclusivo') DEFAULT 'spot',
  `payment_terms` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: purchase_requests
CREATE TABLE IF NOT EXISTS `purchase_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `urgency` enum('baja','media','alta','critica') NOT NULL DEFAULT 'baja',
  `unit_of_business` varchar(100) DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `status` enum('borrador','enviada','en_revision','aprobada','rechazada','convertida_odc') NOT NULL DEFAULT 'borrador',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_request_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: request_items
CREATE TABLE IF NOT EXISTS `request_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(50) DEFAULT 'unidades',
  PRIMARY KEY (`id`),
  KEY `request_id` (`request_id`),
  CONSTRAINT `fk_item_request` FOREIGN KEY (`request_id`) REFERENCES `purchase_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: purchase_orders
CREATE TABLE IF NOT EXISTS `purchase_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_number` varchar(50) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `status` enum('emitida','enviada','confirmada','entregada','cancelada') NOT NULL DEFAULT 'emitida',
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `delivery_date_committed` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `po_number` (`po_number`),
  KEY `provider_id` (`provider_id`),
  CONSTRAINT `fk_po_provider` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: po_items
CREATE TABLE IF NOT EXISTS `po_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_id` int(11) NOT NULL,
  `item_description` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `price_unit` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `po_id` (`po_id`),
  CONSTRAINT `fk_po_items_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Data (Initial Users)
INSERT INTO `users` (`full_name`, `email`, `password`, `role`, `department`) VALUES
('Juan Solicitante', 'juan@empresa.com', '1234', 'solicitante', 'Obras Civiles'),
('Ana Compradora', 'ana@empresa.com', '1234', 'comprador', 'Compras');

INSERT INTO `providers` (`name`, `contact_name`, `contract_type`) VALUES
('Proveedor A (Materiales)', 'Carlos Lopez', 'marco'),
('Proveedor B (EPP)', 'Maria Ruiz', 'spot');

COMMIT;
