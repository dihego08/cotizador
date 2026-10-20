<<?php
session_start();
require_once 'config/db.php';

// Si ya inició sesión con empresa válida, enviarlo al Dashboard
if (isset($_SESSION['usuario_id']) && !empty($_SESSION['id_empresa'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Validar si el usuario existe y la contraseña es correcta
        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            
            $idEmpresaUser = intval($usuario['id_empresa'] ?? 0);
            $nombreUser    = $usuario['nombre_usuario'] ?? $usuario['nombre'] ?? 'Usuario';

            // 🛑 CONTROL DE SEGURIDAD STRICTO
            // Si el usuario no tiene empresa asignada, BARRERA DE SEGURIDAD.
            if ($idEmpresaUser <= 0) {
                $error = "¡Hola, <strong>" . htmlspecialchars($nombreUser) . "</strong>! Tus accesos aún no han sido habilitados completamente. Por favor, contacta al administrador para que asignen tu empresa al sistema.";
            } else {
                // 🟢 ACCESO CONCEDIDO: El usuario sí tiene una empresa asignada
                $_SESSION['usuario_id']     = $usuario['id_usuario'];
                $_SESSION['id_usuario']      = $usuario['id_usuario'];
                $_SESSION['usuario_nombre']  = $nombreUser;
                $_SESSION['usuario_rol']     = $usuario['rol'] ?? 'usuario';
                $_SESSION['id_empresa']      = $idEmpresaUser; // ID nativo asignado estrictamente en la BD
                $_SESSION['usuario_cargo']   = $usuario['cargo'] ?? 'ASESOR COMERCIAL';
                $_SESSION['usuario_celular'] = $usuario['celular'] ?? '';

                // Cargar Módulos Autorizados según la tabla `usuario_permisos`
                $stmtPermisos = $pdo->prepare("
                    SELECT m.clave 
                    FROM usuario_permisos up
                    JOIN modulos m ON up.id_modulo = m.id_modulo
                    WHERE up.id_usuario = :id
                ");
                $stmtPermisos->execute([':id' => $usuario['id_usuario']]);
                $_SESSION['permisos'] = $stmtPermisos->fetchAll(PDO::FETCH_COLUMN);

                header('Location: index.php');
                exit;
            }

        } else {
            $error = 'Correo electrónico o contraseña incorrectos.';
        }
    } else {
        $error = 'Por favor, ingresa tu correo y contraseña.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Cotizaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f4f6f9;
        }
        .login-card {
            max-width: 400px;
            margin: 80px auto;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card login-card shadow-lg border-0 rounded-3">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <i class="fa-solid fa-boxes-stacked fa-3x text-primary mb-2"></i>
                <h4 class="fw-bold">Gestión de Almacén</h4>
                <p class="text-muted small">Ingresa tus credenciales para continuar</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 small" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Correo Electrónico</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" required placeholder="admin@almacen.com">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                    <i class="fa-solid fa-right-to-bracket me-1"></i> Iniciar Sesión
                </button>
            </form>
        </div>
    </div>
</div>

</body>
</html>