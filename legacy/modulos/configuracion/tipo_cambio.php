<?php
date_default_timezone_set('America/Lima');

require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/header.php';

// Fecha seleccionada en el formulario (por defecto hoy)
$fecha_sel = $_POST['fecha'] ?? $_GET['fecha'] ?? date('Y-m-d');

// Guardar o actualizar tipo de cambio de la fecha seleccionada
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha  = $_POST['fecha'] ?? date('Y-m-d');
    $compra = (float)($_POST['compra'] ?? 0);
    $venta  = (float)($_POST['venta'] ?? 0);

    if ($compra > 0 && $venta > 0) {
        $stmt = $pdo->prepare("INSERT INTO tipo_cambio (fecha, compra, venta) 
                                VALUES (:fecha, :compra, :venta)
                                ON DUPLICATE KEY UPDATE compra = VALUES(compra), venta = VALUES(venta)");
        $stmt->execute([
            ':fecha'  => $fecha,
            ':compra' => $compra,
            ':venta'  => $venta
        ]);
        $msg = "updated";
        $fecha_sel = $fecha; // Mantener la fecha guardada
    }
}

// 1. Buscar si ya existe TC para la fecha seleccionada
$stmt = $pdo->prepare("SELECT * FROM tipo_cambio WHERE fecha = :fecha");
$stmt->execute([':fecha' => $fecha_sel]);
$tc_actual = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. Si no existe para esta fecha, obtener el ÚLTIMO registrado como sugerencia
if (!$tc_actual) {
    $stmt_last = $pdo->prepare("SELECT * FROM tipo_cambio WHERE fecha <= :fecha ORDER BY fecha DESC LIMIT 1");
    $stmt_last->execute([':fecha' => $fecha_sel]);
    $tc_sugerido = $stmt_last->fetch(PDO::FETCH_ASSOC);
    
    $val_compra = $tc_sugerido['compra'] ?? '3.7200';
    $val_venta  = $tc_sugerido['venta'] ?? '3.7500';
} else {
    $val_compra = $tc_actual['compra'];
    $val_venta  = $tc_actual['venta'];
}

// Historial reciente
$historial = $pdo->query("SELECT * FROM tipo_cambio ORDER BY fecha DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold m-0"><i class="fa-solid fa-dollar-sign me-2"></i>Tipo de Cambio del Día</h2>
            <p class="text-muted m-0">Gestiona las tasas de conversión (USD / PEN) para las cotizaciones.</p>
        </div>
    </div>

    <?php if (isset($msg) && $msg === 'updated'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i>Tipo de cambio guardado/actualizado para la fecha <strong><?= date('d/m/Y', strtotime($fecha_sel)) ?></strong>.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Formulario de la fecha -->
        <div class="col-md-5">
            <div class="card shadow-sm border-primary">
                <div class="card-header bg-primary text-white fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-calendar-day me-1"></i> Registro de Tasa</span>
                </div>
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Fecha</label>
                            <input type="date" name="fecha" class="form-control" value="<?= $fecha_sel ?>" onchange="window.location.href='?fecha=' + this.value" required>
                            <small class="text-muted">Cambia la fecha si deseas consultar o modificar un historial pasados.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Precio Compra (S/.)</label>
                            <input type="number" step="0.0001" name="compra" class="form-control form-control-lg" value="<?= number_format((float)$val_compra, 4, '.', '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Precio Venta (S/.)</label>
                            <input type="number" step="0.0001" name="venta" class="form-control form-control-lg" value="<?= number_format((float)$val_venta, 4, '.', '') ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Guardar / Actualizar Tasa
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Historial -->
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header fw-bold">
                    <i class="fa-solid fa-clock-rotate-left me-1"></i> Historial Reciente
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Compra</th>
                                <th>Venta</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historial as $h): ?>
                                <tr class="<?= $h['fecha'] === $fecha_sel ? 'table-warning fw-bold' : '' ?>">
                                    <td><?= date('d/m/Y', strtotime($h['fecha'])) ?></td>
                                    <td>S/. <?= number_format($h['compra'], 4) ?></td>
                                    <td>S/. <?= number_format($h['venta'], 4) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>