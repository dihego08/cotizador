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

## 🔐 Acceso

Las credenciales no se guardan en el repositorio. Para recuperar el acceso de un usuario use
"¿Olvidaste tu contraseña?" en el login (requiere el correo configurado, ver Despliegue).

---

## 🏢 Modelo multiempresa (SaaS)

```
Cuenta de plataforma (única, "Administración de la plataforma")
 └─ Usuarios SUPERADMIN: solo el panel Plataforma → Cuentas de Clientes

Cuenta (cliente que contrata el sistema)
 ├─ Empresas (razones sociales / RUC)
 ├─ Usuarios
 └─ Clientes, categorías, marcas, tipo de cambio
      └─ cada empresa: productos, almacenes, cotizaciones, ventas, CRM, helpdesk
```

- **Aislamiento automático**: los modelos usan `PerteneceACuenta` / `PerteneceACuentaPorPadre`
  (`backend/app/Models/Concerns`). Toda consulta se filtra por la cuenta del usuario autenticado,
  que define el middleware `cuenta`. Para validar ids recibidos use `new ExisteEnCuenta(Modelo::class)`
  en lugar de la regla `exists` (la regla nativa no aplica el filtro).
- **Roles**: `SUPERADMIN` (dueño de la plataforma), `ADMIN` (administrador de su cuenta) y roles con
  permisos por módulo. El SUPERADMIN solo existe en la cuenta de plataforma y solo administra cuentas:
  los módulos del negocio le responden 403 (middleware `cuenta.cliente`). Si el dueño también usa el
  sistema para su propio negocio, lo hace con otro usuario en una cuenta de cliente normal.
- **Permisos**: se validan en el servidor con el middleware `permiso:{clave}` (ver `routes/api.php`).

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

## 🧪 Pruebas

Las pruebas usan MySQL/MariaDB (base `sistema_almacen_test`, se crea vacía) y corren en GitHub Actions en cada push.
Incluyen las pruebas de aislamiento entre cuentas: **si alguna falla, hay datos expuestos entre clientes.**

```bash
cd backend
php artisan test              # DB_PORT=3307 php artisan test para otro puerto
```

---

## 🚢 Despliegue en Producción

Los cambios de base de datos se hacen con **migraciones de Laravel** (`backend/database/migrations`).
Los scripts `.sql` de la raíz quedan solo como historial: **no los ejecute más**.

### Primera vez con migraciones (base de datos existente)

1. **Respalde la base de datos** (exportación completa desde phpMyAdmin).
2. Suba el código y en `backend/` ejecute:
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan db:seed --force          # catálogo de módulos y permisos (no duplica)
   php artisan config:cache && php artisan route:cache
   ```
   La migración `esquema_base` detecta que las tablas ya existen y no las toca; las siguientes crean
   la tabla `cuentas` y asignan todos los datos actuales a una primera cuenta.

### Instalación nueva (base de datos vacía)

```bash
php artisan migrate --seed --force
```
Luego cree el super administrador (crea también la cuenta de plataforma y muestra una contraseña temporal):
```bash
php artisan plataforma:crear-superadmin su-correo@dominio.com --nombre="Su Nombre"
```
Las cuentas de clientes se crean desde el panel *Plataforma*.

### Actualizaciones siguientes

```bash
php artisan down
git pull && composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache
php artisan up
```

### Variables de entorno de producción (`backend/.env`)

| Variable | Valor |
| :--- | :--- |
| `APP_ENV` / `APP_DEBUG` | `production` / **`false`** (con `true` los errores muestran datos internos) |
| `FRONTEND_URL` | URL del frontend, para los enlaces de los correos |
| `SANCTUM_EXPIRATION` | Minutos de vida de una sesión (por defecto 720 = 12 h) |
| `MAIL_*` | SMTP o servicio de correo, necesario para recuperar contraseñas |
| `ARCHIVOS_DISK` | `public` (por defecto) o `s3` para logos y adjuntos |
| `CACHE_STORE` | `database` (la tabla `cache` la crea `migrate`; la usa el límite de intentos de login) |

### Tareas programadas

Configure el cron de Laravel (borra las sesiones vencidas):
```
* * * * * cd /ruta/backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🗄️ Historial: scripts SQL anteriores a las migraciones

Estos scripts ya están incluidos en `backend/database/schema/esquema_base.sql`. Se conservan solo como referencia:

### 1. Documentos de Venta y Cobranzas
📄 [actualizacion_ventas_cobranzas.sql](actualizacion_ventas_cobranzas.sql)
- Crea `personal_access_tokens`, `ventas`, `venta_detalles` y `venta_pagos`.

### 2. Mini-CRM y Pipeline de Tratos
📄 [actualizacion_crm_pipeline.sql](actualizacion_crm_pipeline.sql)
- Agrega campos `estado_crm` y `origen_lead` a `clientes`.
- Crea `crm_oportunidades` (tratos, valores estimados, etapas, prioridades y probabilidades).
- Crea `crm_actividades` (bitácora de llamadas, reuniones, WhatsApp y notas de seguimiento).
