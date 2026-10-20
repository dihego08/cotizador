<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

requerirPermiso('usuarios');

$id_usuario = intval($_GET['id'] ?? 0);
if ($id_usuario <= 0) {
    header('Location: index.php');
    exit;
}

// Obtener datos del usuario
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = :id");
$stmt->execute([':id' => $id_usuario]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header('Location: index.php');
    exit;
}

// Obtener permisos asignados al usuario
$stmtPerms = $pdo->prepare("SELECT id_modulo FROM usuario_permisos WHERE id_usuario = :id");
$stmtPerms->execute([':id' => $id_usuario]);
$permisosActuales = $stmtPerms->fetchAll(PDO::FETCH_COLUMN);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre     = trim($_POST['nombre_usuario']);
    $email      = trim($_POST['email']);
    $password   = trim($_POST['password']);
    $rol        = trim($_POST['rol']);
    $id_empresa = !empty($_POST['id_empresa']) ? intval($_POST['id_empresa']) : null;
    $cargo      = trim($_POST['cargo']);
    $celular    = trim($_POST['celular']);
    $modulos    = $_POST['modulos'] ?? []; // Array con IDs de módulos seleccionados

    if (!empty($nombre) && !empty($email)) {
        try {
            $pdo->beginTransaction();

            // Carga/Actualización de Firma
            $firma_path = $usuario['firma_path'];
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

            // Actualizar tabla usuarios
            if (!empty($password)) {
                $passHash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE usuarios SET nombre_usuario = :nombre, email = :email, password_hash = :pass, 
                        rol = :rol, id_empresa = :id_empresa, cargo = :cargo, celular = :celular, firma_path = :firma 
                        WHERE id_usuario = :id";
                $stmtUp = $pdo->prepare($sql);
                $stmtUp->execute([
                    ':nombre' => $nombre, ':email' => $email, ':pass' => $passHash, ':rol' => $rol,
                    ':id_empresa' => $id_empresa, ':cargo' => $cargo, ':celular' => $celular, ':firma' => $firma_path, ':id' => $id_usuario
                ]);
            } else {
                $sql = "UPDATE usuarios SET nombre_usuario = :nombre, email = :email, 
                        rol = :rol, id_empresa = :id_empresa, cargo = :cargo, celular = :celular, firma_path = :firma 
                        WHERE id_usuario = :id";
                $stmtUp = $pdo->prepare($sql);
                $stmtUp->execute([
                    ':nombre' => $nombre, ':email' => $email, ':rol' => $rol,
                    ':id_empresa' => $id_empresa, ':cargo' => $cargo, ':celular' => $celular, ':firma' => $firma_path, ':id' => $id_usuario
                ]);
            }

            // Sincronizar Permisos de Módulos (Incluyendo los IDs Padres correspondientes)
            $pdo->prepare("DELETE FROM usuario_permisos WHERE id_usuario = :id")->execute([':id' => $id_usuario]);
            
            if (!empty($modulos)) {
                $modulosAInsertar = array_map('intval', $modulos);

                // Obtener automáticamente los ID de los padres de los submódulos seleccionados
                $inQuery = implode(',', $modulosAInsertar);
                $stmtPadres = $pdo->query("SELECT DISTINCT id_padre FROM modulos WHERE id_modulo IN ($inQuery) AND id_padre IS NOT NULL");
                $padresIDs = $stmtPadres->fetchAll(PDO::FETCH_COLUMN);

                // Unir submódulos + sus padres (evitando duplicados)
                $todosLosModulos = array_unique(array_merge($modulosAInsertar, $padresIDs));

                $stmtMod = $pdo->prepare("INSERT INTO usuario_permisos (id_usuario, id_modulo) VALUES (:id_user, :id_mod)");
                foreach ($todosLosModulos as $id_mod) {
                    $stmtMod->execute([':id_user' => $id_usuario, ':id_mod' => intval($id_mod)]);
                }
            }

            $pdo->commit();
            header('Location: index.php?msg=updated');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error al actualizar usuario: ' . $e->getMessage();
        }
    } else {
        $error = 'Por favor completa todos los campos obligatorios.';
    }
}

// Cargar empresas
$empresas = $pdo->query("SELECT id_empresa, razon_social FROM empresas ORDER BY razon_social ASC")->fetchAll();

// Cargar Módulos Estructurados (Padres con sus Submódulos)
$modulosPadres = $pdo->query("SELECT * FROM modulos WHERE id_padre IS NULL ORDER BY orden ASC")->fetchAll(PDO::FETCH_ASSOC);

require_once '../../includes/header.php';
?>

<div class="container my-4" style="max-width: 950px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold"><i class="fa-solid fa-user-pen me-2"></i>Editar Usuario</h3>
        <a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Volver</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger py-2"><?= $error ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form action="editar.php?id=<?= $id_usuario ?>" method="POST" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text" name="nombre_usuario" class="form-control" required value="<?= htmlspecialchars($usuario['nombre_usuario'] ?? $usuario['nombre'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Correo Electrónico <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($usuario['email']) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Contraseña <small class="text-muted fw-normal">(Dejar en blanco para conservar)</small></label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Rol</label>
                        <select name="rol" class="form-select">
                            <option value="vendedor" <?= strtoupper($usuario['rol']) !== 'ADMIN' ? 'selected' : '' ?>>VENDEDOR / OPERATIVO</option>
                            <option value="ADMIN" <?= strtoupper($usuario['rol']) === 'ADMIN' ? 'selected' : '' ?>>ADMINISTRADOR GENERAL</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Empresa Asignada</label>
                        <select name="id_empresa" class="form-select">
                            <option value="">-- Sin Empresa / Acceso Global --</option>
                            <?php foreach ($empresas as $emp): ?>
                                <option value="<?= $emp['id_empresa'] ?>" <?= $usuario['id_empresa'] == $emp['id_empresa'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($emp['razon_social']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Cargo para Cotizaciones</label>
                        <input type="text" name="cargo" class="form-control" value="<?= htmlspecialchars($usuario['cargo'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Celular Directo</label>
                        <input type="text" name="celular" class="form-control" value="<?= htmlspecialchars($usuario['celular'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Firma Digital (Imagen PNG/JPG)</label>
                        <input type="file" name="firma" class="form-control" accept="image/*">
                    </div>

                    <!-- MÓDULOS DE PERMISOS JERÁRQUICOS -->
                    <div class="col-12 mt-4">
                        <h5 class="fw-bold text-primary border-bottom pb-2">
                            <i class="fa-solid fa-shield-halved me-2"></i>Permisos de Módulos del Sistema
                        </h5>

                        <div class="row g-3 mt-1">
                            <?php foreach ($modulosPadres as $padre): 
                                // Cargar submódulos de cada padre
                                $stmtHijos = $pdo->prepare("SELECT * FROM modulos WHERE id_padre = :id_padre ORDER BY orden ASC");
                                $stmtHijos->execute([':id_padre' => $padre['id_modulo']]);
                                $submodulos = $stmtHijos->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card h-100 shadow-sm border">
                                        <div class="card-header bg-dark text-white fw-bold d-flex align-items-center">
                                            <i class="fa-solid <?= htmlspecialchars($padre['icono'] ?? 'fa-folder') ?> me-2 text-warning"></i>
                                            <?= htmlspecialchars($padre['nombre']) ?>
                                        </div>
                                        <div class="card-body bg-light">
                                            <?php if (empty($submodulos)): ?>
                                                <small class="text-muted">Sin opciones adicionales</small>
                                            <?php else: ?>
                                                <?php foreach ($submodulos as $sub): 
                                                    $checked = in_array($sub['id_modulo'], $permisosActuales) ? 'checked' : '';
                                                ?>
                                                    <div class="form-check form-switch mb-2">
                                                        <input class="form-check-input" type="checkbox" name="modulos[]" 
                                                               value="<?= $sub['id_modulo'] ?>" 
                                                               id="mod_<?= $sub['id_modulo'] ?>" <?= $checked ?>>
                                                        <label class="form-check-label fw-semibold" for="mod_<?= $sub['id_modulo'] ?>">
                                                            <i class="fa-solid <?= htmlspecialchars($sub['icono'] ?? 'fa-file') ?> text-secondary me-1"></i>
                                                            <?= htmlspecialchars($sub['nombre']) ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="col-12 mt-4 text-end">
                        <button type="submit" class="btn btn-primary fw-bold px-4">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Actualizar Usuario
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>