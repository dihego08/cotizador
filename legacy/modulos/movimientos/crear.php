<?php
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Obtener productos y almacenes activos
$productos = $pdo->query("SELECT id_producto, codigo_interno, nombre_producto FROM productos ORDER BY nombre_producto ASC")->fetchAll();
$almacenes = $pdo->query("SELECT id_almacen, nombre_almacen FROM almacenes ORDER BY nombre_almacen ASC")->fetchAll();
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fa-solid fa-boxes-packing me-2"></i>Registrar Entrada o Salida</h5>
            </div>
            <div class="card-body">
                <form action="guardar.php" method="POST">
                    <div class="row g-3">
                        
                        <div class="col-md-6">
                            <label for="tipo_movimiento" class="form-label">Tipo de Movimiento *</label>
                            <select class="form-select" id="tipo_movimiento" name="tipo_movimiento" required>
                                <option value="ENTRADA">ENTRADA (Ingreso a stock)</option>
                                <option value="SALIDA">SALIDA (Retiro de stock)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="id_almacen" class="form-label">Almacén *</label>
                            <select class="form-select" id="id_almacen" name="id_almacen" required>
                                <option value="">-- Seleccionar Almacén --</option>
                                <?php foreach ($almacenes as $alm): ?>
                                    <option value="<?= $alm['id_almacen'] ?>"><?= htmlspecialchars($alm['nombre_almacen']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label for="id_producto" class="form-label">Producto *</label>
                            <select class="form-select" id="id_producto" name="id_producto" required>
                                <option value="">-- Seleccionar Producto --</option>
                                <?php foreach ($productos as $prod): ?>
                                    <option value="<?= $prod['id_producto'] ?>">
                                        [<?= htmlspecialchars($prod['codigo_interno']) ?>] <?= htmlspecialchars($prod['nombre_producto']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="cantidad" class="form-label">Cantidad *</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="cantidad" name="cantidad" required placeholder="0.00">
                        </div>

                        <div class="col-md-12">
                            <label for="motivo" class="form-label">Motivo / Observación</label>
                            <input type="text" class="form-control" id="motivo" name="motivo" placeholder="Ej. Compra Factura #1234, Devolución, Salida por venta, etc.">
                        </div>

                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="index.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Cancelar</a>
                        <button type="submit" class="btn btn-success"><i class="fa-solid fa-floppy-disk me-1"></i> Procesar Movimiento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>