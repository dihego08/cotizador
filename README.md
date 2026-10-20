# Migración Sistema de Almacén e Inventarios: Laravel + React

Sistema corporativo migrado desde la versión PHP procedural legacy a una arquitectura moderna desacoplada: **Backend API RESTful en Laravel 12 (PHP 8.2)** + **Frontend SPA en React 19 (Vite + Tailwind CSS v4)**.

---

## 📁 Estructura del Repositorio

- **`legacy/`**: Código fuente original PHP procedural preservado para consulta histórica.
- **`backend/`**: API RESTful completa desarrollada en Laravel con autenticación por tokens Sanctum.
- **`frontend/`**: Aplicación de página única (SPA) desarrollada en React + Vite + Tailwind CSS + Lucide Icons.
- **`sistema_almacen 18-10-26.sql`**: Dump de base de datos MySQL con las 19 tablas relacionales y datos iniciales.

---

## 🚀 Cómo Ejecutar el Proyecto en Desarrollo

### 1. Iniciar el Backend (Laravel API)

Asegúrate de que tu servicio MySQL (WampServer o XAMPP) esté iniciado con la base de datos `sistema_almacen`.

```bash
cd backend
php artisan serve
```
> La API estará disponible en `http://127.0.0.1:8000/api`

### 2. Iniciar el Frontend (React + Vite)

En otra terminal:

```bash
cd frontend
npm run dev
```
> La aplicación web abrirá en `http://localhost:5173` y se comunicará automáticamente con el backend a través del proxy configurado.

---

## 🔐 Cuentas de Acceso Preconfiguradas

Las credenciales provienen de la base de datos importada (`sistema_almacen 18-10-26.sql`):

| Empresa | Correo | Contraseña | Rol |
| :--- | :--- | :--- | :--- |
| **RUVINDUSTRIAS S.A.C.** | `admin@ruvindustrias.com` | `admin123` | Administrador / Gerente |
| **Mi Empresa S.A.C.** | `admin@almacen.com` | `admin123` | Administrador |

---

## ⚡ Módulos Migrados

1. **Dashboard Ejecutivo**: Métricas en tiempo real, alertas de stock bajo, resumen de cotizaciones y últimos movimientos kardex.
2. **Productos y Servicios**: Catálogo con buscador en tiempo real, códigos de barra, calculadora interactiva de precios y márgenes de ganancia.
3. **Control de Stock**: Existencias por almacén, semáforo de alerta por stock mínimo y ajuste de límites.
4. **Kardex y Movimientos**: Entradas, Salidas, Transferencias y Ajustes con actualización transaccional atómica de stock.
5. **Trazabilidad de Series**: Registro individual de números de serie por producto y almacén con estados de ciclo de vida.
6. **Cotizaciones Comerciales**: Constructor de cotizaciones con ítems dinámicos, cálculo automático de IGV (18%), estados de aprobación y vista de impresión/PDF.
7. **Mesa de Ayuda & Helpdesk**: Registro de incidencias con cronómetro interactivo en vivo (iniciar, pausar, reanudar), canales de soporte y evaluación de satisfacción.
8. **Clientes & Contactos**: Directorio corporativo (DNI/RUC) y contactos vinculados.
9. **Mini-CRM & Pipeline de Oportunidades**: Embudo visual tipo Kanban con 6 etapas de venta (Prospecto, Contactado, Propuesta, Negociación, Ganado, Perdido), cálculo de valor en pipeline, bitácora cronológica de actividades (llamadas, reuniones, WhatsApp, notas) y conversión a cotización.
10. **Ventas y Cobranzas (Facturas / Boletas / Notas de Venta)**: Emisión directa o desde cotizaciones aprobadas, control de cobranzas por cuotas/amortizaciones parciales, semáforo de saldo pendiente y comprobante imprimible.
11. **Almacenes**: Sucursales físicas con responsables y empresas asignadas.
12. **Tipo de Cambio**: Histórico cambiario PEN/USD y widget para actualización diaria.
13. **Gestión de Empresas & Selector Multi-Empresa**: Soporte multi-empresa con switcher en el Navbar para emitir cotizaciones y ventas con los datos fiscales de la empresa seleccionada.
14. **Usuarios y Permisos**: Matriz de permisos granular por módulo y gestión de accesos.

---

## 🗄️ Actualizaciones de Base de Datos para Nuevos Módulos

Para habilitar los nuevos módulos en tu base de datos MySQL (local o en phpMyAdmin en Hostinger `u622044135_sis_almacen`), ejecuta los siguientes scripts SQL ubicados en la raíz del proyecto:

### 1. Documentos de Venta y Cobranzas
📄 [actualizacion_ventas_cobranzas.sql](actualizacion_ventas_cobranzas.sql)
- Crea `personal_access_tokens`, `ventas`, `venta_detalles` y `venta_pagos`.

### 2. Mini-CRM y Pipeline de Tratos
📄 [actualizacion_crm_pipeline.sql](actualizacion_crm_pipeline.sql)
- Agrega campos `estado_crm` y `origen_lead` a `clientes`.
- Crea `crm_oportunidades` (tratos, valores estimados, etapas, prioridades y probabilidades).
- Crea `crm_actividades` (bitácora de llamadas, reuniones, WhatsApp y notas de seguimiento).
