<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Recepción y sanitización de variables
    $id_producto         = intval($_POST['id_producto'] ?? 0);
    $tipo                = trim($_POST['tipo'] ?? 'PRODUCTO');
    $nombre_producto     = trim($_POST['nombre_producto'] ?? '');
    $codigo_interno      = trim($_POST['codigo_interno'] ?? '');
    $moneda              = trim($_POST['moneda'] ?? 'PEN');
    
    // Datos Financieros (Precios, Costo y Utilidad)
    $precio_costo        = floatval($_POST['precio_costo'] ?? 0);
    $porcentaje_utilidad = floatval($_POST['porcentaje_utilidad'] ?? 0);
    $precio_venta        = floatval($_POST['precio_venta'] ?? 0);
    
    // Datos Adicionales
    $codigo_barra        = trim($_POST['codigo_barra'] ?? '');
    $codigo_proveedor    = trim($_POST['codigo_proveedor'] ?? '');
    $numero_parte        = trim($_POST['numero_parte'] ?? '');
    $marca               = trim($_POST['marca'] ?? '');
    $modelo              = trim($_POST['modelo'] ?? '');
    $id_categoria        = !empty($_POST['id_categoria']) ? intval($_POST['id_categoria']) : null;
    $unidad_medida       = trim($_POST['unidad_medida'] ?? 'UNIDAD');
    $descripcion         = trim($_POST['descripcion'] ?? '');

    // Validación básica de campos obligatorios
    if ($id_producto <= 0 || empty($nombre_producto) || empty($codigo_interno)) {
        header('Location: index.php');
        exit;
    }

    try {
        // 2. Verificar que el código interno no pertenezca a OTRO producto diferente
        $stmtCheck = $pdo->prepare("SELECT id_producto FROM productos WHERE codigo_interno = :codigo AND id_producto != :id");
        $stmtCheck->execute([
            ':codigo' => $codigo_interno, 
            ':id'     => $id_producto
        ]);
        
        if ($stmtCheck->fetch()) {
            header("Location: editar.php?id={$id_producto}&error=duplicate_code");
            exit;
        }

        // 3. Consulta UPDATE con TODOS los campos (incluyendo costo y utilidad)
        $sql = "UPDATE productos SET 
                    tipo                = :tipo,
                    nombre_producto     = :nombre_producto,
                    codigo_interno      = :codigo_interno,
                    moneda              = :moneda,
                    precio_costo        = :precio_costo,
                    porcentaje_utilidad = :porcentaje_utilidad,
                    precio_venta        = :precio_venta,
                    codigo_barra        = :codigo_barra,
                    codigo_proveedor    = :codigo_proveedor,
                    numero_parte        = :numero_parte,
                    marca               = :marca,
                    modelo              = :modelo,
                    id_categoria        = :id_categoria,
                    unidad_medida       = :unidad_medida,
                    descripcion         = :descripcion
                WHERE id_producto = :id_producto";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':tipo'                => $tipo,
            ':nombre_producto'     => $nombre_producto,
            ':codigo_interno'      => $codigo_interno,
            ':moneda'              => $moneda,
            ':precio_costo'        => $precio_costo,
            ':porcentaje_utilidad' => $porcentaje_utilidad,
            ':precio_venta'        => $precio_venta,
            ':codigo_barra'        => $codigo_barra,
            ':codigo_proveedor'    => $codigo_proveedor,
            ':numero_parte'        => $numero_parte,
            ':marca'               => $marca,
            ':modelo'              => $modelo,
            ':id_categoria'        => $id_categoria,
            ':unidad_medida'       => $unidad_medida,
            ':descripcion'         => $descripcion,
            ':id_producto'         => $id_producto
        ]);

        // Redireccionar al listado tras éxito
        header('Location: index.php?msg=updated');
        exit;

    } catch (PDOException $e) {
        die("Error al actualizar el producto: " . $e->getMessage());
    }
} else {
    header('Location: index.php');
    exit;
}