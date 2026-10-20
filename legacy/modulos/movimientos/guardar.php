<?php
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_movimiento = $_POST['tipo_movimiento'];
    $id_almacen      = (int)$_POST['id_almacen'];
    $id_producto     = (int)$_POST['id_producto'];
    $cantidad        = (float)$_POST['cantidad'];
    $motivo          = trim($_POST['motivo']);

    if (!empty($tipo_movimiento) && $id_almacen > 0 && $id_producto > 0 && $cantidad > 0) {
        try {
            // Iniciamos la Transacción PDO
            $pdo->beginTransaction();

            // 1. Insertar el movimiento en el historial
            $sqlMov = "INSERT INTO movimientos (id_producto, id_almacen, tipo_movimiento, cantidad, motivo) 
                       VALUES (:id_producto, :id_almacen, :tipo, :cantidad, :motivo)";
            $stmtMov = $pdo->prepare($sqlMov);
            $stmtMov->execute([
                ':id_producto' => $id_producto,
                ':id_almacen'  => $id_almacen,
                ':tipo'        => $tipo_movimiento,
                ':cantidad'    => $cantidad,
                ':motivo'      => $motivo
            ]);

            // 2. Verificar si ya existe registro de stock para ese producto en ese almacén
            $sqlStock = "SELECT id_stock, cantidad_actual FROM stock WHERE id_producto = :id_producto AND id_almacen = :id_almacen FOR UPDATE";
            $stmtStock = $pdo->prepare($sqlStock);
            $stmtStock->execute([':id_producto' => $id_producto, ':id_almacen' => $id_almacen]);
            $stockActual = $stmtStock->fetch();

            if ($tipo_movimiento === 'ENTRADA') {
                if ($stockActual) {
                    // Actualizar el stock sumando
                    $nuevoStock = $stockActual['cantidad_actual'] + $cantidad;
                    $sqlUpdate = "UPDATE stock SET cantidad_actual = :nueva_cant WHERE id_stock = :id_stock";
                    $stmtUp = $pdo->prepare($sqlUpdate);
                    $stmtUp->execute([':nueva_cant' => $nuevoStock, ':id_stock' => $stockActual['id_stock']]);
                } else {
                    // Crear un nuevo registro de stock
                    $sqlInsert = "INSERT INTO stock (id_producto, id_almacen, cantidad_actual) VALUES (:id_producto, :id_almacen, :cantidad)";
                    $stmtIn = $pdo->prepare($sqlInsert);
                    $stmtIn->execute([':id_producto' => $id_producto, ':id_almacen' => $id_almacen, ':cantidad' => $cantidad]);
                }
            } else if ($tipo_movimiento === 'SALIDA') {
                if (!$stockActual || $stockActual['cantidad_actual'] < $cantidad) {
                    // Revertir todo si no hay stock suficiente
                    $pdo->rollBack();
                    die("Error: No hay suficiente stock disponible para realizar esta salida. <a href='crear.php'>Volver</a>");
                } else {
                    // Restar stock
                    $nuevoStock = $stockActual['cantidad_actual'] - $cantidad;
                    $sqlUpdate = "UPDATE stock SET cantidad_actual = :nueva_cant WHERE id_stock = :id_stock";
                    $stmtUp = $pdo->prepare($sqlUpdate);
                    $stmtUp->execute([':nueva_cant' => $nuevoStock, ':id_stock' => $stockActual['id_stock']]);
                }
            }

            // Confirmar transacción
            $pdo->commit();

        } catch (\Exception $e) {
            $pdo->rollBack();
            die("Error al procesar el movimiento: " . $e->getMessage());
        }
    }
}

header('Location: index.php');
exit;