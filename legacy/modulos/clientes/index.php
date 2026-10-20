<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/header.php';

// Obtener la lista de clientes
$stmt = $pdo->query("SELECT * FROM clientes ORDER BY id_cliente DESC");
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold m-0"><i class="fa-solid fa-users me-2"></i>Gestión de Clientes</h2>
            <p class="text-muted m-0">Administra los clientes para las cotizaciones, ventas y Help Desk.</p>
        </div>
        <a href="crear.php" class="btn btn-primary">
            <i class="fa-solid fa-user-plus me-1"></i> Nuevo Cliente
        </a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i>Cliente registrado exitosamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i>Cliente actualizado exitosamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>RUC / DNI</th>
                            <th>Razón Social / Nombre</th>
                            <th>Atención (Contacto)</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th class="text-center">Help Desk</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clientes)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No hay clientes registrados aún.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($clientes as $cli): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($cli['num_documento']) ?></span></td>
                                    <td class="fw-bold"><?= htmlspecialchars($cli['razon_social']) ?></td>
                                    <td><?= htmlspecialchars($cli['atencion'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($cli['telefono'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($cli['email'] ?? '-') ?></td>
                                    <td class="text-center">
                                        <?php if ($cli['es_cliente_helpdesk'] == 1): ?>
                                            <span class="badge bg-success"><i class="fa-solid fa-headset me-1"></i> Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="editar.php?id=<?= $cli['id_cliente'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Editar">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
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