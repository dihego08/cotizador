<?php
session_start();
require_once '../../config/db.php'; // Conexión PDO
require_once '../../includes/auth.php';

// Verificar permisos para acceder a Help Desk
requerirPermiso('helpdesk');

$idEmpresaSesion = $_SESSION['id_empresa'] ?? null;
$idUsuarioSesion = $_SESSION['usuario_id'] ?? $_SESSION['id_usuario'] ?? 4;
$nombreTecnico   = $_SESSION['usuario_nombre'] ?? 'Técnico Asignado';

// Consulta para obtener solo clientes habilitados para Help Desk
$sqlClientes = "SELECT id_cliente, razon_social, num_documento 
                FROM clientes 
                WHERE es_cliente_helpdesk = 1 
                ORDER BY razon_social ASC";

$stmtClientes = $pdo->prepare($sqlClientes);
$stmtClientes->execute();
$listaClientes = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);

// Consulta de Categorías Principales
$sqlCategorias = "SELECT id_categoria, nombre FROM helpdesk_categorias WHERE id_padre IS NULL AND activo = 1 ORDER BY nombre ASC";
$stmtCat = $pdo->prepare($sqlCategorias);
$stmtCat->execute();
$listaCategorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

// Consulta para contar solicitudes pendientes de aprobación
$sqlCantPendientes = "SELECT COUNT(DISTINCT documento_solicitante) AS total 
                      FROM helpdesk_atenciones 
                      WHERE id_contacto IS NULL";
$stmtCant = $pdo->prepare($sqlCantPendientes);
$stmtCant->execute();
$cantPendientes = $stmtCant->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Incluir cabecera principal con menú y sesión del sistema
require_once '../../includes/header.php';
?>

<style>
  .timer-display {
    font-family: 'Courier New', Courier, monospace;
    font-size: 2.8rem;
    font-weight: bold;
    letter-spacing: 2px;
    color: #0d6efd;
  }
  .card-timer {
    background-color: #f8f9fa;
    border: 2px dashed #0d6efd;
  }
  .star-rating i {
    font-size: 1.5rem;
    color: #ffc107;
    cursor: pointer;
  }
</style>

<div class="container-fluid py-2">
  <div class="row">
    <div class="col-12">
      
      <!-- CABECERA DE MÓDULO CON NAVEGACIÓN Y NOTIFICACIÓN -->
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h3 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-headset me-2 text-primary"></i>Atención de Soporte Help Desk</h3>
          <p class="text-muted small mb-0">Registro de tickets, validación de usuarios y control de tiempo de atención.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
          
          <!-- BOTÓN ACCESO AL HISTORIAL DE TICKETS -->
          <a href="historial.php" class="btn btn-outline-primary fw-bold" title="Consultar atenciones registradas">
            <i class="fa-solid fa-clock-rotate-left me-1"></i> Historial de Tickets
          </a>

          <!-- BOTÓN CON NOTIFICACIÓN DE APROBACIONES PENDIENTES -->
          <?php if ($cantPendientes > 0): ?>
            <a href="aprobaciones.php" class="btn btn-warning fw-bold position-relative">
              <i class="fa-solid fa-user-clock me-1"></i> Aprobaciones
              <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <?php echo $cantPendientes; ?>
              </span>
            </a>
          <?php else: ?>
            <a href="aprobaciones.php" class="btn btn-outline-secondary">
              <i class="fa-solid fa-user-check me-1"></i> Aprobaciones
            </a>
          <?php endif; ?>
          
          <span class="badge bg-secondary fs-6 py-2 px-3">
            <i class="fa-solid fa-user-gear me-1"></i> Técnico: <strong><?php echo htmlspecialchars($nombreTecnico); ?></strong>
          </span>
        </div>
      </div>

      <form id="formHelpdesk" method="POST" action="guardar_atencion.php">
        
        <div class="row g-3">

          <!-- COLUMNA IZQUIERDA: CONTÓMETRO Y CLASIFICACIÓN (4 columnas) -->
          <div class="col-lg-4">
            
            <!-- TARJETA DEL CONTÓMETRO (TIMER) -->
            <div class="card card-timer shadow-sm mb-3">
              <div class="card-body text-center py-3">
                <span class="text-uppercase fw-semibold text-muted small"><i class="fa-solid fa-stopwatch me-1"></i> Tiempo de Atención Efectivo</span>
                
                <!-- Display del Cronómetro -->
                <div class="timer-display my-1" id="timerDisplay">00:00:00</div>
                
                <!-- Campos ocultos para enviar los timestamps y segundos reales al backend -->
                <input type="hidden" name="hora_inicio" id="inputHoraInicio">
                <input type="hidden" name="hora_fin" id="inputHoraFin">
                <input type="hidden" name="tiempo_efectivo_segundos" id="inputTiempoSegundos" value="0">
                <input type="hidden" name="tiempo_pausa_segundos" id="inputPausaSegundos" value="0">

                <!-- Botones de Control del Cronómetro -->
                <div class="d-flex justify-content-center gap-2 mt-2">
                  <button type="button" id="btnStart" class="btn btn-success px-3 fw-bold">
                    <i class="fa-solid fa-play me-1"></i> Iniciar
                  </button>
                  <button type="button" id="btnPause" class="btn btn-warning px-3 fw-bold text-dark d-none">
                    <i class="fa-solid fa-pause me-1"></i> Pausar
                  </button>
                  <button type="button" id="btnResume" class="btn btn-info px-3 fw-bold text-white d-none">
                    <i class="fa-solid fa-play me-1"></i> Reanudar
                  </button>
                  <button type="button" id="btnStop" class="btn btn-danger px-3 fw-bold d-none">
                    <i class="fa-solid fa-stop me-1"></i> Detener
                  </button>
                </div>
                
                <div class="mt-2 text-muted small" id="statusTimer">
                  <i class="fa-solid fa-circle-info me-1"></i> Presiona <b>Iniciar</b> para comenzar la atención.
                </div>
              </div>
            </div>

            <!-- TARJETA DE CLASIFICACIÓN Y CANAL -->
            <div class="card shadow-sm mb-3">
              <div class="card-header bg-white fw-bold">
                <i class="fa-solid fa-filter me-1 text-primary"></i> Clasificación del Servicio
              </div>
              <div class="card-body">
                
                <!-- Categoría Principal -->
                <div class="mb-3">
                  <label for="categoria" class="form-label fw-semibold">Categoría Principal</label>
                  <select class="form-select" id="categoria" name="categoria" required>
                    <option value="">-- Seleccione Categoría --</option>
                    <?php foreach ($listaCategorias as $cat): ?>
                      <option value="<?php echo $cat['id_categoria']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <!-- Subcategoría con Botón para Agregar Nueva -->
                <div class="mb-3">
                  <label for="subcategoria" class="form-label fw-semibold">Subcategoría / Problema Específico</label>
                  <div class="input-group">
                    <select class="form-select" id="subcategoria" name="id_subcategoria" disabled>
                      <option value="">-- Seleccione Primero Categoría --</option>
                    </select>
                    <button class="btn btn-outline-primary" type="button" id="btnNuevaSubcategoria" title="Agregar nueva subcategoría" disabled data-bs-toggle="modal" data-bs-target="#modalSubcategoria">
                      <i class="fa-solid fa-plus"></i>
                    </button>
                  </div>
                </div>

                <!-- Canal de Atención -->
                <div class="mb-3">
                  <label for="canal_atencion" class="form-label fw-semibold">Canal de Atención</label>
                  <select class="form-select" id="canal_atencion" name="canal_atencion" required>
                    <option value="LLAMADA" selected>Llamada Telefónica</option>
                    <option value="ACCESO_REMOTO">Acceso Remoto (AnyDesk / RDP)</option>
                    <option value="PRESENCIAL">Presencial / En Campo</option>
                    <option value="CORREO">Correo / Mensajería</option>
                    <option value="WHATSAPP">WhatsApp / Chat</option>
                  </select>
                </div>

                <!-- Perfil del Técnico Responsable (Auto-Cargado de Sesión) -->
                <div class="mb-2">
                  <label class="form-label fw-semibold">Técnico Atendiendo</label>
                  <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($nombreTecnico); ?>" readonly>
                  <input type="hidden" name="id_tecnico" value="<?php echo (int)$idUsuarioSesion; ?>">
                </div>

              </div>
            </div>

          </div>

          <!-- COLUMNA DERECHA: DATOS DEL CLIENTE, DETALLE Y CIERRE (8 columnas) -->
          <div class="col-lg-8">
            
            <!-- TARJETA 1: VALIDACIÓN Y DATOS DEL SOLICITANTE -->
            <div class="card shadow-sm mb-3">
              <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-id-card me-1 text-primary"></i> Datos del Cliente y Usuario</span>
                <span class="badge bg-success" id="badgeValidado"><i class="fa-solid fa-check-circle me-1"></i> Usuario Validado</span>
              </div>
              <div class="card-body">
                
                <div class="row g-3">
                  <!-- Empresa Cliente -->
                  <div class="col-md-6">
                    <label for="id_cliente" class="form-label fw-semibold">Empresa / Cliente Atendido</label>
                    <select class="form-select" id="id_cliente" name="id_cliente" required>
                      <option value="">-- Seleccione Cliente --</option>
                      <?php foreach ($listaClientes as $cliente): ?>
                        <option value="<?php echo $cliente['id_cliente']; ?>">
                          <?php echo htmlspecialchars($cliente['razon_social'] . ' (' . $cliente['num_documento'] . ')'); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <!-- Tipo de Usuario -->
                  <div class="col-md-3">
                    <label for="tipo_usuario" class="form-label fw-semibold">Tipo Usuario</label>
                    <select class="form-select" id="tipo_usuario" name="tipo_usuario">
                      <option value="CORPORATIVO" selected>Corporativo</option>
                      <option value="PARTICULAR">Particular</option>
                    </select>
                  </div>

                  <!-- Búsqueda por DNI/Documento -->
                  <div class="col-md-3">
                    <label for="documento_solicitante" class="form-label fw-semibold">DNI / Documento</label>
                    <div class="input-group">
                      <input type="text" class="form-control" id="documento_solicitante" name="documento_solicitante" placeholder="Ej. 44556677" value="" required>
                      <button class="btn btn-outline-primary" type="button" id="btnValidarDoc" title="Buscar en contactos autorizados"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </div>
                  </div>

                  <!-- Nombre Completo del Solicitante -->
                  <div class="col-md-8">
                    <label for="nombre_solicitante" class="form-label fw-semibold">Nombre del Solicitante</label>
                    <input type="text" class="form-control" id="nombre_solicitante" name="nombre_solicitante" placeholder="Nombres y Apellidos" value="" required>
                  </div>

                  <!-- ID de Contacto -->
                  <input type="hidden" name="id_contacto" id="id_contacto" value="">

                  <div class="col-md-4">
                    <label for="cargo_solicitante" class="form-label fw-semibold">Cargo / Área</label>
                    <input type="text" class="form-control bg-light" id="cargo_solicitante" value="" readonly>
                  </div>
                </div>

              </div>
            </div>

            <!-- TARJETA 2: DESCRIPCIÓN DEL REQUERIMIENTO Y ACCIONES -->
            <div class="card shadow-sm mb-3">
              <div class="card-header bg-white fw-bold">
                <i class="fa-solid fa-clipboard-list me-1 text-primary"></i> Registro del Requerimiento y Atenciones
              </div>
              <div class="card-body">
                
                <!-- Problema presentado -->
                <div class="mb-3">
                  <label for="descripcion_requerimiento" class="form-label fw-semibold">Descripción del Problema / Requerimiento</label>
                  <textarea class="form-control" id="descripcion_requerimiento" name="descripcion_requerimiento" rows="3" placeholder="Detalle el problema reportado por el cliente..." required></textarea>
                </div>

                <!-- Acciones Realizadas -->
                <div class="mb-1">
                  <label for="acciones_realizadas" class="form-label fw-semibold">Acciones Realizadas / Diagnóstico</label>
                  <textarea class="form-control" id="acciones_realizadas" name="acciones_realizadas" rows="3" placeholder="Describa los pasos y pruebas ejecutadas durante esta atención..." required></textarea>
                </div>

              </div>
            </div>

            <!-- TARJETA 3: ESTADO DE CIERRE, SATISFACCIÓN Y REAGENDAMIENTO CONDICIONAL -->
            <div class="card shadow-sm mb-3">
              <div class="card-header bg-white fw-bold">
                <i class="fa-solid fa-flag-checkered me-1 text-primary"></i> Estado Final de Cierre y Próximos Pasos
              </div>
              <div class="card-body">
                
                <div class="row g-3">
                  <!-- Estado de Cierre -->
                  <div class="col-md-6">
                    <label for="estado_cierre" class="form-label fw-semibold">Estado de Cierre</label>
                    <select class="form-select border-primary" id="estado_cierre" name="estado_cierre" required>
                      <option value="CONCLUIDO" selected>CONCLUIDO / RESUELTO</option>
                      <option value="EN_PROCESO">EN PROCESO / SEGUNDA REVISIÓN</option>
                      <option value="ESCALADO_N2">ESCALADO A NIVEL 2</option>
                      <option value="ESPERA_REPUESTO">ESPERA DE REPUESTO / CLIENTE</option>
                    </select>
                  </div>

                  <!-- Nivel de Satisfacción -->
                  <div class="col-md-6">
                    <label for="nivel_satisfaccion" class="form-label fw-semibold">Nivel de Satisfacción del Cliente</label>
                    <select class="form-select" id="nivel_satisfaccion" name="nivel_satisfaccion">
                      <option value="5">5 ★★★★★ - Excelente / Muy Satisfecho</option>
                      <option value="4">4 ★★★★☆ - Bueno / Satisfecho</option>
                      <option value="3">3 ★★★☆☆ - Regular / Aceptable</option>
                      <option value="2">2 ★★☆☆☆ - Malo / Insatisfecho</option>
                      <option value="1">1 ★☆☆☆☆ - Muy Malo</option>
                    </select>
                  </div>
                </div>

                <!-- SECCIÓN CONDICIONAL: SE MUESTRA SOLO SI ESTADO_CIERRE != 'CONCLUIDO' -->
                <div id="secSeguimiento" class="row g-3 mt-2 pt-3 border-top d-none">
                  <div class="col-12">
                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-calendar-day me-1"></i> Programación de Próxima Revisión</span>
                  </div>

                  <!-- Acciones Programadas -->
                  <div class="col-12">
                    <label for="acciones_programadas" class="form-label fw-semibold">Acciones Programadas a Realizar</label>
                    <textarea class="form-control" id="acciones_programadas" name="acciones_programadas" rows="2" placeholder="Describa los pendientes o tareas para la siguiente visita/llamada..."></textarea>
                  </div>

                  <!-- Fecha de Próxima Revisión -->
                  <div class="col-md-6">
                    <label for="fecha_proxima_revision" class="form-label fw-semibold">Fecha y Hora de Próxima Revisión</label>
                    <input type="datetime-local" class="form-control" id="fecha_proxima_revision" name="fecha_proxima_revision">
                  </div>

                  <!-- Responsable del Seguimiento -->
                  <div class="col-md-6">
                    <label for="id_tecnico_seguimiento" class="form-label fw-semibold">Responsable del Seguimiento</label>
                    <select class="form-select" id="id_tecnico_seguimiento" name="id_tecnico_seguimiento">
                      <option value="<?php echo (int)$idUsuarioSesion; ?>" selected><?php echo htmlspecialchars($nombreTecnico); ?></option>
                    </select>
                  </div>
                </div>

              </div>
            </div>

            <!-- BOTÓN GUARDAR ATENCIÓN Y FINALIZAR -->
            <div class="d-grid gap-2 mb-4">
              <button type="submit" id="btnGuardarAtencion" class="btn btn-primary btn-lg fw-bold shadow-sm" disabled>
                <i class="fa-solid fa-floppy-disk me-2"></i> Guardar y Finalizar Atención
              </button>
            </div>

          </div>

        </div>

      </form>

    </div>
  </div>
</div>

<!-- MODAL PARA AGREGAR NUEVA SUBCATEGORÍA -->
<div class="modal fade" id="modalSubcategoria" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-folder-plus text-primary me-2"></i>Nueva Subcategoría</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">Categoría Padre</label>
          <input type="text" class="form-control bg-light" id="modalCatPadreNombre" readonly>
          <input type="hidden" id="modalCatPadreId">
        </div>
        <div class="mb-3">
          <label for="nombreSubcategoria" class="form-label fw-semibold">Nombre de la Subcategoría *</label>
          <input type="text" class="form-control" id="nombreSubcategoria" placeholder="Ej: Configuración Router / Switch" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary fw-bold" id="btnGuardarSubcategoria">
          <i class="fa-solid fa-floppy-disk me-1"></i> Guardar Subcategoría
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Cargar script con timestamp para evitar caché del navegador -->
<script src="js/helpdesk_timer.js?v=<?php echo time(); ?>"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cboCategoria = document.getElementById('categoria');
    const cboSubcategoria = document.getElementById('subcategoria');
    const btnNuevaSub = document.getElementById('btnNuevaSubcategoria');

    // Habilitar o deshabilitar el botón "+" según la selección de categoría principal
    if (cboCategoria) {
        cboCategoria.addEventListener('change', function() {
            if (this.value) {
                btnNuevaSub.disabled = false;
                document.getElementById('modalCatPadreId').value = this.value;
                document.getElementById('modalCatPadreNombre').value = this.options[this.selectedIndex].text;
            } else {
                btnNuevaSub.disabled = true;
            }
        });
    }

    // Procesar guardado AJAX de la nueva subcategoría
    const btnGuardarSub = document.getElementById('btnGuardarSubcategoria');
    if (btnGuardarSub) {
        btnGuardarSub.addEventListener('click', function() {
            const idPadre = document.getElementById('modalCatPadreId').value;
            const nombre = document.getElementById('nombreSubcategoria').value.trim();

            if (!nombre) {
                alert('Por favor, ingrese el nombre de la subcategoría.');
                return;
            }

            const formData = new FormData();
            formData.append('id_padre', idPadre);
            formData.append('nombre', nombre);

            btnGuardarSub.disabled = true;

            fetch('guardar_subcategoria_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Habilitar el desplegable de subcategorías si estaba inactivo
                    cboSubcategoria.disabled = false;

                    // Insertar la nueva opción y seleccionarla automáticamente
                    const opt = new Option(data.nombre, data.id_subcategoria, true, true);
                    cboSubcategoria.add(opt);

                    // Limpiar input y cerrar modal
                    document.getElementById('nombreSubcategoria').value = '';
                    const modalEl = document.getElementById('modalSubcategoria');
                    const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    modalInstance.hide();
                } else {
                    alert(data.message || 'Error al guardar la subcategoría.');
                }
            })
            .catch(err => {
                alert('Error al comunicarse con el servidor.');
            })
            .finally(() => {
                btnGuardarSub.disabled = false;
            });
        });
    }
});
</script>

<?php 
// Incluir el pie de página con los scripts de Bootstrap
require_once '../../includes/footer.php'; 
?>