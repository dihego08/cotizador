<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

$id_cliente = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 1. OBTENER DATOS DEL CLIENTE
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id_cliente = :id");
$stmt->execute([':id' => $id_cliente]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    header('Location: index.php');
    exit;
}

// 2. PROCESAR ACTUALIZACIÓN DEL FORMULARIO (ANTES DE CUALQUIER SALIDA HTML)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_documento      = $_POST['tipo_documento'] ?? 'RUC';
    $num_documento       = trim($_POST['num_documento'] ?? '');
    $razon_social        = trim($_POST['razon_social'] ?? '');
    $atencion            = !empty($_POST['atencion']) ? trim($_POST['atencion']) : null;
    $telefono            = !empty($_POST['telefono']) ? trim($_POST['telefono']) : null;
    $email               = !empty($_POST['email']) ? trim($_POST['email']) : null;
    $direccion           = !empty($_POST['direccion']) ? trim($_POST['direccion']) : null;
    $departamento        = !empty($_POST['departamento']) ? trim($_POST['departamento']) : null;
    $provincia           = !empty($_POST['provincia']) ? trim($_POST['provincia']) : null;
    $distrito            = !empty($_POST['distrito']) ? trim($_POST['distrito']) : null;
    $ubigeo              = !empty($_POST['ubigeo']) ? trim($_POST['ubigeo']) : null;
    $es_cliente_helpdesk = isset($_POST['es_cliente_helpdesk']) ? 1 : 0;

    if (!empty($num_documento) && !empty($razon_social)) {
        try {
            $sql = "UPDATE clientes SET 
                        tipo_documento = :tipo,
                        num_documento = :doc,
                        razon_social = :razon,
                        atencion = :atencion,
                        telefono = :tel,
                        email = :email,
                        direccion = :dir,
                        departamento = :dep,
                        provincia = :prov,
                        distrito = :dist,
                        ubigeo = :ubi,
                        es_cliente_helpdesk = :hd
                    WHERE id_cliente = :id";
            
            $stmtUpd = $pdo->prepare($sql);
            $stmtUpd->execute([
                ':tipo'     => $tipo_documento,
                ':doc'      => $num_documento,
                ':razon'    => $razon_social,
                ':atencion' => $atencion,
                ':tel'      => $telefono,
                ':email'    => $email,
                ':dir'      => $direccion,
                ':dep'      => $departamento,
                ':prov'     => $provincia,
                ':dist'     => $distrito,
                ':ubi'      => $ubigeo,
                ':hd'       => $es_cliente_helpdesk,
                ':id'       => $id_cliente
            ]);

            header('Location: index.php?msg=updated');
            exit;
        } catch (PDOException $e) {
            die("Error al actualizar cliente: " . $e->getMessage());
        }
    }
}

// 3. INCLUIR LA CABECERA HTML SÓLO PARA MOSTRAR EL FORMULARIO (PETICIÓN GET)
require_once '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold m-0"><i class="fa-solid fa-user-pen me-2"></i>Editar Cliente</h2>
            <p class="text-muted m-0">Modifica los datos fiscales y accesos del cliente.</p>
        </div>
        <a href="index.php" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form action="editar.php?id=<?= $id_cliente ?>" method="POST" id="formCliente">
                <input type="hidden" name="ubigeo" id="ubigeo" value="<?= htmlspecialchars($cliente['ubigeo'] ?? '') ?>">

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Tipo de Documento *</label>
                        <select name="tipo_documento" id="tipo_documento" class="form-select" required>
                            <option value="RUC" <?= $cliente['tipo_documento'] === 'RUC' ? 'selected' : '' ?>>RUC (11 dígitos)</option>
                            <option value="DNI" <?= $cliente['tipo_documento'] === 'DNI' ? 'selected' : '' ?>>DNI (8 dígitos)</option>
                            <option value="CE" <?= $cliente['tipo_documento'] === 'CE' ? 'selected' : '' ?>>Carnet de Extranjería</option>
                            <option value="PASAPORTE" <?= $cliente['tipo_documento'] === 'PASAPORTE' ? 'selected' : '' ?>>Pasaporte</option>
                            <option value="OTROS" <?= $cliente['tipo_documento'] === 'OTROS' ? 'selected' : '' ?>>Otros</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">N° de Documento *</label>
                        <input type="text" name="num_documento" id="num_documento" class="form-control" value="<?= htmlspecialchars($cliente['num_documento']) ?>" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Razón Social / Nombre Completo *</label>
                        <input type="text" name="razon_social" id="razon_social" class="form-control" value="<?= htmlspecialchars($cliente['razon_social']) ?>" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Persona de Atención (Contacto)</label>
                        <input type="text" name="atencion" class="form-control" value="<?= htmlspecialchars($cliente['atencion'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Teléfono / Celular</label>
                        <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($cliente['telefono'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($cliente['email'] ?? '') ?>">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Dirección Fiscal / Principal</label>
                        <input type="text" name="direccion" id="direccion" class="form-control" value="<?= htmlspecialchars($cliente['direccion'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Departamento</label>
                        <input type="text" name="departamento" class="form-control" value="<?= htmlspecialchars($cliente['departamento'] ?? '') ?>">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Provincia</label>
                        <input type="text" name="provincia" class="form-control" value="<?= htmlspecialchars($cliente['provincia'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Distrito</label>
                        <input type="text" name="distrito" class="form-control" value="<?= htmlspecialchars($cliente['distrito'] ?? '') ?>">
                    </div>
                </div>

                <!-- Campo Adicional: Activar Módulo Help Desk -->
                <div class="card bg-light border-0 mb-4">
                    <div class="card-body">
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" name="es_cliente_helpdesk" id="es_cliente_helpdesk" value="1" <?= $cliente['es_cliente_helpdesk'] == 1 ? 'checked' : '' ?>>
                            <label class="form-check-input-label fw-bold text-dark ms-2" for="es_cliente_helpdesk">
                                <i class="fa-solid fa-headset text-primary me-1"></i> Habilitar a este cliente en el módulo de Help Desk / Soporte Técnico
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Actualizar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>