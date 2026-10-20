<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si no existe la sesión de usuario, redirigir al login
if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['id_usuario'])) {
    header('Location: /sistema_almacen/login.php');
    exit;
}

/**
 * Verifica si el usuario logueado tiene permiso para acceder a un módulo.
 * El admin siempre tiene acceso global.
 */
if (!function_exists('tienePermiso')) {
    function tienePermiso($moduloClave) {
        $rol = strtolower($_SESSION['usuario_rol'] ?? '');
        
        // Si es Admin, tiene acceso total
        if ($rol === 'admin' || $rol === 'administrador') {
            return true;
        }
        
        $permisos = $_SESSION['permisos'] ?? [];
        return in_array($moduloClave, $permisos);
    }
}

/**
 * Bloquea el acceso a páginas específicas si el usuario no tiene el módulo asignado.
 */
if (!function_exists('requerirPermiso')) {
    function requerirPermiso($moduloClave) {
        if (!tienePermiso($moduloClave)) {
            header('Location: /sistema_almacen/index.php?error=acceso_denegado');
            exit;
        }
    }
}
?>