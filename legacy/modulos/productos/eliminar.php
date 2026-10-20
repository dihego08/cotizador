<?php
require_once '../../config/db.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM productos WHERE id_producto = :id");
        $stmt->execute([':id' => $id]);
    } catch (\PDOException $e) {
        die("No se puede eliminar este producto porque ya tiene registro de movimientos o stock asociado.");
    }
}

header('Location: index.php');
exit;