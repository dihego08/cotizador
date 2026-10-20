<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/header.php';

$categorias = $pdo->query("SELECT id_categoria, nombre_categoria FROM categorias ORDER BY nombre_categoria ASC")->fetchAll(PDO::FETCH_ASSOC);
$marcas = $pdo->query("SELECT nombre_marca FROM marcas ORDER BY nombre_marca ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold m-0"><i class="fa-solid fa-plus me-2"></i>Nuevo Producto / Servicio</h2>
            <p class="text-muted m-0">Registra un nuevo ítem en tu catálogo para inventario o cotizaciones.</p>
        </div>
        <a href="index.php" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="guardar.php" method="POST">
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Tipo *</label>
                        <select name="tipo" id="select_tipo" class="form-select fw-bold bg-light" required>
                            <option value="PRODUCTO">📦 PRODUCTO</option>
                            <option value="SERVICIO">🛠️ SERVICIO</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Nombre del Producto / Servicio *</label>
                        <input type="text" name="nombre_producto" class="form-control" placeholder="Ej. Laptop HP o Servicio de Mantenimiento" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Código Interno *</label>
                        <input type="text" name="codigo_interno" class="form-control" placeholder="Ej. PROD-0001" required>
                    </div>
                </div>

                <!-- Bloque de Precios y Moneda -->
                <div class="card bg-light p-3 mb-3 border-0">
                    <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-calculator me-1"></i> Estructura de Precios</h6>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Moneda</label>
                            <select name="moneda" class="form-select">
                                <option value="PEN">Soles (S/.)</option>
                                <option value="USD">Dólares ($)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Precio Costo</label>
                            <input type="number" step="0.01" name="precio_costo" id="precio_costo" class="form-control" value="0.00">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">% Utilidad</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="porcentaje_utilidad" id="porcentaje_utilidad" class="form-control" value="30.00">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-success">Precio Venta (Sugerido)</label>
                            <input type="number" step="0.01" name="precio_venta" id="precio_venta" class="form-control fw-bold border-success text-success" value="0.00">
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Código de Barra</label>
                        <input type="text" name="codigo_barra" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Código Proveedor</label>
                        <input type="text" name="codigo_proveedor" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Número de Parte</label>
                        <input type="text" name="numero_parte" class="form-control">
                    </div>
                    
                    <!-- CAMPO MARCA CON BOTÓN AGREGAR NAVEGABLE -->
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Marca</label>
                        <div class="input-group">
                            <select name="marca" id="select_marca" class="form-select">
                                <option value="">-- Seleccionar --</option>
                                <?php foreach ($marcas as $m): ?>
                                    <option value="<?= htmlspecialchars($m['nombre_marca']) ?>"><?= htmlspecialchars($m['nombre_marca']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalMarca" title="Nueva Marca">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Modelo</label>
                        <input type="text" name="modelo" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Categoría</label>
                        <select name="id_categoria" class="form-select">
                            <option value="">-- Sin Categoría --</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id_categoria'] ?>"><?= htmlspecialchars($cat['nombre_categoria']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Unidad de Medida *</label>
                        <select name="unidad_medida" id="unidad_medida" class="form-select" required onchange="evaluarOtroUnidad(this)">
                            <option value="">-- Seleccionar Unidad --</option>
                            <option value="KILOGRAMOS">KILOGRAMOS</option>
                            <option value="LIBRAS">LIBRAS</option>
                            <option value="TONELADAS LARGAS">TONELADAS LARGAS</option>
                            <option value="TONELADAS MÉTRICAS">TONELADAS MÉTRICAS</option>
                            <option value="TONELADAS CORTAS">TONELADAS CORTAS</option>
                            <option value="GRAMOS">GRAMOS</option>
                            <option value="UNIDADES" selected>UNIDADES</option>
                            <option value="LITROS">LITROS</option>
                            <option value="GALONES">GALONES</option>
                            <option value="BARRILES">BARRILES</option>
                            <option value="LATAS">LATAS</option>
                            <option value="CAJAS">CAJAS</option>
                            <option value="MILLARES">MILLARES</option>
                            <option value="METROS CÚBICOS">METROS CÚBICOS</option>
                            <option value="METROS">METROS</option>
                            <option value="OTROS">OTROS (ESPECIFICAR)</option>
                        </select>
                    </div>
                </div>

                <!-- Campo extra que aparece solo si selecciona "OTROS (ESPECIFICAR)" -->
                <div class="mb-3 d-none" id="divOtraUnidad">
                    <label class="form-label fw-bold">Especifique Unidad de Medida *</label>
                    <input type="text" name="otra_unidad_medida" id="otra_unidad_medida" class="form-control" placeholder="Ej: PAQUETES, ROLLOS, etc.">
                </div>

                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="3"></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL PARA CREAR NUEVA MARCA AL VUELO -->
<div class="modal fade" id="modalMarca" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title fw-bold"><i class="fa-solid fa-tag me-2"></i>Agregar Nueva Marca</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nombre de la Marca *</label>
                    <input type="text" id="inputNuevaMarca" class="form-control" placeholder="Ej. Sony, Apple, Samsung">
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnGuardarMarca" class="btn btn-sm btn-primary fw-bold">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Guardar Marca
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Manejador de "Otros" en Unidad de Medida
function evaluarOtroUnidad(select) {
    const divOtro = document.getElementById('divOtraUnidad');
    const inputOtro = document.getElementById('otra_unidad_medida');
    if (select.value === 'OTROS') {
        divOtro.classList.remove('d-none');
        inputOtro.required = true;
    } else {
        divOtro.classList.add('d-none');
        inputOtro.required = false;
        inputOtro.value = '';
    }
}

// Cálculo automático de Margen Real: Costo / (1 - % Utilidad)
function calcularPrecioVenta() {
    const costo = parseFloat(document.getElementById('precio_costo').value) || 0;
    const utilidad = parseFloat(document.getElementById('porcentaje_utilidad').value) || 0;

    if (utilidad >= 100) {
        alert('El porcentaje de utilidad sobre la venta debe ser menor al 100%.');
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

// Procesar Nueva Marca vía AJAX
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
            
            // Verificar si la marca ya estaba en el desplegable
            let existe = false;
            for (let i = 0; i < select.options.length; i++) {
                if (select.options[i].value.toLowerCase() === data.nombre_marca.toLowerCase()) {
                    select.selectedIndex = i;
                    existe = true;
                    break;
                }
            }

            // Si no estaba, se crea la opción y se selecciona por defecto
            if (!existe) {
                const newOption = new Option(data.nombre_marca, data.nombre_marca, true, true);
                select.add(newOption);
            }

            nombreInput.value = '';
            
            // Cerrar el modal de Bootstrap
            const modalEl = document.getElementById('modalMarca');
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.hide();
        } else {
            alert(data.message || 'Error al guardar la marca.');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error al conectar con el servidor.');
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