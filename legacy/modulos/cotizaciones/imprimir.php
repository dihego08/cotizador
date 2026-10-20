<?php
session_start();
require_once '../../config/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die("ID de cotización no válido.");
}

// 1. CONSULTA SQL DE LA COTIZACIÓN CON CLIENTE Y USUARIO
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        cl.num_documento AS cliente_num_doc,
        cl.razon_social  AS cliente_razon_social,
        cl.direccion     AS cliente_direccion,
        cl.email         AS cliente_email,
        cl.telefono      AS cliente_telefono,
        cl.atencion      AS cliente_atencion,
        u.nombre_usuario, 
        u.cargo          AS cargo_usuario, 
        u.celular        AS celular_usuario,
        u.firma_path     AS firma_usuario
    FROM cotizaciones c
    LEFT JOIN clientes cl ON c.id_cliente = cl.id_cliente
    LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario
    WHERE c.id_cotizacion = :id
");
$stmt->execute([':id' => $id]);
$cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cotizacion) {
    die("Error: La cotización solicitada no existe.");
}

// 2. RESPALDO DE USUARIO (Si id_usuario en la cotización es 0, consultamos con el ID de la sesión)
$idUsuarioCot = intval($cotizacion['id_usuario'] ?? 0);

if ($idUsuarioCot <= 0) {
    $idUsuarioSesion = intval($_SESSION['usuario_id'] ?? 0);
    
    if ($idUsuarioSesion > 0) {
        $stmtUsr = $pdo->prepare("SELECT nombre_usuario, cargo, celular, firma_path FROM usuarios WHERE id_usuario = :id LIMIT 1");
        $stmtUsr->execute([':id' => $idUsuarioSesion]);
        $usrActual = $stmtUsr->fetch(PDO::FETCH_ASSOC);

        if ($usrActual) {
            $cotizacion['nombre_usuario'] = $usrActual['nombre_usuario'];
            $cotizacion['cargo_usuario']  = $usrActual['cargo'];
            $cotizacion['celular_usuario'] = $usrActual['celular'];
            $cotizacion['firma_usuario']   = $usrActual['firma_path'];
        }
    }
}

// 3. CONSULTAR EMPRESA EMISORA
$idEmpresa = !empty($cotizacion['id_empresa']) ? intval($cotizacion['id_empresa']) : ($_SESSION['id_empresa'] ?? 1);
$stmtEmp = $pdo->prepare("SELECT * FROM empresas WHERE id_empresa = :id_empresa LIMIT 1");
$stmtEmp->execute([':id_empresa' => $idEmpresa]);
$empresa = $stmtEmp->fetch(PDO::FETCH_ASSOC) ?: [];

// 4. CONSULTAR PRODUCTOS
$stmtDet = $pdo->prepare("SELECT * FROM cotizacion_detalles WHERE id_cotizacion = :id ORDER BY id_detalle ASC");
$stmtDet->execute([':id' => $id]);
$detalles = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

// --- VARIABLES DE FIRMA ---
$nombreFirma   = $cotizacion['nombre_usuario'] ?? '';
$cargoFirma    = $cotizacion['cargo_usuario'] ?? '';
$celularFirma  = $cotizacion['celular_usuario'] ?? '';
$firmaImgPath  = $cotizacion['firma_usuario'] ?? '';
$primerNombreEmpresa = $empresa['razon_social'] ?? '';

// --- VARIABLES CLIENTE Y MONEDA ---
$clienteRuc       = !empty($cotizacion['cliente_num_doc']) ? $cotizacion['cliente_num_doc'] : ($cotizacion['num_documento'] ?? '-');
$clienteRazon     = !empty($cotizacion['cliente_razon_social']) ? $cotizacion['cliente_razon_social'] : ($cotizacion['razon_social'] ?? '-');
$clienteDireccion = !empty($cotizacion['cliente_direccion']) ? $cotizacion['cliente_direccion'] : ($cotizacion['direccion'] ?? '-');
$clienteEmail     = !empty($cotizacion['cliente_email']) ? $cotizacion['cliente_email'] : ($cotizacion['email_cliente'] ?? '-');
$clienteAtencion  = !empty($cotizacion['cliente_atencion']) ? $cotizacion['cliente_atencion'] : ($cotizacion['atencion'] ?? '-');

$simboloMoneda  = ($cotizacion['moneda'] ?? 'PEN') === 'USD' ? '$' : 'S/';
$textoMoneda    = ($cotizacion['moneda'] ?? 'PEN') === 'USD' ? 'DÓLARES AMERICANOS' : 'SOLES';
$plazoEntrega   = !empty($cotizacion['plazo_entrega']) ? $cotizacion['plazo_entrega'] : 'A TRATAR';
$vigenciaOferta = !empty($cotizacion['vigencia_oferta']) ? $cotizacion['vigencia_oferta'] : '5 DÍAS';
$asuntoVal      = $cotizacion['asunto'] ?? '';

$claustros = [];
if (!empty($cotizacion['condiciones_json'])) {
    $claustros = json_decode($cotizacion['condiciones_json'], true) ?: [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización <?= htmlspecialchars($cotizacion['numero_cotizacion']) ?></title>
    <style>
        * { box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; }
        body { margin: 0; padding: 20px; background-color: #f4f4f4; color: #000; font-size: 13px; }
        
        .paper {
            background: #fff;
            width: 210mm;
            min-height: 297mm;
            margin: auto;
            padding: 25px 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.15);
        }

        /* CABECERA */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .header-left { vertical-align: top; width: 65%; }
        .header-right { vertical-align: top; width: 35%; text-align: right; }
        
        .logo-img { max-height: 125px; max-width: 350px; object-fit: contain; margin-bottom: 8px; }
        .company-info { font-size: 12px; line-height: 1.5; color: #222; }

        .ruc-box {
            border: 2px solid #888;
            text-align: center;
            width: 100%;
            border-radius: 2px;
            overflow: hidden;
        }
        .ruc-box .title { font-size: 18px; font-weight: bold; padding: 6px 0; letter-spacing: 1px; }
        .ruc-box .ruc-num { background-color: #3ba3a8; color: #fff; font-size: 15px; font-weight: bold; padding: 5px 0; }
        .ruc-box .number { font-size: 16px; font-weight: bold; padding: 6px 0; }
        .ruc-box .date { font-size: 14px; padding-bottom: 6px; }

        /* DATOS DEL CLIENTE */
        .box-title { background-color: #d9d9d9; font-weight: bold; padding: 5px 8px; font-size: 12px; text-transform: uppercase; border: 1px solid #777; border-bottom: none; }
        
        .client-table { width: 100%; border-collapse: collapse; font-size: 12px; margin-bottom: 12px; border: 1px solid #777; }
        .client-table td { padding: 4px 8px; border: 1px solid #ccc; }
        .client-table .label { font-weight: bold; width: 15%; background-color: #fbfbfb; }
        .client-table .val-left { width: 45%; }
        .client-table .label-right { font-weight: bold; width: 22%; text-align: right; background-color: #fbfbfb; }
        .client-table .val-right { width: 18%; text-align: center; }

        /* TITULO / ASUNTO */
        .asunto-box { 
            text-align: center; 
            font-weight: bold; 
            font-size: 14px; 
            padding: 6px 0 14px 0; 
            letter-spacing: 0.5px;
        }

        /* TABLA DE PRODUCTOS */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 12px; }
        .items-table th { background-color: #d9d9d9; border: 1px solid #777; padding: 6px; font-weight: bold; text-align: center; }
        .items-table td { border: 1px solid #ccc; padding: 6px; }

        /* TOTALES */
        .totals-table { width: 250px; margin-left: auto; border-collapse: collapse; font-size: 12px; float: right; }
        .totals-table td { border: 1px solid #777; padding: 4px 8px; }
        .totals-table .t-label { font-weight: bold; text-align: right; background-color: #fff; width: 50%; }
        .totals-table .t-val { text-align: right; width: 50%; }

        /* PIE DE PÁGINA Y FIRMA */
        .footer-signature { margin-top: 45px; text-align: center; clear: both; font-size: 12px; color: #333; }
        .dashed-line { letter-spacing: 3px; color: #888; margin-bottom: 6px; }
        .firm-name { font-weight: bold; color: #111; text-transform: uppercase; font-size: 13px; }
        .firm-title { font-size: 11px; text-transform: uppercase; color: #444; margin-top: 2px; }

        /* CONTROLES IMPRESIÓN */
        .no-print-bar { text-align: center; margin-bottom: 20px; }
        .btn-print { background: #3ba3a8; color: white; border: none; padding: 10px 20px; font-weight: bold; cursor: pointer; border-radius: 4px; font-size: 14px; }

        @media print {
            body { background: none; padding: 0; }
            .paper { box-shadow: none; width: 100%; margin: 0; padding: 0; }
            .no-print-bar { display: none !important; }
            @page { margin: 12mm; }
        }
    </style>
</head>
<body>

<div class="no-print-bar">
    <button class="btn-print" onclick="window.print()">🖨️ Imprimir / Guardar en PDF</button>
</div>

<div class="paper">
    <!-- ENCABEZADO -->
    <table class="header-table">
        <tr>
            <td class="header-left">
                <?php if (!empty($empresa['logo_path']) && file_exists("../../" . $empresa['logo_path'])): ?>
                    <img src="../../<?= htmlspecialchars($empresa['logo_path']) ?>" class="logo-img" alt="Logo">
                <?php else: ?>
                    <h2 style="margin:0; color:#3ba3a8;"><?= htmlspecialchars($empresa['razon_social'] ?? 'MI EMPRESA S.A.C.') ?></h2>
                <?php endif; ?>
                
                <div class="company-info">
                    <strong>Teléfono:</strong> <?= htmlspecialchars($empresa['telefono'] ?? '') ?><br>
                    <strong>E-mail:</strong> <?= htmlspecialchars($empresa['email'] ?? '') ?><br>
                    <strong>Dirección:</strong> <?= htmlspecialchars($empresa['direccion'] ?? '') ?>
                </div>
            </td>
            <td class="header-right">
                <div class="ruc-box">
                    <div class="title">COTIZACIÓN</div>
                    <div class="ruc-num">RUC: <?= htmlspecialchars($empresa['ruc'] ?? '') ?></div>
                    <div class="number">N° <?= htmlspecialchars($cotizacion['numero_cotizacion']) ?></div>
                    <div class="date"><?= date('d-m-Y', strtotime($cotizacion['fecha_emision'])) ?></div>
                </div>
            </td>
        </tr>
    </table>

    <!-- DATOS DEL CLIENTE -->
    <div class="box-title">DATOS DEL CLIENTE</div>
    <table class="client-table">
        <tr>
            <td class="label">RUC :</td>
            <td class="val-left"><?= htmlspecialchars($clienteRuc) ?></td>
            <td class="label-right">Forma de Pago :</td>
            <td class="val-right"><?= htmlspecialchars($cotizacion['forma_pago'] ?? 'CONTADO') ?></td>
        </tr>
        <tr>
            <td class="label">Razón Social :</td>
            <td class="val-left"><strong><?= htmlspecialchars($clienteRazon) ?></strong></td>
            <td class="label-right">Moneda :</td>
            <td class="val-right"><?= $textoMoneda ?></td>
        </tr>
        <tr>
            <td class="label">Dirección :</td>
            <td class="val-left"><?= htmlspecialchars($clienteDireccion) ?></td>
            <td class="label-right">Plazo de Entrega :</td>
            <td class="val-right"><?= htmlspecialchars($plazoEntrega) ?></td>
        </tr>
        <tr>
            <td class="label">E-mail :</td>
            <td class="val-left"><?= htmlspecialchars($clienteEmail) ?></td>
            <td class="label-right">Vigencia de Oferta :</td>
            <td class="val-right"><?= htmlspecialchars($vigenciaOferta) ?></td>
        </tr>
        <tr>
            <td class="label">Atención :</td>
            <td class="val-left" colspan="3"><?= htmlspecialchars($clienteAtencion) ?></td>
        </tr>
    </table>

    <!-- TITULO / ASUNTO -->
    <?php if (!empty(trim($asuntoVal))): ?>
        <div class="asunto-box">
            <?= mb_strtoupper(htmlspecialchars(trim($asuntoVal))) ?>
        </div>
    <?php endif; ?>

    <!-- TABLA DE DETALLES -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 6%;">ITEM</th>
                <th>Descripción</th>
                <th style="width: 14%;">Marca</th>
                <th style="width: 8%;">Cant.</th>
                <th style="width: 8%;">Unid.</th>
                <th style="width: 12%;">Unitario</th>
                <th style="width: 12%;">Parcial</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($detalles as $idx => $det): ?>
                <?php 
                    $subtotalItem = (floatval($det['subtotal'] ?? 0) > 0) 
                        ? floatval($det['subtotal']) 
                        : (floatval($det['cantidad']) * floatval($det['precio_unitario']));
                ?>
                <tr>
                    <td style="text-align: center;"><?= $idx + 1 ?></td>
                    <td><?= htmlspecialchars($det['descripcion']) ?></td>
                    <td style="text-align: center;"><?= htmlspecialchars($det['marca'] ?? '-') ?></td>
                    <td style="text-align: center;"><?= number_format($det['cantidad'], 1) ?></td>
                    <td style="text-align: center;"><?= htmlspecialchars($det['unidad_medida'] ?? 'UND') ?></td>
                    <td style="text-align: right;"><?= number_format($det['precio_unitario'], 2) ?></td>
                    <td style="text-align: right;"><?= number_format($subtotalItem, 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- TOTALES -->
    <div style="width: 100%; overflow: hidden; margin-top: 5px;">
        <table class="totals-table">
            <tr>
                <td class="t-label">Subtotal</td>
                <td class="t-val"><?= $simboloMoneda ?> <?= number_format($cotizacion['subtotal'], 2) ?></td>
            </tr>
            <tr>
                <td class="t-label">I.G.V. 18%</td>
                <td class="t-val"><?= $simboloMoneda ?> <?= number_format($cotizacion['igv'], 2) ?></td>
            </tr>
            <tr>
                <td class="t-label">TOTAL</td>
                <td class="t-val"><strong><?= $simboloMoneda ?> <?= number_format($cotizacion['total'], 2) ?></strong></td>
            </tr>
        </table>
    </div>

    <!-- CLÁUSULAS ADICIONALES -->
    <?php if (!empty($claustros)): ?>
        <div style="clear: both; padding-top: 15px; margin-bottom: 10px;">
            <div class="box-title">CLÁUSULAS Y CONDICIONES ADICIONALES</div>
            <table class="client-table" style="margin-bottom: 0;">
                <?php foreach ($claustros as $tituloClause => $contenidoClause): ?>
                    <tr>
                        <td class="label" style="width: 25%; vertical-align: top; background-color: #fbfbfb;">
                            <?= htmlspecialchars($tituloClause) ?> :
                        </td>
                        <td class="val-left" style="width: 75%; white-space: pre-line;">
                            <?= htmlspecialchars($contenidoClause) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>

   <!-- SECCIÓN DE FIRMA -->
    <div class="footer-signature">
        
        <!-- Si el usuario tiene imagen de firma registrada -->
        <?php if (!empty($firmaImgPath) && file_exists("../../" . $firmaImgPath)): ?>
            <div style="margin-bottom: -15px;">
                <img src="../../<?= htmlspecialchars($firmaImgPath) ?>" style="max-height: 70px; object-fit: contain;" alt="Firma">
            </div>
        <?php endif; ?>

        <div class="dashed-line">------------------------------------</div>
        
        <?php if (!empty($nombreFirma)): ?>
            <div class="firm-name"><?= htmlspecialchars($nombreFirma) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($cargoFirma)): ?>
            <div class="firm-title"><?= htmlspecialchars($cargoFirma) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($celularFirma)): ?>
            <div style="font-size: 11px; margin-top: 3px; font-weight: bold;">Cel: <?= htmlspecialchars($celularFirma) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($primerNombreEmpresa)): ?>
            <div style="margin-top: 10px; font-weight: bold; color: #3ba3a8; font-size: 15px;">
                <?= htmlspecialchars($primerNombreEmpresa) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>