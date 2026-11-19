-- Esquema base del sistema (estado de producción al 2026-11-19).
-- Lo carga la migración 2026_11_19_000000_esquema_base solo en una base de datos vacía.
-- No editar: los cambios nuevos van en database/migrations.

CREATE TABLE `almacenes` (
  `id_almacen` int NOT NULL AUTO_INCREMENT,
  `id_empresa` int NOT NULL,
  `id_responsable` int DEFAULT NULL,
  `nombre_almacen` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ubicacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_almacen`),
  KEY `idx_almacenes_empresa` (`id_empresa`),
  KEY `idx_almacenes_responsable` (`id_responsable`),
  CONSTRAINT `fk_almacenes_empresas` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_almacenes_usuarios` FOREIGN KEY (`id_responsable`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `categorias` (
  `id_categoria` int NOT NULL AUTO_INCREMENT,
  `nombre_categoria` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_categoria`),
  UNIQUE KEY `uk_categorias_nombre` (`nombre_categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cliente_contactos` (
  `id_contacto` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int NOT NULL,
  `tipo_documento` enum('DNI','CE','PASAPORTE','OTROS') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DNI',
  `num_documento` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombres_apellidos` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cargo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_contacto`),
  UNIQUE KEY `uk_cliente_doc` (`id_cliente`,`num_documento`),
  KEY `fk_contactos_clientes` (`id_cliente`),
  CONSTRAINT `fk_contactos_clientes` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `clientes` (
  `id_cliente` int NOT NULL AUTO_INCREMENT,
  `tipo_documento` enum('DNI','RUC','CE','PASAPORTE','OTROS') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'RUC',
  `num_documento` varchar(15) COLLATE utf8mb4_general_ci NOT NULL,
  `razon_social` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `departamento` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `provincia` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `distrito` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ubigeo` varchar(6) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telefono` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `atencion` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `es_cliente_helpdesk` tinyint(1) NOT NULL DEFAULT '0',
  `estado_crm` enum('PROSPECTO','ACTIVO','INACTIVO') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ACTIVO',
  `origen_lead` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'DIRECTO',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_cliente`),
  UNIQUE KEY `num_documento` (`num_documento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `cotizacion_detalles` (
  `id_detalle` int NOT NULL AUTO_INCREMENT,
  `id_cotizacion` int NOT NULL,
  `id_producto` int DEFAULT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `nota_item` text COLLATE utf8mb4_general_ci,
  `marca` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cantidad` decimal(10,2) NOT NULL DEFAULT '1.00',
  `unidad_medida` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'UND',
  `porcentaje_utilidad` decimal(5,2) DEFAULT '0.00',
  `precio_unitario` decimal(10,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id_detalle`),
  KEY `id_cotizacion` (`id_cotizacion`),
  CONSTRAINT `cotizacion_detalles_ibfk_1` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizaciones` (`id_cotizacion`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `cotizaciones` (
  `id_cotizacion` int NOT NULL AUTO_INCREMENT,
  `id_empresa` int DEFAULT NULL,
  `numero_cotizacion` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `asunto` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_cliente` int NOT NULL,
  `atencion` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `forma_pago` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Contado',
  `moneda` varchar(5) COLLATE utf8mb4_general_ci DEFAULT 'USD',
  `tipo_cambio` decimal(6,3) DEFAULT '1.000',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `igv` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `validez_dias` int DEFAULT '15',
  `observacion` text COLLATE utf8mb4_general_ci,
  `condiciones_comerciales` text COLLATE utf8mb4_general_ci,
  `alcance` text COLLATE utf8mb4_general_ci,
  `restricciones` text COLLATE utf8mb4_general_ci,
  `fecha_emision` date NOT NULL,
  `estado` enum('PENDIENTE','APROBADA','RECHAZADA') COLLATE utf8mb4_general_ci DEFAULT 'PENDIENTE',
  `id_usuario` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `vigencia` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '15 días calendario',
  `tiempo_entrega` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_cotizacion`),
  UNIQUE KEY `numero_cotizacion` (`numero_cotizacion`),
  KEY `id_cliente` (`id_cliente`),
  KEY `fk_cotiz_empresa` (`id_empresa`),
  CONSTRAINT `cotizaciones_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`),
  CONSTRAINT `fk_cotiz_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `crm_actividades` (
  `id_actividad` int NOT NULL AUTO_INCREMENT,
  `id_empresa` int NOT NULL,
  `id_cliente` int NOT NULL,
  `id_oportunidad` int DEFAULT NULL,
  `id_usuario` int NOT NULL,
  `tipo` enum('LLAMADA','REUNION','WHATSAPP','CORREO','NOTA') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NOTA',
  `titulo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `archivo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `archivo_nombre` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `archivo_size` int DEFAULT NULL,
  `fecha_actividad` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `proximo_seguimiento` date DEFAULT NULL,
  `completado` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_actividad`),
  KEY `idx_crm_act_empresa` (`id_empresa`),
  KEY `idx_crm_act_cliente` (`id_cliente`),
  KEY `idx_crm_act_oportunidad` (`id_oportunidad`),
  KEY `idx_crm_act_usuario` (`id_usuario`),
  KEY `idx_crm_act_fecha` (`fecha_actividad`),
  CONSTRAINT `fk_crm_act_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_crm_act_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_crm_act_oportunidad` FOREIGN KEY (`id_oportunidad`) REFERENCES `crm_oportunidades` (`id_oportunidad`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_crm_act_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `crm_oportunidades` (
  `id_oportunidad` int NOT NULL AUTO_INCREMENT,
  `id_empresa` int NOT NULL,
  `id_cliente` int NOT NULL,
  `id_usuario` int NOT NULL,
  `id_cotizacion` int DEFAULT NULL,
  `titulo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `valor_estimado` decimal(12,2) NOT NULL DEFAULT '0.00',
  `moneda` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `etapa` enum('PROSPECTO','CONTACTADO','PROPUESTA','NEGOCIACION','GANADO','PERDIDO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PROSPECTO',
  `probabilidad` tinyint NOT NULL DEFAULT '20',
  `prioridad` enum('BAJA','MEDIA','ALTA') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MEDIA',
  `fecha_cierre_estimada` date DEFAULT NULL,
  `motivo_perdida` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_oportunidad`),
  KEY `idx_crm_op_empresa` (`id_empresa`),
  KEY `idx_crm_op_cliente` (`id_cliente`),
  KEY `idx_crm_op_usuario` (`id_usuario`),
  KEY `idx_crm_op_cotizacion` (`id_cotizacion`),
  KEY `idx_crm_op_etapa` (`etapa`),
  CONSTRAINT `fk_crm_op_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON UPDATE CASCADE,
  CONSTRAINT `fk_crm_op_cotizacion` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizaciones` (`id_cotizacion`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_crm_op_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_crm_op_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `empresas` (
  `id_empresa` int NOT NULL AUTO_INCREMENT,
  `razon_social` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ruc` varchar(11) COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'assets/img/logo-default.png',
  `nombre_gerente` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cargo_gerente` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cel_gerente` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `es_principal` tinyint(1) DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_empresa`),
  UNIQUE KEY `uk_empresas_ruc` (`ruc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `helpdesk_atenciones` (
  `id_atencion` int NOT NULL AUTO_INCREMENT,
  `codigo_ticket` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_empresa` int NOT NULL,
  `id_cliente` int NOT NULL,
  `id_contacto` int DEFAULT NULL,
  `tipo_usuario` enum('CORPORATIVO','PARTICULAR') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CORPORATIVO',
  `documento_solicitante` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_solicitante` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categoria` enum('HARDWARE','SOFTWARE','REDES_CONECTIVIDAD','SEGURIDAD','SISTEMAS','OTROS') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SOFTWARE',
  `id_subcategoria` int DEFAULT NULL,
  `canal_atencion` enum('LLAMADA','ACCESO_REMOTO','PRESENCIAL','CORREO','WHATSAPP') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'LLAMADA',
  `id_tecnico` int NOT NULL,
  `descripcion_requerimiento` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `acciones_realizadas` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado_cierre` enum('CONCLUIDO','EN_PROCESO','ESCALADO_N2','ESPERA_REPUESTO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CONCLUIDO',
  `nivel_satisfaccion` tinyint(1) DEFAULT NULL,
  `acciones_programadas` text COLLATE utf8mb4_unicode_ci,
  `fecha_proxima_revision` datetime DEFAULT NULL,
  `id_tecnico_seguimiento` int DEFAULT NULL,
  `fecha_atencion` date NOT NULL,
  `hora_inicio` datetime NOT NULL,
  `hora_fin` datetime NOT NULL,
  `tiempo_pausa_segundos` int DEFAULT '0',
  `tiempo_efectivo_segundos` int NOT NULL,
  `tiempo_efectivo_horas` decimal(6,2) GENERATED ALWAYS AS ((`tiempo_efectivo_segundos` / 3600.0)) STORED,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_atencion`),
  UNIQUE KEY `uk_codigo_ticket` (`codigo_ticket`),
  KEY `idx_hd_empresa` (`id_empresa`),
  KEY `idx_hd_cliente` (`id_cliente`),
  KEY `idx_hd_tecnico` (`id_tecnico`),
  KEY `idx_hd_fecha` (`fecha_atencion`),
  KEY `fk_hd_contactos` (`id_contacto`),
  KEY `fk_hd_usuarios_seguimiento` (`id_tecnico_seguimiento`),
  KEY `fk_hd_subcategoria` (`id_subcategoria`),
  CONSTRAINT `fk_hd_clientes` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON UPDATE CASCADE,
  CONSTRAINT `fk_hd_contactos` FOREIGN KEY (`id_contacto`) REFERENCES `cliente_contactos` (`id_contacto`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_hd_empresas` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_hd_subcategoria` FOREIGN KEY (`id_subcategoria`) REFERENCES `helpdesk_categorias` (`id_categoria`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_hd_usuarios_seguimiento` FOREIGN KEY (`id_tecnico_seguimiento`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_hd_usuarios_tecnico` FOREIGN KEY (`id_tecnico`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `helpdesk_categorias` (
  `id_categoria` int NOT NULL AUTO_INCREMENT,
  `id_padre` int DEFAULT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_categoria`),
  KEY `fk_hd_cat_padre` (`id_padre`),
  CONSTRAINT `fk_hd_cat_padre` FOREIGN KEY (`id_padre`) REFERENCES `helpdesk_categorias` (`id_categoria`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `helpdesk_pausas` (
  `id_pausa` int NOT NULL AUTO_INCREMENT,
  `id_atencion` int NOT NULL,
  `hora_inicio_pausa` datetime NOT NULL,
  `hora_fin_pausa` datetime DEFAULT NULL,
  `motivo_pausa` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT 'Pausa en la atención',
  PRIMARY KEY (`id_pausa`),
  KEY `fk_pausas_atencion` (`id_atencion`),
  CONSTRAINT `fk_pausas_atencion` FOREIGN KEY (`id_atencion`) REFERENCES `helpdesk_atenciones` (`id_atencion`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `marcas` (
  `id_marca` int NOT NULL AUTO_INCREMENT,
  `nombre_marca` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_marca`),
  UNIQUE KEY `nombre_marca` (`nombre_marca`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `modulos` (
  `id_modulo` int NOT NULL AUTO_INCREMENT,
  `id_padre` int DEFAULT NULL,
  `clave` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `icono` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'fa-folder',
  `orden` int DEFAULT '0',
  PRIMARY KEY (`id_modulo`),
  UNIQUE KEY `clave` (`clave`),
  KEY `fk_modulos_padre` (`id_padre`),
  CONSTRAINT `fk_modulos_padre` FOREIGN KEY (`id_padre`) REFERENCES `modulos` (`id_modulo`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `movimientos` (
  `id_movimiento` int NOT NULL AUTO_INCREMENT,
  `id_producto` int NOT NULL,
  `id_almacen` int NOT NULL,
  `id_almacen_origen` int NOT NULL,
  `id_almacen_destino` int DEFAULT NULL,
  `id_usuario` int NOT NULL,
  `tipo_movimiento` enum('ENTRADA','SALIDA','TRANSFERENCIA','AJUSTE') COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `costo_unitario` decimal(10,2) DEFAULT '0.00',
  `fecha_movimiento` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `referencia` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_movimiento`),
  KEY `idx_movimientos_producto` (`id_producto`),
  KEY `idx_movimientos_almacen_origen` (`id_almacen_origen`),
  KEY `idx_movimientos_almacen_destino` (`id_almacen_destino`),
  KEY `idx_movimientos_usuario` (`id_usuario`),
  KEY `fk_movimientos_almacenes` (`id_almacen`),
  CONSTRAINT `fk_movimientos_almacenes` FOREIGN KEY (`id_almacen`) REFERENCES `almacenes` (`id_almacen`),
  CONSTRAINT `fk_movimientos_almacenes_destino` FOREIGN KEY (`id_almacen_destino`) REFERENCES `almacenes` (`id_almacen`) ON UPDATE CASCADE,
  CONSTRAINT `fk_movimientos_almacenes_origen` FOREIGN KEY (`id_almacen_origen`) REFERENCES `almacenes` (`id_almacen`) ON UPDATE CASCADE,
  CONSTRAINT `fk_movimientos_productos` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON UPDATE CASCADE,
  CONSTRAINT `fk_movimientos_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `productos` (
  `id_producto` int NOT NULL AUTO_INCREMENT,
  `tipo` enum('PRODUCTO','SERVICIO') COLLATE utf8mb4_unicode_ci DEFAULT 'PRODUCTO',
  `id_categoria` int DEFAULT NULL,
  `codigo_interno` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo_proveedor` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre_producto` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `numero_parte` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marca` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modelo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unidad_medida` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `moneda` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT 'PEN',
  `precio_costo` decimal(12,2) DEFAULT '0.00',
  `porcentaje_utilidad` decimal(5,2) DEFAULT '0.00',
  `precio_venta` decimal(12,2) DEFAULT '0.00',
  `codigo_barra` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_producto`),
  UNIQUE KEY `uk_productos_codigo_interno` (`codigo_interno`),
  KEY `idx_productos_categoria` (`id_categoria`),
  CONSTRAINT `fk_productos_categorias` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `series_productos` (
  `id_serie` int NOT NULL AUTO_INCREMENT,
  `id_producto` int NOT NULL,
  `id_almacen` int NOT NULL,
  `numero_serie` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('DISPONIBLE','VENDIDO','BAJA','TRANSITO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DISPONIBLE',
  `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_serie`),
  UNIQUE KEY `uk_series_numero_serie` (`numero_serie`),
  KEY `idx_series_producto` (`id_producto`),
  KEY `idx_series_almacen` (`id_almacen`),
  CONSTRAINT `fk_series_almacenes` FOREIGN KEY (`id_almacen`) REFERENCES `almacenes` (`id_almacen`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_series_productos` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `stock` (
  `id_stock` int NOT NULL AUTO_INCREMENT,
  `id_producto` int NOT NULL,
  `id_almacen` int NOT NULL,
  `cantidad_actual` decimal(10,2) NOT NULL DEFAULT '0.00',
  `stock_minimo` decimal(10,2) DEFAULT '0.00',
  `stock_maximo` decimal(10,2) DEFAULT '0.00',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_stock`),
  UNIQUE KEY `uk_stock_producto_almacen` (`id_producto`,`id_almacen`),
  KEY `idx_stock_almacen` (`id_almacen`),
  CONSTRAINT `fk_stock_almacenes` FOREIGN KEY (`id_almacen`) REFERENCES `almacenes` (`id_almacen`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_productos` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tipo_cambio` (
  `id_tc` int NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `compra` decimal(10,4) NOT NULL,
  `venta` decimal(10,4) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tc`),
  UNIQUE KEY `fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usuario_permisos` (
  `id_usuario` int NOT NULL,
  `id_modulo` int NOT NULL,
  PRIMARY KEY (`id_usuario`,`id_modulo`),
  KEY `id_modulo` (`id_modulo`),
  CONSTRAINT `usuario_permisos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  CONSTRAINT `usuario_permisos_ibfk_2` FOREIGN KEY (`id_modulo`) REFERENCES `modulos` (`id_modulo`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `usuarios` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `id_empresa` int NOT NULL,
  `nombre_usuario` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `cargo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `celular` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `firma_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `uk_usuarios_email` (`email`),
  KEY `fk_usuarios_empresas` (`id_empresa`),
  CONSTRAINT `fk_usuarios_empresas` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `venta_detalles` (
  `id_detalle` int NOT NULL AUTO_INCREMENT,
  `id_venta` int NOT NULL,
  `id_producto` int DEFAULT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `marca` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unidad_medida` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'UND',
  `cantidad` decimal(10,2) NOT NULL DEFAULT '1.00',
  `precio_unitario` decimal(12,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id_detalle`),
  KEY `idx_vdetalle_venta` (`id_venta`),
  KEY `idx_vdetalle_producto` (`id_producto`),
  CONSTRAINT `fk_vdetalle_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_vdetalle_venta` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `venta_pagos` (
  `id_pago` int NOT NULL AUTO_INCREMENT,
  `id_venta` int NOT NULL,
  `id_usuario` int NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `fecha_pago` date NOT NULL,
  `metodo_pago` enum('TRANSFERENCIA','EFECTIVO','YAPE_PLIN','TARJETA','CHEQUE','OTROS') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TRANSFERENCIA',
  `referencia_operacion` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `banco` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notas` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pago`),
  KEY `idx_vpagos_venta` (`id_venta`),
  KEY `idx_vpagos_usuario` (`id_usuario`),
  CONSTRAINT `fk_vpagos_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE,
  CONSTRAINT `fk_vpagos_venta` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ventas` (
  `id_venta` int NOT NULL AUTO_INCREMENT,
  `id_empresa` int NOT NULL,
  `id_cliente` int NOT NULL,
  `id_cotizacion` int DEFAULT NULL,
  `id_usuario` int NOT NULL,
  `tipo_documento` enum('FACTURA','BOLETA','NOTA_VENTA') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'FACTURA',
  `serie` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `correlativo` int NOT NULL,
  `serie_correlativo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `moneda` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PEN',
  `tipo_cambio` decimal(6,3) DEFAULT '1.000',
  `forma_pago` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Contado',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `igv` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_pagado` decimal(12,2) NOT NULL DEFAULT '0.00',
  `saldo_pendiente` decimal(12,2) NOT NULL DEFAULT '0.00',
  `estado_pago` enum('PENDIENTE','PARCIAL','PAGADO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDIENTE',
  `estado_anulado` tinyint(1) NOT NULL DEFAULT '0',
  `fecha_emision` date NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_venta`),
  UNIQUE KEY `uk_venta_serie_correlativo` (`id_empresa`,`tipo_documento`,`serie`,`correlativo`),
  KEY `idx_ventas_empresa` (`id_empresa`),
  KEY `idx_ventas_cliente` (`id_cliente`),
  KEY `idx_ventas_cotizacion` (`id_cotizacion`),
  KEY `idx_ventas_usuario` (`id_usuario`),
  KEY `idx_ventas_fecha` (`fecha_emision`),
  CONSTRAINT `fk_ventas_clientes` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ventas_cotizaciones` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizaciones` (`id_cotizacion`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ventas_empresas` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ventas_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;;
