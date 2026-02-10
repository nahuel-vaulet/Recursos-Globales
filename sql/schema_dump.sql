-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: erp_global
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `administracion_fondo_fijo`
--

DROP TABLE IF EXISTS `administracion_fondo_fijo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `administracion_fondo_fijo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `monto_fondo` decimal(10,2) NOT NULL DEFAULT 100000.00,
  `fecha_ultima_reposicion` date DEFAULT NULL,
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `administracion_fondo_fijo`
--

LOCK TABLES `administracion_fondo_fijo` WRITE;
/*!40000 ALTER TABLE `administracion_fondo_fijo` DISABLE KEYS */;
INSERT INTO `administracion_fondo_fijo` VALUES (1,100000.00,NULL,'2026-02-03 14:57:15');
/*!40000 ALTER TABLE `administracion_fondo_fijo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `administracion_gastos`
--

DROP TABLE IF EXISTS `administracion_gastos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `administracion_gastos` (
  `id_gasto` int(11) NOT NULL AUTO_INCREMENT,
  `monto` decimal(10,2) NOT NULL,
  `tipo_gasto` enum('Ferreteria','Comida','Peajes','Combustible_Emergencia','Insumos_Oficina','Otros') NOT NULL,
  `id_responsable` int(11) NOT NULL,
  `comprobante_path` varchar(500) NOT NULL,
  `fecha_gasto` date NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` enum('Pendiente','Rendido','Rechazado') DEFAULT 'Pendiente',
  `id_rendicion` int(11) DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `usuario_creacion` int(11) DEFAULT NULL,
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_gasto`),
  KEY `idx_tipo_gasto` (`tipo_gasto`),
  KEY `idx_responsable` (`id_responsable`),
  KEY `idx_fecha` (`fecha_gasto`),
  KEY `idx_estado` (`estado`),
  KEY `idx_rendicion` (`id_rendicion`),
  CONSTRAINT `fk_gasto_responsable` FOREIGN KEY (`id_responsable`) REFERENCES `personal` (`id_personal`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `administracion_gastos`
--

LOCK TABLES `administracion_gastos` WRITE;
/*!40000 ALTER TABLE `administracion_gastos` DISABLE KEYS */;
INSERT INTO `administracion_gastos` VALUES (1,0.06,'Ferreteria',1,'gasto_20260205_121917_69847cb583d0b.jpeg','2026-02-05','','Pendiente',NULL,'2026-02-05 08:19:17',2,NULL);
/*!40000 ALTER TABLE `administracion_gastos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `administracion_rendiciones`
--

DROP TABLE IF EXISTS `administracion_rendiciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `administracion_rendiciones` (
  `id_rendicion` int(11) NOT NULL AUTO_INCREMENT,
  `fecha_rendicion` datetime DEFAULT current_timestamp(),
  `monto_total` decimal(10,2) NOT NULL,
  `cantidad_comprobantes` int(11) NOT NULL,
  `usuario_rendicion` int(11) DEFAULT NULL,
  `estado` enum('Pendiente_Reposicion','Repuesto','Cancelado') DEFAULT 'Pendiente_Reposicion',
  `observaciones` text DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_rendicion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `administracion_rendiciones`
--

LOCK TABLES `administracion_rendiciones` WRITE;
/*!40000 ALTER TABLE `administracion_rendiciones` DISABLE KEYS */;
/*!40000 ALTER TABLE `administracion_rendiciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asistencia`
--

DROP TABLE IF EXISTS `asistencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asistencia` (
  `id_asistencia` int(11) NOT NULL AUTO_INCREMENT,
  `id_personal` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora_entrada` time DEFAULT NULL,
  `hora_salida` time DEFAULT NULL,
  `estado_dia` enum('Presente','Falta Justificada','Injustificada','Dia Lluvia') DEFAULT NULL,
  `horas_emergencia` decimal(4,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_asistencia`),
  KEY `id_personal` (`id_personal`),
  CONSTRAINT `asistencia_ibfk_1` FOREIGN KEY (`id_personal`) REFERENCES `personal` (`id_personal`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asistencia`
--

LOCK TABLES `asistencia` WRITE;
/*!40000 ALTER TABLE `asistencia` DISABLE KEYS */;
/*!40000 ALTER TABLE `asistencia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `combustibles_cargas`
--

DROP TABLE IF EXISTS `combustibles_cargas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `combustibles_cargas` (
  `id_carga` int(11) NOT NULL AUTO_INCREMENT,
  `id_tanque` int(11) DEFAULT NULL,
  `destino_tipo` enum('stock','vehiculo') NOT NULL DEFAULT 'stock',
  `tipo_combustible` varchar(50) DEFAULT NULL,
  `id_cuadrilla` int(11) DEFAULT NULL,
  `id_vehiculo` int(11) DEFAULT NULL,
  `conductor` varchar(100) DEFAULT NULL,
  `fecha_hora` datetime NOT NULL,
  `litros` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) DEFAULT 0.00,
  `proveedor` varchar(100) DEFAULT NULL,
  `nro_factura` varchar(50) DEFAULT NULL,
  `foto_ticket` varchar(255) DEFAULT NULL,
  `comprobante_path` varchar(255) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_carga`),
  KEY `id_tanque` (`id_tanque`),
  CONSTRAINT `combustibles_cargas_ibfk_1` FOREIGN KEY (`id_tanque`) REFERENCES `combustibles_tanques` (`id_tanque`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `combustibles_cargas`
--

LOCK TABLES `combustibles_cargas` WRITE;
/*!40000 ALTER TABLE `combustibles_cargas` DISABLE KEYS */;
INSERT INTO `combustibles_cargas` VALUES (1,1,'stock','Gasoil',NULL,NULL,NULL,'2026-02-03 15:47:00',50.00,75000.00,'ypf','7872148565',NULL,NULL,2,'2026-02-03 14:47:33');
INSERT INTO `combustibles_cargas` VALUES (2,1,'stock','Gasoil',NULL,NULL,NULL,'2026-02-03 15:47:00',50.00,75000.00,'ypf','7872148565',NULL,NULL,2,'2026-02-03 14:47:37');
INSERT INTO `combustibles_cargas` VALUES (3,1,'stock','Gasoil',NULL,NULL,NULL,'2026-02-03 15:47:00',50.00,75000.00,'ypf','44851463',NULL,NULL,2,'2026-02-03 14:48:05');
INSERT INTO `combustibles_cargas` VALUES (4,1,'vehiculo','Gasoil',1,1,NULL,'2026-02-03 16:36:00',0.04,20.00,'','7872148565',NULL,NULL,2,'2026-02-03 15:37:06');
INSERT INTO `combustibles_cargas` VALUES (5,2,'stock','Gasoil',NULL,NULL,NULL,'2026-02-03 18:05:00',800.00,300.00,'','88558',NULL,NULL,2,'2026-02-03 17:06:08');
INSERT INTO `combustibles_cargas` VALUES (6,1,'vehiculo','Gasoil',2,2,'nahuel','2026-02-09 15:21:00',5.00,88478.00,'','7890',NULL,NULL,1,'2026-02-09 14:22:24');
/*!40000 ALTER TABLE `combustibles_cargas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `combustibles_despachos`
--

DROP TABLE IF EXISTS `combustibles_despachos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `combustibles_despachos` (
  `id_despacho` int(11) NOT NULL AUTO_INCREMENT,
  `id_tanque` int(11) NOT NULL,
  `id_vehiculo` int(11) NOT NULL,
  `id_cuadrilla` int(11) DEFAULT NULL,
  `fecha_hora` datetime NOT NULL,
  `litros` decimal(10,2) NOT NULL,
  `odometro_actual` decimal(10,1) NOT NULL,
  `usuario_despacho` int(11) DEFAULT NULL,
  `usuario_conductor` varchar(100) DEFAULT NULL,
  `destino_obra` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_despacho`),
  KEY `id_tanque` (`id_tanque`),
  KEY `id_vehiculo` (`id_vehiculo`),
  KEY `id_cuadrilla` (`id_cuadrilla`),
  CONSTRAINT `combustibles_despachos_ibfk_1` FOREIGN KEY (`id_tanque`) REFERENCES `combustibles_tanques` (`id_tanque`),
  CONSTRAINT `combustibles_despachos_ibfk_2` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculos` (`id_vehiculo`),
  CONSTRAINT `combustibles_despachos_ibfk_3` FOREIGN KEY (`id_cuadrilla`) REFERENCES `cuadrillas` (`id_cuadrilla`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `combustibles_despachos`
--

LOCK TABLES `combustibles_despachos` WRITE;
/*!40000 ALTER TABLE `combustibles_despachos` DISABLE KEYS */;
INSERT INTO `combustibles_despachos` VALUES (1,1,1,NULL,'2026-02-03 10:26:29',45.50,0.0,1,'Test Driver',NULL,'2026-02-03 13:26:29');
INSERT INTO `combustibles_despachos` VALUES (2,1,2,NULL,'2026-02-03 10:26:29',20.00,0.0,1,'Test Driver 2',NULL,'2026-02-03 13:26:29');
INSERT INTO `combustibles_despachos` VALUES (3,2,1,NULL,'2026-02-03 18:17:00',75.00,0.0,2,'nahuel piva','','2026-02-03 17:17:13');
INSERT INTO `combustibles_despachos` VALUES (4,2,1,NULL,'2026-02-03 18:17:00',25.00,0.0,2,'nahuel piva','','2026-02-03 17:19:08');
INSERT INTO `combustibles_despachos` VALUES (5,2,1,NULL,'2026-02-03 18:29:00',15.00,0.0,2,'nahuel piva','','2026-02-03 17:29:50');
INSERT INTO `combustibles_despachos` VALUES (6,2,1,NULL,'2026-02-03 18:31:00',88.00,0.0,2,'nahuel piva','','2026-02-03 17:31:37');
INSERT INTO `combustibles_despachos` VALUES (7,2,1,NULL,'2026-02-03 18:32:00',25.00,0.0,2,'nahuel piva','','2026-02-03 17:32:54');
INSERT INTO `combustibles_despachos` VALUES (8,2,1,NULL,'2026-02-03 18:38:00',7.00,0.0,2,'nahuel piva','','2026-02-03 17:38:28');
INSERT INTO `combustibles_despachos` VALUES (9,2,1,NULL,'2026-02-04 13:48:00',20.00,0.0,2,'nahuel piva','','2026-02-04 12:48:31');
INSERT INTO `combustibles_despachos` VALUES (10,1,2,NULL,'2026-02-04 13:49:00',27.00,0.0,2,'nahuel piva','','2026-02-04 14:55:15');
INSERT INTO `combustibles_despachos` VALUES (11,2,1,1,'2026-02-05 17:20:00',5.00,0.0,2,'nahuel piva','','2026-02-05 16:20:38');
INSERT INTO `combustibles_despachos` VALUES (12,2,1,1,'2026-02-05 17:20:00',10.00,0.0,2,'nahuel piva','','2026-02-05 16:20:59');
INSERT INTO `combustibles_despachos` VALUES (13,2,1,1,'2026-02-05 17:21:00',10.00,0.0,2,'nahuel piva','','2026-02-05 16:21:41');
INSERT INTO `combustibles_despachos` VALUES (14,1,1,1,'2026-02-05 17:21:00',10.00,0.0,2,'nahuel piva','','2026-02-05 16:21:55');
INSERT INTO `combustibles_despachos` VALUES (15,2,2,1,'2026-02-05 17:21:00',3.00,0.0,2,'nahuel piva','','2026-02-05 16:22:26');
INSERT INTO `combustibles_despachos` VALUES (16,1,1,1,'2026-02-05 18:48:00',5.00,0.0,2,'nahuel piva','','2026-02-05 17:49:18');
INSERT INTO `combustibles_despachos` VALUES (17,2,1,1,'2026-02-05 18:49:00',5.00,0.0,2,'nahuel piva','','2026-02-05 17:49:31');
INSERT INTO `combustibles_despachos` VALUES (18,1,1,1,'2026-02-05 18:49:00',5.00,0.0,2,'nahuel piva','','2026-02-05 17:49:40');
INSERT INTO `combustibles_despachos` VALUES (19,2,1,1,'2026-02-05 18:56:00',5.00,0.0,2,'nahuel piva','','2026-02-05 17:56:56');
INSERT INTO `combustibles_despachos` VALUES (20,1,1,1,'2026-02-05 18:57:00',5.00,0.0,2,'nahuel piva','','2026-02-05 17:57:09');
INSERT INTO `combustibles_despachos` VALUES (21,2,1,1,'2026-02-05 19:05:00',5.00,0.0,2,'pablo','','2026-02-05 18:05:13');
INSERT INTO `combustibles_despachos` VALUES (22,1,1,1,'2026-02-05 19:05:00',5.00,0.0,2,'pablo','','2026-02-05 18:05:26');
INSERT INTO `combustibles_despachos` VALUES (23,1,1,1,'2026-02-05 19:14:00',5.00,0.0,2,'pablo','','2026-02-05 18:14:44');
INSERT INTO `combustibles_despachos` VALUES (24,2,1,1,'2026-02-05 19:14:00',5.00,0.0,2,'pablo','','2026-02-05 18:14:58');
INSERT INTO `combustibles_despachos` VALUES (25,2,1,1,'2026-02-09 15:18:00',20.00,0.0,3,'pablo','','2026-02-09 14:20:41');
/*!40000 ALTER TABLE `combustibles_despachos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `combustibles_tanques`
--

DROP TABLE IF EXISTS `combustibles_tanques`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `combustibles_tanques` (
  `id_tanque` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `tipo_combustible` varchar(50) NOT NULL DEFAULT 'Diesel',
  `capacidad_maxima` decimal(10,2) NOT NULL,
  `stock_actual` decimal(10,2) DEFAULT 0.00,
  `ubicacion` varchar(100) DEFAULT 'Base Central',
  `estado` enum('Activo','Inactivo','Mantenimiento') DEFAULT 'Activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_tanque`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `combustibles_tanques`
--

LOCK TABLES `combustibles_tanques` WRITE;
/*!40000 ALTER TABLE `combustibles_tanques` DISABLE KEYS */;
INSERT INTO `combustibles_tanques` VALUES (1,'Tanque Principal (Gasoil)','Diesel',1000.00,88.00,'Base Central','Activo','2026-02-03 13:07:57');
INSERT INTO `combustibles_tanques` VALUES (2,'Tanque Auxiliar (Nafta)','Nafta',1000.00,727.00,'Base Central','Activo','2026-02-03 13:07:57');
/*!40000 ALTER TABLE `combustibles_tanques` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cuadrilla_tipos_trabajo`
--

DROP TABLE IF EXISTS `cuadrilla_tipos_trabajo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cuadrilla_tipos_trabajo` (
  `id_cuadrilla` int(11) NOT NULL,
  `id_tipologia` int(11) NOT NULL,
  PRIMARY KEY (`id_cuadrilla`,`id_tipologia`),
  KEY `id_tipologia` (`id_tipologia`),
  CONSTRAINT `cuadrilla_tipos_trabajo_ibfk_1` FOREIGN KEY (`id_tipologia`) REFERENCES `tipos_trabajos` (`id_tipologia`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cuadrilla_tipos_trabajo`
--

LOCK TABLES `cuadrilla_tipos_trabajo` WRITE;
/*!40000 ALTER TABLE `cuadrilla_tipos_trabajo` DISABLE KEYS */;
/*!40000 ALTER TABLE `cuadrilla_tipos_trabajo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cuadrillas`
--

DROP TABLE IF EXISTS `cuadrillas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cuadrillas` (
  `id_cuadrilla` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_cuadrilla` varchar(100) NOT NULL,
  `tipo_especialidad` varchar(50) DEFAULT NULL,
  `id_vehiculo_asignado` int(11) DEFAULT NULL,
  `id_celular_asignado` varchar(50) DEFAULT NULL,
  `zona_asignada` varchar(100) DEFAULT NULL,
  `url_grupo_whatsapp` varchar(255) DEFAULT NULL,
  `estado_operativo` enum('Activa','Mantenimiento','Baja') DEFAULT 'Activa',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `color_hex` varchar(7) DEFAULT '#2196F3',
  PRIMARY KEY (`id_cuadrilla`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cuadrillas`
--

LOCK TABLES `cuadrillas` WRITE;
/*!40000 ALTER TABLE `cuadrillas` DISABLE KEYS */;
INSERT INTO `cuadrillas` VALUES (1,'Norte','Veredas',1,NULL,NULL,NULL,'Activa','2026-01-29 15:20:39','#ec5f13');
INSERT INTO `cuadrillas` VALUES (2,'Sur',NULL,2,NULL,NULL,NULL,'Activa','2026-01-29 15:20:39','#37bdd7');
/*!40000 ALTER TABLE `cuadrillas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `herramientas`
--

DROP TABLE IF EXISTS `herramientas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `herramientas` (
  `id_herramienta` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `numero_serie` varchar(50) DEFAULT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `modelo` varchar(50) DEFAULT NULL,
  `precio_reposicion` decimal(10,2) DEFAULT 0.00,
  `id_proveedor` int(11) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `estado` enum('Disponible','Asignada','Reparación','Baja') DEFAULT 'Disponible',
  `id_cuadrilla_asignada` int(11) DEFAULT NULL,
  `id_personal_asignado` int(11) DEFAULT NULL,
  `fecha_compra` date DEFAULT NULL,
  `fecha_calibracion` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_asignacion` date DEFAULT NULL,
  PRIMARY KEY (`id_herramienta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `herramientas`
--

LOCK TABLES `herramientas` WRITE;
/*!40000 ALTER TABLE `herramientas` DISABLE KEYS */;
/*!40000 ALTER TABLE `herramientas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `herramientas_movimientos`
--

DROP TABLE IF EXISTS `herramientas_movimientos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `herramientas_movimientos` (
  `id_movimiento` int(11) NOT NULL AUTO_INCREMENT,
  `id_herramienta` int(11) NOT NULL,
  `tipo_movimiento` enum('Compra','Asignacion','Devolucion','Reparacion','Baja','Sancion','Reposicion') NOT NULL,
  `id_cuadrilla` int(11) DEFAULT NULL,
  `id_personal` int(11) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `monto` decimal(10,2) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_movimiento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `herramientas_movimientos`
--

LOCK TABLES `herramientas_movimientos` WRITE;
/*!40000 ALTER TABLE `herramientas_movimientos` DISABLE KEYS */;
/*!40000 ALTER TABLE `herramientas_movimientos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `herramientas_sanciones`
--

DROP TABLE IF EXISTS `herramientas_sanciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `herramientas_sanciones` (
  `id_sancion` int(11) NOT NULL AUTO_INCREMENT,
  `id_herramienta` int(11) NOT NULL,
  `id_personal` int(11) NOT NULL,
  `id_cuadrilla` int(11) DEFAULT NULL,
  `tipo_sancion` enum('Perdida','Rotura','Mal Uso') NOT NULL,
  `descripcion` text NOT NULL,
  `monto_descuento` decimal(10,2) DEFAULT 0.00,
  `estado` enum('Pendiente','Aplicada','Anulada') DEFAULT 'Pendiente',
  `fecha_incidente` date NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_sancion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `herramientas_sanciones`
--

LOCK TABLES `herramientas_sanciones` WRITE;
/*!40000 ALTER TABLE `herramientas_sanciones` DISABLE KEYS */;
/*!40000 ALTER TABLE `herramientas_sanciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kits_herramientas`
--

DROP TABLE IF EXISTS `kits_herramientas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kits_herramientas` (
  `id_kit` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_kit` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_entrega` date DEFAULT NULL,
  `id_personal_asignado` int(11) DEFAULT NULL,
  `estado` enum('Completo','Incompleto','Perdido') DEFAULT 'Completo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_kit`),
  UNIQUE KEY `codigo_kit` (`codigo_kit`),
  KEY `id_personal_asignado` (`id_personal_asignado`),
  CONSTRAINT `kits_herramientas_ibfk_1` FOREIGN KEY (`id_personal_asignado`) REFERENCES `personal` (`id_personal`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kits_herramientas`
--

LOCK TABLES `kits_herramientas` WRITE;
/*!40000 ALTER TABLE `kits_herramientas` DISABLE KEYS */;
/*!40000 ALTER TABLE `kits_herramientas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maestro_materiales`
--

DROP TABLE IF EXISTS `maestro_materiales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maestro_materiales` (
  `id_material` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `unidad_medida` varchar(20) DEFAULT NULL,
  `punto_pedido` decimal(10,2) DEFAULT NULL,
  `id_contacto_primario` int(11) DEFAULT NULL,
  `costo_primario` decimal(10,2) DEFAULT NULL,
  `id_contacto_secundario` int(11) DEFAULT NULL,
  `costo_secundario` decimal(10,2) DEFAULT NULL,
  `fecha_ultima_cotizacion` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_material`),
  KEY `id_contacto_primario` (`id_contacto_primario`),
  KEY `id_contacto_secundario` (`id_contacto_secundario`),
  CONSTRAINT `maestro_materiales_ibfk_1` FOREIGN KEY (`id_contacto_primario`) REFERENCES `proveedores_contactos` (`id_contacto`) ON DELETE SET NULL,
  CONSTRAINT `maestro_materiales_ibfk_2` FOREIGN KEY (`id_contacto_secundario`) REFERENCES `proveedores_contactos` (`id_contacto`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maestro_materiales`
--

LOCK TABLES `maestro_materiales` WRITE;
/*!40000 ALTER TABLE `maestro_materiales` DISABLE KEYS */;
INSERT INTO `maestro_materiales` VALUES (1,'MAT-0001','arena','','M3',50.00,NULL,30.00,NULL,NULL,NULL,'2026-01-29 15:38:07');
INSERT INTO `maestro_materiales` VALUES (2,NULL,'Tierra','jsalfjhasfiqhwf','M3',10.00,1,5000.00,1,3000.00,'2026-01-30','2026-01-30 00:30:07');
/*!40000 ALTER TABLE `maestro_materiales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movimientos`
--

DROP TABLE IF EXISTS `movimientos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movimientos` (
  `id_movimiento` int(11) NOT NULL AUTO_INCREMENT,
  `nro_documento` varchar(50) DEFAULT NULL,
  `tipo_movimiento` enum('Compra_Material','Recepcion_ASSA_Oficina','Entrega_Oficina_Cuadrilla','Consumo_Cuadrilla_Obra','Devolucion_ASSA','Devolucion_Compra') NOT NULL,
  `id_material` int(11) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `id_cuadrilla` int(11) DEFAULT NULL,
  `id_odt` int(11) DEFAULT NULL,
  `id_proveedor` int(11) DEFAULT NULL,
  `fecha_hora` datetime DEFAULT current_timestamp(),
  `usuario_despacho` int(11) DEFAULT NULL,
  `usuario_recepcion` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_movimiento`),
  KEY `id_material` (`id_material`),
  KEY `id_cuadrilla` (`id_cuadrilla`),
  KEY `id_odt` (`id_odt`),
  CONSTRAINT `movimientos_ibfk_1` FOREIGN KEY (`id_material`) REFERENCES `maestro_materiales` (`id_material`) ON DELETE CASCADE,
  CONSTRAINT `movimientos_ibfk_2` FOREIGN KEY (`id_cuadrilla`) REFERENCES `cuadrillas` (`id_cuadrilla`) ON DELETE SET NULL,
  CONSTRAINT `movimientos_ibfk_3` FOREIGN KEY (`id_odt`) REFERENCES `odt_maestro` (`id_odt`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimientos`
--

LOCK TABLES `movimientos` WRITE;
/*!40000 ALTER TABLE `movimientos` DISABLE KEYS */;
INSERT INTO `movimientos` VALUES (1,'1','Recepcion_ASSA_Oficina',1,0.01,NULL,NULL,NULL,'2026-01-29 13:06:04',NULL,NULL,'2026-01-29 16:06:04');
INSERT INTO `movimientos` VALUES (2,'1','Entrega_Oficina_Cuadrilla',1,0.01,1,NULL,NULL,'2026-01-29 13:06:39',NULL,NULL,'2026-01-29 16:06:39');
INSERT INTO `movimientos` VALUES (3,'1','',1,0.02,NULL,NULL,1,'2026-01-29 17:36:16',0,'','2026-01-29 16:36:16');
INSERT INTO `movimientos` VALUES (4,'1','',1,0.05,NULL,NULL,1,'2026-01-29 17:36:16',0,'','2026-01-29 16:36:16');
INSERT INTO `movimientos` VALUES (6,'','Compra_Material',1,25.00,NULL,NULL,1,'2026-01-30 01:27:26',0,'','2026-01-30 00:27:26');
INSERT INTO `movimientos` VALUES (7,'FAST-TRANSFER','Entrega_Oficina_Cuadrilla',1,0.05,1,NULL,NULL,'2026-01-30 13:00:43',0,'','2026-01-30 12:00:43');
INSERT INTO `movimientos` VALUES (8,'FAST-TRANSFER','Entrega_Oficina_Cuadrilla',1,15.00,1,NULL,NULL,'2026-01-30 13:02:39',0,'','2026-01-30 12:02:39');
INSERT INTO `movimientos` VALUES (9,'FAST-TRANSFER','Entrega_Oficina_Cuadrilla',1,5.00,1,NULL,NULL,'2026-01-30 14:25:25',0,'','2026-01-30 13:25:25');
INSERT INTO `movimientos` VALUES (10,'FAST-TRANSFER','Entrega_Oficina_Cuadrilla',1,5.00,2,NULL,NULL,'2026-01-30 19:15:17',0,'','2026-01-30 18:15:17');
INSERT INTO `movimientos` VALUES (12,'FAST-TRANSFER','Entrega_Oficina_Cuadrilla',1,0.01,1,NULL,NULL,'2026-02-02 12:51:17',0,'','2026-02-02 11:51:17');
INSERT INTO `movimientos` VALUES (13,'47652562352','Compra_Material',1,785.00,NULL,NULL,1,'2026-02-02 18:32:36',0,'','2026-02-02 17:32:36');
INSERT INTO `movimientos` VALUES (14,'FAST-TRANSFER','Entrega_Oficina_Cuadrilla',1,15.00,2,NULL,NULL,'2026-02-02 18:34:11',0,'','2026-02-02 17:34:11');
INSERT INTO `movimientos` VALUES (15,'FAST-TRANSFER','Entrega_Oficina_Cuadrilla',1,35.00,1,NULL,NULL,'2026-02-02 18:34:37',0,'','2026-02-02 17:34:37');
INSERT INTO `movimientos` VALUES (16,'FAST-TRANSFER','Entrega_Oficina_Cuadrilla',1,20.00,2,NULL,NULL,'2026-02-02 20:12:42',0,'','2026-02-02 19:12:42');
INSERT INTO `movimientos` VALUES (17,'FAST-TRANSFER','Entrega_Oficina_Cuadrilla',1,25.00,1,NULL,NULL,'2026-02-03 16:01:04',0,'','2026-02-03 15:01:04');
INSERT INTO `movimientos` VALUES (18,'FAST-TRANSFER','Entrega_Oficina_Cuadrilla',1,73.00,1,NULL,NULL,'2026-02-03 18:38:12',0,'','2026-02-03 17:38:12');
INSERT INTO `movimientos` VALUES (19,'FAST-TRANSFER','Entrega_Oficina_Cuadrilla',1,15.00,1,NULL,NULL,'2026-02-04 13:48:54',0,'','2026-02-04 12:48:54');
INSERT INTO `movimientos` VALUES (20,'FAST-TRANSFER','Entrega_Oficina_Cuadrilla',1,0.02,1,NULL,NULL,'2026-02-05 17:18:16',0,'','2026-02-05 16:18:16');
INSERT INTO `movimientos` VALUES (21,'','Recepcion_ASSA_Oficina',1,1.00,NULL,NULL,NULL,'2026-02-09 15:29:15',0,'','2026-02-09 14:29:15');
INSERT INTO `movimientos` VALUES (22,'','Recepcion_ASSA_Oficina',2,1.00,NULL,NULL,NULL,'2026-02-09 15:29:15',0,'','2026-02-09 14:29:15');
INSERT INTO `movimientos` VALUES (23,'FAST-TRANSFER','Entrega_Oficina_Cuadrilla',2,1.00,1,NULL,NULL,'2026-02-09 15:29:38',0,'','2026-02-09 14:29:38');
INSERT INTO `movimientos` VALUES (24,NULL,'Consumo_Cuadrilla_Obra',1,15.00,2,4,NULL,'2026-02-10 08:24:30',2,NULL,'2026-02-10 11:24:30');
/*!40000 ALTER TABLE `movimientos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `odt_fotos`
--

DROP TABLE IF EXISTS `odt_fotos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `odt_fotos` (
  `id_foto` int(11) NOT NULL AUTO_INCREMENT,
  `id_odt` int(11) NOT NULL,
  `ruta_archivo` varchar(255) NOT NULL,
  `tipo_foto` varchar(50) DEFAULT 'Avance',
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp(),
  `subido_por` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_foto`),
  KEY `id_odt` (`id_odt`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `odt_fotos`
--

LOCK TABLES `odt_fotos` WRITE;
/*!40000 ALTER TABLE `odt_fotos` DISABLE KEYS */;
INSERT INTO `odt_fotos` VALUES (1,4,'uploads/odt_photos/1770646600_6989ec4811f44_debug_after_copied_text.png','ODT','2026-02-09 14:16:40',NULL);
INSERT INTO `odt_fotos` VALUES (2,5,'uploads/odt_photos/1770667483_698a3ddb1fdb9_17706674318515010914008699626494.jpg','ODT','2026-02-09 20:04:43',NULL);
INSERT INTO `odt_fotos` VALUES (3,5,'uploads/odt_photos/1770667483_698a3ddb23f5b_17706674489856493261609972243136.jpg','TRABAJO','2026-02-09 20:04:43',NULL);
INSERT INTO `odt_fotos` VALUES (4,5,'uploads/odt_photos/1770667483_698a3ddb247e7_17706674571973674315182162039797.jpg','TRABAJO','2026-02-09 20:04:43',NULL);
/*!40000 ALTER TABLE `odt_fotos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `odt_items`
--

DROP TABLE IF EXISTS `odt_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `odt_items` (
  `id_item` int(11) NOT NULL AUTO_INCREMENT,
  `id_odt` int(11) NOT NULL,
  `descripcion_item` varchar(200) NOT NULL,
  `seleccionado` tinyint(1) DEFAULT 1,
  `medida_1` decimal(10,2) DEFAULT NULL,
  `medida_2` decimal(10,2) DEFAULT NULL,
  `medida_3` decimal(10,2) DEFAULT NULL,
  `unidad` varchar(20) DEFAULT 'm',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_item`),
  KEY `id_odt` (`id_odt`),
  CONSTRAINT `odt_items_ibfk_1` FOREIGN KEY (`id_odt`) REFERENCES `odt_maestro` (`id_odt`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `odt_items`
--

LOCK TABLES `odt_items` WRITE;
/*!40000 ALTER TABLE `odt_items` DISABLE KEYS */;
INSERT INTO `odt_items` VALUES (1,4,'fesfesfa',1,NULL,NULL,NULL,'m','2026-02-10 11:24:30');
INSERT INTO `odt_items` VALUES (2,4,'daSasQWD',1,NULL,NULL,NULL,'m','2026-02-10 11:24:30');
/*!40000 ALTER TABLE `odt_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `odt_maestro`
--

DROP TABLE IF EXISTS `odt_maestro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `odt_maestro` (
  `id_odt` int(11) NOT NULL AUTO_INCREMENT,
  `nro_odt_assa` varchar(50) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `id_tipologia` int(11) DEFAULT NULL,
  `prioridad` enum('Normal','Urgente') DEFAULT 'Normal',
  `estado_gestion` enum('Sin Programar','Programaci¾n Solicitada','Programado','Ejecuci¾n','Ejecutado','Precertificada','Finalizado','Re-programar','Aprobado por inspector','Retrabajo') NOT NULL DEFAULT 'Sin Programar',
  `fecha_inicio_plazo` date DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `avance` text DEFAULT NULL,
  `inspector` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_odt`),
  UNIQUE KEY `nro_odt_assa` (`nro_odt_assa`),
  KEY `id_tipologia` (`id_tipologia`),
  CONSTRAINT `odt_maestro_ibfk_1` FOREIGN KEY (`id_tipologia`) REFERENCES `tipologias` (`id_tipologia`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `odt_maestro`
--

LOCK TABLES `odt_maestro` WRITE;
/*!40000 ALTER TABLE `odt_maestro` DISABLE KEYS */;
INSERT INTO `odt_maestro` VALUES (1,'ODT-9901','Av. Santa Fe 1234',NULL,'Normal','Ejecutado',NULL,'2026-03-06',NULL,NULL,'2026-01-29 15:20:39');
INSERT INTO `odt_maestro` VALUES (2,'ODT-9902','Calle Lavalle 550',NULL,'Urgente','Programado',NULL,'2026-03-07',NULL,NULL,'2026-01-29 15:20:39');
INSERT INTO `odt_maestro` VALUES (4,'4357','Cochabamba',6,'Urgente','Programado','2026-02-05','2026-02-25','','V DSVSZDV','2026-02-05 19:47:11');
INSERT INTO `odt_maestro` VALUES (5,'555','Ryhtt',NULL,'Normal','Sin Programar','2026-02-09',NULL,'','','2026-02-09 20:04:43');
/*!40000 ALTER TABLE `odt_maestro` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `odt_materiales`
--

DROP TABLE IF EXISTS `odt_materiales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `odt_materiales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_odt` int(11) NOT NULL,
  `id_material` int(11) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_odt` (`id_odt`),
  KEY `id_material` (`id_material`),
  CONSTRAINT `odt_materiales_ibfk_1` FOREIGN KEY (`id_odt`) REFERENCES `odt_maestro` (`id_odt`) ON DELETE CASCADE,
  CONSTRAINT `odt_materiales_ibfk_2` FOREIGN KEY (`id_material`) REFERENCES `maestro_materiales` (`id_material`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `odt_materiales`
--

LOCK TABLES `odt_materiales` WRITE;
/*!40000 ALTER TABLE `odt_materiales` DISABLE KEYS */;
INSERT INTO `odt_materiales` VALUES (1,4,1,7.00,'2026-02-10 11:24:30');
INSERT INTO `odt_materiales` VALUES (2,4,1,8.00,'2026-02-10 11:24:30');
/*!40000 ALTER TABLE `odt_materiales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `partes_diarios`
--

DROP TABLE IF EXISTS `partes_diarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `partes_diarios` (
  `id_parte` int(11) NOT NULL AUTO_INCREMENT,
  `id_odt` int(11) NOT NULL,
  `id_programacion` int(11) DEFAULT NULL,
  `id_cuadrilla` int(11) NOT NULL,
  `fecha_ejecucion` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `tiempo_ejecucion_real` int(11) GENERATED ALWAYS AS (timestampdiff(MINUTE,concat(`fecha_ejecucion`,' ',`hora_inicio`),concat(`fecha_ejecucion`,' ',`hora_fin`))) STORED COMMENT 'Tiempo en minutos calculado autom├íticamente',
  `id_tipologia` int(11) NOT NULL COMMENT 'Tipo de trabajo realizado',
  `largo` decimal(10,2) DEFAULT 0.00 COMMENT 'Metros',
  `ancho` decimal(10,2) DEFAULT 0.00 COMMENT 'Metros',
  `profundidad` decimal(10,2) DEFAULT 0.00 COMMENT 'Metros (para M3)',
  `volumen_calculado` decimal(10,3) GENERATED ALWAYS AS (case when `profundidad` > 0 then `largo` * `ancho` * `profundidad` else `largo` * `ancho` end) STORED COMMENT 'M3 o M2 seg├║n profundidad',
  `unidad_volumen` varchar(5) GENERATED ALWAYS AS (case when `profundidad` > 0 then 'M3' else 'M2' end) VIRTUAL,
  `id_vehiculo` int(11) DEFAULT NULL,
  `km_inicial` int(11) DEFAULT NULL,
  `km_final` int(11) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `estado` enum('Borrador','Enviado','Aprobado','Rechazado') DEFAULT 'Borrador',
  `usuario_creacion` int(11) DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_parte`),
  KEY `fk_parte_tipologia` (`id_tipologia`),
  KEY `fk_parte_vehiculo` (`id_vehiculo`),
  KEY `idx_fecha_ejecucion` (`fecha_ejecucion`),
  KEY `idx_cuadrilla` (`id_cuadrilla`),
  KEY `idx_odt` (`id_odt`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_parte_cuadrilla` FOREIGN KEY (`id_cuadrilla`) REFERENCES `cuadrillas` (`id_cuadrilla`),
  CONSTRAINT `fk_parte_odt` FOREIGN KEY (`id_odt`) REFERENCES `odt_maestro` (`id_odt`),
  CONSTRAINT `fk_parte_tipologia` FOREIGN KEY (`id_tipologia`) REFERENCES `tipologias` (`id_tipologia`),
  CONSTRAINT `fk_parte_vehiculo` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculos` (`id_vehiculo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partes_diarios`
--

LOCK TABLES `partes_diarios` WRITE;
/*!40000 ALTER TABLE `partes_diarios` DISABLE KEYS */;
/*!40000 ALTER TABLE `partes_diarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `partes_fotos`
--

DROP TABLE IF EXISTS `partes_fotos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `partes_fotos` (
  `id_foto` int(11) NOT NULL AUTO_INCREMENT,
  `id_parte` int(11) NOT NULL,
  `tipo_foto` enum('Inicio','Proceso','Fin') NOT NULL,
  `ruta_archivo` varchar(500) NOT NULL,
  `latitud` decimal(10,8) DEFAULT NULL COMMENT 'Coordenada GPS',
  `longitud` decimal(11,8) DEFAULT NULL COMMENT 'Coordenada GPS',
  `fecha_captura` datetime DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_foto`),
  UNIQUE KEY `uk_parte_tipo` (`id_parte`,`tipo_foto`),
  CONSTRAINT `fk_pf_parte` FOREIGN KEY (`id_parte`) REFERENCES `partes_diarios` (`id_parte`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partes_fotos`
--

LOCK TABLES `partes_fotos` WRITE;
/*!40000 ALTER TABLE `partes_fotos` DISABLE KEYS */;
/*!40000 ALTER TABLE `partes_fotos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `partes_materiales`
--

DROP TABLE IF EXISTS `partes_materiales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `partes_materiales` (
  `id_parte_material` int(11) NOT NULL AUTO_INCREMENT,
  `id_parte` int(11) NOT NULL,
  `id_material` int(11) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_parte_material`),
  UNIQUE KEY `uk_parte_material` (`id_parte`,`id_material`),
  KEY `fk_pm_material` (`id_material`),
  CONSTRAINT `fk_pm_material` FOREIGN KEY (`id_material`) REFERENCES `maestro_materiales` (`id_material`),
  CONSTRAINT `fk_pm_parte` FOREIGN KEY (`id_parte`) REFERENCES `partes_diarios` (`id_parte`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partes_materiales`
--

LOCK TABLES `partes_materiales` WRITE;
/*!40000 ALTER TABLE `partes_materiales` DISABLE KEYS */;
/*!40000 ALTER TABLE `partes_materiales` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER IF NOT EXISTS tr_descontar_stock_cuadrilla
AFTER INSERT ON partes_materiales
FOR EACH ROW
BEGIN
    DECLARE v_id_cuadrilla INT;
    
    
    SELECT id_cuadrilla INTO v_id_cuadrilla 
    FROM partes_diarios 
    WHERE id_parte = NEW.id_parte;
    
    
    UPDATE stock_cuadrilla 
    SET cantidad = cantidad - NEW.cantidad,
        updated_at = NOW()
    WHERE id_cuadrilla = v_id_cuadrilla 
      AND id_material = NEW.id_material;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER IF NOT EXISTS tr_revertir_stock_cuadrilla
AFTER DELETE ON partes_materiales
FOR EACH ROW
BEGIN
    DECLARE v_id_cuadrilla INT;
    
    
    SELECT id_cuadrilla INTO v_id_cuadrilla 
    FROM partes_diarios 
    WHERE id_parte = OLD.id_parte;
    
    
    IF v_id_cuadrilla IS NOT NULL THEN
        UPDATE stock_cuadrilla 
        SET cantidad = cantidad + OLD.cantidad,
            updated_at = NOW()
        WHERE id_cuadrilla = v_id_cuadrilla 
          AND id_material = OLD.id_material;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `partes_personal`
--

DROP TABLE IF EXISTS `partes_personal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `partes_personal` (
  `id_parte_personal` int(11) NOT NULL AUTO_INCREMENT,
  `id_parte` int(11) NOT NULL,
  `id_personal` int(11) NOT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_parte_personal`),
  UNIQUE KEY `uk_parte_personal` (`id_parte`,`id_personal`),
  KEY `fk_pp_personal` (`id_personal`),
  CONSTRAINT `fk_pp_parte` FOREIGN KEY (`id_parte`) REFERENCES `partes_diarios` (`id_parte`) ON DELETE CASCADE,
  CONSTRAINT `fk_pp_personal` FOREIGN KEY (`id_personal`) REFERENCES `personal` (`id_personal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partes_personal`
--

LOCK TABLES `partes_personal` WRITE;
/*!40000 ALTER TABLE `partes_personal` DISABLE KEYS */;
/*!40000 ALTER TABLE `partes_personal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal`
--

DROP TABLE IF EXISTS `personal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal` (
  `id_personal` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_apellido` varchar(100) NOT NULL,
  `dni` varchar(20) DEFAULT NULL,
  `rol` enum('Oficial','Ayudante','Administrativo','Supervisor','Chofer') DEFAULT 'Ayudante',
  `id_cuadrilla` int(11) DEFAULT NULL,
  `id_kit_herramientas` int(11) DEFAULT NULL,
  `telefono_personal` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `seguro_art` varchar(100) DEFAULT NULL,
  `talle_ropa` varchar(20) DEFAULT NULL,
  `talle_calzado` varchar(10) DEFAULT NULL,
  `fecha_ultima_entrega_epp` date DEFAULT NULL,
  `vencimiento_carnet_conducir` date DEFAULT NULL,
  `grupo_sanguineo` varchar(10) DEFAULT NULL,
  `alergias_condiciones` text DEFAULT NULL,
  `numero_emergencia` varchar(50) DEFAULT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  `cbu_alias` varchar(50) DEFAULT NULL,
  `domicilio` varchar(255) DEFAULT NULL,
  `link_legajo_digital` varchar(255) DEFAULT NULL,
  `tiene_carnet` tinyint(1) DEFAULT 0,
  `tipo_carnet` varchar(50) DEFAULT NULL,
  `foto_carnet` varchar(255) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `cuil` varchar(20) DEFAULT NULL,
  `estado_civil` varchar(50) DEFAULT NULL,
  `contacto_emergencia_nombre` varchar(100) DEFAULT NULL,
  `contacto_emergencia_parentesco` varchar(50) DEFAULT NULL,
  `personas_a_cargo` text DEFAULT NULL,
  `foto_usuario` varchar(255) DEFAULT NULL,
  `talle_camisa` varchar(10) DEFAULT NULL,
  `talle_pantalon` varchar(10) DEFAULT NULL,
  `talle_remera` varchar(10) DEFAULT NULL,
  `planilla_epp` varchar(255) DEFAULT NULL,
  `tareas_desempenadas` text DEFAULT NULL,
  `obra_social` varchar(100) DEFAULT NULL,
  `obra_social_telefono` varchar(50) DEFAULT NULL,
  `obra_social_lugar_atencion` varchar(200) DEFAULT NULL,
  `estado_documentacion` enum('Completo','Pendiente','Incompleto') DEFAULT 'Incompleto',
  `motivo_pendiente` text DEFAULT NULL,
  `responsable_carga_id` int(11) DEFAULT NULL,
  `documento_firmado` varchar(255) DEFAULT NULL,
  `fecha_firma_hys` date DEFAULT NULL,
  `fecha_examen_preocupacional` date DEFAULT NULL,
  `empresa_examen_preocupacional` varchar(100) DEFAULT NULL,
  `documento_preocupacional` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_personal`),
  UNIQUE KEY `dni` (`dni`),
  KEY `id_cuadrilla` (`id_cuadrilla`),
  CONSTRAINT `personal_ibfk_1` FOREIGN KEY (`id_cuadrilla`) REFERENCES `cuadrillas` (`id_cuadrilla`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal`
--

LOCK TABLES `personal` WRITE;
/*!40000 ALTER TABLE `personal` DISABLE KEYS */;
INSERT INTO `personal` VALUES (1,'nahuel piva','44526179','Administrativo',NULL,NULL,'3424325417','2026-02-02 16:04:03','65165162','M','42','2026-02-02','2026-04-21','A+','','3425228510','2026-02-02','nahuelpiva','hernandarias 1673','',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Incompleto',NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `personal` VALUES (2,'pablo','3333333','Chofer',1,NULL,'','2026-02-05 18:04:30','','','',NULL,NULL,'','','',NULL,'','','',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Incompleto',NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `personal` VALUES (3,'nahuel','44444444','Chofer',2,NULL,'','2026-02-05 18:04:53','','','',NULL,NULL,'','','',NULL,'','','',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Incompleto',NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `personal` VALUES (4,'Agustina bongiovanni','36523186','Administrativo',NULL,NULL,'','2026-02-06 21:06:11','',NULL,'',NULL,NULL,'','','',NULL,'','','',0,NULL,NULL,NULL,'','','','','',NULL,'','','',NULL,'','','','','Incompleto','',NULL,NULL,NULL,NULL,'',NULL);
/*!40000 ALTER TABLE `personal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `programacion_semanal`
--

DROP TABLE IF EXISTS `programacion_semanal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `programacion_semanal` (
  `id_programacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_odt` int(11) NOT NULL,
  `id_cuadrilla` int(11) NOT NULL,
  `fecha_programada` date DEFAULT NULL,
  `turno` enum('Mañana','Tarde') NOT NULL DEFAULT 'Mañana',
  `estado_programacion` enum('Tildado_Admin','Confirmado_ASSA') DEFAULT 'Tildado_Admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_programacion`),
  KEY `id_odt` (`id_odt`),
  KEY `id_cuadrilla` (`id_cuadrilla`),
  CONSTRAINT `programacion_semanal_ibfk_1` FOREIGN KEY (`id_odt`) REFERENCES `odt_maestro` (`id_odt`) ON DELETE CASCADE,
  CONSTRAINT `programacion_semanal_ibfk_2` FOREIGN KEY (`id_cuadrilla`) REFERENCES `cuadrillas` (`id_cuadrilla`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `programacion_semanal`
--

LOCK TABLES `programacion_semanal` WRITE;
/*!40000 ALTER TABLE `programacion_semanal` DISABLE KEYS */;
INSERT INTO `programacion_semanal` VALUES (1,2,1,'2026-02-05','Mañana','Tildado_Admin','2026-02-05 19:28:12');
INSERT INTO `programacion_semanal` VALUES (2,1,1,'2026-02-05','Mañana','Tildado_Admin','2026-02-05 19:28:12');
INSERT INTO `programacion_semanal` VALUES (3,1,2,'2026-02-05','Mañana','Tildado_Admin','2026-02-05 19:33:27');
INSERT INTO `programacion_semanal` VALUES (4,4,1,'2026-02-05','Mañana','Tildado_Admin','2026-02-05 21:06:41');
INSERT INTO `programacion_semanal` VALUES (5,4,1,'2026-02-06','Mañana','Tildado_Admin','2026-02-06 04:08:55');
INSERT INTO `programacion_semanal` VALUES (6,2,1,'2026-02-06','Mañana','Tildado_Admin','2026-02-06 04:08:55');
INSERT INTO `programacion_semanal` VALUES (7,1,1,'2026-02-06','Mañana','Tildado_Admin','2026-02-06 04:08:55');
INSERT INTO `programacion_semanal` VALUES (8,4,2,'2026-02-06','Mañana','Tildado_Admin','2026-02-06 04:19:02');
INSERT INTO `programacion_semanal` VALUES (9,2,2,'2026-02-06','Mañana','Tildado_Admin','2026-02-06 04:19:02');
INSERT INTO `programacion_semanal` VALUES (10,1,2,'2026-02-06','Mañana','Tildado_Admin','2026-02-06 04:19:02');
INSERT INTO `programacion_semanal` VALUES (11,4,1,'2026-02-06','Mañana','Tildado_Admin','2026-02-06 04:19:14');
INSERT INTO `programacion_semanal` VALUES (12,2,1,'2026-02-06','Mañana','Tildado_Admin','2026-02-06 04:19:14');
INSERT INTO `programacion_semanal` VALUES (13,1,1,'2026-02-06','Mañana','Tildado_Admin','2026-02-06 04:19:14');
INSERT INTO `programacion_semanal` VALUES (14,4,2,'2026-02-06','Mañana','Tildado_Admin','2026-02-06 11:54:34');
INSERT INTO `programacion_semanal` VALUES (15,2,2,'2026-02-06','Mañana','Tildado_Admin','2026-02-06 11:54:34');
INSERT INTO `programacion_semanal` VALUES (16,1,2,'2026-02-06','Mañana','Tildado_Admin','2026-02-06 11:54:34');
INSERT INTO `programacion_semanal` VALUES (17,2,1,'2026-02-08','Mañana','Tildado_Admin','2026-02-08 20:39:38');
INSERT INTO `programacion_semanal` VALUES (18,1,1,'2026-02-08','Mañana','Tildado_Admin','2026-02-08 20:39:38');
INSERT INTO `programacion_semanal` VALUES (19,4,2,'2026-02-08','Mañana','Tildado_Admin','2026-02-08 20:39:54');
INSERT INTO `programacion_semanal` VALUES (20,4,1,'2026-02-09','Mañana','Tildado_Admin','2026-02-09 14:11:52');
INSERT INTO `programacion_semanal` VALUES (21,4,1,'2026-02-09','Mañana','Tildado_Admin','2026-02-09 14:13:19');
INSERT INTO `programacion_semanal` VALUES (22,4,1,'2026-02-09','Mañana','Tildado_Admin','2026-02-09 14:44:43');
INSERT INTO `programacion_semanal` VALUES (23,1,1,'2026-02-09','Mañana','Tildado_Admin','2026-02-09 14:44:43');
INSERT INTO `programacion_semanal` VALUES (24,4,2,'2026-02-09','Mañana','Tildado_Admin','2026-02-09 14:52:37');
/*!40000 ALTER TABLE `programacion_semanal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proveedores`
--

DROP TABLE IF EXISTS `proveedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `proveedores` (
  `id_proveedor` int(11) NOT NULL AUTO_INCREMENT,
  `razon_social` varchar(100) NOT NULL,
  `cuit` varchar(20) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_proveedor`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proveedores`
--

LOCK TABLES `proveedores` WRITE;
/*!40000 ALTER TABLE `proveedores` DISABLE KEYS */;
INSERT INTO `proveedores` VALUES (1,'Santa','2033955032',NULL,'2026-01-29 15:51:26');
/*!40000 ALTER TABLE `proveedores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proveedores_contactos`
--

DROP TABLE IF EXISTS `proveedores_contactos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `proveedores_contactos` (
  `id_contacto` int(11) NOT NULL AUTO_INCREMENT,
  `id_proveedor` int(11) NOT NULL,
  `nombre_vendedor` varchar(100) DEFAULT NULL,
  `telefono_contacto` varchar(50) DEFAULT NULL,
  `email_vendedor` varchar(100) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_contacto`),
  KEY `id_proveedor` (`id_proveedor`),
  CONSTRAINT `proveedores_contactos_ibfk_1` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proveedores_contactos`
--

LOCK TABLES `proveedores_contactos` WRITE;
/*!40000 ALTER TABLE `proveedores_contactos` DISABLE KEYS */;
INSERT INTO `proveedores_contactos` VALUES (1,1,'Santa crack','3425191550',NULL,NULL,'2026-01-29 15:51:26');
/*!40000 ALTER TABLE `proveedores_contactos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `remitos`
--

DROP TABLE IF EXISTS `remitos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `remitos` (
  `id_remito` int(11) NOT NULL AUTO_INCREMENT,
  `numero_remito` varchar(20) NOT NULL,
  `fecha_emision` datetime DEFAULT current_timestamp(),
  `id_cuadrilla` int(11) DEFAULT NULL,
  `tipo_remito` enum('Entrega_Cuadrilla','Devolucion') DEFAULT 'Entrega_Cuadrilla',
  `observaciones` text DEFAULT NULL,
  `usuario_emision` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_remito`),
  UNIQUE KEY `numero_remito` (`numero_remito`),
  KEY `idx_remitos_numero` (`numero_remito`),
  KEY `idx_remitos_cuadrilla` (`id_cuadrilla`),
  CONSTRAINT `remitos_ibfk_1` FOREIGN KEY (`id_cuadrilla`) REFERENCES `cuadrillas` (`id_cuadrilla`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `remitos`
--

LOCK TABLES `remitos` WRITE;
/*!40000 ALTER TABLE `remitos` DISABLE KEYS */;
INSERT INTO `remitos` VALUES (1,'REM-20260130-0001','2026-01-30 09:00:43',1,'Entrega_Cuadrilla',NULL,'Sistema (Quick)','2026-01-30 12:00:43');
INSERT INTO `remitos` VALUES (2,'REM-20260130-0002','2026-01-30 09:02:39',1,'Entrega_Cuadrilla',NULL,'Sistema (Quick)','2026-01-30 12:02:39');
INSERT INTO `remitos` VALUES (3,'REM-20260130-0003','2026-01-30 10:25:25',1,'Entrega_Cuadrilla',NULL,'Sistema (Quick)','2026-01-30 13:25:25');
INSERT INTO `remitos` VALUES (4,'REM-20260130-0004','2026-01-30 15:15:17',2,'Entrega_Cuadrilla',NULL,'Sistema (Quick)','2026-01-30 18:15:17');
INSERT INTO `remitos` VALUES (5,'REM-20260202-0001','2026-02-02 08:51:17',1,'Entrega_Cuadrilla',NULL,'Sistema (Quick)','2026-02-02 11:51:17');
INSERT INTO `remitos` VALUES (6,'REM-20260202-0002','2026-02-02 14:34:12',2,'Entrega_Cuadrilla',NULL,'Sistema (Quick)','2026-02-02 17:34:12');
INSERT INTO `remitos` VALUES (7,'REM-20260202-0003','2026-02-02 14:34:37',1,'Entrega_Cuadrilla',NULL,'Sistema (Quick)','2026-02-02 17:34:37');
INSERT INTO `remitos` VALUES (8,'REM-20260202-0004','2026-02-02 16:12:42',2,'Entrega_Cuadrilla',NULL,'Sistema (Quick)','2026-02-02 19:12:42');
INSERT INTO `remitos` VALUES (9,'REM-20260203-0001','2026-02-03 12:01:04',1,'Entrega_Cuadrilla',NULL,'Sistema (Quick)','2026-02-03 15:01:04');
INSERT INTO `remitos` VALUES (10,'REM-20260203-0002','2026-02-03 14:38:12',1,'Entrega_Cuadrilla',NULL,'Sistema (Quick)','2026-02-03 17:38:12');
INSERT INTO `remitos` VALUES (11,'REM-20260204-0001','2026-02-04 09:48:54',1,'Entrega_Cuadrilla',NULL,'Sistema (Quick)','2026-02-04 12:48:54');
INSERT INTO `remitos` VALUES (12,'REM-20260205-0001','2026-02-05 13:18:16',1,'Entrega_Cuadrilla',NULL,'Sistema (Quick)','2026-02-05 16:18:16');
INSERT INTO `remitos` VALUES (13,'REM-20260209-0001','2026-02-09 11:29:38',1,'Entrega_Cuadrilla',NULL,'Sistema (Quick)','2026-02-09 14:29:38');
/*!40000 ALTER TABLE `remitos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `remitos_detalle`
--

DROP TABLE IF EXISTS `remitos_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `remitos_detalle` (
  `id_detalle` int(11) NOT NULL AUTO_INCREMENT,
  `id_remito` int(11) NOT NULL,
  `id_material` int(11) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `id_movimiento` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_detalle`),
  KEY `id_remito` (`id_remito`),
  KEY `id_material` (`id_material`),
  KEY `id_movimiento` (`id_movimiento`),
  CONSTRAINT `remitos_detalle_ibfk_1` FOREIGN KEY (`id_remito`) REFERENCES `remitos` (`id_remito`) ON DELETE CASCADE,
  CONSTRAINT `remitos_detalle_ibfk_2` FOREIGN KEY (`id_material`) REFERENCES `maestro_materiales` (`id_material`) ON DELETE CASCADE,
  CONSTRAINT `remitos_detalle_ibfk_3` FOREIGN KEY (`id_movimiento`) REFERENCES `movimientos` (`id_movimiento`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `remitos_detalle`
--

LOCK TABLES `remitos_detalle` WRITE;
/*!40000 ALTER TABLE `remitos_detalle` DISABLE KEYS */;
INSERT INTO `remitos_detalle` VALUES (1,1,1,0.05,7);
INSERT INTO `remitos_detalle` VALUES (2,2,1,15.00,8);
INSERT INTO `remitos_detalle` VALUES (3,3,1,5.00,9);
INSERT INTO `remitos_detalle` VALUES (4,4,1,5.00,10);
INSERT INTO `remitos_detalle` VALUES (5,5,1,0.01,12);
INSERT INTO `remitos_detalle` VALUES (6,6,1,15.00,14);
INSERT INTO `remitos_detalle` VALUES (7,7,1,35.00,15);
INSERT INTO `remitos_detalle` VALUES (8,8,1,20.00,16);
INSERT INTO `remitos_detalle` VALUES (9,9,1,25.00,17);
INSERT INTO `remitos_detalle` VALUES (10,10,1,73.00,18);
INSERT INTO `remitos_detalle` VALUES (11,11,1,15.00,19);
INSERT INTO `remitos_detalle` VALUES (12,12,1,0.02,20);
INSERT INTO `remitos_detalle` VALUES (13,13,2,1.00,23);
/*!40000 ALTER TABLE `remitos_detalle` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_cuadrilla`
--

DROP TABLE IF EXISTS `stock_cuadrilla`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_cuadrilla` (
  `id_stock_cuadrilla` int(11) NOT NULL AUTO_INCREMENT,
  `id_cuadrilla` int(11) NOT NULL,
  `id_material` int(11) NOT NULL,
  `cantidad` decimal(10,2) DEFAULT 0.00,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_stock_cuadrilla`),
  KEY `id_cuadrilla` (`id_cuadrilla`),
  KEY `id_material` (`id_material`),
  CONSTRAINT `stock_cuadrilla_ibfk_1` FOREIGN KEY (`id_cuadrilla`) REFERENCES `cuadrillas` (`id_cuadrilla`) ON DELETE CASCADE,
  CONSTRAINT `stock_cuadrilla_ibfk_2` FOREIGN KEY (`id_material`) REFERENCES `maestro_materiales` (`id_material`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_cuadrilla`
--

LOCK TABLES `stock_cuadrilla` WRITE;
/*!40000 ALTER TABLE `stock_cuadrilla` DISABLE KEYS */;
INSERT INTO `stock_cuadrilla` VALUES (1,1,1,168.09,'2026-02-05 16:18:16');
INSERT INTO `stock_cuadrilla` VALUES (2,2,1,25.00,'2026-02-10 11:24:30');
INSERT INTO `stock_cuadrilla` VALUES (3,1,2,1.00,'2026-02-09 14:29:38');
/*!40000 ALTER TABLE `stock_cuadrilla` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_saldos`
--

DROP TABLE IF EXISTS `stock_saldos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_saldos` (
  `id_saldo` int(11) NOT NULL AUTO_INCREMENT,
  `id_material` int(11) NOT NULL,
  `stock_oficina` decimal(10,2) DEFAULT 0.00,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_saldo`),
  KEY `id_material` (`id_material`),
  CONSTRAINT `stock_saldos_ibfk_1` FOREIGN KEY (`id_material`) REFERENCES `maestro_materiales` (`id_material`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_saldos`
--

LOCK TABLES `stock_saldos` WRITE;
/*!40000 ALTER TABLE `stock_saldos` DISABLE KEYS */;
INSERT INTO `stock_saldos` VALUES (1,1,602.99,'2026-02-09 14:29:15');
INSERT INTO `stock_saldos` VALUES (2,2,0.00,'2026-02-09 14:29:38');
/*!40000 ALTER TABLE `stock_saldos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tareas`
--

DROP TABLE IF EXISTS `tareas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tareas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT 1,
  `titulo` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_limite` date DEFAULT NULL,
  `prioridad` enum('Alta','Media','Baja') DEFAULT 'Media',
  `estado` enum('Pendiente','En progreso','Completada','Cancelada') DEFAULT 'Pendiente',
  `categoria` varchar(50) DEFAULT 'Otros',
  `recordatorio_especial` tinyint(1) DEFAULT 0,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `responsable` varchar(100) DEFAULT 'Cache',
  `fecha_completada` datetime DEFAULT NULL,
  `id_usuario_creador` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tareas`
--

LOCK TABLES `tareas` WRITE;
/*!40000 ALTER TABLE `tareas` DISABLE KEYS */;
INSERT INTO `tareas` VALUES (1,2,'cxgasg','zsdgesd','0000-00-00','Baja','Pendiente','Otros',0,'2026-02-09 09:39:58','Cache',NULL,2);
INSERT INTO `tareas` VALUES (2,2,'cxgasg','bsrtbs','2026-02-09','Baja','Pendiente','Otros',0,'2026-02-09 09:40:13','Cache',NULL,2);
/*!40000 ALTER TABLE `tareas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tareas_definicion`
--

DROP TABLE IF EXISTS `tareas_definicion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tareas_definicion` (
  `id_definicion` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `importancia` enum('Alta','Baja') DEFAULT 'Baja',
  `tipo_recurrencia` enum('Unica','Diaria','Semanal','Mensual') DEFAULT 'Unica',
  `parametro_recurrencia` varchar(50) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `ultimo_generado` date DEFAULT NULL,
  `id_creador` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_definicion`),
  KEY `id_creador` (`id_creador`),
  CONSTRAINT `tareas_definicion_ibfk_1` FOREIGN KEY (`id_creador`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tareas_definicion`
--

LOCK TABLES `tareas_definicion` WRITE;
/*!40000 ALTER TABLE `tareas_definicion` DISABLE KEYS */;
INSERT INTO `tareas_definicion` VALUES (1,'Solicitud de materiales ASSA','','Alta','Semanal','1','2026-02-02',NULL,'2026-02-16',2,'2026-02-02 15:03:42');
INSERT INTO `tareas_definicion` VALUES (2,'aaa','aaaa','Alta','Semanal','1','2026-02-02',NULL,'2026-02-16',2,'2026-02-02 15:11:52');
INSERT INTO `tareas_definicion` VALUES (3,'buscar a mare','asfagsdgasdg','Alta','Semanal','1','2026-02-02',NULL,'2026-02-16',2,'2026-02-02 15:38:16');
INSERT INTO `tareas_definicion` VALUES (4,'buscar a mare','','Alta','Unica',NULL,'2026-02-03','2026-02-03','2026-02-03',2,'2026-02-02 16:10:07');
INSERT INTO `tareas_definicion` VALUES (5,'buscar a mare','','Baja','Unica',NULL,'2026-02-03','2026-02-03','2026-02-03',2,'2026-02-02 16:10:21');
INSERT INTO `tareas_definicion` VALUES (6,'buscar a mare','','Alta','Diaria',NULL,'2026-02-02',NULL,'2026-02-17',2,'2026-02-02 17:25:39');
INSERT INTO `tareas_definicion` VALUES (7,'Solicitud de materiales ASSA','','Baja','Diaria',NULL,'2026-02-02',NULL,'2026-02-17',2,'2026-02-02 17:26:39');
INSERT INTO `tareas_definicion` VALUES (8,'test','','Baja','Unica',NULL,'2026-02-04','2026-02-04','2026-02-04',2,'2026-02-04 12:46:43');
/*!40000 ALTER TABLE `tareas_definicion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tareas_instancia`
--

DROP TABLE IF EXISTS `tareas_instancia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tareas_instancia` (
  `id_tarea` int(11) NOT NULL AUTO_INCREMENT,
  `id_definicion` int(11) NOT NULL,
  `titulo` varchar(150) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_vencimiento` date NOT NULL,
  `fecha_completada` datetime DEFAULT NULL,
  `estado` enum('Pendiente','En Curso','Completada','Cancelada') DEFAULT 'Pendiente',
  `importancia` enum('Alta','Baja') DEFAULT 'Baja',
  `id_responsable` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_tarea`),
  KEY `id_definicion` (`id_definicion`),
  KEY `id_responsable` (`id_responsable`),
  KEY `idx_tarea_fecha` (`fecha_vencimiento`),
  KEY `idx_tarea_estado` (`estado`),
  KEY `idx_tarea_prioridad` (`importancia`),
  CONSTRAINT `tareas_instancia_ibfk_1` FOREIGN KEY (`id_definicion`) REFERENCES `tareas_definicion` (`id_definicion`) ON DELETE CASCADE,
  CONSTRAINT `tareas_instancia_ibfk_2` FOREIGN KEY (`id_responsable`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tareas_instancia`
--

LOCK TABLES `tareas_instancia` WRITE;
/*!40000 ALTER TABLE `tareas_instancia` DISABLE KEYS */;
INSERT INTO `tareas_instancia` VALUES (1,1,'Solicitud de materiales ASSA','','2026-02-02','2026-02-02 12:10:30','Completada','Alta',2,'2026-02-02 15:03:42');
INSERT INTO `tareas_instancia` VALUES (2,1,'Solicitud de materiales ASSA','','2026-02-09','2026-02-02 12:10:21','Completada','Alta',2,'2026-02-02 15:03:50');
INSERT INTO `tareas_instancia` VALUES (3,2,'aaa','aaaa','2026-02-02','2026-02-02 12:25:24','Completada','Alta',2,'2026-02-02 15:11:52');
INSERT INTO `tareas_instancia` VALUES (4,2,'aaa','aaaa','2026-02-09',NULL,'Pendiente','Alta',NULL,'2026-02-02 15:11:56');
INSERT INTO `tareas_instancia` VALUES (5,3,'buscar a mare','asfagsdgasdg','2026-02-02','2026-02-02 12:39:13','Completada','Alta',2,'2026-02-02 15:38:16');
INSERT INTO `tareas_instancia` VALUES (6,3,'buscar a mare','asfagsdgasdg','2026-02-09','2026-02-02 12:39:20','Completada','Alta',2,'2026-02-02 15:38:26');
INSERT INTO `tareas_instancia` VALUES (7,4,'buscar a mare','','2026-02-03','2026-02-02 19:56:33','Completada','Alta',2,'2026-02-02 16:10:07');
INSERT INTO `tareas_instancia` VALUES (8,5,'buscar a mare','','2026-02-03',NULL,'Pendiente','Baja',NULL,'2026-02-02 16:10:21');
INSERT INTO `tareas_instancia` VALUES (9,6,'buscar a mare','','2026-02-02','2026-02-02 19:56:27','Completada','Alta',2,'2026-02-02 17:25:39');
INSERT INTO `tareas_instancia` VALUES (10,6,'buscar a mare','','2026-02-03','2026-02-02 19:59:20','Completada','Alta',2,'2026-02-02 17:26:04');
INSERT INTO `tareas_instancia` VALUES (11,6,'buscar a mare','','2026-02-04','2026-02-04 09:42:47','Completada','Alta',2,'2026-02-02 17:26:07');
INSERT INTO `tareas_instancia` VALUES (12,6,'buscar a mare','','2026-02-05','2026-02-04 09:43:49','Completada','Alta',2,'2026-02-02 17:26:39');
INSERT INTO `tareas_instancia` VALUES (13,7,'Solicitud de materiales ASSA','','2026-02-02',NULL,'Pendiente','Baja',NULL,'2026-02-02 17:26:39');
INSERT INTO `tareas_instancia` VALUES (14,6,'buscar a mare','','2026-02-06',NULL,'Pendiente','Alta',NULL,'2026-02-02 17:26:48');
INSERT INTO `tareas_instancia` VALUES (15,7,'Solicitud de materiales ASSA','','2026-02-03',NULL,'Pendiente','Baja',NULL,'2026-02-02 17:26:48');
INSERT INTO `tareas_instancia` VALUES (16,6,'buscar a mare','','2026-02-07',NULL,'Pendiente','Alta',NULL,'2026-02-02 17:49:31');
INSERT INTO `tareas_instancia` VALUES (17,7,'Solicitud de materiales ASSA','','2026-02-04','2026-02-04 09:44:30','Completada','Baja',2,'2026-02-02 17:49:31');
INSERT INTO `tareas_instancia` VALUES (18,6,'buscar a mare','','2026-02-08',NULL,'Pendiente','Alta',NULL,'2026-02-02 17:51:27');
INSERT INTO `tareas_instancia` VALUES (19,7,'Solicitud de materiales ASSA','','2026-02-05','2026-02-04 09:44:28','Completada','Baja',2,'2026-02-02 17:51:27');
INSERT INTO `tareas_instancia` VALUES (20,6,'buscar a mare','','2026-02-09',NULL,'Pendiente','Alta',NULL,'2026-02-02 17:53:26');
INSERT INTO `tareas_instancia` VALUES (21,7,'Solicitud de materiales ASSA','','2026-02-06',NULL,'Pendiente','Baja',NULL,'2026-02-02 17:53:26');
INSERT INTO `tareas_instancia` VALUES (22,7,'Solicitud de materiales ASSA','','2026-02-07',NULL,'Pendiente','Baja',NULL,'2026-02-02 17:55:17');
INSERT INTO `tareas_instancia` VALUES (23,7,'Solicitud de materiales ASSA','','2026-02-08',NULL,'Pendiente','Baja',NULL,'2026-02-02 17:55:19');
INSERT INTO `tareas_instancia` VALUES (24,7,'Solicitud de materiales ASSA','','2026-02-09',NULL,'Pendiente','Baja',NULL,'2026-02-02 17:55:19');
INSERT INTO `tareas_instancia` VALUES (25,6,'buscar a mare','','2026-02-10',NULL,'Pendiente','Alta',NULL,'2026-02-02 23:00:03');
INSERT INTO `tareas_instancia` VALUES (26,7,'Solicitud de materiales ASSA','','2026-02-10',NULL,'Pendiente','Baja',NULL,'2026-02-02 23:00:03');
INSERT INTO `tareas_instancia` VALUES (27,6,'buscar a mare','','2026-02-11',NULL,'Pendiente','Alta',NULL,'2026-02-04 12:12:43');
INSERT INTO `tareas_instancia` VALUES (28,7,'Solicitud de materiales ASSA','','2026-02-11',NULL,'Pendiente','Baja',NULL,'2026-02-04 12:12:43');
INSERT INTO `tareas_instancia` VALUES (29,8,'test','','2026-02-04',NULL,'Pendiente','Baja',NULL,'2026-02-04 12:46:43');
INSERT INTO `tareas_instancia` VALUES (30,6,'buscar a mare','','2026-02-12',NULL,'Pendiente','Alta',NULL,'2026-02-05 11:13:40');
INSERT INTO `tareas_instancia` VALUES (31,7,'Solicitud de materiales ASSA','','2026-02-12',NULL,'Pendiente','Baja',NULL,'2026-02-05 11:13:40');
INSERT INTO `tareas_instancia` VALUES (32,6,'buscar a mare','','2026-02-13',NULL,'Pendiente','Alta',NULL,'2026-02-06 01:24:32');
INSERT INTO `tareas_instancia` VALUES (33,7,'Solicitud de materiales ASSA','','2026-02-13',NULL,'Pendiente','Baja',NULL,'2026-02-06 01:24:32');
INSERT INTO `tareas_instancia` VALUES (34,6,'buscar a mare','','2026-02-14',NULL,'Pendiente','Alta',NULL,'2026-02-08 20:42:34');
INSERT INTO `tareas_instancia` VALUES (35,7,'Solicitud de materiales ASSA','','2026-02-14',NULL,'Pendiente','Baja',NULL,'2026-02-08 20:42:34');
INSERT INTO `tareas_instancia` VALUES (36,1,'Solicitud de materiales ASSA','','2026-02-16',NULL,'Pendiente','Alta',NULL,'2026-02-09 12:27:13');
INSERT INTO `tareas_instancia` VALUES (37,2,'aaa','aaaa','2026-02-16',NULL,'Pendiente','Alta',NULL,'2026-02-09 12:27:13');
INSERT INTO `tareas_instancia` VALUES (38,3,'buscar a mare','asfagsdgasdg','2026-02-16',NULL,'Pendiente','Alta',NULL,'2026-02-09 12:27:13');
INSERT INTO `tareas_instancia` VALUES (39,6,'buscar a mare','','2026-02-15',NULL,'Pendiente','Alta',NULL,'2026-02-09 12:27:13');
INSERT INTO `tareas_instancia` VALUES (40,7,'Solicitud de materiales ASSA','','2026-02-15',NULL,'Pendiente','Baja',NULL,'2026-02-09 12:27:13');
INSERT INTO `tareas_instancia` VALUES (41,6,'buscar a mare','','2026-02-16',NULL,'Pendiente','Alta',NULL,'2026-02-09 12:27:16');
INSERT INTO `tareas_instancia` VALUES (42,7,'Solicitud de materiales ASSA','','2026-02-16',NULL,'Pendiente','Baja',NULL,'2026-02-09 12:27:16');
INSERT INTO `tareas_instancia` VALUES (43,6,'buscar a mare','','2026-02-17',NULL,'Pendiente','Alta',NULL,'2026-02-10 01:13:16');
INSERT INTO `tareas_instancia` VALUES (44,7,'Solicitud de materiales ASSA','','2026-02-17',NULL,'Pendiente','Baja',NULL,'2026-02-10 01:13:16');
/*!40000 ALTER TABLE `tareas_instancia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipologias`
--

DROP TABLE IF EXISTS `tipologias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipologias` (
  `id_tipologia` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `codigo_trabajo` varchar(50) DEFAULT NULL,
  `tiempo_limite_dias` int(11) DEFAULT 1,
  `unidad_medida` varchar(20) DEFAULT NULL,
  `descripcion_larga` text DEFAULT NULL,
  `descripcion_breve` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_tipologia`),
  UNIQUE KEY `codigo_trabajo` (`codigo_trabajo`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipologias`
--

LOCK TABLES `tipologias` WRITE;
/*!40000 ALTER TABLE `tipologias` DISABLE KEYS */;
INSERT INTO `tipologias` VALUES (1,'Demolición y Corte',NULL,1,NULL,NULL,NULL,'2026-02-09 20:12:37');
INSERT INTO `tipologias` VALUES (2,'Excavación y Relleno',NULL,1,NULL,NULL,NULL,'2026-02-09 20:12:37');
INSERT INTO `tipologias` VALUES (3,'Reparación de Veredas',NULL,1,NULL,NULL,NULL,'2026-02-09 20:12:37');
INSERT INTO `tipologias` VALUES (4,'Refacción de Calzada',NULL,1,NULL,NULL,NULL,'2026-02-09 20:12:37');
INSERT INTO `tipologias` VALUES (5,'Instalación de Medidores',NULL,1,NULL,NULL,NULL,'2026-02-09 20:12:37');
INSERT INTO `tipologias` VALUES (6,'Servicios de Agua',NULL,1,NULL,NULL,NULL,'2026-02-09 20:12:37');
INSERT INTO `tipologias` VALUES (7,'Servicios de Cloaca',NULL,1,NULL,NULL,NULL,'2026-02-09 20:12:37');
INSERT INTO `tipologias` VALUES (8,'Logística y Carga',NULL,1,NULL,NULL,NULL,'2026-02-09 20:12:37');
INSERT INTO `tipologias` VALUES (9,'Seguridad y EPP',NULL,1,NULL,NULL,NULL,'2026-02-09 20:12:37');
INSERT INTO `tipologias` VALUES (10,'Gestión Operativa',NULL,1,NULL,NULL,NULL,'2026-02-09 20:12:37');
INSERT INTO `tipologias` VALUES (11,'Hormigonado y BR (Bocas de Registro)',NULL,1,NULL,NULL,NULL,'2026-02-09 20:12:37');
/*!40000 ALTER TABLE `tipologias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipos_trabajos`
--

DROP TABLE IF EXISTS `tipos_trabajos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipos_trabajos` (
  `id_tipologia` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL COMMENT 'Nombre del tipo de trabajo',
  `codigo_trabajo` varchar(30) NOT NULL COMMENT 'Código interno ASSA/RG (ej: 3.1, 22.5)',
  `tiempo_limite_dias` int(11) DEFAULT NULL COMMENT 'Plazo máximo para ejecución en días',
  `unidad_medida` enum('M2','M3','ML','U') NOT NULL DEFAULT 'U' COMMENT 'Unidad de medida del trabajo',
  `descripcion_larga` text DEFAULT NULL COMMENT 'Descripción detallada del trabajo',
  `descripcion_breve` varchar(255) DEFAULT NULL COMMENT 'Descripción corta para listados',
  `precio_unitario` decimal(12,2) DEFAULT 0.00 COMMENT 'Precio por unidad de medida (OM 2026)',
  `estado` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_tipologia`),
  UNIQUE KEY `codigo_trabajo` (`codigo_trabajo`),
  KEY `idx_codigo` (`codigo_trabajo`),
  KEY `idx_unidad` (`unidad_medida`),
  KEY `idx_estado` (`estado`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de tipos de trabajo para obras ASSA/RG';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipos_trabajos`
--

LOCK TABLES `tipos_trabajos` WRITE;
/*!40000 ALTER TABLE `tipos_trabajos` DISABLE KEYS */;
INSERT INTO `tipos_trabajos` VALUES (1,'Reparación de veredas comunes','3.1',25,'M2','Reparación de veredas comunes en zona urbana','Rep. veredas comunes',109181.88,1,'2026-01-31 12:09:32','2026-02-01 18:36:42');
INSERT INTO `tipos_trabajos` VALUES (2,'Reparación de veredas con concreto','3.2',26,'M2','Reparación de veredas utilizando concreto especial','Rep. veredas concreto',105181.88,1,'2026-01-31 12:09:32','2026-02-01 18:36:50');
INSERT INTO `tipos_trabajos` VALUES (3,'Reparación de fugas de agua en vereda','22.1',13,'U','Reparación de fugas de agua detectadas en zona de vereda','Rep. fugas agua vereda',271024.83,1,'2026-01-31 12:09:32','2026-02-01 18:35:07');
INSERT INTO `tipos_trabajos` VALUES (4,'Reparación de fugas de agua en calzada','22.2',16,'U','Reparación de fugas de agua detectadas en zona de calzada','Rep. fugas agua calzada',98374.18,1,'2026-01-31 12:09:32','2026-02-01 18:35:33');
INSERT INTO `tipos_trabajos` VALUES (5,'Fuga de caño distribuidor en calzada','22.5',18,'U','Reparación de fuga en caño distribuidor ubicado en calzada','Fuga caño distribuidor',NULL,1,'2026-01-31 12:09:32','2026-02-01 18:35:43');
INSERT INTO `tipos_trabajos` VALUES (6,'Conexión nueva de agua calzada','20.1',15,'U','Instalación de nueva conexión de agua en calzada','Conexión nueva calzada',NULL,1,'2026-01-31 12:09:32','2026-02-01 18:33:55');
INSERT INTO `tipos_trabajos` VALUES (7,'Conexión corta en calzada','20.2',8,'U','Instalación de conexión corta de agua en calzada','Conexión corta calzada',NULL,1,'2026-01-31 12:09:32','2026-02-01 18:34:17');
INSERT INTO `tipos_trabajos` VALUES (8,'Conexión larga de agua en vereda','20.4',10,'U','Instalación de conexión larga de agua en zona de vereda','Conexión larga vereda',NULL,1,'2026-01-31 12:09:32','2026-02-01 18:34:31');
INSERT INTO `tipos_trabajos` VALUES (9,'Conexión larga de agua en calzada','20.5',11,'U','Instalación de conexión larga de agua en zona de calzada','Conexión larga calzada',NULL,1,'2026-01-31 12:09:32','2026-02-01 18:34:41');
INSERT INTO `tipos_trabajos` VALUES (10,'Renovación de conexiones corta de agua','21.1',12,'U','Renovación de conexiones cortas de servicio de agua','Renov. conexiones corta',277656.12,1,'2026-01-31 12:09:32','2026-02-01 18:35:01');
INSERT INTO `tipos_trabajos` VALUES (11,'Renovación de llaves maestra','22.7',19,'ML','Renovación de llaves maestras del sistema de agua','Renov. llaves maestra',58201.13,1,'2026-01-31 12:09:32','2026-02-01 18:35:52');
INSERT INTO `tipos_trabajos` VALUES (12,'Empalmes nuevos de agua calzada','20.2b',9,'U','Instalación de empalmes nuevos de agua en calzada','Empalmes nuevos calzada',434445.77,1,'2026-01-31 12:09:32','2026-02-01 18:34:24');
INSERT INTO `tipos_trabajos` VALUES (13,'Reparación fugas de cloaca','22.1b',14,'U','Reparación de fugas detectadas en sistema de cloaca','Rep. fugas cloaca',NULL,1,'2026-01-31 12:09:32','2026-02-01 18:35:15');
INSERT INTO `tipos_trabajos` VALUES (14,'Conexiones corta vereda nueva de cloaca','24.1',20,'U','Instalación de conexión corta de cloaca en vereda nueva','Conex. corta vereda cloaca',NULL,1,'2026-01-31 12:09:32','2026-02-01 18:36:00');
INSERT INTO `tipos_trabajos` VALUES (15,'Conexiones larga en vereda hasta 2,5mt de prof','24.5',23,'U','Conexión larga en vereda con profundidad hasta 2.5 metros','Conex. larga vereda 2.5m',NULL,1,'2026-01-31 12:09:32','2026-02-01 18:36:27');
INSERT INTO `tipos_trabajos` VALUES (16,'Renovación de conexiones de cloaca','25.1',24,'ML','Renovación completa de conexiones del sistema de cloaca','Renov. conexiones cloaca',396749.80,1,'2026-01-31 12:09:32','2026-02-01 18:36:35');
INSERT INTO `tipos_trabajos` VALUES (17,'Renovación de redes de cloaca','18',8,'U','Renovación de redes principales del sistema de cloaca','Renov. redes cloaca',424895.94,1,'2026-01-31 12:09:32','2026-02-01 18:33:41');
INSERT INTO `tipos_trabajos` VALUES (18,'Conexiones corta hidrantes','24.1b',21,'U','Instalación de conexiones cortas para hidrantes','Conexiones hidrantes',NULL,1,'2026-01-31 12:09:32','2026-02-01 18:36:10');
INSERT INTO `tipos_trabajos` VALUES (19,'Conexiones de válvulas','24.2',22,'U','Instalación de conexiones para válvulas del sistema','Conexiones válvulas',NULL,1,'2026-01-31 12:09:32','2026-02-01 18:36:20');
INSERT INTO `tipos_trabajos` VALUES (20,'Renovación de redes de agua','22.1c',15,'U','Renovación de redes principales del sistema de agua','Renov. redes agua',NULL,1,'2026-01-31 12:09:32','2026-02-01 18:35:23');
INSERT INTO `tipos_trabajos` VALUES (21,'Colocación de marco y tapa p/boca de registro (calzada)','43.2',27,'U','Colocación de marco y tapa para boca de registro en calzada','Marco y tapa calzada',52238.35,1,'2026-01-31 12:09:32','2026-02-01 18:36:57');
INSERT INTO `tipos_trabajos` VALUES (22,'Instalaciones medidores SC','43.3',28,'U','Instalación de medidores de servicio continuo','Instalaciones medidores',23743.17,1,'2026-01-31 12:09:32','2026-02-01 18:37:03');
INSERT INTO `tipos_trabajos` VALUES (23,'Renovación Marco y Tapa','43.4',29,'ML','Renovación de marco y tapa existente','Renov. marco y tapa',52238.35,1,'2026-01-31 12:09:32','2026-02-01 18:37:10');
INSERT INTO `tipos_trabajos` VALUES (24,'Recambios de medidores','43.8',30,'U','Recambio de medidores en servicio','Recambio medidores',52238.35,1,'2026-01-31 12:09:32','2026-02-01 18:37:17');
INSERT INTO `tipos_trabajos` VALUES (25,'Playa de Secado - REHABILITACIÓN','PS-01',31,'U','Trabajos de rehabilitación en playa de secado','Playa secado rehab.',NULL,1,'2026-01-31 12:09:32','2026-02-01 18:37:23');
INSERT INTO `tipos_trabajos` VALUES (26,'Playas de secado - vereda + finalización','PS-02',32,'U','Trabajos de vereda y finalización en playa de secado','Playa secado vereda',NULL,1,'2026-01-31 12:09:32','2026-02-01 18:37:32');
/*!40000 ALTER TABLE `tipos_trabajos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `id_personal` int(11) DEFAULT NULL,
  `nombre` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `tipo_usuario` enum('Gerente','Administrativo','JefeCuadrilla','Coordinador ASSA','Administrativo ASSA','Inspector ASSA') NOT NULL DEFAULT 'Administrativo',
  `estado` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_cuadrilla` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `id_personal` (`id_personal`),
  KEY `fk_usuario_cuadrilla` (`id_cuadrilla`),
  CONSTRAINT `fk_usuario_cuadrilla` FOREIGN KEY (`id_cuadrilla`) REFERENCES `cuadrillas` (`id_cuadrilla`) ON DELETE SET NULL,
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_personal`) REFERENCES `personal` (`id_personal`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,NULL,'Administrativo','admin@erp.com','$2y$10$ovWyJoCKayrBZkBDEqEKG.1xQmpcujFEqN6ylZpV5IBcOKXu0qb72','Administrativo',1,'2026-01-29 15:11:37',NULL);
INSERT INTO `usuarios` VALUES (2,NULL,'Gerente','gerente@erp.com','$2y$10$7MpS.QUBHHpXTDDrP8W5h.eRjLhzKqio1VdWDhSmW/ExIh4LqEapO','Gerente',1,'2026-01-30 21:04:24',NULL);
INSERT INTO `usuarios` VALUES (4,NULL,'Jefe Cuadrilla','jefe@erp.com','$2y$10$YR1rlF9rZippqX37JauSq.DzB2TKVtuZ6HPLMQhNg7dp4cQYhG5eC','JefeCuadrilla',1,'2026-01-30 21:04:24',NULL);
INSERT INTO `usuarios` VALUES (5,NULL,'Jefe Norte','jefe.cuadrillanortejuanpedro@erp.com','$2y$10$YR1rlF9rZippqX37JauSq.DzB2TKVtuZ6HPLMQhNg7dp4cQYhG5eC','JefeCuadrilla',1,'2026-01-30 21:12:00',1);
INSERT INTO `usuarios` VALUES (6,NULL,'Jefe Sur','jefe.cuadrillasurcarlosluis@erp.com','$2y$10$YR1rlF9rZippqX37JauSq.DzB2TKVtuZ6HPLMQhNg7dp4cQYhG5eC','JefeCuadrilla',1,'2026-01-30 21:12:00',2);
INSERT INTO `usuarios` VALUES (7,NULL,'Inspector','inspector@hotmail.es','$2y$10$D5rBdNnO.nCeRl9zb0BBmO4x1crTT.n5J1QaDmZ1FEAJmm1JPZA6K','Inspector ASSA',1,'2026-02-01 15:46:37',NULL);
INSERT INTO `usuarios` VALUES (8,NULL,'Inspector 2','Vaulet_nahuel_utn@hotmail.es','$2y$10$nO.7eGn.BjgM4IYwUVyjO.Y3K0kjf4o0QSNJBu5m8TxuLsjdefkC2','Inspector ASSA',1,'2026-02-01 16:43:05',NULL);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `v_partes_completos`
--

DROP TABLE IF EXISTS `v_partes_completos`;
/*!50001 DROP VIEW IF EXISTS `v_partes_completos`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_partes_completos` AS SELECT
 1 AS `id_parte`,
  1 AS `fecha_ejecucion`,
  1 AS `hora_inicio`,
  1 AS `hora_fin`,
  1 AS `tiempo_ejecucion_real`,
  1 AS `volumen_calculado`,
  1 AS `unidad_volumen`,
  1 AS `estado`,
  1 AS `observaciones`,
  1 AS `id_odt`,
  1 AS `nro_odt_assa`,
  1 AS `odt_direccion`,
  1 AS `odt_estado`,
  1 AS `id_cuadrilla`,
  1 AS `nombre_cuadrilla`,
  1 AS `id_tipologia`,
  1 AS `tipologia_nombre`,
  1 AS `codigo_trabajo`,
  1 AS `id_vehiculo`,
  1 AS `vehiculo_patente`,
  1 AS `cant_personal`,
  1 AS `cant_materiales`,
  1 AS `cant_fotos` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `vehiculos`
--

DROP TABLE IF EXISTS `vehiculos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vehiculos` (
  `id_vehiculo` int(11) NOT NULL AUTO_INCREMENT,
  `patente` varchar(20) NOT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `modelo` varchar(50) DEFAULT NULL,
  `anio` int(11) DEFAULT NULL,
  `tipo` enum('Camioneta','Utilitario','Cami├│n','Moto','Retropala','Generador','Otro') DEFAULT 'Camioneta',
  `vencimiento_vtv` date DEFAULT NULL,
  `vencimiento_seguro` date DEFAULT NULL,
  `estado` enum('Operativo','En Taller','Baja') DEFAULT 'Operativo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `nivel_aceite` enum('OK','Bajo','Cr├¡tico') DEFAULT 'OK',
  `nivel_combustible` enum('Lleno','Medio','Bajo','Reserva') DEFAULT 'Medio',
  `estado_frenos` enum('OK','Desgastados','Cambiar') DEFAULT 'OK',
  `km_actual` int(11) DEFAULT 0,
  `proximo_service_km` int(11) DEFAULT NULL,
  `fecha_ultimo_inventario` date DEFAULT NULL,
  `costo_reposicion` decimal(12,2) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `tipo_combustible` varchar(20) DEFAULT 'Diesel',
  `id_cuadrilla` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_vehiculo`),
  UNIQUE KEY `patente` (`patente`),
  KEY `id_cuadrilla` (`id_cuadrilla`),
  CONSTRAINT `vehiculos_ibfk_1` FOREIGN KEY (`id_cuadrilla`) REFERENCES `cuadrillas` (`id_cuadrilla`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehiculos`
--

LOCK TABLES `vehiculos` WRITE;
/*!40000 ALTER TABLE `vehiculos` DISABLE KEYS */;
INSERT INTO `vehiculos` VALUES (1,'AA123BB','Toyota','Hilux',2022,'Camioneta',NULL,NULL,'Operativo','2026-02-03 13:26:29','OK','Medio','OK',0,NULL,NULL,NULL,NULL,'Nafta',1);
INSERT INTO `vehiculos` VALUES (2,'CC987DD','Ford','Ranger',2021,'Camioneta',NULL,NULL,'Operativo','2026-02-03 13:26:29','OK','Medio','OK',0,NULL,NULL,NULL,NULL,'Gasoil',2);
/*!40000 ALTER TABLE `vehiculos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Final view structure for view `v_partes_completos`
--

/*!50001 DROP VIEW IF EXISTS `v_partes_completos`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_partes_completos` AS select `pd`.`id_parte` AS `id_parte`,`pd`.`fecha_ejecucion` AS `fecha_ejecucion`,`pd`.`hora_inicio` AS `hora_inicio`,`pd`.`hora_fin` AS `hora_fin`,`pd`.`tiempo_ejecucion_real` AS `tiempo_ejecucion_real`,`pd`.`volumen_calculado` AS `volumen_calculado`,`pd`.`unidad_volumen` AS `unidad_volumen`,`pd`.`estado` AS `estado`,`pd`.`observaciones` AS `observaciones`,`o`.`id_odt` AS `id_odt`,`o`.`nro_odt_assa` AS `nro_odt_assa`,`o`.`direccion` AS `odt_direccion`,`o`.`estado_gestion` AS `odt_estado`,`c`.`id_cuadrilla` AS `id_cuadrilla`,`c`.`nombre_cuadrilla` AS `nombre_cuadrilla`,`t`.`id_tipologia` AS `id_tipologia`,`t`.`nombre` AS `tipologia_nombre`,`t`.`codigo_trabajo` AS `codigo_trabajo`,`v`.`id_vehiculo` AS `id_vehiculo`,`v`.`patente` AS `vehiculo_patente`,(select count(0) from `partes_personal` `pp` where `pp`.`id_parte` = `pd`.`id_parte`) AS `cant_personal`,(select count(0) from `partes_materiales` `pm` where `pm`.`id_parte` = `pd`.`id_parte`) AS `cant_materiales`,(select count(0) from `partes_fotos` `pf` where `pf`.`id_parte` = `pd`.`id_parte`) AS `cant_fotos` from ((((`partes_diarios` `pd` left join `odt_maestro` `o` on(`pd`.`id_odt` = `o`.`id_odt`)) left join `cuadrillas` `c` on(`pd`.`id_cuadrilla` = `c`.`id_cuadrilla`)) left join `tipologias` `t` on(`pd`.`id_tipologia` = `t`.`id_tipologia`)) left join `vehiculos` `v` on(`pd`.`id_vehiculo` = `v`.`id_vehiculo`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-02-10 12:49:18
