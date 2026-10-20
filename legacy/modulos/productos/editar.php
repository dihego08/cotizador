<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/header.php';

$id_producto = (int)($_GET['id'] ?? 0);

if ($id_producto <= 0) {
    header('Location: index.php');
    exit;
}

// Cargar los datos del producto
$stmt = $pdo->prepare("SELECT * FROM productos WHERE id_producto = :id");
$stmt->execute([':id' => $id_producto]);
$prod = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$prod) {
    header('Location: index.php');
    exit;
}

// Cargar categorías y marcas
$categorias = $pdo->query("SELECT id_categoria, nombre_categoria FROM categorias ORDER BY nombre_categoria ASC")->fetchAll(PDO::FETCH_ASSOC);
$marcas = $pdo->query("SELECT nombre_marca FROM marcas ORDER BY nombre_marca ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold m-0"><i class="fa-solid fa-pen-to-square me-2"></i>Editar Producto / Servicio</h2>
            <p class="text-muted m-0">Modifica la información del artículo seleccionado.</p>
        </div>
        <a href="index.php" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'duplicate_code'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>El código interno ingresado ya pertenece a otro producto.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="actualizar.php" method="POST">
                <input type="hidden" name="id_producto" value="<?= $prod['id_producto'] ?>">

                <!-- Tipo, Nombre y Código Interno -->
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Tipo *</label>
                        <select name="tipo" id="select_tipo" class="form-select fw-bold bg-light" required>
                            <option value="PRODUCTO" <?= ($prod['tipo'] ?? 'PRODUCTO') === 'PRODUCTO' ? 'selected' : '' ?>>📦 PRODUCTO</option>
                            <option value="SERVICIO" <?= ($prod['tipo'] ?? '') === 'SERVICIO' ? 'selected' : '' ?>>🛠️ SERVICIO</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Nombre del Producto / Servicio *</label>
                        <input type="text" name="nombre_producto" class="form-control" value="<?= htmlspecialchars($prod['nombre_producto']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Código Interno *</label>
                        <input type="text" name="codigo_interno" class="form-control" value="<?= htmlspecialchars($prod['codigo_interno']) ?>" required>
                    </div>
                </div>

                <!-- Bloque de Estructura de Precios -->
                <div class="card bg-light p-3 mb-3 border-0">
                    <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-calculator me-1"></i> Estructura de Precios</h6>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Moneda</label>
                            <select name="moneda" class="form-select">
                                <option value="PEN" <?= ($prod['moneda'] ?? 'PEN') === 'PEN' ? 'selected' : '' ?>>Soles (S/.)</option>
                                <option value="USD" <?= ($prod['moneda'] ?? '') === 'USD' ? 'selected' : '' ?>>Dólares ($)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Precio Costo</label>
                            <input type="number" step="0.01" name="precio_costo" id="precio_costo" class="form-control" value="<?= htmlspecialchars($prod['precio_costo'] ?? '0.00') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">% Utilidad</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="porcentaje_utilidad" id="porcentaje_utilidad" class="form-control" value="<?= htmlspecialchars($prod['porcentaje_utilidad'] ?? '30.00') ?>">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-success">Precio Venta (Sugerido)</label>
                            <input type="number" step="0.01" name="precio_venta" id="precio_venta" class="form-control fw-bold border-success text-success" value="<?= htmlspecialchars($prod['precio_venta'] ?? '0.00') ?>">
                        </div>
                    </div>
                </div>

                <!-- Códigos y Marca -->
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Código de Barra</label>
                        <input type="text" name="codigo_barra" class="form-control" value="<?= htmlspecialchars($prod['codigo_barra'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Código Proveedor</label>
                        <input type="text" name="codigo_proveedor" class="form-control" value="<?= htmlspecialchars($prod['codigo_proveedor'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Número de Parte</label>
                        <input type="text" name="numero_parte" class="form-control" value="<?= htmlspecialchars($prod['numero_parte'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Marca</label>
                        <div class="input-group">
                            <select name="marca" id="select_marca" class="form-select">
                                <option value="">-- Seleccionar Marca --</option>
                                <?php foreach ($marcas as $m): ?>
                                    <option value="<?= htmlspecialchars($m['nombre_marca']) ?>" <?= (($prod['marca'] ?? '') == $m['nombre_marca']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['nombre_marca']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalMarca" title="Nueva Marca">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modelo, Categoría y Unidad -->
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Modelo</label>
                        <input type="text" name="modelo" class="form-control" value="<?= htmlspecialchars($prod['modelo'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Categoría</label>
                        <select name="id_categoria" class="form-select">
                            <option value="">-- Sin Categoría --</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id_categoria'] ?>" <?= (($prod['id_categoria'] ?? '') == $cat['id_categoria']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nombre_categoria']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Unidad de Medida *</label>
                        <input type="text" name="unidad_medida" class="form-control" value="<?= htmlspecialchars($prod['unidad_medida'] ?? 'UNIDAD') ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="3"><?= htmlspecialchars($prod['descripcion'] ?? '') ?></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Agregar Nueva Marca -->
<div class="modal fade" id="modalMarca" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-tag me-2"></i>Agregar Nueva Marca</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nombre de la Marca *</label>
                    <input type="text" id="inputNuevaMarca" class="form-control" placeholder="Ej. HP, Dell, Samsung">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnGuardarMarca" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Guardar Marca
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// 1. Cálculo automático con la fórmula de Margen Real: Costo / (1 - % Utilidad)
function calcularPrecioVenta() {
    const costo = parseFloat(document.getElementById('precio_costo').value) || 0;
    const utilidad = parseFloat(document.getElementById('porcentaje_utilidad').value) || 0;

    if (utilidad >= 100) {
        alert('El porcentaje de utilidad debe ser menor al 100%.');
        document.getElementById('porcentaje_utilidad').value = 99;
        calcularPrecioVenta();
        return;
    }

    const divisor = 1 - (utilidad / 100);

    if (divisor > 0) {
        const precioVenta = costo / divisor;
        document.getElementById('precio_venta').value = precioVenta.toFixed(2);
    } else {
        document.getElementById('precio_venta').value = '0.00';
    }
}

document.getElementById('precio_costo').addEventListener('input', calcularPrecioVenta);
document.getElementById('porcentaje_utilidad').addEventListener('input', calcularPrecioVenta);

// 2. Función para guardar la marca por AJAX
function procesarGuardarMarca() {
    const nombreInput = document.getElementById('inputNuevaMarca');
    const nombre = nombreInput.value.trim();

    if (!nombre) {
        alert('Por favor, ingresa el nombre de la marca.');
        return;
    }

    fetch('guardar_marca_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'nombre_marca=' + encodeURIComponent(nombre)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const select = document.getElementById('select_marca');
            const newOption = new Option(data.nombre_marca, data.nombre_marca, true, true);
            select.add(newOption);
            nombreInput.value = '';
            
            const modalEl = document.getElementById('modalMarca');
            const modal = bootstrap.Modal.getInstance(modalEl);
            modal.hide();
        } else {
            alert(data.message || 'Error al guardar la marca.');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error en la solicitud al servidor.');
    });
}

document.getElementById('btnGuardarMarca').addEventListener('click', procesarGuardarMarca);

document.getElementById('inputNuevaMarca').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        procesarGuardarMarca();
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>