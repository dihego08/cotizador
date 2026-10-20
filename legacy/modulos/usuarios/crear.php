<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

requerirPermiso('usuarios');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre     = trim($_POST['nombre_usuario']);
    $email      = trim($_POST['email']);
    $password   = trim($_POST['password']);
    $rol        = trim($_POST['rol']);
    $id_empresa = !empty($_POST['id_empresa']) ? intval($_POST['id_empresa']) : null;
    $cargo      = trim($_POST['cargo']);
    $celular    = trim($_POST['celular']);
    $modulos    = $_POST['modulos'] ?? [];

    if (!empty($nombre) && !empty($email) && !empty($password)) {
        try {
            $pdo->beginTransaction();

            // Subida de Firma Digital
            $firma_path = null;
            if (isset($_FILES['firma']) && $_FILES['firma']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['firma']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $dirUpload = '../../uploads/firmas/';
                    if (!is_dir($dirUpload)) mkdir($dirUpload, 0777, true);
                    $filename = 'firma_' . time() . '_' . rand(100, 999) . '.' . $ext;
                    if (move_uploaded_file($_FILES['firma']['tmp_name'], $dirUpload . $filename)) {
                        $firma_path = 'uploads/firmas/' . $filename;
                    }
                }
            }

            // Insertar Usuario
            $passHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO usuarios (nombre_usuario, email, password_hash, rol, id_empresa, cargo, celular, firma_path, activo) 
                VALUES (:nombre, :email, :pass, :rol, :id_empresa, :cargo, :celular, :firma, 1)
            ");
            $stmt->execute([
                ':nombre'     => $nombre,
                ':email'      => $email,
                ':pass'       => $passHash,
                ':rol'        => $rol,
                ':id_empresa' => $id_empresa,
                ':cargo'      => $cargo,
                ':celular'    => $celular,
                ':firma'      => $firma_path
            ]);
            $id_usuario = $pdo->lastInsertId();

            // Insertar Módulos Autorizados
            if (!empty($modulos)) {
                $stmtMod = $pdo->prepare("INSERT INTO usuario_permisos (id_usuario, id_modulo) VALUES (:id_user, :id_mod)");
                foreach ($modulos as $id_mod) {
                    $stmtMod->execute([':id_user' => $id_usuario, ':id_mod' => intval($id_mod)]);
                }
            }

            $pdo->commit();
            header('Location: index.php?msg=created');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error al registrar usuario: ' . $e->getMessage();
        }
    } else {
        $error = 'Por favor completa todos los campos obligatorios.';
    }
}

// Cargar empresas y módulos disponibles
$empresas = $pdo->query("SELECT id_empresa, razon_social FROM empresas WHERE activo = 1 ORDER BY razon_social ASC")->fetchAll(PDO::FETCH_ASSOC);
$modulosList = $pdo->query("SELECT * FROM modulos ORDER BY id_modulo ASC")->fetchAll(PDO::FETCH_ASSOC);

require_once '../../includes/header.php';
?>

<div class="container my-4" style="max-width: 850px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold"><i class="fa-solid fa-user-plus me-2"></i>Crear Nuevo Usuario</h3>
        <a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Volver</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger py-2"><?= $error ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form action="crear.php" method="POST" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text" name="nombre_usuario" class="form-control" required placeholder="Ej: Angely Flores">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Correo Electrónico <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required placeholder="angely@empresa.com">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Contraseña <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required placeholder="••••••••">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Rol</label>
                        <select name="rol" class="form-select">
                            <option value="vendedor">VENDEDOR / OPERATIVO</option>
                            <option value="ADMIN">ADMINISTRADOR GENERAL</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Empresa Asignada</label>
                        <select name="id_empresa" class="form-select">
                            <option value="">-- Sin Empresa / Acceso Global --</option>
                            <?php foreach ($empresas as $emp): ?>
                                <option value="<?= $emp['id_empresa'] ?>"><?= htmlspecialchars($emp['razon_social']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Cargo para Cotizaciones</label>
                        <input type="text" name="cargo" class="form-control" placeholder="Ej: ASESORA COMERCIAL">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Celular Directo</label>
                        <input type="text" name="celular" class="form-control" placeholder="+51 987 654 321">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Firma Digital (Imagen PNG/JPG)</label>
                        <input type="file" name="firma" class="form-control" accept="image/*">
                    </div>

                    <!-- MÓDULOS DE PERMISOS -->
                    <div class="col-12 mt-4">
                        <h5 class="fw-bold text-primary border-bottom pb-2"><i class="fa-solid fa-shield-halved me-1"></i> Permisos de Módulos</h5>
                        <div class="row g-2">
                            <?php foreach ($modulosList as $mod): ?>
                                <div class="col-md-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="modulos[]" value="<?= $mod['id_modulo'] ?>" id="mod_<?= $mod['id_modulo'] ?>">
                                        <label class="form-check-label fw-semibold" for="mod_<?= $mod['id_modulo'] ?>">
                                            <i class="fa-solid <?= $mod['icono'] ?? 'fa-folder' ?> text-muted me-1"></i>
                                            <?= htmlspecialchars($mod['nombre']) ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="col-12 mt-4 text-end">
                        <button type="submit" class="btn btn-primary fw-bold px-4"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar Usuario</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>