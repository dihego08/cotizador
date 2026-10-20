<?php
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Consulta para listar los movimientos ordenados por fecha reciente
$sql = "SELECT m.*, p.nombre_producto, p.codigo_interno, a.nombre_almacen 
        FROM movimientos m
        INNER JOIN productos p ON m.id_producto = p.id_producto
        INNER JOIN almacenes a ON m.id_almacen = a.id_almacen
        ORDER BY m.id_movimiento DESC";

$stmt = $pdo->query($sql);
$movimientos = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold m-0"><i class="fa-solid fa-arrow-right-arrow-left me-2"></i>Movimientos de Inventario</h2>
        <p class="text-muted m-0">Historial de entradas y salidas de almacén.</p>
    </div>
    <a href="crear.php" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> Registrar Movimiento</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Producto</th>
                        <th>Almacén</th>
                        <th>Cantidad</th>
                        <th>Motivo / Referencia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($movimientos)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No hay movimientos registrados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($movimientos as $mov): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($mov['fecha_movimiento'])) ?></td>
                                <td>
                                    <?php if ($mov['tipo_movimiento'] === 'ENTRADA'): ?>
                                        <span class="badge bg-success"><i class="fa-solid fa-arrow-down me-1"></i> ENTRADA</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="fa-solid fa-arrow-up me-1"></i> SALIDA</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($mov['nombre_producto']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($mov['codigo_interno']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($mov['nombre_almacen']) ?></td>
                                <td class="fw-bold <?= $mov['tipo_movimiento'] === 'ENTRADA' ? 'text-success' : 'text-danger' ?>">
                                    <?= $mov['tipo_movimiento'] === 'ENTRADA' ? '+' : '-' ?><?= $mov['cantidad'] ?>
                                </td>
                                <td><?= htmlspecialchars($mov['motivo'] ?? 'Sin especificación') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>