<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --------------------------------------------------------------------------
// 1. MARCAR EMPRESA COMO PRINCIPAL
// --------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'set_principal' && isset($_GET['id'])) {
    $idSet = intval($_GET['id']);
    if ($idSet > 0) {
        $pdo->query("UPDATE empresas SET es_principal = 0");
        $stmt = $pdo->prepare("UPDATE empresas SET es_principal = 1 WHERE id_empresa = :id");
        $stmt->execute([':id' => $idSet]);

        $_SESSION['alert'] = ['type' => 'success', 'title' => '¡Actualizado!', 'message' => 'Empresa principal cambiada correctamente.'];
    }
    header("Location: index.php");
    exit;
}

// --------------------------------------------------------------------------
// 2. PROCESAR FORMULARIO (CREAR / EDITAR)
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id_empresa     = intval($_POST['id_empresa'] ?? 0);
        $ruc            = trim($_POST['ruc'] ?? '');
        $razon_social   = trim($_POST['razon_social'] ?? '');
        $direccion      = trim($_POST['direccion'] ?? '');
        $telefono       = trim($_POST['telefono'] ?? '');
        $email          = trim($_POST['email'] ?? '');
        $nombre_gerente = trim($_POST['nombre_gerente'] ?? '');
        $cargo_gerente  = trim($_POST['cargo_gerente'] ?? '');
        $cel_gerente    = trim($_POST['cel_gerente'] ?? '');
        $es_principal   = isset($_POST['es_principal']) ? 1 : 0;

        if (empty($ruc) || empty($razon_social)) {
            throw new Exception("El RUC y la Razón Social son obligatorios.");
        }

        // Obtener ruta del logo previo si es edición
        $logo_path = 'assets/img/logo-default.png';
        if ($id_empresa > 0) {
            $stmtPrev = $pdo->prepare("SELECT logo_path FROM empresas WHERE id_empresa = :id");
            $stmtPrev->execute([':id' => $id_empresa]);
            $logo_path = $stmtPrev->fetchColumn() ?: $logo_path;
        }

        // Subida de Logo
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            $validExt = ['png', 'jpg', 'jpeg', 'webp', 'svg'];

            if (!in_array($ext, $validExt)) {
                throw new Exception("Formato de imagen no permitido (Usa PNG, JPG, WEBP o SVG).");
            }

            $dirUpload = '../../uploads/';
            if (!is_dir($dirUpload)) {
                mkdir($dirUpload, 0777, true);
            }

            $filename = 'logo_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $destino  = $dirUpload . $filename;

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $destino)) {
                $logo_path = 'uploads/' . $filename;
            }
        }

        // Si es marcado como principal, desmarcar los demás
        if ($es_principal === 1) {
            $pdo->query("UPDATE empresas SET es_principal = 0");
        }

        if ($id_empresa > 0) {
            // EDITAR
            $sql = "UPDATE empresas SET 
                    ruc = :ruc, razon_social = :razon_social, direccion = :direccion, 
                    telefono = :telefono, email = :email, logo_path = :logo, 
                    nombre_gerente = :nombre_gerente, cargo_gerente = :cargo_gerente, 
                    cel_gerente = :cel_gerente, es_principal = :es_principal 
                    WHERE id_empresa = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ruc'            => $ruc,
                ':razon_social'   => $razon_social,
                ':direccion'      => $direccion,
                ':telefono'       => $telefono,
                ':email'          => $email,
                ':logo'           => $logo_path,
                ':nombre_gerente' => $nombre_gerente,
                ':cargo_gerente'  => $cargo_gerente,
                ':cel_gerente'    => $cel_gerente,
                ':es_principal'   => $es_principal,
                ':id'             => $id_empresa
            ]);
            $_SESSION['alert'] = ['type' => 'info', 'title' => '¡Empresa Actualizada!', 'message' => 'Los cambios se guardaron con éxito.'];
        } else {
            // CREAR NUEVA
            $sql = "INSERT INTO empresas (ruc, razon_social, direccion, telefono, email, logo_path, nombre_gerente, cargo_gerente, cel_gerente, es_principal) 
                    VALUES (:ruc, :razon_social, :direccion, :telefono, :email, :logo, :nombre_gerente, :cargo_gerente, :cel_gerente, :es_principal)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ruc'            => $ruc,
                ':razon_social'   => $razon_social,
                ':direccion'      => $direccion,
                ':telefono'       => $telefono,
                ':email'          => $email,
                ':logo'           => $logo_path,
                ':nombre_gerente' => $nombre_gerente,
                ':cargo_gerente'  => $cargo_gerente,
                ':cel_gerente'    => $cel_gerente,
                ':es_principal'   => $es_principal
            ]);
            $_SESSION['alert'] = ['type' => 'success', 'title' => '¡Empresa Registrada!', 'message' => 'La nueva empresa fue agregada al sistema.'];
        }

    } catch (Exception $e) {
        $_SESSION['alert'] = ['type' => 'danger', 'title' => 'Error', 'message' => $e->getMessage()];
    }

    header("Location: index.php");
    exit;
}

// --------------------------------------------------------------------------
// 3. CONSULTAR EMPRESAS
// --------------------------------------------------------------------------
$empresas = $pdo->query("SELECT * FROM empresas ORDER BY es_principal DESC, id_empresa DESC")->fetchAll(PDO::FETCH_ASSOC);

require_once '../../includes/header.php';
?>

<div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold m-0"><i class="fa-solid fa-building me-2"></i>Gestión de Empresas</h2>
            <p class="text-muted m-0">Registra y administra las marcas/empresas emisoras de tus cotizaciones.</p>
        </div>
        <button class="btn btn-primary fw-bold shadow-sm" onclick="nuevaEmpresa()">
            <i class="fa-solid fa-plus me-1"></i> Nueva Empresa
        </button>
    </div>

    <!-- Mensajes de estado -->
    <?php if (isset($_SESSION['alert'])): 
        $alert = $_SESSION['alert']; unset($_SESSION['alert']); ?>
        <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show shadow-sm mb-4">
            <strong><?= $alert['title'] ?></strong> <?= $alert['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Tabla de registros -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small fw-bold text-uppercase">
                        <tr>
                            <th class="ps-3" style="width: 100px;">Logo</th>
                            <th>RUC / Razón Social</th>
                            <th>Contacto</th>
                            <th>Gerencia / Firma</th>
                            <th class="text-center">Por Defecto</th>
                            <th class="text-end pe-3" style="width: 130px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($empresas)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No hay empresas registradas.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($empresas as $emp): ?>
                                <tr>
                                    <td class="ps-3">
                                        <?php if (!empty($emp['logo_path']) && file_exists('../../' . $emp['logo_path'])): ?>
                                            <img src="../../<?= htmlspecialchars($emp['logo_path']) ?>" class="rounded border p-1" style="height: 45px; max-width: 80px; object-fit: contain;">
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark border">Sin Logo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($emp['razon_social']) ?></div>
                                        <small class="text-muted">RUC: <?= htmlspecialchars($emp['ruc']) ?></small>
                                    </td>
                                    <td>
                                        <small class="d-block"><i class="fa-solid fa-phone me-1 text-muted"></i><?= htmlspecialchars($emp['telefono'] ?: '-') ?></small>
                                        <small class="text-muted"><i class="fa-solid fa-envelope me-1"></i><?= htmlspecialchars($emp['email'] ?: '-') ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-bold small"><?= htmlspecialchars($emp['nombre_gerente'] ?: '-') ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($emp['cargo_gerente'] ?: '') ?></small>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($emp['es_principal'] == 1): ?>
                                            <span class="badge bg-success shadow-sm"><i class="fa-solid fa-star me-1"></i>Principal</span>
                                        <?php else: ?>
                                            <a href="index.php?action=set_principal&id=<?= $emp['id_empresa'] ?>" 
                                               class="btn btn-sm btn-outline-secondary">
                                                Usar esta
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <button class="btn btn-sm btn-outline-primary fw-bold" 
                                                onclick='editarEmpresa(<?= json_encode($emp) ?>)'>
                                            <i class="fa-solid fa-pen-to-square me-1"></i> Editar
                                        </button>
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

<!-- MODAL FORMULARIO -->
<div class="modal fade" id="modalEmpresa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="index.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="modalTitulo"><i class="fa-solid fa-building me-2"></i>Empresa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="id_empresa" id="id_empresa" value="0">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">RUC <span class="text-danger">*</span></label>
                            <input type="text" name="ruc" id="ruc" class="form-control" maxlength="11" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Razón Social <span class="text-danger">*</span></label>
                            <input type="text" name="razon_social" id="razon_social" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Dirección Fiscal</label>
                            <input type="text" name="direccion" id="direccion" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Teléfono / Celular</label>
                            <input type="text" name="telefono" id="telefono" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">E-mail</label>
                            <input type="email" name="email" id="email" class="form-control">
                        </div>

                        <hr class="my-3">
                        <h6 class="fw-bold text-primary mb-0"><i class="fa-solid fa-signature me-1"></i> Datos del Firmante / Gerencia</h6>

                        <div class="col-md-5">
                            <label class="form-label fw-bold">Nombre del Firmante</label>
                            <input type="text" name="nombre_gerente" id="nombre_gerente" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Cargo</label>
                            <input type="text" name="cargo_gerente" id="cargo_gerente" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Celular Firma</label>
                            <input type="text" name="cel_gerente" id="cel_gerente" class="form-control">
                        </div>

                        <hr class="my-3">

                        <div class="col-md-8">
                            <label class="form-label fw-bold">Logo de la Empresa</label>
                            <input type="file" name="logo" id="logo" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-4 d-flex align-items-center mt-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="es_principal" id="es_principal" value="1">
                                <label class="form-check-label fw-bold" for="es_principal">Empresa Principal</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">Guardar Empresa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let modalInstance = null;
document.addEventListener('DOMContentLoaded', () => {
    modalInstance = new bootstrap.Modal(document.getElementById('modalEmpresa'));
});

function nuevaEmpresa() {
    document.getElementById('modalTitulo').innerHTML = '<i class="fa-solid fa-building me-2"></i>Nueva Empresa';
    document.getElementById('id_empresa').value = '0';
    document.getElementById('ruc').value = '';
    document.getElementById('razon_social').value = '';
    document.getElementById('direccion').value = '';
    document.getElementById('telefono').value = '';
    document.getElementById('email').value = '';
    document.getElementById('nombre_gerente').value = '';
    document.getElementById('cargo_gerente').value = '';
    document.getElementById('cel_gerente').value = '';
    document.getElementById('es_principal').checked = false;
    document.getElementById('logo').value = '';
    modalInstance.show();
}

function editarEmpresa(empresa) {
    document.getElementById('modalTitulo').innerHTML = '<i class="fa-solid fa-pen-to-square me-2"></i>Editar Empresa';
    document.getElementById('id_empresa').value = empresa.id_empresa;
    document.getElementById('ruc').value = empresa.ruc || '';
    document.getElementById('razon_social').value = empresa.razon_social || '';
    document.getElementById('direccion').value = empresa.direccion || '';
    document.getElementById('telefono').value = empresa.telefono || '';
    document.getElementById('email').value = empresa.email || '';
    document.getElementById('nombre_gerente').value = empresa.nombre_gerente || '';
    document.getElementById('cargo_gerente').value = empresa.cargo_gerente || '';
    document.getElementById('cel_gerente').value = empresa.cel_gerente || '';
    document.getElementById('es_principal').checked = (empresa.es_principal == 1);
    document.getElementById('logo').value = '';
    modalInstance.show();
}
</script>

<?php require_once '../../includes/footer.php'; ?>