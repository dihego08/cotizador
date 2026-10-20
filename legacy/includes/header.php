<?php 
require_once __DIR__ . '/auth.php'; 

// 1. OBTENER INFORMACIÓN DE LA EMPRESA ASOCIADA AL USUARIO LOGUEADO
$empresa_nombre = 'Mi Almacén';
$empresa_logo   = null;

// Intentar cargar la conexión a la BD si aún no está disponible
if (!isset($pdo)) {
    $ruta_db = __DIR__ . '/../config/db.php';
    if (file_exists($ruta_db)) {
        require_once $ruta_db;
    }
}

if (isset($pdo)) {
    // Obtener id_empresa de la sesión o del usuario activo
    $id_empresa = $_SESSION['id_empresa'] ?? null;

    if (!$id_empresa && (isset($_SESSION['usuario_id']) || isset($_SESSION['id_usuario']))) {
        $id_usuario = $_SESSION['usuario_id'] ?? $_SESSION['id_usuario'];
        $stmtUsr = $pdo->prepare("SELECT id_empresa FROM usuarios WHERE id_usuario = :id LIMIT 1");
        $stmtUsr->execute([':id' => $id_usuario]);
        $usrData = $stmtUsr->fetch(PDO::FETCH_ASSOC);
        if ($usrData) {
            $id_empresa = $usrData['id_empresa'];
            $_SESSION['id_empresa'] = $id_empresa;
        }
    }

    // Consultar razon_social y logo_path de la tabla empresas
    if ($id_empresa) {
        $stmtEmp = $pdo->prepare("SELECT razon_social, logo_path FROM empresas WHERE id_empresa = :id LIMIT 1");
        $stmtEmp->execute([':id' => $id_empresa]);
        $empresa = $stmtEmp->fetch(PDO::FETCH_ASSOC);

        if ($empresa) {
            $empresa_nombre = $empresa['razon_social'];
            $empresa_logo   = $empresa['logo_path'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Almacén</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome para íconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .navbar-nav .nav-link {
            white-space: nowrap;
        }
        .logo-empresa-header {
            max-height: 38px;
            max-width: 150px;
            object-fit: contain;
            background-color: #ffffff;
            padding: 2px 6px;
            border-radius: 4px;
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm">
  <div class="container-fluid">
    <!-- MARCA / LOGO Y NOMBRE DE LA EMPRESA DINÁMICO -->
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="/sistema_almacen/index.php">
        <?php 
        $ruta_logo_absoluta = $_SERVER['DOCUMENT_ROOT'] . '/sistema_almacen/' . $empresa_logo;
        if (!empty($empresa_logo) && file_exists($ruta_logo_absoluta)): 
        ?>
            <img src="/sistema_almacen/<?= htmlspecialchars($empresa_logo) ?>" 
                 alt="Logo Empresa" 
                 class="logo-empresa-header">
        <?php else: ?>
            <i class="fa-solid fa-boxes-stacked text-warning"></i>
        <?php endif; ?>
        <span><?= htmlspecialchars($empresa_nombre) ?></span>
    </a>
    
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
      <!-- Menú principal (Izquierda) -->
      <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-lg-center">
        
        <!-- Dashboard / Inicio -->
        <li class="nav-item">
            <a class="nav-link" href="/sistema_almacen/index.php">
                <i class="fa-solid fa-chart-line me-1"></i> Dashboard
            </a>
        </li>

        <!-- 1. MÓDULO OPERACIONES -->
        <?php if (tienePermiso('cotizaciones') || tienePermiso('clientes') || tienePermiso('helpdesk')): ?>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarOperaciones" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-briefcase me-1"></i> Operaciones
                </a>
                <ul class="dropdown-menu shadow border-0" aria-labelledby="navbarOperaciones">
                    <?php if (tienePermiso('cotizaciones')): ?>
                        <li>
                            <a class="dropdown-item py-2" href="/sistema_almacen/modulos/cotizaciones/index.php">
                                <i class="fa-solid fa-file-invoice-dollar me-2 text-secondary"></i>Cotizaciones
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (tienePermiso('clientes')): ?>
                        <li>
                            <a class="dropdown-item py-2" href="/sistema_almacen/modulos/clientes/index.php">
                                <i class="fa-solid fa-users me-2 text-secondary"></i>Clientes
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (tienePermiso('helpdesk')): ?>
                        <li>
                            <a class="dropdown-item py-2" href="/sistema_almacen/modulos/helpdesk/index.php">
                                <i class="fa-solid fa-headset me-2 text-primary"></i>Help Desk & Soporte
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <!-- 2. MÓDULO ALMACÉN -->
        <?php if (tienePermiso('productos') || tienePermiso('categorias_marcas') || tienePermiso('stock') || tienePermiso('movimientos')): ?>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarAlmacen" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-warehouse me-1"></i> Almacén
                </a>
                <ul class="dropdown-menu shadow border-0" aria-labelledby="navbarAlmacen">
                    <?php if (tienePermiso('productos')): ?>
                        <li>
                            <a class="dropdown-item py-2" href="/sistema_almacen/modulos/productos/index.php">
                                <i class="fa-solid fa-boxes-stacked me-2 text-secondary"></i>Productos y Servicios
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (tienePermiso('categorias_marcas')): ?>
                        <li>
                            <a class="dropdown-item py-2" href="/sistema_almacen/modulos/categorias/index.php">
                                <i class="fa-solid fa-layer-group me-2 text-secondary"></i>Categorías
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="/sistema_almacen/modulos/marcas/index.php">
                                <i class="fa-solid fa-tags me-2 text-secondary"></i>Marcas
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (tienePermiso('stock')): ?>
                        <li>
                            <a class="dropdown-item py-2" href="/sistema_almacen/modulos/stock/index.php">
                                <i class="fa-solid fa-chart-simple me-2 text-secondary"></i>Control de Stock
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (tienePermiso('movimientos')): ?>
                        <li>
                            <a class="dropdown-item py-2" href="/sistema_almacen/modulos/movimientos/index.php">
                                <i class="fa-solid fa-arrow-right-arrow-left me-2 text-secondary"></i>Movimientos
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <!-- 3. MÓDULO FINANZAS -->
        <?php if (tienePermiso('tipo_cambio')): ?>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarFinanzas" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-wallet me-1"></i> Finanzas
                </a>
                <ul class="dropdown-menu shadow border-0" aria-labelledby="navbarFinanzas">
                    <?php if (tienePermiso('tipo_cambio')): ?>
                        <li>
                            <a class="dropdown-item py-2" href="/sistema_almacen/modulos/configuracion/tipo_cambio.php">
                                <i class="fa-solid fa-dollar-sign me-2 text-secondary"></i>Tipo de Cambio
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <!-- 4. MÓDULO GESTIÓN CORPORATIVA -->
        <?php if (tienePermiso('corp_gerencia') || tienePermiso('corp_control') || tienePermiso('corp_admin')): ?>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle fw-semibold text-warning" href="#" id="navbarCorporativa" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-chart-pie me-1"></i> Gestión Corporativa
                </a>
                <ul class="dropdown-menu shadow border-0" aria-labelledby="navbarCorporativa">
                    <?php if (tienePermiso('corp_gerencia')): ?>
                        <li>
                            <a class="dropdown-item py-2" href="/sistema_almacen/modulos/corporativa/gerencia.php">
                                <i class="fa-solid fa-user-tie me-2 text-primary"></i>Gerencia y Dirección
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (tienePermiso('corp_control')): ?>
                        <li>
                            <a class="dropdown-item py-2" href="/sistema_almacen/modulos/corporativa/control.php">
                                <i class="fa-solid fa-shield-halved me-2 text-success"></i>Control de Gestión
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (tienePermiso('corp_admin')): ?>
                        <li>
                            <a class="dropdown-item py-2" href="/sistema_almacen/modulos/corporativa/admin.php">
                                <i class="fa-solid fa-file-invoice me-2 text-info"></i>Administración General
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <!-- 5. MÓDULO ADMIN SISTEMA -->
        <?php if (tienePermiso('usuarios') || tienePermiso('empresas')): ?>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarAdminSistema" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-user-shield me-1"></i> Admin Sistema
                </a>
                <ul class="dropdown-menu shadow border-0" aria-labelledby="navbarAdminSistema">
                    <?php if (tienePermiso('usuarios')): ?>
                        <li>
                            <a class="dropdown-item py-2" href="/sistema_almacen/modulos/usuarios/index.php">
                                <i class="fa-solid fa-users-gear me-2 text-secondary"></i>Gestión de Usuarios
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (tienePermiso('empresas')): ?>
                        <li>
                            <a class="dropdown-item py-2" href="/sistema_almacen/modulos/empresas/index.php">
                                <i class="fa-solid fa-building me-2 text-secondary"></i>Gestión de Empresas
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

      </ul>

      <!-- Zona del Usuario y Logout (Derecha) -->
      <ul class="navbar-nav ms-auto align-items-center">
        <?php if (isset($_SESSION['usuario_nombre'])): ?>
            <li class="nav-item me-3 text-light small">
                <i class="fa-solid fa-circle-user me-1"></i>
                <strong><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></strong>
                <span class="badge bg-secondary ms-1"><?= htmlspecialchars($_SESSION['usuario_rol'] ?? '') ?></span>
            </li>
        <?php endif; ?>
        <li class="nav-item">
            <a class="btn btn-outline-danger btn-sm" href="/sistema_almacen/logout.php" title="Cerrar Sesión">
                <i class="fa-solid fa-right-from-bracket me-1"></i> Salir
            </a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container">