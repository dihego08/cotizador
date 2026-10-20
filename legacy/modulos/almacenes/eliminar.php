<?php
require_once '../../config/db.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM almacenes WHERE id_almacen = :id");
        $stmt->execute([':id' => $id]);
    } catch (\PDOException $e) {
        die("No se puede eliminar este almacén porque tiene stock o movimientos registrados.");
    }
}

header('Location: index.php');
exit;