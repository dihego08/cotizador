<?php
require_once '../../config/db.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM categorias WHERE id_categoria = :id");
        $stmt->execute([':id' => $id]);
    } catch (\PDOException $e) {
        die("No se puede eliminar la categoría porque podría tener productos asociados.");
    }
}

header('Location: index.php');
exit;