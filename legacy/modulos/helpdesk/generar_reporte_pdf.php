<?php
session_start();

// Configurar hora local de Perú (UTC-5)
date_default_timezone_set('America/Lima');

require_once '../../config/db.php'; // Conexión PDO

// 1. CAPTURA Y VALIDACIÓN DE PARÁMETROS DE SESIÓN Y FILTROS
$idEmpresaSesion = $_SESSION['id_empresa'] ?? null;

if (!$idEmpresaSesion) {
    die("<div style='font-family:sans-serif; padding:30px; text-align:center;'><h2>Acceso denegado</h2><p>No existe una sesión activa de empresa. Inicie sesión nuevamente.</p></div>");
}

$idCliente   = isset($_GET['id_cliente']) ? (int)$_GET['id_cliente'] : 0;
$tipoReporte = isset($_GET['tipo_reporte']) ? $_GET['tipo_reporte'] : 'RESUMEN'; // RESUMEN o DETALLADO
$fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fechaFin    = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');

if ($idCliente === 0) {
    die("<div style='font-family:sans-serif; padding:30px; text-align:center;'><h2>Error</h2><p>Debe seleccionar un cliente válido para generar el reporte de atenciones.</p></div>");
}

// 2. CONSULTA DINÁMICA DE LA EMPRESA EMISORA (Basada en $_SESSION['id_empresa'])
$stmtEmp = $pdo->prepare("SELECT razon_social, ruc, direccion, telefono, logo_path FROM empresas WHERE id_empresa = :id_empresa");
$stmtEmp->execute([':id_empresa' => $idEmpresaSesion]);
$empresaEmisora = $stmtEmp->fetch(PDO::FETCH_ASSOC);

if (!$empresaEmisora) {
    die("La empresa emisora vinculada a la sesión no fue encontrada.");
}

// Resolver la ruta relativa del logotipo cargado en la BD
$logoRuta = '';
if (!empty($empresaEmisora['logo_path'])) {
    $rutaLimpia = ltrim($empresaEmisora['logo_path'], '/');
    $logoRuta   = '../../' . $rutaLimpia;
}

// 3. CONSULTA DE DATOS DEL CLIENTE ATENDIDO
$stmtCli = $pdo->prepare("SELECT razon_social, num_documento, direccion, telefono, email FROM clientes WHERE id_cliente = :id");
$stmtCli->execute([':id' => $idCliente]);
$cliente = $stmtCli->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    die("El cliente especificado no existe.");
}

// 4. CONSULTA DE TICKETS PERTENECIENTES A LA EMPRESA ACTIVA EN SESIÓN
$sql = "SELECT 
            ha.*, 
            hc.nombre AS categoria_nombre,
            hsub.nombre AS subcategoria_nombre,
            u.nombre_usuario AS tecnico_nombre
        FROM helpdesk_atenciones ha
        LEFT JOIN helpdesk_categorias hc ON ha.categoria = hc.id_categoria
        LEFT JOIN helpdesk_categorias hsub ON ha.id_subcategoria = hsub.id_categoria
        INNER JOIN usuarios u ON ha.id_tecnico = u.id_usuario
        WHERE ha.id_empresa = :id_empresa
          AND ha.id_cliente = :id_cliente 
          AND ha.fecha_atencion BETWEEN :f_inicio AND :f_fin
        ORDER BY ha.fecha_atencion ASC, ha.id_atencion ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':id_empresa' => $idEmpresaSesion,
    ':id_cliente' => $idCliente,
    ':f_inicio'   => $fechaInicio,
    ':f_fin'      => $fechaFin
]);
$atenciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. PROCESAMIENTO MÉTRICO Y CONSOLIDACIÓN DE TIEMPOS
$totalSegundos    = 0;
$totalTickets     = count($atenciones);
$concluidos       = 0;
$enProceso        = 0;
$porCategoria     = [];
$porSolicitante   = [];

foreach ($atenciones as $at) {
    $seg = (int)$at['tiempo_efectivo_segundos'];
    $totalSegundos += $seg;

    if ($at['estado_cierre'] === 'CONCLUIDO') {
        $concluidos++;
    } else {
        $enProceso++;
    }

    $catNombre = $at['categoria_nombre'] ?? 'General / No especificado';
    if (!isset($porCategoria[$catNombre])) $porCategoria[$catNombre] = 0;
    $porCategoria[$catNombre]++;

    $solNombre = $at['nombre_solicitante'];
    if (!isset($porSolicitante[$solNombre])) $porSolicitante[$solNombre] = 0;
    $porSolicitante[$solNombre]++;
}

$horasTotales    = floor($totalSegundos / 3600);
$minutosTotales  = floor(($totalSegundos % 3600) / 60);
$segundosTotales = $totalSegundos % 60;
$tiempoConsolidado = sprintf("%d hrs %02d min %02d seg", $horasTotales, $minutosTotales, $segundosTotales);

function formatearTiempo($seg) {
    $h = floor($seg / 3600);
    $m = floor(($seg % 3600) / 60);
    $s = $seg % 60;
    return sprintf("%02d:%02d:%02d", $h, $m, $s);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reporte_Helpdesk_<?php echo preg_replace('/[^A-Za-z0-9]/', '_', $cliente['razon_social']); ?>_<?php echo $tipoReporte; ?></title>
  <style>
    @page {
      size: A4 portrait;
      margin: 12mm 15mm 15mm 15mm;
    }
    body {
      font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
      font-size: 11px;
      color: #2b2b2b;
      background-color: #fff;
      margin: 0;
      padding: 0;
    }
    @media print {
      .no-print { display: none !important; }
      body { -webkit-print-color-adjust: exact; }
    }
    .btn-print {
      background-color: #0d6efd;
      color: #fff;
      border: none;
      padding: 8px 18px;
      font-size: 13px;
      font-weight: bold;
      border-radius: 4px;
      cursor: pointer;
      margin-bottom: 15px;
    }
    .header-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 5px;
    }
    .logo-img {
      max-height: 55px;
      max-width: 180px;
      object-fit: contain;
    }
    .box-info {
      width: 100%;
      border: 1px solid #dee2e6;
      border-radius: 5px;
      background-color: #f8f9fa;
      padding: 10px;
      margin-bottom: 15px;
    }
    .box-info table {
      width: 100%;
      border-collapse: collapse;
    }
    .box-info td {
      padding: 3px 6px;
      vertical-align: top;
    }
    .metrics-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 15px;
    }
    .metric-card {
      border: 1px solid #ced4da;
      border-radius: 4px;
      padding: 8px;
      text-align: center;
      background-color: #ffffff;
    }
    .metric-title {
      font-size: 9px;
      text-transform: uppercase;
      color: #6c757d;
      font-weight: bold;
    }
    .metric-value {
      font-size: 15px;
      font-weight: bold;
      color: #0d6efd;
      margin-top: 3px;
    }
    .data-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
      margin-bottom: 15px;
    }
    .data-table th {
      background-color: #0d6efd;
      color: #ffffff;
      text-align: left;
      padding: 6px 8px;
      font-size: 10px;
      text-transform: uppercase;
      border: 1px solid #0d6efd;
    }
    .data-table td {
      padding: 6px 8px;
      border: 1px solid #dee2e6;
      font-size: 10px;
    }
    .data-table tr:nth-child(even) {
      background-color: #f8f9fa;
    }
    .detail-box {
      background-color: #ffffff;
      border-left: 3px solid #0d6efd;
      padding: 4px 8px;
      margin-top: 4px;
      font-size: 9.5px;
    }
    .total-row {
      background-color: #e9ecef !important;
      font-weight: bold;
    }
    .badge {
      display: inline-block;
      padding: 2px 5px;
      font-size: 8.5px;
      font-weight: bold;
      border-radius: 3px;
    }
    .badge-success { background-color: #d1e7dd; color: #0f5132; }
    .badge-warning { background-color: #fff3cd; color: #664d03; }
  </style>
</head>
<body>

  <!-- BOTÓN DE IMPRESIÓN DIRECTA -->
  <div class="no-print" style="text-align: right;">
    <button class="btn-print" onclick="window.print();">
      🖨️ Imprimir / Guardar como PDF
    </button>
  </div>

  <!-- ENCABEZADO CON LOGO DE LA EMPRESA EMISORA -->
  <table class="header-table">
    <tr>
      <!-- COLUMNA IZQUIERDA: LOGO Y DATOS DE LA EMPRESA EMISORA DEBAJO -->
      <td width="50%" style="vertical-align: top; text-align: left;">
        <?php if (!empty($logoRuta) && file_exists($logoRuta)): ?>
          <img src="<?php echo htmlspecialchars($logoRuta); ?>" alt="Logo Empresa" class="logo-img" style="margin-bottom: 3px;">
        <?php endif; ?>
        <div style="font-size: 10px; font-weight: bold; color: #333;">
          <?php echo htmlspecialchars($empresaEmisora['razon_social'] ?? ''); ?>
        </div>
        <div style="font-size: 8.5px; color: #6c757d;">
          RUC: <?php echo htmlspecialchars($empresaEmisora['ruc'] ?? 'Sin RUC'); ?>
        </div>
      </td>

      <!-- COLUMNA DERECHA: METADATOS DE EMISIÓN -->
      <td width="50%" style="vertical-align: top; text-align: right;">
        <div style="font-size: 9px; color: #495057;">
          <strong>Emisión:</strong> <?php echo date('d/m/Y h:i:s A'); ?><br>
          <strong>Generado por:</strong> <?php echo htmlspecialchars($_SESSION['nombre_usuario'] ?? 'Hernan Andre Ruiz'); ?>
        </div>
      </td>
    </tr>
  </table>

  <!-- LÍNEA DIVISORIA AZUL -->
  <div style="border-bottom: 2px solid #0d6efd; margin-bottom: 12px; clear: both;"></div>

  <!-- TÍTULO PRINCIPAL DEL REPORTE (DEBAJO DE LA LÍNEA AZUL) -->
  <div style="text-align: center; margin-bottom: 15px;">
    <h2 style="margin: 0; font-size: 16px; font-weight: bold; color: #0d6efd; text-transform: uppercase; letter-spacing: 0.5px;">
      REPORTE <?php echo $tipoReporte === 'DETALLADO' ? 'TÉCNICO DETALLADO DE ATENCIONES' : 'EJECUTIVO DE ATENCIONES'; ?>
    </h2>
    <div style="font-size: 9px; color: #6c757d; font-weight: 600; margin-top: 2px;">
      SISTEMA DE GESTIÓN HELP DESK & CONTROL DE TIEMPO EFECTIVO
    </div>
  </div>

  <!-- MEMBRETES Y PARÁMETROS DEL CLIENTE -->
  <div class="box-info">
    <table>
      <tr>
        <td width="60%">
          <strong>EMPRESA / CLIENTE ATENDIDO:</strong> <?php echo htmlspecialchars($cliente['razon_social']); ?><br>
          <strong>RUC / DOCUMENTO:</strong> <?php echo htmlspecialchars($cliente['num_documento']); ?><br>
          <strong>DIRECCIÓN:</strong> <?php echo htmlspecialchars($cliente['direccion'] ?? 'No registrada'); ?>
        </td>
        <td width="40%">
          <strong>FECHA DE CORTE INICIO:</strong> <?php echo date('d/m/Y', strtotime($fechaInicio)); ?><br>
          <strong>FECHA DE CORTE FIN:</strong> <?php echo date('d/m/Y', strtotime($fechaFin)); ?><br>
          <strong>MODALIDAD DE REPORTE:</strong> <?php echo $tipoReporte === 'DETALLADO' ? 'Informe de Diagnóstico y Trabajo' : 'Resumen Consolidado'; ?>
        </td>
      </tr>
    </table>
  </div>

  <!-- DASHBOARD RESUMEN -->
  <table class="metrics-table">
    <tr>
      <td width="25%">
        <div class="metric-card">
          <div class="metric-title">Total Atenciones</div>
          <div class="metric-value"><?php echo $totalTickets; ?></div>
        </div>
      </td>
      <td width="25%">
        <div class="metric-card">
          <div class="metric-title">Tickets Concluidos</div>
          <div class="metric-value" style="color: #198754;"><?php echo $concluidos; ?></div>
        </div>
      </td>
      <td width="25%">
        <div class="metric-card">
          <div class="metric-title">Pendientes / En Proceso</div>
          <div class="metric-value" style="color: #ffc107;"><?php echo $enProceso; ?></div>
        </div>
      </td>
      <td width="25%">
        <div class="metric-card" style="border-color: #0d6efd; background-color: #f0f7ff;">
          <div class="metric-title" style="color: #0d6efd;">Tiempo Efectivo Total</div>
          <div class="metric-value" style="font-size: 13px; color: #0d6efd;"><?php echo $tiempoConsolidado; ?></div>
        </div>
      </td>
    </tr>
  </table>

  <!-- DISTRIBUCIÓN SINTÉTICA -->
  <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
    <tr>
      <td width="50%" style="vertical-align: top; padding-right: 5px;">
        <table class="data-table" style="margin-top: 0;">
          <thead>
            <tr>
              <th>Categorías Frecuentes</th>
              <th style="width: 50px; text-align: center;">Cant.</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($porCategoria) > 0): ?>
              <?php foreach ($porCategoria as $cat => $cant): ?>
                <tr>
                  <td><?php echo htmlspecialchars($cat); ?></td>
                  <td style="text-align: center; font-weight: bold;"><?php echo $cant; ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="2" style="text-align: center;">Sin registros</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </td>
      <td width="50%" style="vertical-align: top; padding-left: 5px;">
        <table class="data-table" style="margin-top: 0;">
          <thead>
            <tr>
              <th>Usuarios con Mayor Requerimiento</th>
              <th style="width: 50px; text-align: center;">Cant.</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            arsort($porSolicitante);
            $topSol = array_slice($porSolicitante, 0, 5, true);
            if (count($topSol) > 0):
              foreach ($topSol as $sol => $cant): 
              ?>
                <tr>
                  <td><?php echo htmlspecialchars($sol); ?></td>
                  <td style="text-align: center; font-weight: bold;"><?php echo $cant; ?></td>
                </tr>
              <?php 
              endforeach; 
            else:
              ?>
              <tr>
                <td colspan="2" style="text-align: center;">Sin registros</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </td>
    </tr>
  </table>

  <!-- TABLA DE ATENCIONES -->
  <table class="data-table">
    <thead>
      <tr>
        <th style="width: 80px;">N° Ticket</th>
        <th style="width: 65px;">Fecha</th>
        <th>Solicitante / Usuario</th>
        <th>Categoría</th>
        <th style="width: 80px;">Técnico</th>
        <th style="width: 65px; text-align: center;">Estado</th>
        <th style="width: 75px; text-align: right;">Tiempo Real</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($atenciones) > 0): ?>
        <?php foreach ($atenciones as $at): ?>
          <tr>
            <td><strong><code><?php echo htmlspecialchars($at['codigo_ticket']); ?></code></strong></td>
            <td><?php echo date('d/m/Y', strtotime($at['fecha_atencion'])); ?></td>
            <td>
              <strong><?php echo htmlspecialchars($at['nombre_solicitante']); ?></strong><br>
              <span style="color: #6c757d; font-size: 8.5px;">DNI: <?php echo htmlspecialchars($at['documento_solicitante']); ?></span>
            </td>
            <td>
              <?php echo htmlspecialchars($at['categoria_nombre'] ?? 'General'); ?>
              <?php if (!empty($at['subcategoria_nombre'])): ?>
                <br><span style="color: #6c757d; font-size: 8.5px;">▪ <?php echo htmlspecialchars($at['subcategoria_nombre']); ?></span>
              <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($at['tecnico_nombre']); ?></td>
            <td style="text-align: center;">
              <?php if ($at['estado_cierre'] === 'CONCLUIDO'): ?>
                <span class="badge badge-success">Concluido</span>
              <?php else: ?>
                <span class="badge badge-warning"><?php echo str_replace('_', ' ', $at['estado_cierre']); ?></span>
              <?php endif; ?>
            </td>
            <td style="text-align: right; font-weight: bold; font-family: monospace;">
              <?php echo formatearTiempo($at['tiempo_efectivo_segundos']); ?>
            </td>
          </tr>

          <?php if ($tipoReporte === 'DETALLADO'): ?>
            <tr>
              <td colspan="7" style="background-color: #fafafa; padding: 6px 10px;">
                <div class="detail-box">
                  <strong>Requerimiento / Problema:</strong> <?php echo nl2br(htmlspecialchars($at['descripcion_requerimiento'])); ?>
                </div>
                <div class="detail-box" style="border-left-color: #198754; margin-bottom: 4px;">
                  <strong>Diagnóstico / Acciones Ejecutadas:</strong> <?php echo nl2br(htmlspecialchars($at['acciones_realizadas'])); ?>
                </div>
              </td>
            </tr>
          <?php endif; ?>

        <?php endforeach; ?>

        <tr class="total-row">
          <td colspan="6" style="text-align: right; text-transform: uppercase;">SUMATORIA TOTAL DE TIEMPO EFECTIVO ACUMULADO:</td>
          <td style="text-align: right; font-size: 11px; color: #0d6efd; font-family: monospace;">
            <?php echo sprintf("%02d:%02d:%02d", $horasTotales, $minutosTotales, $segundosTotales); ?>
          </td>
        </tr>

      <?php else: ?>
        <tr>
          <td colspan="7" style="text-align: center; padding: 20px; color: #6c757d;">
            No existen registros de atenciones para el cliente seleccionado en el rango de fechas indicado.
          </td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- FIRMAS -->
  <?php if ($tipoReporte === 'DETALLADO' && count($atenciones) > 0): ?>
    <table style="width: 100%; margin-top: 40px; border-collapse: collapse;">
      <tr>
        <td width="50%" style="text-align: center; vertical-align: bottom;">
          __________________________________________<br>
          <strong>TÉCNICO RESPONSABLE TI</strong><br>
          <span><?php echo htmlspecialchars($empresaEmisora['razon_social']); ?></span>
        </td>
        <td width="50%" style="text-align: center; vertical-align: bottom;">
          __________________________________________<br>
          <strong>CONFORMIDAD DEL CLIENTE</strong><br>
          <span><?php echo htmlspecialchars($cliente['razon_social']); ?></span>
        </td>
      </tr>
    </table>
  <?php endif; ?>

</body>
</html>