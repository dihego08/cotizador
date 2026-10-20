-- =====================================================================
-- ACTUALIZACIÓN DE BASE DE DATOS: DOCUMENTOS DE VENTA Y COBRANZAS
-- Base de Datos: sistema_almacen / u622044135_sis_almacen
-- =====================================================================

-- 1. Tabla de Tokens Sanctum (Por si aún no se ejecutó)
CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(191) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla Principal de Documentos de Venta (Facturas, Boletas, Notas de Venta)
CREATE TABLE IF NOT EXISTS `ventas` (
  `id_venta` int(11) NOT NULL AUTO_INCREMENT,
  `id_empresa` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_cotizacion` int(11) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  `tipo_documento` enum('FACTURA','BOLETA','NOTA_VENTA') NOT NULL DEFAULT 'FACTURA',
  `serie` varchar(10) NOT NULL,
  `correlativo` int(11) NOT NULL,
  `serie_correlativo` varchar(30) NOT NULL,
  `moneda` varchar(5) NOT NULL DEFAULT 'PEN',
  `tipo_cambio` decimal(6,3) DEFAULT 1.000,
  `forma_pago` varchar(50) DEFAULT 'Contado',
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `igv` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_pagado` decimal(12,2) NOT NULL DEFAULT 0.00,
  `saldo_pendiente` decimal(12,2) NOT NULL DEFAULT 0.00,
  `estado_pago` enum('PENDIENTE','PARCIAL','PAGADO') NOT NULL DEFAULT 'PENDIENTE',
  `estado_anulado` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_emision` date NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_venta`),
  UNIQUE KEY `uk_venta_serie_correlativo` (`id_empresa`, `tipo_documento`, `serie`, `correlativo`),
  KEY `idx_ventas_empresa` (`id_empresa`),
  KEY `idx_ventas_cliente` (`id_cliente`),
  KEY `idx_ventas_cotizacion` (`id_cotizacion`),
  KEY `idx_ventas_usuario` (`id_usuario`),
  KEY `idx_ventas_fecha` (`fecha_emision`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla de Detalles de Venta (Productos y Servicios Facturados)
CREATE TABLE IF NOT EXISTS `venta_detalles` (
  `id_detalle` int(11) NOT NULL AUTO_INCREMENT,
  `id_venta` int(11) NOT NULL,
  `id_producto` int(11) DEFAULT NULL,
  `descripcion` varchar(255) NOT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `unidad_medida` varchar(20) DEFAULT 'UND',
  `cantidad` decimal(10,2) NOT NULL DEFAULT 1.00,
  `precio_unitario` decimal(12,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id_detalle`),
  KEY `idx_vdetalle_venta` (`id_venta`),
  KEY `idx_vdetalle_producto` (`id_producto`),
  CONSTRAINT `fk_vdetalle_venta` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_vdetalle_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabla de Cobranzas / Pagos Parciales (Amortizaciones de la Venta)
CREATE TABLE IF NOT EXISTS `venta_pagos` (
  `id_pago` int(11) NOT NULL AUTO_INCREMENT,
  `id_venta` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `fecha_pago` date NOT NULL,
  `metodo_pago` enum('TRANSFERENCIA','EFECTIVO','YAPE_PLIN','TARJETA','CHEQUE','OTROS') NOT NULL DEFAULT 'TRANSFERENCIA',
  `referencia_operacion` varchar(100) DEFAULT NULL,
  `banco` varchar(100) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_pago`),
  KEY `idx_vpagos_venta` (`id_venta`),
  KEY `idx_vpagos_usuario` (`id_usuario`),
  CONSTRAINT `fk_vpagos_venta` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_vpagos_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Claves Foráneas para la tabla `ventas`
ALTER TABLE `ventas`
  ADD CONSTRAINT `fk_ventas_empresas` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ventas_clientes` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ventas_cotizaciones` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizaciones` (`id_cotizacion`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ventas_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE;
