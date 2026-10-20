<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo               = $_POST['tipo'] ?? 'PRODUCTO';
    $nombre_producto     = trim($_POST['nombre_producto'] ?? '');
    $codigo_interno      = trim($_POST['codigo_interno'] ?? '');
    $codigo_barra        = !empty($_POST['codigo_barra']) ? trim($_POST['codigo_barra']) : null;
    $codigo_proveedor    = !empty($_POST['codigo_proveedor']) ? trim($_POST['codigo_proveedor']) : null;
    $numero_parte        = !empty($_POST['numero_parte']) ? trim($_POST['numero_parte']) : null;
    $marca               = !empty($_POST['marca']) ? trim($_POST['marca']) : null;
    $modelo              = !empty($_POST['modelo']) ? trim($_POST['modelo']) : null;
    $moneda              = $_POST['moneda'] ?? 'PEN';
    $precio_costo        = (float)($_POST['precio_costo'] ?? 0);
    $porcentaje_utilidad = (float)($_POST['porcentaje_utilidad'] ?? 0);
    $precio_venta        = (float)($_POST['precio_venta'] ?? 0);
    $unidad_medida       = trim($_POST['unidad_medida'] ?? 'UNIDAD');
    $id_categoria        = !empty($_POST['id_categoria']) ? (int)$_POST['id_categoria'] : null;
    $descripcion         = !empty($_POST['descripcion']) ? trim($_POST['descripcion']) : null;

    if (!empty($nombre_producto) && !empty($codigo_interno)) {
        try {
            $sql = "INSERT INTO productos 
                    (tipo, nombre_producto, codigo_interno, codigo_barra, codigo_proveedor, numero_parte, marca, modelo, moneda, precio_costo, porcentaje_utilidad, precio_venta, unidad_medida, id_categoria, descripcion, created_at, updated_at) 
                    VALUES 
                    (:tipo, :nombre, :cod_int, :cod_barra, :cod_prov, :num_parte, :marca, :modelo, :moneda, :costo, :utilidad, :venta, :unidad, :categoria, :descripcion, NOW(), NOW())";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':tipo'      => $tipo,
                ':nombre'    => $nombre_producto,
                ':cod_int'   => $codigo_interno,
                ':cod_barra' => $codigo_barra,
                ':cod_prov'  => $codigo_proveedor,
                ':num_parte' => $numero_parte,
                ':marca'     => $marca,
                ':modelo'    => $modelo,
                ':moneda'    => $moneda,
                ':costo'     => $precio_costo,
                ':utilidad'  => $porcentaje_utilidad,
                ':venta'     => $precio_venta,
                ':unidad'    => $unidad_medida,
                ':categoria' => $id_categoria,
                ':descripcion' => $descripcion
            ]);

            header('Location: index.php?msg=created');
            exit;
        } catch (PDOException $e) {
            die("Error al guardar: " . $e->getMessage());
        }
    }
}

header('Location: index.php');
exit;