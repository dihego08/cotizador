<?php
// 1. Iniciar sesión PRIMERO
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Cargar dependencias y cabecera
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/header.php';

// 3. Obtener y validar el ID de la cotización
$idCotizacion = $_GET['id'] ?? $_GET['id_cotizacion'] ?? null;

if (!$idCotizacion) {
    $_SESSION['alert'] = [
        'type' => 'danger',
        'title' => 'Error',
        'message' => 'No se especificó un ID de cotización válido.'
    ];
    header('Location: index.php');
    exit;
}

// 4. Obtener datos principales de la cotización
$stmtCot = $pdo->prepare("SELECT * FROM cotizaciones WHERE id_cotizacion = :id");
$stmtCot->execute([':id' => $idCotizacion]);
$cotizacion = $stmtCot->fetch(PDO::FETCH_ASSOC);

if (!$cotizacion) {
    $_SESSION['alert'] = [
        'type' => 'warning',
        'title' => 'No encontrado',
        'message' => 'La cotización solicitada no existe o fue eliminada.'
    ];
    header('Location: index.php');
    exit;
}

// 5. Obtener los ítems/detalles de la cotización
$stmtDet = $pdo->prepare("
    SELECT d.*, 
           p.precio_costo, 
           p.porcentaje_utilidad AS prod_utilidad
    FROM cotizacion_detalles d
    LEFT JOIN productos p ON d.id_producto = p.id_producto
    WHERE d.id_cotizacion = :id 
    ORDER BY d.id_detalle ASC
");
$stmtDet->execute([':id' => $idCotizacion]);
$itemsExistentes = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

// 6. Obtener lista de clientes
$stmtClientes = $pdo->query("SELECT id_cliente, num_documento, razon_social, atencion, direccion, email FROM clientes ORDER BY razon_social ASC");
$clientes = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);

// 7. Obtener lista de productos
$stmtProductos = $pdo->query("SELECT id_producto, codigo_interno, nombre_producto, marca, descripcion, precio_costo, porcentaje_utilidad, precio_venta, unidad_medida FROM productos ORDER BY nombre_producto ASC");
$productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    /* Estilos para interfaz ultracompacta */
    .form-label-xs { font-size: 0.78rem; font-weight: 700; margin-bottom: 0.2rem; }
    .card-compact .card-header { padding: 0.4rem 0.75rem; font-size: 0.85rem; }
    .card-compact .card-body { padding: 0.6rem 0.75rem; }
    .text-xs { font-size: 0.75rem; }
</style>

<!-- Alert de sesión -->
<?php if (isset($_SESSION['alert'])): 
    $alert = $_SESSION['alert'];
    unset($_SESSION['alert']);
?>
    <div class="container-fluid mt-2">
        <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show shadow-sm py-2 mb-2" role="alert">
            <strong class="small"><?= $alert['title'] ?>:</strong> <span class="small"><?= $alert['message'] ?></span>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    </div>
<?php endif; ?>

<div class="container-fluid mb-5 mt-2">
    <form action="actualizar.php" method="POST" id="formCotizacion">
        
        <!-- Campo Oculto con el ID de la Cotización -->
        <input type="hidden" name="id_cotizacion" value="<?= $cotizacion['id_cotizacion'] ?>">

        <!-- Header / Título Compacto -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="fw-bold m-0 text-primary">
                    <i class="fa-solid fa-pen-to-square me-2"></i>Editar Cotización: <?= htmlspecialchars($cotizacion['numero_cotizacion']) ?>
                </h4>
                <span class="text-muted text-xs">Modifique los datos necesarios y guarde los cambios.</span>
            </div>
            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-xmark me-1"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-sm btn-primary px-3 fw-bold">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Actualizar Cotización
                </button>
            </div>
        </div>

        <!-- SECCIÓN SUPERIOR COMPACTADA -->
        <div class="row g-2 mb-3">
            <!-- DATOS DEL CLIENTE -->
            <div class="col-lg-6">
                <div class="card card-compact shadow-sm border-0 h-100">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center fw-bold text-secondary">
                        <span><i class="fa-solid fa-user me-1"></i> DATOS DEL CLIENTE</span>
                        <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#modalNuevoCliente">
                            <i class="fa-solid fa-user-plus me-1"></i> Nuevo
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-8">
                                <label class="form-label-xs">Cliente / Razón Social *</label>
                                <select name="id_cliente" id="cboCliente" class="form-select form-select-sm" required>
                                    <option value="">-- Seleccionar Cliente --</option>
                                    <?php foreach ($clientes as $cli): ?>
                                        <option value="<?= $cli['id_cliente'] ?>" 
                                                <?= ($cli['id_cliente'] == $cotizacion['id_cliente']) ? 'selected' : '' ?>
                                                data-doc="<?= htmlspecialchars($cli['num_documento']) ?>"
                                                data-dir="<?= htmlspecialchars($cli['direccion'] ?? '') ?>"
                                                data-email="<?= htmlspecialchars($cli['email'] ?? '') ?>"
                                                data-atencion="<?= htmlspecialchars($cli['atencion'] ?? '') ?>">
                                            <?= htmlspecialchars($cli['num_documento'] . ' - ' . $cli['razon_social']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-xs">Contacto (Atención)</label>
                                <input type="text" name="atencion" id="txtAtencion" class="form-control form-control-sm" value="<?= htmlspecialchars($cotizacion['atencion'] ?? '') ?>" placeholder="Ej: Janeth Torres">
                            </div>

                            <div class="col-md-8">
                                <label class="form-label-xs text-muted">Dirección Fiscal</label>
                                <input type="text" id="txtDireccion" class="form-control form-control-sm bg-light text-xs" readonly placeholder="Autocompletado...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-xs text-muted">E-mail</label>
                                <input type="text" id="txtEmail" class="form-control form-control-sm bg-light text-xs" readonly placeholder="Autocompletado...">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PARAMETROS DE LA COTIZACIÓN -->
            <div class="col-lg-6">
                <div class="card card-compact shadow-sm border-0 h-100">
                    <div class="card-header bg-light fw-bold text-secondary">
                        <i class="fa-solid fa-sliders me-1"></i> DATOS DE LA COTIZACIÓN
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label-xs">N° Cotización</label>
                                <input type="text" name="numero_cotizacion" class="form-control form-control-sm fw-bold text-primary" value="<?= htmlspecialchars($cotizacion['numero_cotizacion']) ?>" required readonly>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label-xs">Asunto / Proyecto</label>
                                <input type="text" name="asunto" class="form-control form-control-sm" value="<?= htmlspecialchars($cotizacion['asunto'] ?? '') ?>" placeholder="Ej: Mantenimiento Preventivo">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label-xs">Emisión *</label>
                                <input type="date" name="fecha_emision" id="fecha_emision" class="form-control form-control-sm" value="<?= $cotizacion['fecha_emision'] ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-xs">T. Cambio</label>
                                <input type="number" step="0.001" name="tipo_cambio" id="tipo_cambio" class="form-control form-control-sm" value="<?= floatval($cotizacion['tipo_cambio'] ?? 1.000) ?>" placeholder="1.000">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-xs">Moneda *</label>
                                <select name="moneda" id="cboMoneda" class="form-select form-select-sm">
                                    <option value="USD" <?= ($cotizacion['moneda'] === 'USD') ? 'selected' : '' ?>>$ USD</option>
                                    <option value="PEN" <?= ($cotizacion['moneda'] === 'PEN') ? 'selected' : '' ?>>S/ Soles</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-xs">Forma Pago *</label>
                                <select name="forma_pago" class="form-select form-select-sm">
                                    <option value="Contado" <?= ($cotizacion['forma_pago'] === 'Contado') ? 'selected' : '' ?>>Contado</option>
                                    <option value="Crédito 15 días" <?= ($cotizacion['forma_pago'] === 'Crédito 15 días') ? 'selected' : '' ?>>15 días</option>
                                    <option value="Crédito 30 días" <?= ($cotizacion['forma_pago'] === 'Crédito 30 días') ? 'selected' : '' ?>>30 días</option>
                                    <option value="50% Adelanto / 50% Entrega" <?= ($cotizacion['forma_pago'] === '50% Adelanto / 50% Entrega') ? 'selected' : '' ?>>50% / 50%</option>
                                </select>
                            </div>

                            <!-- VIGENCIA EDITABLE CON SUGERENCIAS -->
                            <div class="col-md-6">
                                <label class="form-label-xs">Vigencia Oferta *</label>
                                <input type="text" name="vigencia" id="txtVigencia" class="form-control form-control-sm" list="listaVigencias" value="<?= htmlspecialchars($cotizacion['vigencia'] ?? '15 días calendario') ?>" placeholder="Escriba o seleccione..." required>
                                <datalist id="listaVigencias">
                                    <option value="24 horas"></option>
                                    <option value="2 días calendario"></option>
                                    <option value="5 días calendario"></option>
                                    <option value="7 días calendario"></option>
                                    <option value="10 días calendario"></option>
                                    <option value="15 días calendario"></option>
                                    <option value="30 días calendario"></option>
                                    <option value="Hasta agotar stock"></option>
                                </datalist>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-xs">Plazo de Entrega</label>
                                <input type="text" name="tiempo_entrega" class="form-control form-control-sm" value="<?= htmlspecialchars($cotizacion['tiempo_entrega'] ?? '') ?>" placeholder="Ej: 3 a 5 días hábiles">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABLA DE ITEMS -->
        <div class="card card-compact shadow-sm border-0 mb-3">
            <div class="card-header bg-light d-flex justify-content-between align-items-center fw-bold text-secondary">
                <span><i class="fa-solid fa-list-check me-1"></i> DETALLE DE PRODUCTOS / SERVICIOS</span>
                <button type="button" class="btn btn-xs btn-success py-1 px-2 fw-bold" onclick="agregarFilaItem()">
                    <i class="fa-solid fa-plus me-1"></i> Agregar Ítem
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" id="tablaItems" style="font-size: 0.82rem;">
                        <thead class="table-light text-center fw-bold">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>DESCRIPCIÓN / OBSERVACIÓN</th>
                                <th style="width: 100px;">MARCA</th>
                                <th style="width: 75px;">CANT.</th>
                                <th style="width: 95px;">UNID.</th>
                                <th style="width: 80px;" title="Porcentaje de utilidad">% UTIL.</th>
                                <th style="width: 110px;">P. UNIT.</th>
                                <th style="width: 110px;">PARCIAL</th>
                                <th style="width: 40px;"></th>
                            </tr>
                        </thead>
                        <tbody id="tbodyItems">
                            <!-- Filas dinámicas cargadas por JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ZONA INFERIOR: CLÁUSULAS ADICIONALES (MODALES) + TOTALES -->
        <div class="row g-3 align-items-start">
            
            <!-- PANEL DE CLÁUSULAS ADICIONALES -->
            <div class="col-md-7 col-lg-8">
                <div class="card card-compact shadow-sm border-0">
                    <div class="card-header bg-light fw-bold text-secondary d-flex justify-content-between align-items-center">
                        <span><i class="fa-solid fa-sliders me-1"></i> CLÁUSULAS Y CONDICIONES ADICIONALES</span>
                        <span class="badge bg-secondary font-monospace" style="font-size: 0.7rem;">Opcional</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            
                            <!-- 1. Alcance -->
                            <?php $hasAlcance = !empty(trim($cotizacion['alcance'] ?? '')); ?>
                            <div class="col-12">
                                <div class="border rounded p-2 bg-light d-flex align-items-center justify-content-between">
                                    <div class="form-check m-0">
                                        <input class="form-check-input" type="checkbox" id="chkAlcance" <?= $hasAlcance ? 'checked' : '' ?> onchange="toggleClausula('alcance')">
                                        <label class="form-check-label fw-bold text-dark text-xs" for="chkAlcance">
                                            <i class="fa-solid fa-circle-check text-success me-1"></i> Incluir Alcance del Servicio / Proyecto
                                        </label>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span id="badgeAlcance" class="badge <?= $hasAlcance ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-muted border' ?> text-xs">
                                            <?= $hasAlcance ? '✓ Texto asignado (' . strlen($cotizacion['alcance']) . ' chars)' : 'Sin texto' ?>
                                        </span>
                                        <button type="button" id="btnAlcance" class="btn btn-xs btn-outline-primary py-0" <?= $hasAlcance ? '' : 'disabled' ?> onclick="abrirModalClausula('alcance')">
                                            <i class="fa-solid fa-pen-to-square me-1"></i> Editar
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Restricciones -->
                            <?php $hasRestricciones = !empty(trim($cotizacion['restricciones'] ?? '')); ?>
                            <div class="col-12">
                                <div class="border rounded p-2 bg-light d-flex align-items-center justify-content-between">
                                    <div class="form-check m-0">
                                        <input class="form-check-input" type="checkbox" id="chkRestricciones" <?= $hasRestricciones ? 'checked' : '' ?> onchange="toggleClausula('restricciones')">
                                        <label class="form-check-label fw-bold text-dark text-xs" for="chkRestricciones">
                                            <i class="fa-solid fa-ban text-danger me-1"></i> Incluir Restricciones y Exclusiones
                                        </label>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span id="badgeRestricciones" class="badge <?= $hasRestricciones ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-muted border' ?> text-xs">
                                            <?= $hasRestricciones ? '✓ Texto asignado (' . strlen($cotizacion['restricciones']) . ' chars)' : 'Sin texto' ?>
                                        </span>
                                        <button type="button" id="btnRestricciones" class="btn btn-xs btn-outline-primary py-0" <?= $hasRestricciones ? '' : 'disabled' ?> onclick="abrirModalClausula('restricciones')">
                                            <i class="fa-solid fa-pen-to-square me-1"></i> Editar
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Condiciones -->
                            <?php $hasCondiciones = !empty(trim($cotizacion['condiciones_comerciales'] ?? '')); ?>
                            <div class="col-12">
                                <div class="border rounded p-2 bg-light d-flex align-items-center justify-content-between">
                                    <div class="form-check m-0">
                                        <input class="form-check-input" type="checkbox" id="chkCondiciones" <?= $hasCondiciones ? 'checked' : '' ?> onchange="toggleClausula('condiciones')">
                                        <label class="form-check-label fw-bold text-dark text-xs" for="chkCondiciones">
                                            <i class="fa-solid fa-handshake text-primary me-1"></i> Incluir Condiciones Comercial / Garantía
                                        </label>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span id="badgeCondiciones" class="badge <?= $hasCondiciones ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-muted border' ?> text-xs">
                                            <?= $hasCondiciones ? '✓ Texto asignado (' . strlen($cotizacion['condiciones_comerciales']) . ' chars)' : 'Sin texto' ?>
                                        </span>
                                        <button type="button" id="btnCondiciones" class="btn btn-xs btn-outline-primary py-0" <?= $hasCondiciones ? '' : 'disabled' ?> onclick="abrirModalClausula('condiciones')">
                                            <i class="fa-solid fa-pen-to-square me-1"></i> Editar
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <!-- CAMPOS OCULTOS CON EL CONTENIDO RECUPERADO -->
            <textarea name="alcance" id="hidden_alcance" class="d-none"><?= htmlspecialchars($cotizacion['alcance'] ?? '') ?></textarea>
            <textarea name="restricciones" id="hidden_restricciones" class="d-none"><?= htmlspecialchars($cotizacion['restricciones'] ?? '') ?></textarea>
            <textarea name="condiciones_comerciales" id="hidden_condiciones" class="d-none"><?= htmlspecialchars($cotizacion['condiciones_comerciales'] ?? '') ?></textarea>

            <!-- TOTALES -->
            <div class="col-md-5 col-lg-4">
                <div class="card card-compact shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-bold small">Subtotal:</span>
                            <span class="fw-bold fs-6"><span class="lblMoneda">$</span> <span id="lblSubtotal"><?= number_format($cotizacion['subtotal'], 2) ?></span></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div>
                                <input class="form-check-input me-1" type="checkbox" id="chkIGV" <?= (floatval($cotizacion['igv']) > 0) ? 'checked' : '' ?> onchange="calcularTotales()">
                                <label class="form-check-label fw-bold small" for="chkIGV">I.G.V. 18%:</label>
                            </div>
                            <span class="fw-bold fs-6"><span class="lblMoneda">$</span> <span id="lblIGV"><?= number_format($cotizacion['igv'], 2) ?></span></span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between align-items-center text-primary">
                            <span class="fw-bold fs-6">TOTAL:</span>
                            <span class="fs-4 fw-bold"><span class="lblMoneda">$</span> <span id="lblTotal"><?= number_format($cotizacion['total'], 2) ?></span></span>
                        </div>

                        <input type="hidden" name="subtotal" id="inputSubtotal" value="<?= $cotizacion['subtotal'] ?>">
                        <input type="hidden" name="igv" id="inputIGV" value="<?= $cotizacion['igv'] ?>">
                        <input type="hidden" name="total" id="inputTotal" value="<?= $cotizacion['total'] ?>">
                    </div>
                </div>
            </div>
        </div>

    </form>
</div>

<!-- MODAL PARA EDITAR CLÁUSULA -->
<div class="modal fade" id="modalClausulas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2 bg-light">
                <h6 class="modal-title fw-bold" id="modalClausulaTitulo"><i class="fa-solid fa-pen-to-square me-2"></i> Editar Contenido</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modalClausulaTipo">
                <label class="form-label text-muted text-xs mb-1">Escriba en detalle los puntos a incluir en la propuesta:</label>
                <textarea id="modalClausulaTexto" class="form-control" rows="6" placeholder="Detalle aquí la información..."></textarea>
            </div>
            <div class="modal-footer py-1">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-primary fw-bold" onclick="guardarTextoModalClausula()">
                    <i class="fa-solid fa-check me-1"></i> Aceptar y Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- DATALIST OCULTO DE PRODUCTOS -->
<datalist id="listaProductosCatalog">
    <?php foreach ($productos as $p): ?>
        <?php 
            // Mostramos el nombre o el código interno como valor de búsqueda
            $textoBusqueda = !empty($p['codigo_interno']) ? $p['codigo_interno'] . ' - ' . $p['nombre_producto'] : $p['nombre_producto'];
        ?>
        <option value="<?= htmlspecialchars($textoBusqueda) ?>" 
                data-codigo="<?= htmlspecialchars($p['codigo_interno'] ?? '') ?>"
                data-nombre="<?= htmlspecialchars($p['nombre_producto'] ?? '') ?>"
                data-costo="<?= htmlspecialchars($p['precio_costo'] ?? 0) ?>"
                data-utilidad="<?= htmlspecialchars($p['porcentaje_utilidad'] ?? 0) ?>"
                data-precio="<?= htmlspecialchars($p['precio_venta'] ?? 0) ?>"
                data-marca="<?= htmlspecialchars($p['marca'] ?? '') ?>"
                data-unid="<?= htmlspecialchars($p['unidad_medida'] ?? 'UND') ?>">
            <?= htmlspecialchars($p['codigo_interno'] . ' | ' . $p['nombre_producto']) ?>
        </option>
    <?php endforeach; ?>
</datalist>

<!-- MODAL REGISTRO CLIENTE -->
<div class="modal fade" id="modalNuevoCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title fw-bold"><i class="fa-solid fa-user-plus me-2"></i>Nuevo Cliente</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formClienteModal">
                    <div class="row g-2 mb-2">
                        <div class="col-md-4">
                            <label class="form-label-xs">Tipo Doc *</label>
                            <select name="tipo_documento" id="m_tipo_documento" class="form-select form-select-sm">
                                <option value="RUC" selected>RUC</option>
                                <option value="DNI">DNI</option>
                                <option value="CE">CE</option>
                                <option value="PASAPORTE">PASAPORTE</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label-xs">N° Documento *</label>
                            <div class="input-group input-group-sm">
                                <input type="text" name="num_documento" id="m_num_documento" class="form-control" placeholder="Ingrese número" required>
                                <button class="btn btn-outline-primary" type="button" onclick="consultarDocumentoModal()">
                                    <i class="fa-solid fa-magnifying-glass me-1"></i> Validar
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label-xs">Razón Social / Nombre Completo *</label>
                        <input type="text" name="razon_social" id="m_razon_social" class="form-control form-control-sm" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label-xs">Dirección Fiscal</label>
                        <input type="text" name="direccion" id="m_direccion" class="form-control form-control-sm">
                    </div>

                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label-xs">E-mail</label>
                            <input type="email" name="email" id="m_email" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-xs">Teléfono</label>
                            <input type="text" name="telefono" id="m_telefono" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-xs">Atención (Contacto)</label>
                            <input type="text" name="atencion" id="m_atencion" class="form-control form-control-sm">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer py-1">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-primary fw-bold" onclick="guardarClienteAjax()">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Guardar Cliente
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let itemCount = 0;
// Ítems traídos directamente de la BD
const itemsCargados = <?= json_encode($itemsExistentes) ?>;

document.getElementById('cboCliente').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (opt.value) {
        document.getElementById('txtAtencion').value = opt.getAttribute('data-atencion') || '';
        document.getElementById('txtDireccion').value = opt.getAttribute('data-dir') || '';
        document.getElementById('txtEmail').value = opt.getAttribute('data-email') || '';
    }
});

/* LÓGICA DE CLÁUSULAS FLOTANTES */
function toggleClausula(tipo) {
    const chk = document.getElementById(`chk${capitalize(tipo)}`);
    const btn = document.getElementById(`btn${capitalize(tipo)}`);
    const badge = document.getElementById(`badge${capitalize(tipo)}`);
    const hiddenInput = document.getElementById(`hidden_${tipo}`);

    if (chk.checked) {
        btn.disabled = false;
        badge.classList.remove('d-none');
        if (!hiddenInput.value.trim()) {
            abrirModalClausula(tipo);
        }
    } else {
        btn.disabled = true;
        badge.classList.add('d-none');
        hiddenInput.value = '';
    }
}

function abrirModalClausula(tipo) {
    const titles = {
        'alcance': '<i class="fa-solid fa-circle-check text-success me-2"></i> Alcance del Servicio / Proyecto',
        'restricciones': '<i class="fa-solid fa-ban text-danger me-2"></i> Restricciones y Exclusiones',
        'condiciones': '<i class="fa-solid fa-handshake text-primary me-2"></i> Condiciones Comerciales y Garantía'
    };

    document.getElementById('modalClausulaTitulo').innerHTML = titles[tipo];
    document.getElementById('modalClausulaTipo').value = tipo;
    document.getElementById('modalClausulaTexto').value = document.getElementById(`hidden_${tipo}`).value;

    const modal = new bootstrap.Modal(document.getElementById('modalClausulas'));
    modal.show();
}

function guardarTextoModalClausula() {
    const tipo = document.getElementById('modalClausulaTipo').value;
    const texto = document.getElementById('modalClausulaTexto').value.trim();
    
    document.getElementById(`hidden_${tipo}`).value = texto;

    const badge = document.getElementById(`badge${capitalize(tipo)}`);
    if (texto.length > 0) {
        badge.className = "badge bg-success-subtle text-success border border-success-subtle text-xs";
        badge.innerText = `✓ Texto asignado (${texto.length} chars)`;
    } else {
        badge.className = "badge bg-light text-muted border text-xs";
        badge.innerText = "Sin texto";
    }

    const modalEl = document.getElementById('modalClausulas');
    const modal = bootstrap.Modal.getInstance(modalEl);
    modal.hide();
}

function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

/* LÓGICA DE TABLA DE ÍTEMS */
function actualizarMonedaYPrecios() {
    const sim = document.getElementById('cboMoneda').value === 'USD' ? '$' : 'S/';
    document.querySelectorAll('.lblMoneda').forEach(e => e.innerText = sim);
}

function agregarFilaItem(data = null) {
    itemCount++;
    const tbody = document.getElementById('tbodyItems');
    const tr = document.createElement('tr');
    tr.id = `fila_${itemCount}`;
    
    const desc = data ? data.descripcion : '';
    const nota = data ? (data.nota_item || '') : '';
    const marca = data ? (data.marca || '') : '';
    const cant = data ? parseFloat(data.cantidad) : 1.00;
    const unid = data ? (data.unidad_medida || 'UND') : 'UND';
    
    // Costo base y Utilidad traídos mediante el JOIN con la tabla productos
    const costoPen = data ? parseFloat(data.precio_costo || 0) : 0;
    const util = data ? parseFloat(data.prod_utilidad || 0) : 0.00;
    
    // El precio unitario YA está guardado en la moneda de la cotización (USD o PEN)
    const precio = data ? parseFloat(data.precio_unitario || 0) : 0.0000;

    tr.dataset.costoPen = costoPen;

    tr.innerHTML = `
        <td class="text-center fw-bold num-item align-top pt-2">${itemCount}</td>
        <td>
            <input type="text" name="items[${itemCount}][descripcion]" class="form-control form-control-sm fw-bold mb-1 input-desc" list="listaProductosCatalog" value="${escapeHtml(desc)}" placeholder="Escriba o busque producto..." required oninput="cargarPrecioUnidad(this, ${itemCount})" onchange="cargarPrecioUnidad(this, ${itemCount})">
            <input type="text" name="items[${itemCount}][nota_item]" class="form-control form-control-sm text-muted fst-italic text-xs" value="${escapeHtml(nota)}" placeholder="Observaciones opcionales del ítem...">
        </td>
        <td><input type="text" name="items[${itemCount}][marca]" class="form-control form-control-sm text-center input-marca" value="${escapeHtml(marca)}" placeholder="Marca"></td>
        <td><input type="number" step="0.01" name="items[${itemCount}][cantidad]" class="form-control form-control-sm text-center cant-input" value="${cant.toFixed(2)}" oninput="calcularFila(${itemCount})" required></td>
        <td><input type="text" name="items[${itemCount}][unidad_medida]" class="form-control form-control-sm text-center unid-input" value="${escapeHtml(unid)}"></td>
        <td><input type="number" step="0.01" name="items[${itemCount}][porcentaje_utilidad]" class="form-control form-control-sm text-center util-input" value="${util.toFixed(2)}" oninput="recalcularPorUtilidad(${itemCount})"></td>
        <td><input type="number" step="0.0001" name="items[${itemCount}][precio_unitario]" class="form-control form-control-sm text-end precio-input" value="${precio.toFixed(4)}" oninput="recalcularPorPrecio(${itemCount})" required></td>
        <td class="text-end fw-bold align-middle"><span class="parcial-span" id="parcial_${itemCount}">0.00</span></td>
        <td class="text-center align-middle">
            <button type="button" class="btn btn-xs btn-outline-danger p-1" onclick="eliminarFilaItem(${itemCount})"><i class="fa-solid fa-trash"></i></button>
        </td>
    `;
    tbody.appendChild(tr);
    renumerarFilas();

    // Calcula únicamente el subtotal parcial del ítem sin alterar el precio guardado
    calcularFila(itemCount);
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/"/g, '&quot;');
}

function cargarPrecioUnidad(input, id) {
    const val = input.value.trim().toLowerCase();
    if (!val) return;

    const datalist = document.getElementById('listaProductosCatalog');
    if (!datalist) return;

    // Busca coincidencia en el valor del option, código interno o nombre
    const option = [...datalist.options].find(o => {
        const valueOpt = (o.value || '').trim().toLowerCase();
        const codigoOpt = (o.getAttribute('data-codigo') || '').trim().toLowerCase();
        const nombreOpt = (o.getAttribute('data-nombre') || '').trim().toLowerCase();

        return valueOpt === val || 
               (codigoOpt !== '' && (val === codigoOpt || val.includes(codigoOpt))) || 
               (nombreOpt !== '' && (val === nombreOpt || val.includes(nombreOpt)));
    });

    if (option) {
        const fila = document.getElementById(`fila_${id}`);
        const costoPen = parseFloat(option.getAttribute('data-costo')) || 0;
        const utilidad = parseFloat(option.getAttribute('data-utilidad')) || 0;
        let precioPen = parseFloat(option.getAttribute('data-precio')) || 0;
        const unid = option.getAttribute('data-unid') || 'UND';
        const marca = option.getAttribute('data-marca') || '';

        // Si el precio de venta es 0 pero hay costo y utilidad, lo calcula dinámicamente
        if (precioPen === 0 && costoPen > 0 && utilidad > 0) {
            precioPen = costoPen / (1 - (utilidad / 100));
        }

        fila.dataset.costoPen = costoPen;
        fila.dataset.precioPen = precioPen;

        // Asignar los valores a los inputs correspondientes
        fila.querySelector('.util-input').value = utilidad.toFixed(2);
        fila.querySelector('.unid-input').value = unid;
        fila.querySelector('.input-marca').value = marca;

        // Convertir moneda si la cotización está en USD
        const moneda = document.getElementById('cboMoneda').value;
        const tc = parseFloat(document.getElementById('tipo_cambio').value) || 1;
        let precioFinal = (moneda === 'USD' && tc > 0) ? (precioPen / tc) : precioPen;

        fila.querySelector('.precio-input').value = precioFinal.toFixed(4);

        // Recalcular el parcial de la fila y totales
        calcularFila(id);
    }
}

function recalcularPorUtilidad(id) {
    const fila = document.getElementById(`fila_${id}`);
    let costo = parseFloat(fila.dataset.costoPen) || 0;
    const tc = parseFloat(document.getElementById('tipo_cambio').value) || 1;
    const moneda = document.getElementById('cboMoneda').value;
    
    if (moneda === 'USD' && tc > 0) {
        costo = costo / tc;
    }

    const util = parseFloat(fila.querySelector('.util-input').value) || 0;

    if (costo > 0) {
        const nuevoPrecio = costo / (1 - (util / 100));
        fila.querySelector('.precio-input').value = nuevoPrecio.toFixed(4);
    }
    calcularFila(id);
}

function recalcularPorPrecio(id) {
    const fila = document.getElementById(`fila_${id}`);
    let costo = parseFloat(fila.dataset.costoPen) || 0;
    const tc = parseFloat(document.getElementById('tipo_cambio').value) || 1;
    const moneda = document.getElementById('cboMoneda').value;
    
    if (moneda === 'USD' && tc > 0) {
        costo = costo / tc;
    }

    const precio = parseFloat(fila.querySelector('.precio-input').value) || 0;

    if (costo > 0 && precio > 0) {
        const nuevaUtilidad = ((precio - costo) / precio) * 100;
        fila.querySelector('.util-input').value = nuevaUtilidad.toFixed(2);
    }
    calcularFila(id);
}

function eliminarFilaItem(id) {
    const fila = document.getElementById(`fila_${id}`);
    if (fila) {
        fila.remove();
        renumerarFilas();
        calcularTotales();
    }
}

function renumerarFilas() {
    const filas = document.querySelectorAll('#tbodyItems tr');
    filas.forEach((f, idx) => {
        f.querySelector('.num-item').innerText = idx + 1;
    });
}

function calcularFila(id) {
    const fila = document.getElementById(`fila_${id}`);
    if (!fila) return;
    const cant = parseFloat(fila.querySelector('.cant-input').value) || 0;
    const precio = parseFloat(fila.querySelector('.precio-input').value) || 0;
    const parcial = cant * precio;
    
    document.getElementById(`parcial_${id}`).innerText = parcial.toFixed(2);
    calcularTotales();
}

function calcularTotales() {
    let subtotal = 0;
    document.querySelectorAll('.parcial-span').forEach(span => {
        subtotal += parseFloat(span.innerText) || 0;
    });

    const incluyeIGV = document.getElementById('chkIGV').checked;
    const igv = incluyeIGV ? (subtotal * 0.18) : 0;
    const total = subtotal + igv;

    document.getElementById('lblSubtotal').innerText = subtotal.toFixed(2);
    document.getElementById('lblIGV').innerText = igv.toFixed(2);
    document.getElementById('lblTotal').innerText = total.toFixed(2);

    document.getElementById('inputSubtotal').value = subtotal.toFixed(2);
    document.getElementById('inputIGV').value = igv.toFixed(2);
    document.getElementById('inputTotal').value = total.toFixed(2);
}

// Consultas y peticiones AJAX
function consultarDocumentoModal() {
    const tipo = document.getElementById('m_tipo_documento').value;
    const doc = document.getElementById('m_num_documento').value.trim();

    if (!doc) {
        alert('Ingrese un número de documento.');
        return;
    }

    fetch(`consulta_doc.php?tipo=${tipo}&documento=${doc}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('m_razon_social').value = data.razon_social || '';
                document.getElementById('m_direccion').value = data.direccion || '';
            } else {
                alert(data.message || 'No se encontraron resultados.');
            }
        })
        .catch(err => console.error(err));
}

function guardarClienteAjax() {
    const form = document.getElementById('formClienteModal');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);

    fetch('guardar_cliente_ajax.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            const selectCliente = document.getElementById('cboCliente');
            const option = document.createElement('option');
            option.value = data.cliente.id_cliente;
            option.text = `${data.cliente.num_documento} - ${data.cliente.razon_social}`;
            option.setAttribute('data-doc', data.cliente.num_documento);
            option.setAttribute('data-dir', data.cliente.direccion || '');
            option.setAttribute('data-email', data.cliente.email || '');
            option.setAttribute('data-atencion', data.cliente.atencion || '');

            selectCliente.appendChild(option);
            selectCliente.value = data.cliente.id_cliente;
            selectCliente.dispatchEvent(new Event('change'));

            const modalEl = document.getElementById('modalNuevoCliente');
            bootstrap.Modal.getInstance(modalEl).hide();
            form.reset();
        } else {
            alert(data.message || 'Error al guardar cliente.');
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // 1. Desencadenar el autocompletado de los campos del cliente actual
    document.getElementById('cboCliente').dispatchEvent(new Event('change'));

    // 2. Cargar los ítems guardados en la base de datos
    if (itemsCargados && itemsCargados.length > 0) {
        itemsCargados.forEach(item => agregarFilaItem(item));
    } else {
        agregarFilaItem();
    }

    // 3. Listeners para cambios de moneda / tipo de cambio
    const inputTC = document.getElementById('tipo_cambio');
    const cboMoneda = document.getElementById('cboMoneda');

    if (inputTC) inputTC.addEventListener('input', actualizarMonedaYPrecios);
    if (cboMoneda) cboMoneda.addEventListener('change', actualizarMonedaYPrecios);

    actualizarMonedaYPrecios();
});
</script>
<?php require_once '../../includes/footer.php'; ?>