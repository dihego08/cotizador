<?php
session_start();
require_once '../../config/db.php'; // Tu conexión PDO

// Procesar la aprobación o rechazo de un contacto excepcional
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // APROBAR E INCORPORAR A CLIENTE_CONTACTOS
    if ($_POST['action'] === 'aprobar') {
        $idCliente = (int)$_POST['id_cliente'];
        $numDoc    = trim($_POST['num_documento']);
        $nombre    = trim($_POST['nombre_solicitante']);
        $cargo     = trim($_POST['cargo']);

        try {
            // 1. Insertar en cliente_contactos
            $sqlIns = "INSERT INTO cliente_contactos (id_cliente, tipo_documento, num_documento, nombres_apellidos, cargo, activo) 
                       VALUES (:id_cliente, 'DNI', :num_doc, :nombre, :cargo, 1)";
            $stmtIns = $pdo->prepare($sqlIns);
            $stmtIns->execute([
                ':id_cliente' => $idCliente,
                ':num_doc'    => $numDoc,
                ':nombre'     => $nombre,
                ':cargo'      => $cargo
            ]);
            $nuevoIdContacto = $pdo->lastInsertId();

            // 2. Vincular las atenciones previas de este DNI al nuevo id_contacto
            $sqlUpd = "UPDATE helpdesk_atenciones 
                       SET id_contacto = :id_contacto 
                       WHERE id_cliente = :id_cliente AND documento_solicitante = :num_doc";
            $stmtUpd = $pdo->prepare($sqlUpd);
            $stmtUpd->execute([
                ':id_contacto' => $nuevoIdContacto,
                ':id_cliente'  => $idCliente,
                ':num_doc'     => $numDoc
            ]);

            $mensaje = '<div class="alert alert-success">Contacto aprobado e incorporado exitosamente a la lista oficial.</div>';
        } catch (PDOException $e) {
            $mensaje = '<div class="alert alert-danger">Error al procesar la aprobación: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// Consultar los tickets con solicitudes de contactos excepcionales (id_contacto IS NULL)
$sqlPendientes = "SELECT 
                    ha.id_atencion,
                    ha.codigo_ticket,
                    ha.id_cliente,
                    c.razon_social AS cliente_nombre,
                    ha.documento_solicitante,
                    ha.nombre_solicitante,
                    ha.fecha_atencion,
                    u.nombre_usuario AS tecnico_nombre
                  FROM helpdesk_atenciones ha
                  INNER JOIN clientes c ON ha.id_cliente = c.id_cliente
                  INNER JOIN usuarios u ON ha.id_tecnico = u.id_usuario
                  WHERE ha.id_contacto IS NULL
                  GROUP BY ha.id_cliente, ha.documento_solicitante
                  ORDER BY ha.created_at DESC";

$stmtPend = $pdo->prepare($sqlPendientes);
$stmtPend->execute();
$pendientes = $stmtPend->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Aprobación de Contactos - Help Desk</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h3 class="fw-bold text-dark"><i class="fa-solid fa-user-check text-primary me-2"></i>Aprobación de Contactos Excepcionales</h3>
      <p class="text-muted small mb-0">Revisión de usuarios temporales registrados por los técnicos durante atenciones de Help Desk.</p>
    </div>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Volver al Help Desk</a>
  </div>

  <?php echo $mensaje; ?>

  <div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 fw-bold">
      <i class="fa-solid fa-list-check me-1 text-primary"></i> Solicitudes de Contactos Pendientes de Validar
    </div>
    <div class="card-body p-0">
      
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Cliente / Empresa</th>
              <th>DNI / Documento</th>
              <th>Nombre Solicitante</th>
              <th>Último Ticket</th>
              <th>Técnico Registrador</th>
              <th class="text-center">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($pendientes) > 0): ?>
              <?php foreach ($pendientes as $p): ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($p['cliente_nombre']); ?></strong></td>
                  <td><span class="badge bg-secondary"><?php echo htmlspecialchars($p['documento_solicitante']); ?></span></td>
                  <td><?php echo htmlspecialchars($p['nombre_solicitante']); ?></td>
                  <td><code><?php echo htmlspecialchars($p['codigo_ticket']); ?></code></td>
                  <td><small><?php echo htmlspecialchars($p['tecnico_nombre']); ?></small></td>
                  <td class="text-center">
                    <form method="POST" class="d-inline">
                      <input type="hidden" name="action" value="aprobar">
                      <input type="hidden" name="id_cliente" value="<?php echo $p['id_cliente']; ?>">
                      <input type="hidden" name="num_documento" value="<?php echo htmlspecialchars($p['documento_solicitante']); ?>">
                      <input type="hidden" name="nombre_solicitante" value="<?php echo htmlspecialchars($p['nombre_solicitante']); ?>">
                      <input type="hidden" name="cargo" value="Usuario Aprobado">
                      
                      <button type="submit" class="btn btn-sm btn-success fw-bold" onclick="return confirm('¿Desea incorporar permanentemente este contacto a la empresa?');">
                        <i class="fa-solid fa-check me-1"></i> Aprobar e Incorporar
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="text-center py-4 text-muted">
                  <i class="fa-solid fa-circle-check text-success fs-3 d-block mb-2"></i>
                  No hay solicitudes de contactos pendientes por aprobar.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>

</body>
</html>