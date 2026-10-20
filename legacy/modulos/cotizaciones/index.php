<?php
// 1. Asegurar inicio de sesión antes de cualquier salida
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/header.php';

// Obtener todas las cotizaciones con datos del cliente
$sql = "SELECT c.*, cl.razon_social, cl.num_documento, cl.tipo_documento 
        FROM cotizaciones c
        INNER JOIN clientes cl ON c.id_cliente = cl.id_cliente
        ORDER BY c.id_cotizacion DESC";
$stmt = $pdo->query($sql);
$cotizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid mb-5">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold m-0"><i class="fa-solid fa-file-invoice-dollar me-2"></i>Gestión de Cotizaciones</h2>
            <p class="text-muted m-0">Revisa, imprime y gestiona el historial de cotizaciones emitidas.</p>
        </div>
        <a href="crear.php" class="btn btn-primary fw-bold shadow-sm">
            <i class="fa-solid fa-plus me-1"></i> Nueva Cotización
        </a>
    </div>

    <!-- 1. ALERTAS DE SESIÓN (Procesadas desde actualizar.php, crear.php, etc.) -->
    <?php if (isset($_SESSION['alert'])): 
    $alert = $_SESSION['alert'];
    unset($_SESSION['alert']);
?>
    <div class="alert alert-<?= htmlspecialchars($alert['type']) ?> alert-dismissible fade show shadow-sm mb-4" role="alert">
        <i class="fa-solid <?= $alert['type'] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?> me-2"></i>
        <strong><?= htmlspecialchars($alert['title']) ?>:</strong> <?= $alert['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

    <!-- 2. ALERTAS VÍA GET (Soporte heredado) -->
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i>Cotización registrada correctamente.
            <?php if (isset($_GET['id'])): ?>
                <a href="imprimir.php?id=<?= $_GET['id'] ?>" target="_blank" class="alert-link ms-2">
                    <i class="fa-solid fa-print me-1"></i>Imprimir Cotización N° <?= $_GET['id'] ?>
                </a>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
        <div class="alert alert-info alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-info-circle me-2"></i>Estado o datos de la cotización actualizados correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Tabla de Cotizaciones -->
    <div class="card shadow-sm border-0" style="min-height: 320px;">
        <div class="card-body p-0">
            <div class="table-responsive" style="min-height: 280px; overflow: visible;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">N° Cotización</th>
                            <th>Asunto / Proyecto</th>
                            <th>Fecha</th>
                            <th>Cliente / Documento</th>
                            <th>Forma Pago</th>
                            <th class="text-end">Total</th>
                            <th class="text-center" style="width: 150px;">Estado</th>
                            <th class="text-end pe-3" style="width: 180px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cotizaciones)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-folder-open fa-2x mb-2 d-block"></i>
                                    No se han generado cotizaciones todavía.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($cotizaciones as $cot): ?>
                                <?php 
                                    $simboloMoneda = ($cot['moneda'] === 'USD') ? '$' : 'S/';
                                    
                                    // Badge de estado
                                    $badgeClass = 'bg-warning text-dark';
                                    if ($cot['estado'] === 'APROBADA') $badgeClass = 'bg-success text-white';
                                    if ($cot['estado'] === 'RECHAZADA') $badgeClass = 'bg-danger text-white';
                                ?>
                                <tr>
                                    <!-- N° Cotización -->
                                    <td class="ps-3 fw-bold text-primary">
                                        <?= htmlspecialchars($cot['numero_cotizacion']) ?>
                                    </td>

                                    <!-- Asunto / Proyecto -->
                                    <td>
                                        <?php if (!empty($cot['asunto'])): ?>
                                            <span class="fw-bold text-dark"><?= htmlspecialchars($cot['asunto']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small"><em>- Sin Asunto -</em></span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Fecha -->
                                    <td>
                                        <i class="fa-regular fa-calendar me-1 text-muted"></i>
                                        <?= date('d/m/Y', strtotime($cot['fecha_emision'])) ?>
                                    </td>

                                    <!-- Cliente -->
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($cot['razon_social']) ?></div>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($cot['tipo_documento'] ?? 'DOC') ?>: <?= htmlspecialchars($cot['num_documento']) ?>
                                        </small>
                                    </td>

                                    <!-- Forma Pago -->
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= htmlspecialchars($cot['forma_pago']) ?>
                                        </span>
                                    </td>

                                    <!-- Total -->
                                    <td class="text-end fw-bold fs-6">
                                        <?= $simboloMoneda ?> <?= number_format($cot['total'], 2) ?>
                                    </td>
                                    
                                    <!-- Cambiar Estado (Dropdown) -->
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-sm <?= $badgeClass ?> dropdown-toggle fw-bold px-3 shadow-sm" 
                                                    type="button" 
                                                    data-bs-toggle="dropdown" 
                                                    data-bs-boundary="viewport" 
                                                    aria-expanded="false">
                                                <?= htmlspecialchars($cot['estado']) ?>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                                <li>
                                                    <a class="dropdown-item text-warning fw-bold py-2" href="cambiar_estado.php?id=<?= $cot['id_cotizacion'] ?>&estado=PENDIENTE">
                                                        <i class="fa-solid fa-clock me-2"></i>PENDIENTE
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item text-success fw-bold py-2" href="cambiar_estado.php?id=<?= $cot['id_cotizacion'] ?>&estado=APROBADA">
                                                        <i class="fa-solid fa-circle-check me-2"></i>APROBADA
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item text-danger fw-bold py-2" href="cambiar_estado.php?id=<?= $cot['id_cotizacion'] ?>&estado=RECHAZADA">
                                                        <i class="fa-solid fa-circle-xmark me-2"></i>RECHAZADA
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>

                                    <!-- Acciones (Imprimir y Editar) -->
                                    <td class="text-end pe-3">
                                        <div class="btn-group" role="group">
                                            <!-- Imprimir PDF -->
                                            <a href="imprimir.php?id=<?= $cot['id_cotizacion'] ?>" 
                                               target="_blank" 
                                               class="btn btn-sm btn-outline-secondary fw-bold shadow-sm" 
                                               title="Imprimir / Ver PDF">
                                                <i class="fa-solid fa-print"></i> PDF
                                            </a>

                                            <!-- Editar (Condicional: Solo si está PENDIENTE) -->
                                            <?php if ($cot['estado'] === 'PENDIENTE'): ?>
                                                <a href="editar.php?id=<?= $cot['id_cotizacion'] ?>" 
                                                   class="btn btn-sm btn-outline-primary fw-bold shadow-sm" 
                                                   title="Editar Cotización">
                                                    <i class="fa-solid fa-pen-to-square"></i> Editar
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-secondary fw-bold shadow-sm opacity-50" 
                                                        disabled 
                                                        title="No se puede editar una cotización APROBADA o RECHAZADA">
                                                    <i class="fa-solid fa-lock"></i> Editar
                                                </button>
                                            <?php endif; ?>
                                        </div>
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

<?php require_once '../../includes/footer.php'; ?>