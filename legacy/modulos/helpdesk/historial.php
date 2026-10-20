<?php
session_start();
require_once '../../config/db.php'; // Conexión PDO

// Obtener filtros enviados por GET
$idCliente   = isset($_GET['id_cliente']) ? $_GET['id_cliente'] : '';$estado      = isset($_GET['estado_cierre']) ?$_GET['estado_cierre'] : '';
$fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');$fechaFin    = isset($_GET['fecha_fin']) ?$_GET['fecha_fin'] : date('Y-m-d');

// Consulta base con JOINs a Clientes, Categorías y Usuarios
$sql = "SELECT 
            ha.id_atencion,
            ha.codigo_ticket,
            c.razon_social AS cliente_nombre,
            ha.documento_solicitante,
            ha.nombre_solicitante,
            hc.nombre AS categoria_nombre,
            ha.canal_atencion,
            u.nombre_usuario AS tecnico_nombre,
            ha.estado_cierre,
            ha.fecha_atencion,
            ha.tiempo_efectivo_segundos,
            ha.tiempo_efectivo_horas
        FROM helpdesk_atenciones ha
        INNER JOIN clientes c ON ha.id_cliente = c.id_cliente
        LEFT JOIN helpdesk_categorias hc ON ha.categoria = hc.id_categoria
        INNER JOIN usuarios u ON ha.id_tecnico = u.id_usuario
        WHERE ha.fecha_atencion BETWEEN :fecha_inicio AND :fecha_fin";

$params = [
    ':fecha_inicio' => $fechaInicio,
    ':fecha_fin'    => $fechaFin
];

if (!empty($idCliente)) {$sql .= " AND ha.id_cliente = :id_cliente";
    $params[':id_cliente'] =$idCliente;
}

if (!empty($estado)) {$sql .= " AND ha.estado_cierre = :estado_cierre";
    $params[':estado_cierre'] =$estado;
}

$sql .= " ORDER BY ha.id_atencion DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets =$stmt->fetchAll(PDO::FETCH_ASSOC);

// Cargar catálogo de clientes para el filtro
$sqlClientes = "SELECT id_cliente, razon_social FROM clientes WHERE es_cliente_helpdesk = 1 ORDER BY razon_social ASC";
$listaClientes = $pdo->query($sqlClientes)->fetchAll(PDO::FETCH_ASSOC);

// Función para formatear segundos a HH:MM:SS
function formatearSegundos($seg) {
    $hrs = str_pad(floor($seg / 3600), 2, '0', STR_PAD_LEFT);
    $min = str_pad(floor(($seg % 3600) / 60), 2, '0', STR_PAD_LEFT);
    $sec = str_pad($seg % 60, 2, '0', STR_PAD_LEFT);
    return "{$hrs}:{$min}:{$sec}";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Historial de Atenciones - Help Desk</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container-fluid py-4 px-4">
  
  <!-- CABECERA DE LA VISTA -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h3 class="fw-bold text-dark mb-0"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>Historial de Tickets de Soporte</h3>
      <p class="text-muted small mb-0">Consulta, seguimiento y control del tiempo efectivo de atenciones realizadas.</p>
    </div>
    <div>
      <a href="index.php" class="btn btn-primary fw-bold"><i class="fa-solid fa-plus me-1"></i> Nueva Atención</a>
    </div>
  </div>

  <!-- TARJETA DE FILTROS Y BOTÓN DE REPORTES -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
      <form id="formFiltros" method="GET" action="historial.php" class="row g-3 align-items-end">
        
        <div class="col-md-3">
          <label class="form-label fw-semibold">Empresa / Cliente</label>
          <select name="id_cliente" id="id_cliente" class="form-select">
            <option value="">-- Todos los Clientes --</option>
            <?php foreach ($listaClientes as$cli): ?>
              <option value="<?php echo $cli['id_cliente']; ?>" <?php echo $idCliente ==$cli['id_cliente'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cli['razon_social']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label fw-semibold">Estado de Cierre</label>
          <select name="estado_cierre" class="form-select">
            <option value="">-- Todos --</option>
            <option value="CONCLUIDO" <?php echo $estado == 'CONCLUIDO' ? 'selected' : ''; ?>>CONCLUIDO</option>
            <option value="EN_PROCESO" <?php echo $estado == 'EN_PROCESO' ? 'selected' : ''; ?>>EN PROCESO</option>
            <option value="ESCALADO_N2" <?php echo $estado == 'ESCALADO_N2' ? 'selected' : ''; ?>>ESCALADO N2</option>
            <option value="ESPERA_REPUESTO" <?php echo $estado == 'ESPERA_REPUESTO' ? 'selected' : ''; ?>>ESPERA REPUESTO</option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label fw-semibold">Fecha Inicio</label>
          <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" value="<?php echo $fechaInicio; ?>">
        </div>

        <div class="col-md-2">
          <label class="form-label fw-semibold">Fecha Fin</label>
          <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" value="<?php echo $fechaFin; ?>">
        </div>

        <div class="col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-outline-primary fw-bold w-100"><i class="fa-solid fa-filter me-1"></i> Filtrar</button>
          
          <!-- BOTÓN DESPLEGABLE DE GENERACIÓN DE REPORTES PDF -->
          <div class="dropdown w-100">
            <button class="btn btn-danger fw-bold dropdown-toggle w-100" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fa-solid fa-file-pdf me-1"></i> Reporte PDF
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <a class="dropdown-item" href="#" onclick="generarReportePDF('RESUMEN'); return false;">
                  <i class="fa-solid fa-chart-pie me-2 text-primary"></i>Reporte Ejecutivo (Resumen)
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="#" onclick="generarReportePDF('DETALLADO'); return false;">
                  <i class="fa-solid fa-list-check me-2 text-success"></i>Reporte Técnico (Detallado)
                </a>
              </li>
            </ul>
          </div>

          <a href="historial.php" class="btn btn-outline-secondary" title="Limpiar Filtros"><i class="fa-solid fa-rotate-left"></i></a>
        </div>

      </form>
    </div>
  </div>

  <!-- TABLA DE RESULTADOS -->
  <div class="card shadow-sm border-0">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Ticket</th>
              <th>Fecha</th>
              <th>Cliente</th>
              <th>Solicitante</th>
              <th>Categoría</th>
              <th>Canal</th>
              <th>Técnico</th>
              <th>Tiempo Efectivo</th>
              <th>Estado</th>
              <th class="text-center">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($tickets) > 0): ?>
              <?php foreach ($tickets as$t): ?>
                <tr>
                  <td><code><?php echo htmlspecialchars($t['codigo_ticket']); ?></code></td>
                  <td><small><?php echo date('d/m/Y', strtotime($t['fecha_atencion'])); ?></small></td>
                  <td><strong><?php echo htmlspecialchars($t['cliente_nombre']); ?></strong></td>
                  <td>
                    <?php echo htmlspecialchars($t['nombre_solicitante']); ?><br>
                    <small class="text-muted">DNI: <?php echo htmlspecialchars($t['documento_solicitante']); ?></small>
                  </td>
                  <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($t['categoria_nombre'] ?? 'General'); ?></span></td>
                  <td><small><?php echo htmlspecialchars($t['canal_atencion']); ?></small></td>
                  <td><small><?php echo htmlspecialchars($t['tecnico_nombre']); ?></small></td>
                  <td>
                    <span class="badge bg-info text-dark font-monospace">
                      <i class="fa-solid fa-stopwatch me-1"></i><?php echo formatearSegundos($t['tiempo_efectivo_segundos']); ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($t['estado_cierre'] == 'CONCLUIDO'): ?>
                      <span class="badge bg-success">Concluido</span>
                    <?php else: ?>
                      <span class="badge bg-warning text-dark"><?php echo str_replace('_', ' ', $t['estado_cierre']); ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="alert('Detalles del ticket: <?php echo $t['codigo_ticket']; ?>')" title="Ver Detalle">
                      <i class="fa-solid fa-eye"></i>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="10" class="text-center py-4 text-muted">
                  <i class="fa-solid fa-folder-open fs-3 d-block mb-2"></i>
                  No se encontraron atenciones registradas en el rango seleccionado.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<!-- Bootstrap 5 JS Bundle (Requerido para la funcionalidad del dropdown) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function generarReportePDF(tipoReporte) {
    const idCliente = document.getElementById('id_cliente').value;
    const fechaInicio = document.getElementById('fecha_inicio').value;
    const fechaFin = document.getElementById('fecha_fin').value;

    if (!idCliente) {
        alert('Por favor, seleccione una Empresa / Cliente específico para generar el reporte de atenciones.');
        document.getElementById('id_cliente').focus();
        return;
    }

    const url = `generar_reporte_pdf.php?id_cliente=${idCliente}&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&tipo_reporte=${tipoReporte}`;
    window.open(url, '_blank');
}
</script>

</body>
</html>