<?php
require_once 'config/db.php';
require_once 'includes/header.php';

// 1. Obtener métricas generales
$totalProductos  = $pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();
$totalCategorias = $pdo->query("SELECT COUNT(*) FROM categorias")->fetchColumn();
$totalAlmacenes  = $pdo->query("SELECT COUNT(*) FROM almacenes")->fetchColumn();
$totalStock      = $pdo->query("SELECT COALESCE(SUM(cantidad_actual), 0) FROM stock")->fetchColumn();

// 2. Obtener los últimos 5 movimientos
$sqlMov = "SELECT m.*, p.nombre_producto, a.nombre_almacen 
           FROM movimientos m
           INNER JOIN productos p ON m.id_producto = p.id_producto
           INNER JOIN almacenes a ON m.id_almacen = a.id_almacen
           ORDER BY m.id_movimiento DESC LIMIT 5";
$ultimosMovimientos = $pdo->query($sqlMov)->fetchAll();

// 3. Alertas: Productos con stock bajo (menor o igual a 5 unidades) o sin stock
$sqlBajoStock = "SELECT p.codigo_interno, p.nombre_producto, COALESCE(SUM(s.cantidad_actual), 0) AS total_stock
                 FROM productos p
                 LEFT JOIN stock s ON p.id_producto = s.id_producto
                 GROUP BY p.id_producto, p.codigo_interno, p.nombre_producto
                 HAVING total_stock <= 5
                 ORDER BY total_stock ASC LIMIT 5";
$productosBajoStock = $pdo->query($sqlBajoStock)->fetchAll();
?>

<div class="mb-4">
    <h2 class="fw-bold m-0"><i class="fa-solid fa-gauge me-2"></i>Panel de Control (Dashboard)</h2>
    <p class="text-muted m-0">Resumen operativo general de tu inventario.</p>
</div>

<!-- Tarjetas de Resumen (KPIs) -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white shadow-sm h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-uppercase mb-1">Productos</h6>
                    <h2 class="fw-bold mb-0"><?= $totalProductos ?></h2>
                </div>
                <i class="fa-solid fa-boxes-stacked fa-3x opacity-50"></i>
            </div>
            <div class="card-footer bg-transparent border-0 text-white-50 text-end">
                <a href="modulos/productos/index.php" class="text-white text-decoration-none small">Ver catálogo <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-success text-white shadow-sm h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-uppercase mb-1">Stock Total</h6>
                    <h2 class="fw-bold mb-0"><?= number_format($totalStock, 0) ?></h2>
                </div>
                <i class="fa-solid fa-cubes fa-3x opacity-50"></i>
            </div>
            <div class="card-footer bg-transparent border-0 text-white-50 text-end">
                <a href="modulos/stock/index.php" class="text-white text-decoration-none small">Ver stock <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-warning text-dark shadow-sm h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-uppercase mb-1">Categorías</h6>
                    <h2 class="fw-bold mb-0"><?= $totalCategorias ?></h2>
                </div>
                <i class="fa-solid fa-tags fa-3x opacity-50"></i>
            </div>
            <div class="card-footer bg-transparent border-0 text-dark-50 text-end">
                <a href="modulos/categorias/index.php" class="text-dark text-decoration-none small">Ver categorías <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-info text-white shadow-sm h-100">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-uppercase mb-1">Almacenes</h6>
                    <h2 class="fw-bold mb-0"><?= $totalAlmacenes ?></h2>
                </div>
                <i class="fa-solid fa-warehouse fa-3x opacity-50"></i>
            </div>
            <div class="card-footer bg-transparent border-0 text-white-50 text-end">
                <a href="modulos/almacenes/index.php" class="text-white text-decoration-none small">Ver almacenes <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
    </div>
</div>

<!-- Tablas de Actividad Reciente y Alertas -->
<div class="row g-4">
    <!-- Últimos Movimientos -->
    <div class="col-md-7">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fs-6"><i class="fa-solid fa-clock-rotate-left me-2"></i>Últimos Movimientos</h5>
                <a href="modulos/movimientos/index.php" class="btn btn-sm btn-outline-light">Ver Todos</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Producto</th>
                                <th class="text-end">Cant.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ultimosMovimientos)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">Sin movimientos recientes.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($ultimosMovimientos as $m): ?>
                                    <tr>
                                        <td><small><?= date('d/m/Y H:i', strtotime($m['fecha_movimiento'])) ?></small></td>
                                        <td>
                                            <?php if ($m['tipo_movimiento'] === 'ENTRADA'): ?>
                                                <span class="badge bg-success">ENTRADA</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">SALIDA</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($m['nombre_producto']) ?></td>
                                        <td class="text-end fw-bold"><?= $m['cantidad'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerta Stock Bajo -->
    <div class="col-md-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-danger text-white">
                <h5 class="card-title mb-0 fs-6"><i class="fa-solid fa-triangle-exclamation me-2"></i>Alertas de Stock Bajo (≤ 5)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th class="text-end">Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($productosBajoStock)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-3 text-success">¡Todo en orden! No hay productos con bajo stock.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($productosBajoStock as $p): ?>
                                    <tr>
                                        <td><code><?= htmlspecialchars($p['codigo_interno']) ?></code></td>
                                        <td><?= htmlspecialchars($p['nombre_producto']) ?></td>
                                        <td class="text-end">
                                            <span class="badge bg-danger fs-6"><?= number_format($p['total_stock'], 0) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>