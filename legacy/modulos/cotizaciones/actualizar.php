<?php
// 1. Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Cargar base de datos y autenticación
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Validar que la petición venga por POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// 3. Obtener el ID de la cotización
$idCotizacion = $_POST['id_cotizacion'] ?? null;

if (!$idCotizacion) {
    $_SESSION['alert'] = [
        'type' => 'danger',
        'title' => 'Error',
        'message' => 'No se especificó la cotización a actualizar.'
    ];
    header('Location: index.php');
    exit;
}

try {
    // Iniciar transacción de base de datos
    $pdo->beginTransaction();

    // 4. Capturar y limpiar datos de la cabecera
    $idCliente              = $_POST['id_cliente'] ?? null;
    $atencion               = trim($_POST['atencion'] ?? '');
    $asunto                 = trim($_POST['asunto'] ?? '');
    $fechaEmision           = $_POST['fecha_emision'] ?? date('Y-m-d');
    $tipoCambio             = floatval($_POST['tipo_cambio'] ?? 1.000);
    $moneda                 = $_POST['moneda'] ?? 'USD';
    $formaPago              = $_POST['forma_pago'] ?? 'Contado';
    $vigencia               = trim($_POST['vigencia'] ?? '15 días calendario');
    $tiempoEntrega          = trim($_POST['tiempo_entrega'] ?? '');
    $alcance                = trim($_POST['alcance'] ?? '');
    $restricciones          = trim($_POST['restricciones'] ?? '');
    $condicionesComerciales = trim($_POST['condiciones_comerciales'] ?? '');
    $subtotal               = floatval($_POST['subtotal'] ?? 0);
    $igv                    = floatval($_POST['igv'] ?? 0);
    $total                  = floatval($_POST['total'] ?? 0);
    $items                  = $_POST['items'] ?? [];

    // Validaciones básicas
    if (!$idCliente) {
        throw new Exception("Debe seleccionar un cliente válido.");
    }

    if (empty($items)) {
        throw new Exception("La cotización debe contener al menos un producto o servicio.");
    }

    // 5. Actualizar la tabla principal 'cotizaciones'
    $stmtUpdate = $pdo->prepare("
        UPDATE cotizaciones SET
            id_cliente = :id_cliente,
            atencion = :atencion,
            asunto = :asunto,
            fecha_emision = :fecha_emision,
            tipo_cambio = :tipo_cambio,
            moneda = :moneda,
            forma_pago = :forma_pago,
            vigencia = :vigencia,
            tiempo_entrega = :tiempo_entrega,
            alcance = :alcance,
            restricciones = :restricciones,
            condiciones_comerciales = :condiciones_comerciales,
            subtotal = :subtotal,
            igv = :igv,
            total = :total
        WHERE id_cotizacion = :id_cotizacion
    ");

    $stmtUpdate->execute([
        ':id_cliente'             => $idCliente,
        ':atencion'               => $atencion,
        ':asunto'                 => $asunto,
        ':fecha_emision'          => $fechaEmision,
        ':tipo_cambio'            => $tipoCambio,
        ':moneda'                 => $moneda,
        ':forma_pago'             => $formaPago,
        ':vigencia'               => $vigencia,
        ':tiempo_entrega'         => $tiempoEntrega,
        ':alcance'                => $alcance,
        ':restricciones'          => $restricciones,
        ':condiciones_comerciales'=> $condicionesComerciales,
        ':subtotal'               => $subtotal,
        ':igv'                    => $igv,
        ':total'                  => $total,
        ':id_cotizacion'          => $idCotizacion
    ]);

    // 6. Eliminar el detalle anterior en 'cotizacion_detalles' para reinsertar el nuevo
    $stmtDelete = $pdo->prepare("DELETE FROM cotizacion_detalles WHERE id_cotizacion = :id_cotizacion");
    $stmtDelete->execute([':id_cotizacion' => $idCotizacion]);

    // 7. Insertar los nuevos ítems
    $stmtInsertItem = $pdo->prepare("
        INSERT INTO cotizacion_detalles 
            (id_cotizacion, descripcion, nota_item, marca, cantidad, unidad_medida, precio_unitario)
        VALUES 
            (:id_cotizacion, :descripcion, :nota_item, :marca, :cantidad, :unidad_medida, :precio_unitario)
    ");

    foreach ($items as $item) {
        $desc   = trim($item['descripcion'] ?? '');
        $nota   = trim($item['nota_item'] ?? '');
        $marca  = trim($item['marca'] ?? '');
        $cant   = floatval($item['cantidad'] ?? 0);
        $unid   = trim($item['unidad_medida'] ?? 'UNIDADES');
        $precio = floatval($item['precio_unitario'] ?? 0);

        // Guardar solo filas válidas
        if (!empty($desc) && $cant > 0) {
            $stmtInsertItem->execute([
                ':id_cotizacion'   => $idCotizacion,
                ':descripcion'     => $desc,
                ':nota_item'       => $nota,
                ':marca'           => $marca,
                ':cantidad'        => $cant,
                ':unidad_medida'   => $unid,
                ':precio_unitario' => $precio
            ]);
        }
    }

    // Confirmar todos los cambios
    $pdo->commit();

    $_SESSION['alert'] = [
        'type' => 'success',
        'title' => '¡Actualizado!',
        'message' => 'La cotización se actualizó correctamente.'
    ];

} catch (Exception $e) {
    // Si ocurre un error, revertir cambios
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['alert'] = [
        'type' => 'danger',
        'title' => 'Error al actualizar',
        'message' => $e->getMessage()
    ];
}

// Redireccionar al listado principal
header('Location: index.php');
exit;