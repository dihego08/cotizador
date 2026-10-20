-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 18-10-2026 a las 17:21:47
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sistema_almacen`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `almacenes`
--

CREATE TABLE `almacenes` (
  `id_almacen` int(11) NOT NULL,
  `id_empresa` int(11) NOT NULL,
  `id_responsable` int(11) DEFAULT NULL,
  `nombre_almacen` varchar(100) NOT NULL,
  `ubicacion` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `almacenes`
--

INSERT INTO `almacenes` (`id_almacen`, `id_empresa`, `id_responsable`, `nombre_almacen`, `ubicacion`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'prueba', 'francisco bolognesi 303', '2026-08-05 18:44:25', '2026-08-05 18:44:25');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id_categoria` int(11) NOT NULL,
  `nombre_categoria` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id_categoria`, `nombre_categoria`, `descripcion`, `created_at`) VALUES
(1, 'Computadoras', 'Pc de escritorio o All in one', '2026-08-05 18:22:46'),
(2, 'Perifericos', 'Monitores, Teclados, Mouse, Impresoras', '2026-08-05 18:23:10'),
(3, 'Redes y conectividad', 'Switches, Routers, Access Points, Cables', '2026-08-05 18:23:16'),
(4, 'Almacenamiento', 'Discos duros, SSD, NAS, Memorias USB', '2026-08-05 18:23:21'),
(5, 'Componentes internos', 'Procesadores, RAM, Tarjetas gráficas, Placas base', '2026-08-05 18:23:27'),
(6, 'Accesorios', 'Maletines, Bases, Adaptadores, Cargadores', '2026-08-05 18:23:32'),
(8, 'Software/licencias', 'Sistemas operativos, Antivirus, Ofimática', '2026-08-05 18:25:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL,
  `tipo_documento` enum('DNI','RUC','CE','PASAPORTE','OTROS') NOT NULL DEFAULT 'RUC',
  `num_documento` varchar(15) NOT NULL,
  `razon_social` varchar(255) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `departamento` varchar(50) DEFAULT NULL,
  `provincia` varchar(50) DEFAULT NULL,
  `distrito` varchar(50) DEFAULT NULL,
  `ubigeo` varchar(6) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `atencion` varchar(150) DEFAULT NULL,
  `es_cliente_helpdesk` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id_cliente`, `tipo_documento`, `num_documento`, `razon_social`, `direccion`, `departamento`, `provincia`, `distrito`, `ubigeo`, `email`, `telefono`, `atencion`, `es_cliente_helpdesk`, `created_at`) VALUES
(1, 'RUC', '20100199743', 'INCA TOPS S.A.', 'AV. MIGUEL FORGA NRO 348 Z.I. PARQUE INDUSTRIAL', NULL, NULL, NULL, NULL, 'ajcarcasi@incatops.com', '999999999', 'Alex Carcasi', 0, '2026-08-08 00:41:35'),
(2, 'RUC', '20604032904', 'AMPLA SOLVERING E.I.R.L', 'MZA. H LOTE 3 URB. AVIDGE', NULL, NULL, NULL, NULL, '', '', 'Joel Manrique', 1, '2026-10-13 02:16:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente_contactos`
--

CREATE TABLE `cliente_contactos` (
  `id_contacto` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `tipo_documento` enum('DNI','CE','PASAPORTE','OTROS') NOT NULL DEFAULT 'DNI',
  `num_documento` varchar(15) NOT NULL,
  `nombres_apellidos` varchar(150) NOT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cliente_contactos`
--

INSERT INTO `cliente_contactos` (`id_contacto`, `id_cliente`, `tipo_documento`, `num_documento`, `nombres_apellidos`, `cargo`, `email`, `telefono`, `activo`, `created_at`) VALUES
(1, 2, 'DNI', '88776655', 'Carlos Mendoza', 'Usuario Aprobado', NULL, NULL, 1, '2026-10-18 00:34:53');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizaciones`
--

CREATE TABLE `cotizaciones` (
  `id_cotizacion` int(11) NOT NULL,
  `id_empresa` int(11) DEFAULT NULL,
  `numero_cotizacion` varchar(20) NOT NULL,
  `asunto` varchar(255) DEFAULT NULL,
  `id_cliente` int(11) NOT NULL,
  `atencion` varchar(150) DEFAULT NULL,
  `forma_pago` varchar(50) DEFAULT 'Contado',
  `moneda` varchar(5) DEFAULT 'USD',
  `tipo_cambio` decimal(6,3) DEFAULT 1.000,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `igv` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `validez_dias` int(11) DEFAULT 15,
  `observacion` text DEFAULT NULL,
  `condiciones_comerciales` text DEFAULT NULL,
  `alcance` text DEFAULT NULL,
  `restricciones` text DEFAULT NULL,
  `fecha_emision` date NOT NULL,
  `estado` enum('PENDIENTE','APROBADA','RECHAZADA') DEFAULT 'PENDIENTE',
  `id_usuario` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `vigencia` varchar(100) DEFAULT '15 días calendario',
  `tiempo_entrega` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cotizaciones`
--

INSERT INTO `cotizaciones` (`id_cotizacion`, `id_empresa`, `numero_cotizacion`, `asunto`, `id_cliente`, `atencion`, `forma_pago`, `moneda`, `tipo_cambio`, `subtotal`, `igv`, `total`, `validez_dias`, `observacion`, `condiciones_comerciales`, `alcance`, `restricciones`, `fecha_emision`, `estado`, `id_usuario`, `created_at`, `vigencia`, `tiempo_entrega`) VALUES
(2, 2, 'COT-2026-0001', 'Prueba', 1, 'Alex Carcasi', 'Contado', 'USD', 3.750, 72.16, 12.99, 85.15, 15, NULL, '', 'prueba 1\r\nprueba 2', '', '2026-08-08', 'PENDIENTE', 0, '2026-08-08 01:03:15', '15 días calendario', '2 dias'),
(3, 2, 'COT-2026-0002', '', 1, 'Alex Carcasi', 'Contado', 'USD', 3.750, 15.69, 2.82, 18.51, 15, NULL, '', '', '', '2026-08-09', 'PENDIENTE', 0, '2026-08-08 22:33:09', '15 días calendario', ''),
(4, 2, 'COT-2026-0003', 'otro', 1, 'Alex Carcasi', 'Contado', 'USD', 3.750, 87.85, 15.81, 103.66, 15, NULL, '', '', '', '2026-08-09', 'PENDIENTE', 0, '2026-08-08 22:36:37', '15 días calendario', ''),
(5, 2, 'COT-2026-0004', '1555555555', 1, 'Alex Carcasi', 'Contado', 'USD', 3.750, 78.45, 14.12, 92.57, 15, NULL, '', '', '', '2026-08-09', 'PENDIENTE', 4, '2026-08-08 22:47:49', '2 días calendario', '2 d'),
(6, 2, 'COT-2026-0005', 'Cinta Rotuladora', 2, 'Joel Manrique', 'Contado', 'PEN', 3.750, 1219.51, 219.51, 1439.02, 15, NULL, '', '', '', '2026-10-12', 'PENDIENTE', 4, '2026-10-13 02:18:05', '4 días calendario', '3 dias'),
(8, 2, 'COT-2026-0006', 'probando', 2, 'Joel Manrique', 'Crédito 30 días', 'USD', 3.750, 159.99, 28.80, 188.79, 15, NULL, '', '', '', '2026-10-12', 'PENDIENTE', 4, '2026-10-13 03:09:51', '15 días calendario', '5 dias'),
(9, 2, 'COT-2026-0007', 'aaaa', 2, 'Joel Manrique', 'Crédito 30 días', 'USD', 3.750, 78.43, 14.12, 92.55, 15, NULL, '', '', '', '2026-10-12', 'PENDIENTE', 4, '2026-10-13 03:12:13', '15 días calendario', ''),
(10, 1, 'COT-2026-0008', 'Prueba', 1, 'Alex Carcasi', 'Crédito 15 días', 'USD', 3.750, 94.14, 16.95, 111.09, 15, NULL, '', '', '', '2026-10-12', 'PENDIENTE', 4, '2026-10-13 03:50:58', '15 días calendario', ''),
(11, 2, 'COT-2026-0009', '', 2, 'Joel Manrique', 'Crédito 15 días', 'USD', 3.750, 47.06, 8.47, 55.53, 15, NULL, '', '', '', '2026-10-12', 'PENDIENTE', 4, '2026-10-13 03:53:05', '15 días calendario', ''),
(12, 2, 'COT-2026-0010', '', 1, 'Alex Carcasi', 'Contado', 'USD', 3.750, 62.74, 11.29, 74.03, 15, NULL, '', '', '', '2026-10-12', 'PENDIENTE', 4, '2026-10-13 03:53:27', '15 días calendario', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizacion_detalles`
--

CREATE TABLE `cotizacion_detalles` (
  `id_detalle` int(11) NOT NULL,
  `id_cotizacion` int(11) NOT NULL,
  `id_producto` int(11) DEFAULT NULL,
  `descripcion` varchar(255) NOT NULL,
  `nota_item` text DEFAULT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `cantidad` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unidad_medida` varchar(20) DEFAULT 'UND',
  `porcentaje_utilidad` decimal(5,2) DEFAULT 0.00,
  `precio_unitario` decimal(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cotizacion_detalles`
--

INSERT INTO `cotizacion_detalles` (`id_detalle`, `id_cotizacion`, `id_producto`, `descripcion`, `nota_item`, `marca`, `cantidad`, `unidad_medida`, `porcentaje_utilidad`, `precio_unitario`, `subtotal`) VALUES
(15, 3, NULL, 'Casete de cinta 12 mm', '', 'Brother', 1.00, 'UND', 0.00, 15.69, 0.00),
(16, 2, NULL, 'Casete de cinta 12 mm', '', 'Brother', 1.00, 'UND', 0.00, 15.69, 0.00),
(17, 2, NULL, 'WD5000LPLX\'08ZNTT0 - WXV1A97RYY4N', '', 'Wester Digital', 1.00, 'UND', 0.00, 56.47, 0.00),
(18, 5, NULL, 'Casete de cinta 12 mm', '', 'Brother', 5.00, 'UND', 0.00, 15.69, 0.00),
(19, 4, NULL, 'Casete de cinta 12 mm', '', 'Brother', 2.00, 'UND', 0.00, 15.69, 0.00),
(20, 4, NULL, 'WD5000LPLX\'08ZNTT0 - WXV1A97RYY4N', '', 'Wester Digital', 1.00, 'UND', 0.00, 56.47, 0.00),
(21, 6, NULL, 'Casete de cinta 12 mm', NULL, 'Brother', 20.00, 'UND', 0.00, 60.98, 1219.51),
(23, 8, NULL, 'WXV1A97RYY4N', '', 'Wester Digital', 3.00, 'UND', 0.00, 53.33, 0.00),
(24, 9, NULL, 'Casete de cinta 12 mm', NULL, 'Brother', 5.00, 'UND', 0.00, 15.69, 78.43),
(26, 11, NULL, 'Casete de cinta 12 mm', NULL, 'Brother', 3.00, 'UND', 0.00, 15.69, 47.06),
(27, 12, NULL, 'Casete de cinta 12 mm', NULL, 'Brother', 4.00, 'UND', 0.00, 15.69, 62.74),
(28, 10, NULL, 'Casete de cinta 12 mm', '', 'Brother', 6.00, 'UND', 0.00, 15.69, 0.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresas`
--

CREATE TABLE `empresas` (
  `id_empresa` int(11) NOT NULL,
  `razon_social` varchar(150) NOT NULL,
  `ruc` varchar(11) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT 'assets/img/logo-default.png',
  `nombre_gerente` varchar(150) DEFAULT NULL,
  `cargo_gerente` varchar(100) DEFAULT NULL,
  `cel_gerente` varchar(50) DEFAULT NULL,
  `es_principal` tinyint(1) DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `empresas`
--

INSERT INTO `empresas` (`id_empresa`, `razon_social`, `ruc`, `direccion`, `telefono`, `email`, `logo_path`, `nombre_gerente`, `cargo_gerente`, `cel_gerente`, `es_principal`, `created_at`, `updated_at`) VALUES
(1, 'Mi Empresa S.A.C.', '20123456789', 'Av. Principal 123', '987654321', 'contacto@miempresa.com', 'assets/img/logo-default.png', '', '', '', 0, '2026-08-05 18:41:38', '2026-08-08 17:18:54'),
(2, 'RUVINDUSTRIAS S.A.C.', '20606370149', 'Sor Ana de los Ángeles - Cerro Colorado - Arequipa', '978401982 - 98610041', 'info@ruvindustrias.com', 'uploads/logo_1786234379_397.png', 'RUIZ VIZCARRA HERNAN ANDRE', 'GERENTE DE OPERACIONES', '941466485', 1, '2026-08-07 20:29:29', '2026-08-08 19:12:59');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `helpdesk_atenciones`
--

CREATE TABLE `helpdesk_atenciones` (
  `id_atencion` int(11) NOT NULL,
  `codigo_ticket` varchar(25) NOT NULL,
  `id_empresa` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_contacto` int(11) DEFAULT NULL,
  `tipo_usuario` enum('CORPORATIVO','PARTICULAR') NOT NULL DEFAULT 'CORPORATIVO',
  `documento_solicitante` varchar(20) NOT NULL,
  `nombre_solicitante` varchar(150) NOT NULL,
  `categoria` enum('HARDWARE','SOFTWARE','REDES_CONECTIVIDAD','SEGURIDAD','SISTEMAS','OTROS') NOT NULL DEFAULT 'SOFTWARE',
  `id_subcategoria` int(11) DEFAULT NULL,
  `canal_atencion` enum('LLAMADA','ACCESO_REMOTO','PRESENCIAL','CORREO','WHATSAPP') NOT NULL DEFAULT 'LLAMADA',
  `id_tecnico` int(11) NOT NULL,
  `descripcion_requerimiento` text NOT NULL,
  `acciones_realizadas` text NOT NULL,
  `estado_cierre` enum('CONCLUIDO','EN_PROCESO','ESCALADO_N2','ESPERA_REPUESTO') NOT NULL DEFAULT 'CONCLUIDO',
  `nivel_satisfaccion` tinyint(1) DEFAULT NULL,
  `acciones_programadas` text DEFAULT NULL,
  `fecha_proxima_revision` datetime DEFAULT NULL,
  `id_tecnico_seguimiento` int(11) DEFAULT NULL,
  `fecha_atencion` date NOT NULL,
  `hora_inicio` datetime NOT NULL,
  `hora_fin` datetime NOT NULL,
  `tiempo_pausa_segundos` int(11) DEFAULT 0,
  `tiempo_efectivo_segundos` int(11) NOT NULL,
  `tiempo_efectivo_horas` decimal(6,2) GENERATED ALWAYS AS (`tiempo_efectivo_segundos` / 3600.0) STORED,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `helpdesk_atenciones`
--

INSERT INTO `helpdesk_atenciones` (`id_atencion`, `codigo_ticket`, `id_empresa`, `id_cliente`, `id_contacto`, `tipo_usuario`, `documento_solicitante`, `nombre_solicitante`, `categoria`, `id_subcategoria`, `canal_atencion`, `id_tecnico`, `descripcion_requerimiento`, `acciones_realizadas`, `estado_cierre`, `nivel_satisfaccion`, `acciones_programadas`, `fecha_proxima_revision`, `id_tecnico_seguimiento`, `fecha_atencion`, `hora_inicio`, `hora_fin`, `tiempo_pausa_segundos`, `tiempo_efectivo_segundos`, `created_at`, `updated_at`) VALUES
(1, 'HD-2026-0001', 2, 2, 1, 'CORPORATIVO', '88776655', 'Carlos Mendoza', 'HARDWARE', 7, 'ACCESO_REMOTO', 4, 'prueba', 'prueba', 'CONCLUIDO', 5, NULL, NULL, 4, '2026-10-18', '2026-10-18 00:33:02', '2026-10-18 00:33:37', 0, 34, '2026-10-18 00:33:37', '2026-10-18 00:34:53'),
(2, 'HD-2026-0002', 2, 2, NULL, 'CORPORATIVO', '23323221', 'jorge mendoza', 'SOFTWARE', 8, 'LLAMADA', 4, 'aaaaaa', 'aaaaaaa', 'CONCLUIDO', 5, NULL, NULL, 4, '2026-10-18', '2026-10-18 00:37:44', '2026-10-18 00:37:53', 0, 8, '2026-10-18 00:37:53', '2026-10-18 00:37:53'),
(3, 'HD-2026-0003', 2, 2, NULL, 'CORPORATIVO', '46535356', 'Hernan Ruiz', 'REDES_CONECTIVIDAD', 12, 'LLAMADA', 4, 'qqqqqqqqqqqqqqqq', 'qqqqqqqqqqqqqqqqqqqqqqqqq', 'CONCLUIDO', 5, NULL, NULL, 4, '2026-10-18', '2026-10-18 01:19:44', '2026-10-18 01:19:48', 0, 4, '2026-10-18 01:19:48', '2026-10-18 01:19:48');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `helpdesk_categorias`
--

CREATE TABLE `helpdesk_categorias` (
  `id_categoria` int(11) NOT NULL,
  `id_padre` int(11) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `helpdesk_categorias`
--

INSERT INTO `helpdesk_categorias` (`id_categoria`, `id_padre`, `nombre`, `descripcion`, `activo`, `created_at`) VALUES
(1, NULL, 'SOFTWARE Y SISTEMAS', NULL, 1, '2026-10-17 23:50:55'),
(2, NULL, 'HARDWARE Y PERIFÉRICOS', NULL, 1, '2026-10-17 23:50:55'),
(3, NULL, 'REDES Y CONECTIVIDAD', NULL, 1, '2026-10-17 23:50:55'),
(4, NULL, 'SEGURIDAD Y CCTV', NULL, 1, '2026-10-17 23:50:55'),
(5, 1, 'Sistema de Almacén / ERP', NULL, 1, '2026-10-17 23:50:55'),
(6, 1, 'Licencias / Sistema Operativo', NULL, 1, '2026-10-17 23:50:55'),
(7, 1, 'Correo / Ofimática', NULL, 1, '2026-10-17 23:50:55'),
(8, 2, 'Impresoras / Teteck', NULL, 1, '2026-10-17 23:50:55'),
(9, 2, 'PC / Laptop / Servidor', NULL, 1, '2026-10-17 23:50:55'),
(10, 4, 'Cámara Ezviz / Hikvision', NULL, 1, '2026-10-17 23:50:55'),
(11, 4, 'Control de Acceso / Biométrico', NULL, 1, '2026-10-17 23:50:55'),
(12, 3, 'Cableado Estructurado', NULL, 1, '2026-10-18 01:19:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `helpdesk_pausas`
--

CREATE TABLE `helpdesk_pausas` (
  `id_pausa` int(11) NOT NULL,
  `id_atencion` int(11) NOT NULL,
  `hora_inicio_pausa` datetime NOT NULL,
  `hora_fin_pausa` datetime DEFAULT NULL,
  `motivo_pausa` varchar(150) DEFAULT 'Pausa en la atención'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `marcas`
--

CREATE TABLE `marcas` (
  `id_marca` int(11) NOT NULL,
  `nombre_marca` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `marcas`
--

INSERT INTO `marcas` (`id_marca`, `nombre_marca`) VALUES
(6, 'Brother'),
(5, 'Cisco'),
(2, 'Dell'),
(7, 'Hikvision'),
(3, 'HP'),
(4, 'Lenovo'),
(8, 'Satra'),
(1, 'Wester Digital');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modulos`
--

CREATE TABLE `modulos` (
  `id_modulo` int(11) NOT NULL,
  `id_padre` int(11) DEFAULT NULL,
  `clave` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `icono` varchar(50) DEFAULT 'fa-folder',
  `orden` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `modulos`
--

INSERT INTO `modulos` (`id_modulo`, `id_padre`, `clave`, `nombre`, `icono`, `orden`) VALUES
(1, NULL, 'operaciones', 'Operaciones', 'fa-briefcase', 1),
(2, NULL, 'almacen', 'Almacén', 'fa-warehouse', 2),
(3, NULL, 'finanzas', 'Finanzas', 'fa-wallet', 3),
(4, NULL, 'gestion_corporativa', 'Gestión Corporativa', 'fa-chart-pie', 4),
(5, NULL, 'admin_sistema', 'Admin Sistema', 'fa-user-shield', 5),
(33, 1, 'cotizaciones', 'Cotizaciones', 'fa-file-invoice-dollar', 1),
(34, 1, 'clientes', 'Clientes', 'fa-users', 2),
(35, 2, 'productos', 'Productos y Servicios', 'fa-boxes-stacked', 1),
(36, 2, 'categorias_marcas', 'Categorías y Marcas', 'fa-layer-group', 2),
(37, 2, 'stock', 'Control de Stock', 'fa-chart-simple', 3),
(38, 2, 'movimientos', 'Movimientos', 'fa-arrow-right-left', 4),
(39, 3, 'tipo_cambio', 'Tipo de Cambio', 'fa-dollar-sign', 1),
(40, 4, 'corp_gerencia', 'Gerencia y Dirección', 'fa-user-tie', 1),
(41, 4, 'corp_control', 'Control de Gestión', 'fa-shield-halved', 2),
(42, 4, 'corp_admin', 'Administración General', 'fa-file-invoice', 3),
(43, 5, 'usuarios', 'Gestión de Usuarios', 'fa-users-gear', 1),
(44, 5, 'empresas', 'Gestión de Empresas', 'fa-building', 2),
(45, 1, 'helpdesk', 'Help Desk & Soporte', 'fa-headset', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos`
--

CREATE TABLE `movimientos` (
  `id_movimiento` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `id_almacen` int(11) NOT NULL,
  `id_almacen_origen` int(11) NOT NULL,
  `id_almacen_destino` int(11) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  `tipo_movimiento` enum('ENTRADA','SALIDA','TRANSFERENCIA','AJUSTE') NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `costo_unitario` decimal(10,2) DEFAULT 0.00,
  `fecha_movimiento` datetime NOT NULL DEFAULT current_timestamp(),
  `referencia` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id_producto` int(11) NOT NULL,
  `tipo` enum('PRODUCTO','SERVICIO') DEFAULT 'PRODUCTO',
  `id_categoria` int(11) DEFAULT NULL,
  `codigo_interno` varchar(50) NOT NULL,
  `codigo_proveedor` varchar(50) DEFAULT NULL,
  `nombre_producto` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `numero_parte` varchar(100) DEFAULT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `unidad_medida` varchar(20) NOT NULL,
  `moneda` varchar(5) DEFAULT 'PEN',
  `precio_costo` decimal(12,2) DEFAULT 0.00,
  `porcentaje_utilidad` decimal(5,2) DEFAULT 0.00,
  `precio_venta` decimal(12,2) DEFAULT 0.00,
  `codigo_barra` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id_producto`, `tipo`, `id_categoria`, `codigo_interno`, `codigo_proveedor`, `nombre_producto`, `descripcion`, `numero_parte`, `marca`, `modelo`, `unidad_medida`, `moneda`, `precio_costo`, `porcentaje_utilidad`, `precio_venta`, `codigo_barra`, `created_at`, `updated_at`) VALUES
(1, 'PRODUCTO', 6, '012502627654', '', 'Casete de cinta 12 mm', 'Casete de cinta 12 mm / negro sobre cinta Blanca', '', 'Brother', '', 'UND', 'PEN', 50.00, 15.00, 58.82, '012502627654', '2026-08-05 18:32:35', '2026-08-06 23:58:25'),
(2, 'PRODUCTO', NULL, 'WD5000LPLX\'08ZNTT0', '', 'WXV1A97RYY4N', '', '', 'Wester Digital', '', 'UND', 'PEN', 180.00, 15.00, 211.76, '', '2026-08-05 20:25:05', '2026-08-08 13:00:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `series_productos`
--

CREATE TABLE `series_productos` (
  `id_serie` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `id_almacen` int(11) NOT NULL,
  `numero_serie` varchar(100) NOT NULL,
  `estado` enum('DISPONIBLE','VENDIDO','BAJA','TRANSITO') NOT NULL DEFAULT 'DISPONIBLE',
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stock`
--

CREATE TABLE `stock` (
  `id_stock` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `id_almacen` int(11) NOT NULL,
  `cantidad_actual` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock_minimo` decimal(10,2) DEFAULT 0.00,
  `stock_maximo` decimal(10,2) DEFAULT 0.00,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_cambio`
--

CREATE TABLE `tipo_cambio` (
  `id_tc` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `compra` decimal(10,4) NOT NULL,
  `venta` decimal(10,4) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tipo_cambio`
--

INSERT INTO `tipo_cambio` (`id_tc`, `fecha`, `compra`, `venta`, `created_at`) VALUES
(1, '2026-08-06', 3.7200, 3.7500, '2026-08-06 20:01:52'),
(2, '2026-08-08', 3.7200, 3.7500, '2026-08-07 18:44:48'),
(3, '2026-10-12', 3.4500, 3.4570, '2026-10-12 22:56:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `id_empresa` int(11) NOT NULL,
  `nombre_usuario` varchar(100) NOT NULL,
  `rol` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cargo` varchar(100) DEFAULT NULL,
  `celular` varchar(30) DEFAULT NULL,
  `firma_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `id_empresa`, `nombre_usuario`, `rol`, `email`, `password_hash`, `activo`, `created_at`, `updated_at`, `cargo`, `celular`, `firma_path`) VALUES
(1, 1, 'Administrador', 'ADMIN', 'admin@almacen.com', '$2y$10$v0UX91D0Ve2UFysJioDsN.J3HOVJs8TXohtdrBaOINbmK8.etMA12', 1, '2026-08-05 19:02:28', '2026-10-12 21:11:44', '', '', NULL),
(2, 1, 'Jorge Rojas', 'vendedor', 'jrojas@empresa.com', '$2y$10$0BIgrOysL7e1lEvjORIgveWBA.lF/o2AIs6HkfJcGpgBAx5qnl/ZO', 0, '2026-08-05 19:19:21', '2026-08-08 18:05:55', '', '', NULL),
(4, 2, 'Hernan Andre Ruiz Vizcarra', 'ADMIN', 'admin@ruvindustrias.com', '$2y$10$Jbc0oD6kODukl0fw6ReE/.ybxsmHAMKC0/ZIy1nopo2JmrgPmp4I.', 1, '2026-08-07 21:59:48', '2026-10-12 22:11:27', 'GERENTE DE OPERACIONES', '941466485', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_permisos`
--

CREATE TABLE `usuario_permisos` (
  `id_usuario` int(11) NOT NULL,
  `id_modulo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario_permisos`
--

INSERT INTO `usuario_permisos` (`id_usuario`, `id_modulo`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 33),
(1, 34),
(1, 35),
(1, 36),
(1, 37),
(1, 38),
(1, 39),
(1, 40),
(1, 41),
(1, 42),
(1, 43),
(1, 44),
(1, 45),
(4, 1),
(4, 2),
(4, 3),
(4, 4),
(4, 5),
(4, 33),
(4, 34),
(4, 35),
(4, 36),
(4, 37),
(4, 38),
(4, 39),
(4, 40),
(4, 41),
(4, 42),
(4, 43),
(4, 44),
(4, 45);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `almacenes`
--
ALTER TABLE `almacenes`
  ADD PRIMARY KEY (`id_almacen`),
  ADD KEY `idx_almacenes_empresa` (`id_empresa`),
  ADD KEY `idx_almacenes_responsable` (`id_responsable`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id_categoria`),
  ADD UNIQUE KEY `uk_categorias_nombre` (`nombre_categoria`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_cliente`),
  ADD UNIQUE KEY `num_documento` (`num_documento`);

--
-- Indices de la tabla `cliente_contactos`
--
ALTER TABLE `cliente_contactos`
  ADD PRIMARY KEY (`id_contacto`),
  ADD UNIQUE KEY `uk_cliente_doc` (`id_cliente`,`num_documento`),
  ADD KEY `fk_contactos_clientes` (`id_cliente`);

--
-- Indices de la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  ADD PRIMARY KEY (`id_cotizacion`),
  ADD UNIQUE KEY `numero_cotizacion` (`numero_cotizacion`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `fk_cotiz_empresa` (`id_empresa`);

--
-- Indices de la tabla `cotizacion_detalles`
--
ALTER TABLE `cotizacion_detalles`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_cotizacion` (`id_cotizacion`);

--
-- Indices de la tabla `empresas`
--
ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id_empresa`),
  ADD UNIQUE KEY `uk_empresas_ruc` (`ruc`);

--
-- Indices de la tabla `helpdesk_atenciones`
--
ALTER TABLE `helpdesk_atenciones`
  ADD PRIMARY KEY (`id_atencion`),
  ADD UNIQUE KEY `uk_codigo_ticket` (`codigo_ticket`),
  ADD KEY `idx_hd_empresa` (`id_empresa`),
  ADD KEY `idx_hd_cliente` (`id_cliente`),
  ADD KEY `idx_hd_tecnico` (`id_tecnico`),
  ADD KEY `idx_hd_fecha` (`fecha_atencion`),
  ADD KEY `fk_hd_contactos` (`id_contacto`),
  ADD KEY `fk_hd_usuarios_seguimiento` (`id_tecnico_seguimiento`),
  ADD KEY `fk_hd_subcategoria` (`id_subcategoria`);

--
-- Indices de la tabla `helpdesk_categorias`
--
ALTER TABLE `helpdesk_categorias`
  ADD PRIMARY KEY (`id_categoria`),
  ADD KEY `fk_hd_cat_padre` (`id_padre`);

--
-- Indices de la tabla `helpdesk_pausas`
--
ALTER TABLE `helpdesk_pausas`
  ADD PRIMARY KEY (`id_pausa`),
  ADD KEY `fk_pausas_atencion` (`id_atencion`);

--
-- Indices de la tabla `marcas`
--
ALTER TABLE `marcas`
  ADD PRIMARY KEY (`id_marca`),
  ADD UNIQUE KEY `nombre_marca` (`nombre_marca`);

--
-- Indices de la tabla `modulos`
--
ALTER TABLE `modulos`
  ADD PRIMARY KEY (`id_modulo`),
  ADD UNIQUE KEY `clave` (`clave`),
  ADD KEY `fk_modulos_padre` (`id_padre`);

--
-- Indices de la tabla `movimientos`
--
ALTER TABLE `movimientos`
  ADD PRIMARY KEY (`id_movimiento`),
  ADD KEY `idx_movimientos_producto` (`id_producto`),
  ADD KEY `idx_movimientos_almacen_origen` (`id_almacen_origen`),
  ADD KEY `idx_movimientos_almacen_destino` (`id_almacen_destino`),
  ADD KEY `idx_movimientos_usuario` (`id_usuario`),
  ADD KEY `fk_movimientos_almacenes` (`id_almacen`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id_producto`),
  ADD UNIQUE KEY `uk_productos_codigo_interno` (`codigo_interno`),
  ADD KEY `idx_productos_categoria` (`id_categoria`);

--
-- Indices de la tabla `series_productos`
--
ALTER TABLE `series_productos`
  ADD PRIMARY KEY (`id_serie`),
  ADD UNIQUE KEY `uk_series_numero_serie` (`numero_serie`),
  ADD KEY `idx_series_producto` (`id_producto`),
  ADD KEY `idx_series_almacen` (`id_almacen`);

--
-- Indices de la tabla `stock`
--
ALTER TABLE `stock`
  ADD PRIMARY KEY (`id_stock`),
  ADD UNIQUE KEY `uk_stock_producto_almacen` (`id_producto`,`id_almacen`),
  ADD KEY `idx_stock_almacen` (`id_almacen`);

--
-- Indices de la tabla `tipo_cambio`
--
ALTER TABLE `tipo_cambio`
  ADD PRIMARY KEY (`id_tc`),
  ADD UNIQUE KEY `fecha` (`fecha`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `uk_usuarios_email` (`email`),
  ADD KEY `fk_usuarios_empresas` (`id_empresa`);

--
-- Indices de la tabla `usuario_permisos`
--
ALTER TABLE `usuario_permisos`
  ADD PRIMARY KEY (`id_usuario`,`id_modulo`),
  ADD KEY `id_modulo` (`id_modulo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `almacenes`
--
ALTER TABLE `almacenes`
  MODIFY `id_almacen` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `cliente_contactos`
--
ALTER TABLE `cliente_contactos`
  MODIFY `id_contacto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  MODIFY `id_cotizacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `cotizacion_detalles`
--
ALTER TABLE `cotizacion_detalles`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de la tabla `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id_empresa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `helpdesk_atenciones`
--
ALTER TABLE `helpdesk_atenciones`
  MODIFY `id_atencion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `helpdesk_categorias`
--
ALTER TABLE `helpdesk_categorias`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `helpdesk_pausas`
--
ALTER TABLE `helpdesk_pausas`
  MODIFY `id_pausa` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `marcas`
--
ALTER TABLE `marcas`
  MODIFY `id_marca` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `modulos`
--
ALTER TABLE `modulos`
  MODIFY `id_modulo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT de la tabla `movimientos`
--
ALTER TABLE `movimientos`
  MODIFY `id_movimiento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id_producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `series_productos`
--
ALTER TABLE `series_productos`
  MODIFY `id_serie` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `stock`
--
ALTER TABLE `stock`
  MODIFY `id_stock` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tipo_cambio`
--
ALTER TABLE `tipo_cambio`
  MODIFY `id_tc` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `almacenes`
--
ALTER TABLE `almacenes`
  ADD CONSTRAINT `fk_almacenes_empresas` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_almacenes_usuarios` FOREIGN KEY (`id_responsable`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `cliente_contactos`
--
ALTER TABLE `cliente_contactos`
  ADD CONSTRAINT `fk_contactos_clientes` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  ADD CONSTRAINT `cotizaciones_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`),
  ADD CONSTRAINT `fk_cotiz_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`);

--
-- Filtros para la tabla `cotizacion_detalles`
--
ALTER TABLE `cotizacion_detalles`
  ADD CONSTRAINT `cotizacion_detalles_ibfk_1` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizaciones` (`id_cotizacion`) ON DELETE CASCADE;

--
-- Filtros para la tabla `helpdesk_atenciones`
--
ALTER TABLE `helpdesk_atenciones`
  ADD CONSTRAINT `fk_hd_clientes` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hd_contactos` FOREIGN KEY (`id_contacto`) REFERENCES `cliente_contactos` (`id_contacto`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hd_empresas` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hd_subcategoria` FOREIGN KEY (`id_subcategoria`) REFERENCES `helpdesk_categorias` (`id_categoria`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hd_usuarios_seguimiento` FOREIGN KEY (`id_tecnico_seguimiento`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hd_usuarios_tecnico` FOREIGN KEY (`id_tecnico`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `helpdesk_categorias`
--
ALTER TABLE `helpdesk_categorias`
  ADD CONSTRAINT `fk_hd_cat_padre` FOREIGN KEY (`id_padre`) REFERENCES `helpdesk_categorias` (`id_categoria`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `helpdesk_pausas`
--
ALTER TABLE `helpdesk_pausas`
  ADD CONSTRAINT `fk_pausas_atencion` FOREIGN KEY (`id_atencion`) REFERENCES `helpdesk_atenciones` (`id_atencion`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `modulos`
--
ALTER TABLE `modulos`
  ADD CONSTRAINT `fk_modulos_padre` FOREIGN KEY (`id_padre`) REFERENCES `modulos` (`id_modulo`) ON DELETE CASCADE;

--
-- Filtros para la tabla `movimientos`
--
ALTER TABLE `movimientos`
  ADD CONSTRAINT `fk_movimientos_almacenes` FOREIGN KEY (`id_almacen`) REFERENCES `almacenes` (`id_almacen`),
  ADD CONSTRAINT `fk_movimientos_almacenes_destino` FOREIGN KEY (`id_almacen_destino`) REFERENCES `almacenes` (`id_almacen`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_movimientos_almacenes_origen` FOREIGN KEY (`id_almacen_origen`) REFERENCES `almacenes` (`id_almacen`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_movimientos_productos` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_movimientos_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `fk_productos_categorias` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `series_productos`
--
ALTER TABLE `series_productos`
  ADD CONSTRAINT `fk_series_almacenes` FOREIGN KEY (`id_almacen`) REFERENCES `almacenes` (`id_almacen`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_series_productos` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `stock`
--
ALTER TABLE `stock`
  ADD CONSTRAINT `fk_stock_almacenes` FOREIGN KEY (`id_almacen`) REFERENCES `almacenes` (`id_almacen`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_stock_productos` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_empresas` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`);

--
-- Filtros para la tabla `usuario_permisos`
--
ALTER TABLE `usuario_permisos`
  ADD CONSTRAINT `usuario_permisos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `usuario_permisos_ibfk_2` FOREIGN KEY (`id_modulo`) REFERENCES `modulos` (`id_modulo`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
