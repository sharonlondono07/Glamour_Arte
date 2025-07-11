-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         8.0.30 - MySQL Community Server - GPL
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Volcando estructura de base de datos para prototipo
CREATE DATABASE IF NOT EXISTS `prototipo` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `prototipo`;

-- Volcando estructura para tabla prototipo.citas
CREATE TABLE IF NOT EXISTS `citas` (
  `id_cita` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int NOT NULL,
  `id_estilista` int NOT NULL,
  `id_servicio` int NOT NULL,
  `id_pago` int NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `estado` enum('pendiente','cancelada','completada') NOT NULL DEFAULT 'pendiente',
  `creado_por` int DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_cita`),
  KEY `idx_id_cliente` (`id_cliente`),
  KEY `idx_id_estilista` (`id_estilista`),
  KEY `idx_id_servicio` (`id_servicio`),
  KEY `idx_id_pago` (`id_pago`),
  KEY `fk_citas_creado_por` (`creado_por`),
  CONSTRAINT `fk_citas_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_citas_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_citas_estilista` FOREIGN KEY (`id_estilista`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_citas_pago` FOREIGN KEY (`id_pago`) REFERENCES `pagos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_citas_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla prototipo.citas: ~2 rows (aproximadamente)
INSERT INTO `citas` (`id_cita`, `id_cliente`, `id_estilista`, `id_servicio`, `id_pago`, `fecha_hora`, `estado`, `creado_por`, `fecha_creacion`) VALUES
	(28, 27, 22, 3, 34, '2025-07-11 10:30:00', 'pendiente', 27, '2025-07-08 21:09:30'),
	(29, 28, 22, 1, 35, '2025-07-10 10:20:00', 'pendiente', 28, '2025-07-08 21:18:32');

-- Volcando estructura para tabla prototipo.estilistas
CREATE TABLE IF NOT EXISTS `estilistas` (
  `id_usuario` int NOT NULL,
  `descripcion` text,
  `foto` varchar(255) DEFAULT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_usuario`),
  CONSTRAINT `estilistas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla prototipo.estilistas: ~3 rows (aproximadamente)
INSERT INTO `estilistas` (`id_usuario`, `descripcion`, `foto`, `apellido`) VALUES
	(22, 'Especialista en Peinados modernos', '0', NULL),
	(30, 'Maquilladora Profesional', 'estilista_1752112607.png', NULL),
	(31, 'Experta en todas las tecnicas de uñas artificiales', 'estilista_1752112703.png', NULL);

-- Volcando estructura para tabla prototipo.pagos
CREATE TABLE IF NOT EXISTS `pagos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` enum('efectivo','tarjeta','transferencia') NOT NULL,
  `fecha_pago` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('pendiente','completado','fallido') NOT NULL DEFAULT 'pendiente',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla prototipo.pagos: ~5 rows (aproximadamente)
INSERT INTO `pagos` (`id`, `monto`, `metodo_pago`, `fecha_pago`, `estado`) VALUES
	(32, 70.00, 'efectivo', '2025-07-08 11:24:31', 'pendiente'),
	(33, 70.00, 'efectivo', '2025-07-08 11:30:56', 'pendiente'),
	(34, 70.00, 'efectivo', '2025-07-08 21:09:30', 'pendiente'),
	(35, 50.00, 'efectivo', '2025-07-08 21:18:32', 'pendiente'),
	(36, 0.00, 'efectivo', '2025-07-09 20:38:42', 'pendiente');

-- Volcando estructura para tabla prototipo.rol
CREATE TABLE IF NOT EXISTS `rol` (
  `id_rol` int NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(50) NOT NULL,
  PRIMARY KEY (`id_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla prototipo.rol: ~3 rows (aproximadamente)
INSERT INTO `rol` (`id_rol`, `nombre_rol`) VALUES
	(1, 'admin'),
	(2, 'estilista'),
	(3, 'cliente');

-- Volcando estructura para tabla prototipo.servicios
CREATE TABLE IF NOT EXISTS `servicios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre_servicio` varchar(100) NOT NULL,
  `descripcion` text,
  `precio` decimal(10,2) NOT NULL,
  `duracion` int NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla prototipo.servicios: ~3 rows (aproximadamente)
INSERT INTO `servicios` (`id`, `nombre_servicio`, `descripcion`, `precio`, `duracion`, `imagen`) VALUES
	(1, 'manicure', 'uñas en acrigel', 50.00, 180, 'servicio_1.png'),
	(3, 'Uñas press on ', 'Montura desde Cero con tecnica  rusa', 70.00, 120, 'servicio_1751595994.png'),
	(4, 'pedicure Tradicional', 'tener en cuenta que si te haces dibujos de mas se te cobra un adicional.', 15.00, 60, 'servicio_1751935837.png');

-- Volcando estructura para tabla prototipo.trabajos_estilista
CREATE TABLE IF NOT EXISTS `trabajos_estilista` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_estilista` int DEFAULT NULL,
  `foto_trabajo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_estilista` (`id_estilista`),
  CONSTRAINT `trabajos_estilista_ibfk_1` FOREIGN KEY (`id_estilista`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla prototipo.trabajos_estilista: ~1 rows (aproximadamente)
INSERT INTO `trabajos_estilista` (`id`, `id_estilista`, `foto_trabajo`) VALUES
	(4, 22, 'uploads/686dd1f5a3585_acrigel.png');

-- Volcando estructura para tabla prototipo.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_rol` int NOT NULL,
  `correo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `password` varchar(255) NOT NULL,
  `telefono` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `nombre` varchar(100) DEFAULT NULL,
  `apellido` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `verification_code` varchar(6) DEFAULT NULL,
  `code_expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_id_rol` (`id_rol`),
  CONSTRAINT `fk_users_rol` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla prototipo.users: ~14 rows (aproximadamente)
INSERT INTO `users` (`id`, `id_rol`, `correo`, `password`, `telefono`, `fecha_registro`, `nombre`, `apellido`, `verification_code`, `code_expires_at`) VALUES
	(1, 1, 'sharonlondono386@gmail.com', 'sharon3103126359', '3103126359', '2025-06-04 17:42:00', 'sharon', NULL, '410380', '2025-07-08 13:09:58'),
	(5, 3, 'andrea14@gmail.com', '123456789', NULL, '2025-06-04 19:40:43', 'Andrea', 'vargas', NULL, NULL),
	(7, 3, 'maria@gmail.com', '1234', NULL, '2025-06-05 14:35:46', 'maria', 'guitierrez', NULL, NULL),
	(8, 3, 'aleja14@gmail.com', '12345', NULL, '2025-06-05 14:40:57', 'alejandra', NULL, NULL, NULL),
	(16, 3, 'erikaoliveros02@gmail.com', 'sharonveronica', '3103325732', '2025-07-06 01:28:08', NULL, 'oliveros', NULL, NULL),
	(22, 2, 'mariana@gmail.com', '1234567', '322 3278540', '2025-07-08 11:08:27', 'Mariana', 'Molina', NULL, NULL),
	(23, 3, 'Almanza@gmail.com', '123', '3188795678', '2025-07-08 11:29:38', 'juana', NULL, NULL, NULL),
	(24, 3, 'alejandra14@gmail.com', '1234', '3103126357', '2025-07-08 12:24:33', 'Alejandra', 'Lopez', NULL, NULL),
	(25, 3, 'veronicalondono2015@gmail.com', 'vero2015', '3223278540', '2025-07-08 18:55:03', 'Veronica', 'Londoño', NULL, NULL),
	(26, 3, 'nataliaoliveros386@gmail.com', '123456789', '3212612009', '2025-07-08 18:58:03', 'Natalia', 'Oliveros', NULL, NULL),
	(27, 3, 'viviana@gmail.com', 'viviana12345', '3126754890', '2025-07-08 21:08:26', 'Viviana', 'Montenegro', NULL, NULL),
	(28, 3, 'albenis@gmail.com', '123456', '3219845432', '2025-07-08 21:17:43', 'Albenis', 'Torres', NULL, NULL),
	(30, 2, 'patricia@gmail.com', '1234567890', '3123979732', '2025-07-09 20:56:47', 'Patricia', 'Rojas', NULL, NULL),
	(31, 2, 'ana200@gmail.com', '12345', '3001234567', '2025-07-09 20:58:23', 'Ana', 'Rodriguez', NULL, NULL);

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
