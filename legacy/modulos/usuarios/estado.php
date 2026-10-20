<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

requerirPermiso('usuarios');

$id_usuario = intval($_GET['id'] ?? 0);
$estado     = intval($_GET['estado'] ?? 1);

if ($id_usuario > 0) {
    // Evitar que el usuario logueado se desactive a sí mismo
    if ($id_usuario == $_SESSION['usuario_id'] && $estado == 0) {
        header('Location: index.php?msg=self_toggle_error');
        exit;
    }

    $stmt = $pdo->prepare("UPDATE usuarios SET activo = :estado WHERE id_usuario = :id");
    $stmt->execute([':estado' => $estado, ':id' => $id_usuario]);
}

header('Location: index.php?msg=status_changed');
exit;