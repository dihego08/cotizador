<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Cargar la empresa principal
$stmt = $pdo->query("SELECT * FROM empresas WHERE es_principal = 1 LIMIT 1");
$empresa = $stmt->fetch(PDO::FETCH_ASSOC);

// Procesar formulario al guardar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $razon_social   = trim($_POST['razon_social']);
    $ruc            = trim($_POST['ruc']);
    $direccion      = trim($_POST['direccion']);
    $telefono       = trim($_POST['telefono']);
    $email          = trim($_POST['email']);
    $nombre_gerente = trim($_POST['nombre_gerente']);
    $cargo_gerente  = trim($_POST['cargo_gerente']);
    $cel_gerente    = trim($_POST['cel_gerente']);
    $logo_path      = $empresa['logo_path'] ?? 'assets/img/logo.png';

    // Manejar subida del Logo
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $validExt = ['png', 'jpg', 'jpeg', 'svg'];
        
        if (in_array($ext, $validExt)) {
            $dirUpload = '../../uploads/';
            if (!is_dir($dirUpload)) mkdir($dirUpload, 0777, true);
            
            $filename = 'logo_' . time() . '.' . $ext;
            $destino = $dirUpload . $filename;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $destino)) {
                $logo_path = 'uploads/' . $filename;
            }
        }
    }

    if ($empresa) {
        $sql = "UPDATE empresas SET ruc=:ruc, razon_social=:razon_social, direccion=:direccion, 
                telefono=:telefono, email=:email, logo_path=:logo_path, nombre_gerente=:nombre_gerente, 
                cargo_gerente=:cargo_gerente, cel_gerente=:cel_gerente WHERE id_empresa=:id";
        $stmtSave = $pdo->prepare($sql);
        $stmtSave->execute([
            ':ruc' => $ruc, ':razon_social' => $razon_social, ':direccion' => $direccion,
            ':telefono' => $telefono, ':email' => $email, ':logo_path' => $logo_path,
            ':nombre_gerente' => $nombre_gerente, ':cargo_gerente' => $cargo_gerente,
            ':cel_gerente' => $cel_gerente, ':id' => $empresa['id_empresa']
        ]);
    }

    header("Location: empresa.php?msg=saved");
    exit;
}

require_once '../../includes/header.php';
?>

<div class="container my-4">
    <h2 class="fw-bold mb-3"><i class="fa-solid fa-building me-2"></i>Configuración de Datos de Empresa</h2>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'saved'): ?>
        <div class="alert alert-success">Datos y logo actualizados correctamente.</div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form action="empresa.php" method="POST" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-4 text-center">
                        <label class="form-label fw-bold d-block">Logo de la Empresa</label>
                        <?php if (!empty($empresa['logo_path']) && file_exists("../../" . $empresa['logo_path'])): ?>
                            <img src="../../<?= $empresa['logo_path'] ?>" class="img-thumbnail mb-2" style="max-height: 120px;">
                        <?php endif; ?>
                        <input type="file" name="logo" class="form-control form-control-sm" accept="image/*">
                    </div>
                    
                    <div class="col-md-8">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">RUC</label>
                                <input type="text" name="ruc" class="form-control" value="<?= htmlspecialchars($empresa['ruc'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Razón Social</label>
                                <input type="text" name="razon_social" class="form-control" value="<?= htmlspecialchars($empresa['razon_social'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Dirección Fiscal</label>
                                <input type="text" name="direccion" class="form-control" value="<?= htmlspecialchars($empresa['direccion'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Teléfonos</label>
                                <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($empresa['telefono'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">E-mail</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($empresa['email'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h5 class="fw-bold text-primary">Datos del Firmante / Gerencia</h5>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Nombre del Encargado</label>
                        <input type="text" name="nombre_gerente" class="form-control" value="<?= htmlspecialchars($empresa['nombre_gerente'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Cargo</label>
                        <input type="text" name="cargo_gerente" class="form-control" value="<?= htmlspecialchars($empresa['cargo_gerente'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Celular de Contacto</label>
                        <input type="text" name="cel_gerente" class="form-control" value="<?= htmlspecialchars($empresa['cel_gerente'] ?? '') ?>">
                    </div>

                    <div class="col-12 text-end mt-4">
                        <button type="submit" class="btn btn-primary fw-bold px-4">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Guardar Cambios
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>