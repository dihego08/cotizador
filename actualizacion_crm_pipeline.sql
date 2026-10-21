-- =====================================================================
-- ACTUALIZACIÓN DE BASE DE DATOS: MINI-CRM Y PIPELINE DE OPORTUNIDADES
-- Base de Datos: sistema_almacen / u622044135_sis_almacen
-- =====================================================================

-- 1. Campos adicionales para clasificar clientes en el CRM
ALTER TABLE `clientes` 
  ADD COLUMN `estado_crm` ENUM('PROSPECTO', 'ACTIVO', 'INACTIVO') NOT NULL DEFAULT 'ACTIVO' AFTER `es_cliente_helpdesk`,
  ADD COLUMN `origen_lead` VARCHAR(50) DEFAULT 'DIRECTO' AFTER `estado_crm`;

-- 2. Tabla Principal de Oportunidades Comerciales (Deals / Pipeline)
CREATE TABLE IF NOT EXISTS `crm_oportunidades` (
  `id_oportunidad` int(11) NOT NULL AUTO_INCREMENT,
  `id_empresa` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_cotizacion` int(11) DEFAULT NULL,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `valor_estimado` decimal(12,2) NOT NULL DEFAULT 0.00,
  `moneda` varchar(5) NOT NULL DEFAULT 'USD',
  `etapa` enum('PROSPECTO','CONTACTADO','PROPUESTA','NEGOCIACION','GANADO','PERDIDO') NOT NULL DEFAULT 'PROSPECTO',
  `probabilidad` tinyint(3) NOT NULL DEFAULT 20,
  `prioridad` enum('BAJA','MEDIA','ALTA') NOT NULL DEFAULT 'MEDIA',
  `fecha_cierre_estimada` date DEFAULT NULL,
  `motivo_perdida` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_oportunidad`),
  KEY `idx_crm_op_empresa` (`id_empresa`),
  KEY `idx_crm_op_cliente` (`id_cliente`),
  KEY `idx_crm_op_usuario` (`id_usuario`),
  KEY `idx_crm_op_cotizacion` (`id_cotizacion`),
  KEY `idx_crm_op_etapa` (`etapa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla de Bitácora de Actividades e Interacciones (Notas, Llamadas, WhatsApp, etc. con soporte de archivos adjuntos PDF/imágenes)
CREATE TABLE IF NOT EXISTS `crm_actividades` (
  `id_actividad` int(11) NOT NULL AUTO_INCREMENT,
  `id_empresa` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_oportunidad` int(11) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  `tipo` enum('LLAMADA','REUNION','WHATSAPP','CORREO','NOTA') NOT NULL DEFAULT 'NOTA',
  `titulo` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `archivo_path` varchar(255) DEFAULT NULL,
  `archivo_nombre` varchar(255) DEFAULT NULL,
  `archivo_size` int(11) DEFAULT NULL,
  `fecha_actividad` datetime NOT NULL DEFAULT current_timestamp(),
  `proximo_seguimiento` date DEFAULT NULL,
  `completado` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_actividad`),
  KEY `idx_crm_act_empresa` (`id_empresa`),
  KEY `idx_crm_act_cliente` (`id_cliente`),
  KEY `idx_crm_act_oportunidad` (`id_oportunidad`),
  KEY `idx_crm_act_usuario` (`id_usuario`),
  KEY `idx_crm_act_fecha` (`fecha_actividad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- En caso de que ya hayas ejecutado el script y la tabla crm_actividades ya exista,
-- ejecuta estas líneas para añadir las columnas de adjuntos:
-- ALTER TABLE `crm_actividades`
--   ADD COLUMN `archivo_path` varchar(255) DEFAULT NULL AFTER `descripcion`,
--   ADD COLUMN `archivo_nombre` varchar(255) DEFAULT NULL AFTER `archivo_path`,
--   ADD COLUMN `archivo_size` int(11) DEFAULT NULL AFTER `archivo_nombre`;

-- 4. Llaves Foráneas de Integridad
ALTER TABLE `crm_oportunidades`
  ADD CONSTRAINT `fk_crm_op_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_crm_op_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_crm_op_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_crm_op_cotizacion` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizaciones` (`id_cotizacion`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `crm_actividades`
  ADD CONSTRAINT `fk_crm_act_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresas` (`id_empresa`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_crm_act_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_crm_act_oportunidad` FOREIGN KEY (`id_oportunidad`) REFERENCES `crm_oportunidades` (`id_oportunidad`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_crm_act_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE;
