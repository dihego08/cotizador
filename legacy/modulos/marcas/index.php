<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/header.php';

// Consulta para listar marcas y contar cuántos productos tiene cada una
$sql = "SELECT m.id_marca, m.nombre_marca, COUNT(p.id_producto) AS total_productos 
        FROM marcas m 
        LEFT JOIN productos p ON m.nombre_marca = p.marca 
        GROUP BY m.id_marca, m.nombre_marca 
        ORDER BY m.nombre_marca ASC";

$marcas = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold m-0"><i class="fa-solid fa-tags me-2"></i>Gestión de Marcas</h2>
            <p class="text-muted m-0">Administra las marcas registradas en tu sistema.</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearMarca">
            <i class="fa-solid fa-plus me-1"></i> Nueva Marca
        </button>
    </div>

    <!-- Alertas -->
    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'created'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i>Marca registrada con éxito.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['msg'] === 'updated'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i>Marca y productos asociados actualizados correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['msg'] === 'deleted'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-trash-can me-2"></i>Marca eliminada con éxito.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 10%;">ID</th>
                            <th>Nombre de la Marca</th>
                            <th class="text-center">Productos Vinculados</th>
                            <th class="text-center" style="width: 15%;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($marcas)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No hay marcas registradas.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($marcas as $m): ?>
                                <tr>
                                    <td><?= $m['id_marca'] ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($m['nombre_marca']) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary rounded-pill">
                                            <?= $m['total_productos'] ?> producto(s)
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <!-- Botón Editar -->
                                        <button class="btn btn-sm btn-outline-warning me-1" 
                                                onclick="prepararEdicion(<?= $m['id_marca'] ?>, '<?= htmlspecialchars($m['nombre_marca'], ENT_QUOTES) ?>')"
                                                title="Editar Marca">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <!-- Botón Eliminar -->
                                        <a href="acciones.php?accion=eliminar&id=<?= $m['id_marca'] ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('¿Estás seguro de eliminar la marca <?= htmlspecialchars($m['nombre_marca'], ENT_QUOTES) ?>?')"
                                           title="Eliminar Marca">
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

<!-- Modal Crear Marca -->
<div class="modal fade" id="modalCrearMarca" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="acciones.php?accion=crear" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-plus me-2"></i>Nueva Marca</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-bold">Nombre de la Marca *</label>
                    <input type="text" name="nombre_marca" class="form-control" placeholder="Ej. Sony, Apple, Samsung" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Marca -->
<div class="modal fade" id="modalEditarMarca" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="acciones.php?accion=editar" method="POST">
                <input type="hidden" name="id_marca" id="edit_id_marca">
                <input type="hidden" name="nombre_anterior" id="edit_nombre_anterior">
                
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square me-2"></i>Editar Marca</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-bold">Nombre de la Marca *</label>
                    <input type="text" name="nombre_marca" id="edit_nombre_marca" class="form-control" required>
                    <small class="text-muted mt-1 d-block">
                        <i class="fa-solid fa-info-circle me-1"></i>Al cambiar el nombre, se actualizará automáticamente en todos los productos que usen esta marca.
                    </small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function prepararEdicion(id, nombre) {
    document.getElementById('edit_id_marca').value = id;
    document.getElementById('edit_nombre_anterior').value = nombre;
    document.getElementById('edit_nombre_marca').value = nombre;
    
    var modal = new bootstrap.Modal(document.getElementById('modalEditarMarca'));
    modal.show();
}
</script>

<?php require_once '../../includes/footer.php'; ?>