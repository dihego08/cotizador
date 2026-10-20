<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Validar permisos
requerirPermiso('usuarios');

require_once '../../includes/header.php';

// Obtener listado de usuarios (LEFT JOIN para no ocultar usuarios sin empresa asignada)
$sql = "SELECT u.*, e.razon_social AS nombre_empresa 
        FROM usuarios u 
        LEFT JOIN empresas e ON u.id_empresa = e.id_empresa 
        ORDER BY u.id_usuario DESC";
$usuarios = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold m-0"><i class="fa-solid fa-users me-2"></i>Gestión de Usuarios</h2>
        <p class="text-muted m-0">Administra las cuentas, cargos, empresas asignadas y módulos de acceso.</p>
    </div>
    <a href="crear.php" class="btn btn-primary fw-bold"><i class="fa-solid fa-user-plus me-1"></i> Nuevo Usuario</a>
</div>

<?php if (isset($_GET['msg'])): ?>
    <?php if ($_GET['msg'] === 'created'): ?>
        <div class="alert alert-success alert-dismissible fade show">¡Usuario creado exitosamente con sus permisos!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php elseif ($_GET['msg'] === 'updated'): ?>
        <div class="alert alert-info alert-dismissible fade show">¡Datos del usuario y permisos actualizados!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php elseif ($_GET['msg'] === 'status_changed'): ?>
        <div class="alert alert-warning alert-dismissible fade show">¡Estado del usuario modificado!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php elseif ($_GET['msg'] === 'self_toggle_error'): ?>
        <div class="alert alert-danger alert-dismissible fade show">No puedes desactivar tu propia cuenta activa.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nombre / Email</th>
                        <th>Empresa Asignada</th>
                        <th>Cargo / Firma</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No hay usuarios registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?= $u['id_usuario'] ?></td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($u['nombre_usuario'] ?? $u['nombre'] ?? '') ?></div>
                                    <small class="text-muted"><i class="fa-solid fa-envelope me-1"></i><?= htmlspecialchars($u['email']) ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($u['nombre_empresa'])): ?>
                                        <span class="badge bg-info text-dark border"><i class="fa-solid fa-building me-1"></i><?= htmlspecialchars($u['nombre_empresa']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Global / Sin Empresa</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small fw-bold"><?= htmlspecialchars($u['cargo'] ?: 'Sin cargo') ?></div>
                                    <?php if (!empty($u['firma_path']) && file_exists('../../' . $u['firma_path'])): ?>
                                        <small class="text-success"><i class="fa-solid fa-signature me-1"></i>Con Firma</small>
                                    <?php else: ?>
                                        <small class="text-muted"><i class="fa-solid fa-xmark me-1"></i>Sin Firma</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= strtoupper($u['rol']) === 'ADMIN' ? 'danger' : 'primary' ?>">
                                        <?= strtoupper($u['rol']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (($u['activo'] ?? 1) == 1): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="editar.php?id=<?= $u['id_usuario'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Editar">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    
                                    <?php if (($u['activo'] ?? 1) == 1): ?>
                                        <a href="estado.php?id=<?= $u['id_usuario'] ?>&estado=0" class="btn btn-sm btn-outline-danger" title="Desactivar" onclick="return confirm('¿Deseas desactivar este usuario?');">
                                            <i class="fa-solid fa-user-slash"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="estado.php?id=<?= $u['id_usuario'] ?>&estado=1" class="btn btn-sm btn-outline-success" title="Activar" onclick="return confirm('¿Deseas activar este usuario?');">
                                            <i class="fa-solid fa-user-check"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>