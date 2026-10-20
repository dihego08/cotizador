<?php
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Obtener todas las categorías
$stmt = $pdo->query("SELECT * FROM categorias ORDER BY id_categoria DESC");
$categorias = $stmt->fetchAll();
?>

<div class="row">
    <!-- Formulario de Creación -->
    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fa-solid fa-plus me-2"></i>Nueva Categoría</h5>
            </div>
            <div class="card-body">
                <form action="guardar.php" method="POST">
                    <div class="mb-3">
                        <label for="nombre_categoria" class="form-label">Nombre *</label>
                        <input type="text" class="form-control" id="nombre_categoria" name="nombre_categoria" required placeholder="Ej. Electrónica">
                    </div>
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3" placeholder="Descripción opcional"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar Categoría</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabla de Categorías Registradas -->
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="card-title mb-0"><i class="fa-solid fa-tags me-2"></i>Listado de Categorías</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Categoría</th>
                                <th>Descripción</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categorias)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">No hay categorías registradas.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categorias as $cat): ?>
                                    <tr>
                                        <td><?= $cat['id_categoria'] ?></td>
                                        <td class="fw-bold"><?= htmlspecialchars($cat['nombre_categoria']) ?></td>
                                        <td><?= htmlspecialchars($cat['descripcion'] ?? 'Sin descripción') ?></td>
                                        <td class="text-center">
                                            <a href="eliminar.php?id=<?= $cat['id_categoria'] ?>" 
                                               class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('¿Seguro de eliminar esta categoría?')">
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