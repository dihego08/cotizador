<?php
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Consulta para ver el stock consolidado por Almacén y Producto
$sql = "SELECT s.*, p.codigo_interno, p.nombre_producto, p.unidad_medida, c.nombre_categoria, a.nombre_almacen 
        FROM stock s
        INNER JOIN productos p ON s.id_producto = p.id_producto
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
        INNER JOIN almacenes a ON s.id_almacen = a.id_almacen
        ORDER BY a.nombre_almacen ASC, p.nombre_producto ASC";

$stmt = $pdo->query($sql);
$stocks = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold m-0"><i class="fa-solid fa-boxes-stacked me-2"></i>Stock de Inventario</h2>
        <p class="text-muted m-0">Cantidades disponibles en tiempo real por almacén.</p>
    </div>
    <a href="../movimientos/crear.php" class="btn btn-success"><i class="fa-solid fa-arrow-right-arrow-left me-1"></i> Registrar Movimiento</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Almacén</th>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th class="text-end">Stock Actual</th>
                        <th>U. Medida</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stocks)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Aún no hay registros de stock. Realiza una ENTRADA desde el módulo de movimientos.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($stocks as $item): ?>
                            <tr>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($item['nombre_almacen']) ?></span></td>
                                <td><code><?= htmlspecialchars($item['codigo_interno']) ?></code></td>
                                <td class="fw-bold"><?= htmlspecialchars($item['nombre_producto']) ?></td>
                                <td><?= htmlspecialchars($item['nombre_categoria'] ?? 'Sin categoría') ?></td>
                                <td class="text-end fw-bold fs-5 <?= $item['cantidad_actual'] > 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= number_format($item['cantidad_actual'], 2) ?>
                                </td>
                                <td><?= htmlspecialchars($item['unidad_medida']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>