<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_producto  = (int)($_POST['id_producto'] ?? 0);
    $id_almacen   = (int)($_POST['id_almacen'] ?? 0);
    $numero_serie = trim($_POST['numero_serie'] ?? '');

    if ($id_producto > 0 && $id_almacen > 0 && !empty($numero_serie)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO series_productos (id_producto, id_almacen, numero_serie, estado) VALUES (:prod, :alm, :serie, 'DISPONIBLE')");
            $stmt->execute([
                ':prod'  => $id_producto,
                ':alm'   => $id_almacen,
                ':serie' => $numero_serie
            ]);
            
            header('Location: index.php?msg=created');
            exit;
        } catch (PDOException $e) {
            // Manejo de duplicado por índice UNIQUE (SQLSTATE 23000)
            if ($e->getCode() === '23000') {
                header('Location: index.php?error=duplicate_serial');
                exit;
            }
            die("Error en la base de datos: " . $e->getMessage());
        }
    }
}

header('Location: index.php');
exit;