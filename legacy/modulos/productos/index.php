<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Consulta para obtener productos junto con el nombre de su categoría
$sql = "SELECT p.*, c.nombre_categoria 
        FROM productos p 
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria 
        ORDER BY p.id_producto DESC";

$stmt = $pdo->query($sql);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../../includes/header.php';
?>

<div class="container-fluid mb-5">
    <!-- Encabezado y Botones de Acción -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold m-0"><i class="fa-solid fa-boxes-stacked me-2"></i>Catálogo de Productos</h2>
            <p class="text-muted m-0">Administra los artículos de tu inventario e importa datos masivamente.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="importar.php?action=descargar_plantilla" class="btn btn-outline-success fw-bold shadow-sm">
                <i class="fa-solid fa-file-excel me-1"></i> Plantilla CSV
            </a>
            <button type="button" class="btn btn-success fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalImportarProductos">
                <i class="fa-solid fa-file-import me-1"></i> Importar CSV
            </button>
            <a href="crear.php" class="btn btn-primary fw-bold shadow-sm">
                <i class="fa-solid fa-plus me-1"></i> Nuevo Producto
            </a>
        </div>
    </div>

    <!-- 1. ALERTAS VÍA SESIÓN (Procesadas desde importar.php, crear.php, etc.) -->
    <?php if (isset($_SESSION['alert'])): 
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
    ?>
        <div class="alert alert-<?= htmlspecialchars($alert['type']) ?> alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="fa-solid <?= $alert['type'] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?> me-2"></i>
            <strong><?= htmlspecialchars($alert['title']) ?>:</strong> <?= htmlspecialchars($alert['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- 2. ALERTAS VÍA GET (Soporte heredado) -->
    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'updated'): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i>Producto actualizado correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['msg'] === 'created'): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i>Producto registrado con éxito.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Tabla de Productos -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-3">Código Interno</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>U. Medida</th>
                            <th>N° Parte</th>
                            <th class="text-center" style="width: 120px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($productos)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-folder-open fa-2x mb-2 d-block"></i>
                                    No hay productos registrados. ¡Haz clic en <strong>"Nuevo Producto"</strong> o <strong>"Importar CSV"</strong> para empezar!
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($productos as $prod): ?>
                                <tr>
                                    <td class="ps-3">
                                        <span class="badge bg-secondary font-monospace fs-6"><?= htmlspecialchars($prod['codigo_interno']) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($prod['nombre_producto']) ?></div>
                                        <?php if (!empty($prod['marca']) || !empty($prod['modelo'])): ?>
                                            <small class="text-muted d-block">
                                                <?= !empty($prod['marca']) ? '<strong>Marca:</strong> ' . htmlspecialchars($prod['marca']) : '' ?>
                                                <?= (!empty($prod['marca']) && !empty($prod['modelo'])) ? ' | ' : '' ?>
                                                <?= !empty($prod['modelo']) ? '<strong>Mod:</strong> ' . htmlspecialchars($prod['modelo']) : '' ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-dark">
                                            <?= htmlspecialchars($prod['nombre_categoria'] ?? 'Sin Categoría') ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($prod['unidad_medida']) ?></td>
                                    <td><?= htmlspecialchars($prod['numero_parte'] ?? '-') ?></td>
                                    <td class="text-center pe-3">
                                        <!-- Botón Editar -->
                                        <a href="editar.php?id=<?= $prod['id_producto'] ?>" 
                                           class="btn btn-sm btn-outline-warning me-1" 
                                           title="Editar Producto">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <!-- Botón Eliminar -->
                                        <a href="eliminar.php?id=<?= $prod['id_producto'] ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('¿Estás seguro de eliminar este producto?')"
                                           title="Eliminar Producto">
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

<!-- MODAL PARA IMPORTACIÓN MASIVA DE PRODUCTOS -->
<div class="modal fade" id="modalImportarProductos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="importar.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-success text-white py-2">
                    <h6 class="modal-title fw-bold">
                        <i class="fa-solid fa-file-csv me-2"></i>Importación Masiva de Productos
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 text-xs mb-3">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        Descargue la plantilla, complete los datos de sus productos en Excel y guárdelo en formato <strong>CSV (delimitado por comas)</strong> antes de subirlo.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Seleccione el archivo CSV *</label>
                        <input type="file" name="archivo_csv" class="form-control" accept=".csv" required>
                    </div>

                    <div class="text-center pt-2">
                        <a href="importar.php?action=descargar_plantilla" class="text-decoration-none small text-success fw-bold">
                            <i class="fa-solid fa-download me-1"></i> ¿No tiene la plantilla? Descárguela aquí
                        </a>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-success fw-bold px-3">
                        <i class="fa-solid fa-upload me-1"></i> Subir e Importar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>