<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

$accion = $_GET['accion'] ?? '';

// 1. CREAR MARCA
if ($accion === 'crear' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre_marca'] ?? '');

    if (!empty($nombre)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO marcas (nombre_marca) VALUES (:nombre)");
            $stmt->execute([':nombre' => $nombre]);
            header('Location: index.php?msg=created');
            exit;
        } catch (PDOException $e) {
            die("Error al crear la marca: " . $e->getMessage());
        }
    }
}

// 2. EDITAR MARCA (Y SINCRO EN PRODUCTOS)
if ($accion === 'editar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_marca        = (int)($_POST['id_marca'] ?? 0);
    $nombre_anterior = trim($_POST['nombre_anterior'] ?? '');
    $nombre_nuevo    = trim($_POST['nombre_marca'] ?? '');

    if ($id_marca > 0 && !empty($nombre_nuevo)) {
        try {
            $pdo->beginTransaction();

            // Actualizar la marca en la tabla marcas
            $stmt1 = $pdo->prepare("UPDATE marcas SET nombre_marca = :nuevo WHERE id_marca = :id");
            $stmt1->execute([':nuevo' => $nombre_nuevo, ':id' => $id_marca]);

            // Actualizar el nombre en los productos existentes que la usaban
            if (!empty($nombre_anterior)) {
                $stmt2 = $pdo->prepare("UPDATE productos SET marca = :nuevo WHERE marca = :anterior");
                $stmt2->execute([':nuevo' => $nombre_nuevo, ':anterior' => $nombre_anterior]);
            }

            $pdo->commit();
            header('Location: index.php?msg=updated');
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            die("Error al actualizar la marca: " . $e->getMessage());
        }
    }
}

// 3. ELIMINAR MARCA
if ($accion === 'eliminar') {
    $id_marca = (int)($_GET['id'] ?? 0);

    if ($id_marca > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM marcas WHERE id_marca = :id");
            $stmt->execute([':id' => $id_marca]);
            header('Location: index.php?msg=deleted');
            exit;
        } catch (PDOException $e) {
            die("Error al eliminar la marca: " . $e->getMessage());
        }
    }
}

header('Location: index.php');
exit;