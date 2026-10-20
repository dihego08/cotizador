<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

$id     = intval($_GET['id'] ?? 0);
$estado = $_GET['estado'] ?? '';

$estadosValidos = ['PENDIENTE', 'APROBADA', 'RECHAZADA'];

if ($id > 0 && in_array($estado, $estadosValidos)) {
    try {
        $stmt = $pdo->prepare("UPDATE cotizaciones SET estado = :estado WHERE id_cotizacion = :id");
        $stmt->execute([
            ':estado' => $estado,
            ':id'     => $id
        ]);
    } catch (PDOException $e) {
        die("Error al actualizar estado: " . $e->getMessage());
    }
}

header('Location: index.php?msg=updated');
exit;