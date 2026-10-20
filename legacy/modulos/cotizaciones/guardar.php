<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Iniciar transacción SQL para garantizar integridad de datos
        $pdo->beginTransaction();

        // 1. Obtener Usuario desde la Sesión
        $id_usuario = $_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? $_SESSION['id_user'] ?? $_SESSION['usuario_id'] ?? null;

        // 2. Obtener Empresa con Prioridad: Sesión -> Usuario -> Empresa Principal (es_principal = 1) -> Primera registrada
        $id_empresa = $_SESSION['id_empresa'] ?? $_SESSION['user']['id_empresa'] ?? null;

        if (!$id_empresa && $id_usuario) {
            $stmtU = $pdo->prepare("SELECT id_empresa FROM usuarios WHERE id_usuario = :uid LIMIT 1");
            $stmtU->execute([':uid' => $id_usuario]);
            $id_empresa = $stmtU->fetchColumn() ?: null;
        }

        // FALLBACK PRINCIPAL: Si id_empresa sigue siendo nulo o 0, asigna la empresa marcada como Principal (es_principal = 1)
        if (!$id_empresa || $id_empresa <= 0) {
            $id_empresa = $pdo->query("SELECT id_empresa FROM empresas WHERE es_principal = 1 LIMIT 1")->fetchColumn();
        }

        // Fallback secundario si no hay ninguna marcada como principal
        if (!$id_empresa) {
            $id_empresa = $pdo->query("SELECT id_empresa FROM empresas ORDER BY id_empresa ASC LIMIT 1")->fetchColumn();
        }

        // 3. Recibir y mapear los campos del formulario POST
        $id_cliente        = $_POST['id_cliente'] ?? null;
        $atencion          = trim($_POST['atencion'] ?? '');
        $numero_cotizacion = trim($_POST['numero_cotizacion'] ?? $_POST['nro_cotizacion'] ?? '');
        $fecha_emision     = $_POST['fecha_emision'] ?? $_POST['emision'] ?? date('Y-m-d');
        $tipo_cambio       = floatval($_POST['tipo_cambio'] ?? 1.000);
        $moneda            = $_POST['moneda'] ?? 'USD';
        $forma_pago        = $_POST['forma_pago'] ?? 'Contado';
        
        // Asunto / Proyecto
        $asunto = trim($_POST['asunto'] ?? $_POST['asunto_proyecto'] ?? $_POST['proyecto'] ?? $_POST['txtAsunto'] ?? '');

        // Vigencia y Plazos
        $vigencia       = trim($_POST['vigencia_oferta'] ?? $_POST['vigencia'] ?? '');
        $tiempo_entrega = trim($_POST['plazo_entrega'] ?? $_POST['plazo'] ?? $_POST['tiempo_entrega'] ?? '');

        // Cláusulas Adicionales
        $alcance       = trim($_POST['hidden_alcance'] ?? $_POST['alcance_servicio'] ?? $_POST['alcance'] ?? '');
        $restricciones = trim($_POST['hidden_restricciones'] ?? $_POST['restricciones_exclusiones'] ?? $_POST['restricciones'] ?? '');
        $condiciones   = trim($_POST['hidden_condiciones'] ?? $_POST['condiciones_comerciales'] ?? '');

        // Totales y Lista de Ítems
        $subtotal = floatval($_POST['subtotal'] ?? 0);
        $igv      = floatval($_POST['igv'] ?? 0);
        $total    = floatval($_POST['total'] ?? 0);
        $items    = $_POST['items'] ?? [];

        // Validaciones Básicas
        if (empty($id_cliente)) {
            throw new Exception("Debe seleccionar un cliente obligatoriamente.");
        }

        if (empty($numero_cotizacion)) {
            throw new Exception("El número de cotización no fue generado correctamente.");
        }

        if (empty($items) || !is_array($items)) {
            throw new Exception("Debe agregar al menos un ítem o producto a la cotización.");
        }

        // 4. INSERT en la tabla cotizaciones
        $sqlCot = "INSERT INTO cotizaciones (
            numero_cotizacion, id_cliente, id_usuario, id_empresa, atencion, asunto, fecha_emision, 
            tipo_cambio, moneda, forma_pago, vigencia, tiempo_entrega,
            subtotal, igv, total, alcance, restricciones, condiciones_comerciales, estado
        ) VALUES (
            :numero, :cliente, :usuario, :empresa, :atencion, :asunto, :fecha, 
            :tc, :moneda, :forma_pago, :vigencia, :tiempo_entrega,
            :subtotal, :igv, :total, :alcance, :restricciones, :condiciones, 'PENDIENTE'
        )";

        $stmtCot = $pdo->prepare($sqlCot);
        $stmtCot->execute([
            ':numero'         => $numero_cotizacion,
            ':cliente'        => $id_cliente,
            ':usuario'        => $id_usuario,
            ':empresa'        => $id_empresa,
            ':atencion'       => $atencion,
            ':asunto'         => $asunto,
            ':fecha'          => $fecha_emision,
            ':tc'             => $tipo_cambio,
            ':moneda'         => $moneda,
            ':forma_pago'     => $forma_pago,
            ':vigencia'       => $vigencia,
            ':tiempo_entrega' => $tiempo_entrega,
            ':subtotal'       => $subtotal,
            ':igv'            => $igv,
            ':total'          => $total,
            ':alcance'        => $alcance,
            ':restricciones'  => $restricciones,
            ':condiciones'    => $condiciones
        ]);

        $id_cotizacion = $pdo->lastInsertId();

        // 5. Insertar Detalle de Ítems
        $sqlDet = "INSERT INTO cotizacion_detalles (
            id_cotizacion, id_producto, descripcion, nota_item, marca, cantidad, unidad_medida, precio_unitario, subtotal
        ) VALUES (
            :id_cotizacion, :id_producto, :descripcion, :nota_item, :marca, :cantidad, :unidad_medida, :precio_unitario, :subtotal
        )";
        $stmtDet = $pdo->prepare($sqlDet);

        foreach ($items as $item) {
            $desc = trim($item['descripcion'] ?? '');
            if (empty($desc)) continue;

            $cant    = floatval($item['cantidad'] ?? 0);
            $precio  = floatval($item['precio_unitario'] ?? 0);
            $parcial = $cant * $precio;

            $stmtDet->execute([
                ':id_cotizacion'   => $id_cotizacion,
                ':id_producto'     => !empty($item['id_producto']) ? $item['id_producto'] : null,
                ':descripcion'     => $desc,
                ':nota_item'       => !empty($item['nota_item']) ? trim($item['nota_item']) : null,
                ':marca'           => !empty($item['marca']) ? trim($item['marca']) : null,
                ':cantidad'        => $cant,
                ':unidad_medida'   => !empty($item['unidad_medida']) ? trim($item['unidad_medida']) : 'UND',
                ':precio_unitario' => $precio,
                ':subtotal'        => $parcial
            ]);
        }

        // Commit de la Transacción
        $pdo->commit();

        $_SESSION['alert'] = [
            'type'    => 'success',
            'title'   => '¡Cotización Guardada!',
            'message' => "La cotización {$numero_cotizacion} se ha registrado correctamente."
        ];

        header("Location: index.php");
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $_SESSION['alert'] = [
            'type'    => 'danger',
            'title'   => 'Error al guardar',
            'message' => $e->getMessage()
        ];

        header("Location: crear.php");
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}