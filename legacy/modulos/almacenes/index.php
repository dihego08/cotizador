<?php
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Obtener lista de almacenes
$sql = "SELECT a.*, e.razon_social 
        FROM almacenes a 
        INNER JOIN empresas e ON a.id_empresa = e.id_empresa 
        ORDER BY a.id_almacen DESC";

$stmt = $pdo->query($sql);
$almacenes = $stmt->fetchAll();
?>

<div class="row">
    <!-- Formulario para crear Almacén -->
    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fa-solid fa-warehouse me-2"></i>Nuevo Almacén</h5>
            </div>
            <div class="card-body">
                <form action="guardar.php" method="POST">
                    <div class="mb-3">
                        <label for="nombre_almacen" class="form-label">Nombre del Almacén *</label>
                        <input type="text" class="form-control" id="nombre_almacen" name="nombre_almacen" required placeholder="Ej. Almacén Central">
                    </div>
                    <div class="mb-3">
                        <label for="ubicacion" class="form-label">Ubicación / Dirección</label>
                        <input type="text" class="form-control" id="ubicacion" name="ubicacion" placeholder="Ej. Zona Industrial Lote 4">
                    </div>
                    <button type="submit" class="btn btn-success w-100"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar Almacén</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabla de Almacenes -->
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="card-title mb-0"><i class="fa-solid fa-boxes-packing me-2"></i>Lista de Almacenes</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Almacén</th>
                                <th>Ubicación</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($almacenes)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">No hay almacenes registrados.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($almacenes as $alm): ?>
                                    <tr>
                                        <td><?= $alm['id_almacen'] ?></td>
                                        <td class="fw-bold"><?= htmlspecialchars($alm['nombre_almacen']) ?></td>
                                        <td><?= htmlspecialchars($alm['ubicacion'] ?? 'No especificada') ?></td>
                                        <td class="text-center">
                                            <a href="eliminar.php?id=<?= $alm['id_almacen'] ?>" 
                                               class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('¿Seguro de eliminar este almacén?')">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
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

<?php require_once '../../includes/footer.php'; ?>