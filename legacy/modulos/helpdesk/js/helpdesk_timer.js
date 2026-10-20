/**
 * Control del Contómetro y Eventos del Módulo Help Desk
 * Ruta: modulos/helpdesk/js/helpdesk_timer.js
 */

document.addEventListener('DOMContentLoaded', function () {

    // --- VARIABLES DEL CONTÓMETRO ---
    let timerInterval = null;
    let totalSeconds = 0;
    let pauseSeconds = 0;
    let pauseInterval = null;
    let isRunning = false;
    let isPaused = false;
    let isStopped = false;

    // --- ELEMENTOS DEL DOM ---
    const timerDisplay = document.getElementById('timerDisplay');
    const statusTimer = document.getElementById('statusTimer');
    
    const btnStart = document.getElementById('btnStart');
    const btnPause = document.getElementById('btnPause');
    const btnResume = document.getElementById('btnResume');
    const btnStop = document.getElementById('btnStop');
    const btnGuardarAtencion = document.getElementById('btnGuardarAtencion');
    
    const inputHoraInicio = document.getElementById('inputHoraInicio');
    const inputHoraFin = document.getElementById('inputHoraFin');
    const inputTiempoSegundos = document.getElementById('inputTiempoSegundos');
    const inputPausaSegundos = document.getElementById('inputPausaSegundos');

    const estadoCierre = document.getElementById('estado_cierre');
    const secSeguimiento = document.getElementById('secSeguimiento');
    const btnValidarDoc = document.getElementById('btnValidarDoc');
    const docInput = document.getElementById('documento_solicitante');
    const clienteSelect = document.getElementById('id_cliente');

    const selectCategoria = document.getElementById('categoria');
    const selectSubcategoria = document.getElementById('subcategoria');

    // =========================================================================
    // 1. FUNCIONES AUXILIARES DE TIEMPO
    // =========================================================================

    // Formatear segundos a HH:MM:SS
    function formatTime(seconds) {
        const hrs = Math.floor(seconds / 3600).toString().padStart(2, '0');
        const mins = Math.floor((seconds % 3600) / 60).toString().padStart(2, '0');
        const secs = (seconds % 60).toString().padStart(2, '0');
        return `${hrs}:${mins}:${secs}`;
    }

    // Retorna timestamp actual YYYY-MM-DD HH:MM:SS
    function getFormattedTimestamp() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }

    // =========================================================================
    // 2. CONTROLES DEL CRONÓMETRO (INICIAR, PAUSAR, REANUDAR, DETENER)
    // =========================================================================

    // BOTÓN INICIAR
    if (btnStart) {
        btnStart.addEventListener('click', function () {
            if (!isRunning) {
                isRunning = true;
                isPaused = false;
                isStopped = false;

                // Registrar Hora de Inicio real
                inputHoraInicio.value = getFormattedTimestamp();

                // Activar cronómetro principal
                timerInterval = setInterval(() => {
                    totalSeconds++;
                    timerDisplay.textContent = formatTime(totalSeconds);
                }, 1000);

                // Gestión de botones
                btnStart.classList.add('d-none');
                btnPause.classList.remove('d-none');
                btnStop.classList.remove('d-none');
                if (btnGuardarAtencion) btnGuardarAtencion.removeAttribute('disabled');

                statusTimer.innerHTML = `<span class="text-success fw-bold"><i class="fa-solid fa-circle-play me-1"></i> Atención en curso...</span>`;
            }
        });
    }

    // BOTÓN PAUSAR
    if (btnPause) {
        btnPause.addEventListener('click', function () {
            if (isRunning && !isPaused && !isStopped) {
                isPaused = true;
                clearInterval(timerInterval);

                // Iniciar conteo de tiempo de pausa
                pauseInterval = setInterval(() => {
                    pauseSeconds++;
                }, 1000);

                btnPause.classList.add('d-none');
                btnResume.classList.remove('d-none');

                statusTimer.innerHTML = `<span class="text-warning fw-bold"><i class="fa-solid fa-circle-pause me-1"></i> Atención pausada</span>`;
            }
        });
    }

    // BOTÓN REANUDAR
    if (btnResume) {
        btnResume.addEventListener('click', function () {
            if (isRunning && isPaused && !isStopped) {
                isPaused = false;
                clearInterval(pauseInterval);

                // Reanudar cronómetro principal
                timerInterval = setInterval(() => {
                    totalSeconds++;
                    timerDisplay.textContent = formatTime(totalSeconds);
                }, 1000);

                btnResume.classList.add('d-none');
                btnPause.classList.remove('d-none');

                statusTimer.innerHTML = `<span class="text-success fw-bold"><i class="fa-solid fa-circle-play me-1"></i> Atención reanudada...</span>`;
            }
        });
    }

    // BOTÓN DETENER (STOP) - Corta la atención y congela el tiempo
    if (btnStop) {
        btnStop.addEventListener('click', function () {
            if (isRunning && !isStopped) {
                if (confirm('¿Desea detener el tiempo de atención? El cronómetro se congelará para que pueda redactar los detalles con tranquilidad.')) {
                    isStopped = true;
                    
                    // Detener todos los intervalos
                    clearInterval(timerInterval);
                    clearInterval(pauseInterval);

                    // Congelar timestamp de término y totales
                    inputHoraFin.value = getFormattedTimestamp();
                    inputTiempoSegundos.value = totalSeconds;
                    inputPausaSegundos.value = pauseSeconds;

                    // Ocultar botones de reproducción/pausa y deshabilitar stop
                    btnPause.classList.add('d-none');
                    btnResume.classList.add('d-none');
                    btnStop.setAttribute('disabled', 'true');

                    statusTimer.innerHTML = `<span class="text-danger fw-bold"><i class="fa-solid fa-circle-stop me-1"></i> Tiempo detenido (${formatTime(totalSeconds)}). Complete la información y guarde.</span>`;
                }
            }
        });
    }

    // =========================================================================
    // 3. VISIBILIDAD DINÁMICA DEL REAGENDAMIENTO (ESTADO DE CIERRE)
    // =========================================================================
    if (estadoCierre) {
        estadoCierre.addEventListener('change', function () {
            if (this.value !== 'CONCLUIDO') {
                secSeguimiento.classList.remove('d-none');
                document.getElementById('acciones_programadas').setAttribute('required', 'true');
                document.getElementById('fecha_proxima_revision').setAttribute('required', 'true');
            } else {
                secSeguimiento.classList.add('d-none');
                document.getElementById('acciones_programadas').removeAttribute('required');
                document.getElementById('fecha_proxima_revision').removeAttribute('required');
            }
        });
    }

    // =========================================================================
    // 4. VALIDACIÓN DE CONTACTO AUTORIZADO POR DNI (AJAX)
    // =========================================================================
    if (btnValidarDoc) {
        btnValidarDoc.addEventListener('click', function () {
            const doc = docInput.value.trim();
            const idCliente = clienteSelect.value;

            if (!idCliente) {
                alert('Por favor, seleccione primero la Empresa/Cliente.');
                return;
            }

            if (doc === '') {
                alert('Ingrese un número de documento para buscar.');
                return;
            }

            fetch(`controllers/helpdesk_controller.php?action=validar_contacto&id_cliente=${idCliente}&documento=${doc}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.contacto) {
                        document.getElementById('nombre_solicitante').value = data.contacto.nombres_apellidos;
                        document.getElementById('cargo_solicitante').value = data.contacto.cargo || '';
                        document.getElementById('id_contacto').value = data.contacto.id_contacto;

                        const badge = document.getElementById('badgeValidado');
                        badge.className = 'badge bg-success';
                        badge.innerHTML = `<i class="fa-solid fa-check-circle me-1"></i> Usuario Validado`;
                    } else {
                        const badge = document.getElementById('badgeValidado');
                        badge.className = 'badge bg-danger';
                        badge.innerHTML = `<i class="fa-solid fa-circle-xmark me-1"></i> No Registrado`;

                        // Limpiar id_contacto para que inserte NULL y no falle la Foreign Key
                        document.getElementById('id_contacto').value = '';

                        if (confirm('El documento no se encuentra registrado en los contactos autorizados. ¿Desea ingresar el nombre manualmente?')) {
                            document.getElementById('nombre_solicitante').focus();
                        }
                    }
                })
                .catch(error => console.error('Error al validar contacto:', error));
        });
    }

    // =========================================================================
    // 5. CARGA DINÁMICA DE SUBCATEGORÍAS MEDIANTE AJAX
    // =========================================================================
    if (selectCategoria && selectSubcategoria) {
        selectCategoria.addEventListener('change', function () {
            const idPadre = this.value;

            // Limpiar opciones anteriores
            selectSubcategoria.innerHTML = '<option value="">-- Cargando... --</option>';
            selectSubcategoria.setAttribute('disabled', 'true');

            if (!idPadre) {
                selectSubcategoria.innerHTML = '<option value="">-- Seleccione Primero Categoría --</option>';
                return;
            }

            // Petición AJAX al controlador
            fetch(`controllers/helpdesk_controller.php?action=obtener_subcategorias&id_padre=${idPadre}`)
                .then(response => response.json())
                .then(data => {
                    selectSubcategoria.innerHTML = '<option value="">-- Seleccione Subcategoría --</option>';

                    if (data.success && data.subcategorias.length > 0) {
                        data.subcategorias.forEach(sub => {
                            const option = document.createElement('option');
                            option.value = sub.id_categoria;
                            option.textContent = sub.nombre;
                            selectSubcategoria.appendChild(option);
                        });

                        // Habilitar el combo de subcategorías
                        selectSubcategoria.removeAttribute('disabled');
                    } else {
                        selectSubcategoria.innerHTML = '<option value="">-- Sin subcategorías disponibles --</option>';
                    }
                })
                .catch(error => {
                    console.error('Error al obtener subcategorías:', error);
                    selectSubcategoria.innerHTML = '<option value="">-- Error al cargar --</option>';
                });
        });
    }

    // =========================================================================
    // 6. ENVÍO Y FINALIZACIÓN DEL FORMULARIO
    // =========================================================================
    const formHelpdesk = document.getElementById('formHelpdesk');
    if (formHelpdesk) {
        formHelpdesk.addEventListener('submit', function (e) {
            e.preventDefault();

            if (!isRunning) {
                alert('Debe iniciar la atención con el contómetro antes de guardar.');
                return;
            }

            // Si no presionaron "Detener", congelar los datos al momento de guardar
            if (!isStopped) {
                clearInterval(timerInterval);
                clearInterval(pauseInterval);

                inputHoraFin.value = getFormattedTimestamp();
                inputTiempoSegundos.value = totalSeconds;
                inputPausaSegundos.value = pauseSeconds;
            }

            // Asegurar que id_contacto no viaje como string con valor nulo si está vacío
            const inputContacto = document.getElementById('id_contacto');
            if (inputContacto && (inputContacto.value === "" || inputContacto.value === "0")) {
                inputContacto.value = "";
            }

            const formData = new FormData(formHelpdesk);
            formData.append('action', 'guardar_atencion');

            fetch('controllers/helpdesk_controller.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Atención registrada con éxito.\nTicket: ${data.codigo_ticket}\nTiempo Efectivo Registrado: ${formatTime(totalSeconds)}`);
                    window.location.reload();
                } else {
                    alert(`Error al registrar la atención: ${data.message}`);
                }
            })
            .catch(error => {
                console.error('Error en la petición:', error);
                alert('Ocurrió un error al procesar el registro.');
            });
        });
    }

});