<?php
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre_categoria']);
    $descripcion = trim($_POST['descripcion']);

    if (!empty($nombre)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO categorias (nombre_categoria, descripcion) VALUES (:nombre, :descripcion)");
            $stmt->execute([
                ':nombre' => $nombre,
                ':descripcion' => $descripcion
            ]);
        } catch (\PDOException $e) {
            die("Error al guardar la categoría: " . $e->getMessage());
        }
    }
}

// Redireccionar al listado de categorías
header('Location: index.php');
exit;