<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/header.php';

// Filtros de búsqueda
$buscar = $_GET['buscar'] ?? '';
$estado_filtro = $_GET['estado'] ?? '';

// Consulta a series_productos uniendo con productos y almacenes
$sql = "SELECT s.*, 
               p.nombre_producto AS producto_nombre, 
               p.codigo_interno, 
               a.nombre_almacen AS almacen_nombre 
        FROM series_productos s
        INNER JOIN productos p ON s.id_producto = p.id_producto
        INNER JOIN almacenes a ON s.id_almacen = a.id_almacen
        WHERE 1=1";

$params = [];

if (!empty($buscar)) {
    $sql .= " AND (s.numero_serie LIKE :buscar OR p.nombre_producto LIKE :buscar OR p.codigo_interno LIKE :buscar)";
    $params[':buscar'] = "%$buscar%";
}

if (!empty($estado_filtro)) {
    $sql .= " AND s.estado = :estado";
    $params[':estado'] = $estado_filtro;
}

$sql .= " ORDER BY s.id_serie DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$series = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cargar catálogo de Productos y Almacenes para el modal (Sin filtro 'activo' que no existe en estas tablas)
$productos = $pdo->query("SELECT id_producto, nombre_producto, codigo_interno FROM productos ORDER BY nombre_producto ASC")->fetchAll(PDO::FETCH_ASSOC);
$almacenes = $pdo->query("SELECT id_almacen, nombre_almacen FROM almacenes ORDER BY nombre_almacen ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="fa-solid fa-barcode me-2"></i>Seguimiento por Números de Serie</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalSerie">
            <i class="fa-solid fa-plus me-1"></i> Registrar Serie
        </button>
    </div>

    <!-- Mensajes de Notificación -->
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i>¡Número de serie registrado correctamente!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'duplicate_serial'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>El número de serie ingresado ya existe en el sistema.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Buscador y Filtros -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="buscar" class="form-control" placeholder="Buscar por N° serie, producto o código..." value="<?= htmlspecialchars($buscar) ?>">
                </div>
                <div class="col-md-4">
                    <select name="estado" class="form-select">
                        <option value="">-- Todos los estados --</option>
                        <option value="DISPONIBLE" <?= $estado_filtro === 'DISPONIBLE' ? 'selected' : '' ?>>DISPONIBLE</option>
                        <option value="DESPACHADO" <?= $estado_filtro === 'DESPACHADO' ? 'selected' : '' ?>>DESPACHADO</option>
                        <option value="EN_SOPORTE" <?= $estado_filtro === 'EN_SOPORTE' ? 'selected' : '' ?>>EN SOPORTE</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-secondary w-100"><i class="fa-solid fa-filter me-1"></i>Filtrar</button>
                    <a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de resultados -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>N° Serie</th>
                        <th>Producto (Código)</th>
                        <th>Almacén</th>
                        <th>Estado</th>
                        <th>Fecha Registro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($series)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No se encontraron números de serie registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($series as $s): ?>
                            <tr>
                                <td><?= $s['id_serie'] ?></td>
                                <td><code class="fw-bold fs-6 text-dark"><?= htmlspecialchars($s['numero_serie']) ?></code></td>
                                <td>
                                    <strong><?= htmlspecialchars($s['producto_nombre']) ?></strong>
                                    <small class="text-muted d-block">Cód: <?= htmlspecialchars($s['codigo_interno'] ?? 'N/A') ?></small>
                                </td>
                                <td><?= htmlspecialchars($s['almacen_nombre']) ?></td>
                                <td>
                                    <?php
                                        $badgeClass = match($s['estado']) {
                                            'DISPONIBLE' => 'bg-success',
                                            'DESPACHADO' => 'bg-secondary',
                                            'EN_SOPORTE' => 'bg-warning text-dark',
                                            default      => 'bg-dark'
                                        };
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= $s['estado'] ?></span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($s['fecha_registro'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Registrar Serie -->
<div class="modal fade" id="modalSerie" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="guardar.php" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fa-solid fa-barcode me-2"></i>Registrar Número de Serie</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Producto</label>
                        <select name="id_producto" class="form-select" required>
                            <option value="">Seleccione un producto...</option>
                            <?php foreach ($productos as $p): ?>
                                <option value="<?= $p['id_producto'] ?>">
                                    <?= htmlspecialchars($p['nombre_producto']) ?> 
                                    <?= !empty($p['codigo_interno']) ? '('.htmlspecialchars($p['codigo_interno']).')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Almacén de Ubicación</label>
                        <select name="id_almacen" class="form-select" required>
                            <option value="">Seleccione un almacén...</option>
                            <?php foreach ($almacenes as $a): ?>
                                <option value="<?= $a['id_almacen'] ?>"><?= htmlspecialchars($a['nombre_almacen']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Número de Serie</label>
                        <input type="text" name="numero_serie" class="form-control" placeholder="Ej. SN-884920194" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar Serie</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>