<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold m-0"><i class="fa-solid fa-user-plus me-2"></i>Nuevo Cliente</h2>
            <p class="text-muted m-0">Ingresa los datos fiscales y de contacto del cliente.</p>
        </div>
        <a href="index.php" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form action="guardar.php" method="POST" id="formCliente">
                <input type="hidden" name="ubigeo" id="ubigeo">

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Tipo de Documento *</label>
                        <select name="tipo_documento" id="tipo_documento" class="form-select" required>
                            <option value="RUC" selected>RUC (11 dígitos)</option>
                            <option value="DNI">DNI (8 dígitos)</option>
                            <option value="CE">Carnet de Extranjería</option>
                            <option value="PASAPORTE">Pasaporte</option>
                            <option value="OTROS">Otros</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">N° de Documento *</label>
                        <div class="input-group">
                            <input type="text" name="num_documento" id="num_documento" class="form-control" placeholder="20100199743" required>
                            <button class="btn btn-primary" type="button" id="btnBuscarDoc" title="Buscar en SUNAT / RENIEC">
                                <i class="fa-solid fa-magnifying-glass" id="iconBuscar"></i>
                                <span id="textBtnBuscar">Buscar</span>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Razón Social / Nombre Completo *</label>
                        <input type="text" name="razon_social" id="razon_social" class="form-control" placeholder="Ej: INCA TOPS S.A." required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Persona de Atención (Contacto)</label>
                        <input type="text" name="atencion" class="form-control" placeholder="Ej: JANETH TORRES">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Teléfono / Celular</label>
                        <input type="text" name="telefono" class="form-control" placeholder="Ej: 987654321">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" name="email" class="form-control" placeholder="ejemplo@cliente.com">
                    </div>
                </div>

                <!-- Ubicación Geográfica en Desplegables -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Dirección Fiscal / Principal</label>
                        <input type="text" name="direccion" id="direccion" class="form-control" placeholder="Ej: AV. MIGUEL FORGA NRO. 348 Z.I. PARQUE INDUSTRIAL">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Departamento</label>
                        <select name="departamento" id="cboDepartamento" class="form-select">
                            <option value="">-- Seleccionar --</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Provincia</label>
                        <select name="provincia" id="cboProvincia" class="form-select">
                            <option value="">-- Seleccionar --</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Distrito</label>
                        <select name="distrito" id="cboDistrito" class="form-select">
                            <option value="">-- Seleccionar --</option>
                        </select>
                    </div>
                </div>

                <!-- Campo Adicional: Activar Módulo Help Desk -->
                <div class="card bg-light border-0 mb-4">
                    <div class="card-body">
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" name="es_cliente_helpdesk" id="es_cliente_helpdesk" value="1">
                            <label class="form-check-input-label fw-bold text-dark ms-2" for="es_cliente_helpdesk">
                                <i class="fa-solid fa-headset text-primary me-1"></i> Habilitar a este cliente en el módulo de Help Desk / Soporte Técnico
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Guardar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Incluir datos de Ubigeo -->
<script src="../../assets/js/ubigeo.js"></script>

<script>
const selectTipo  = document.getElementById('tipo_documento');
const inputDoc    = document.getElementById('num_documento');
const btnBuscar   = document.getElementById('btnBuscarDoc');

const cboDep = document.getElementById('cboDepartamento');
const cboPro = document.getElementById('cboProvincia');
const cboDis = document.getElementById('cboDistrito');

// 1. Cargar Departamentos iniciales
function cargarDepartamentos() {
    cboDep.innerHTML = '<option value="">-- Seleccionar --</option>';
    for (let dep in ubigeoPeru) {
        cboDep.add(new Option(dep, dep));
    }
}

// 2. Evento Cambio de Departamento
cboDep.addEventListener('change', function() {
    const dep = this.value;
    cboPro.innerHTML = '<option value="">-- Seleccionar --</option>';
    cboDis.innerHTML = '<option value="">-- Seleccionar --</option>';

    if (dep && ubigeoPeru[dep]) {
        for (let pro in ubigeoPeru[dep]) {
            cboPro.add(new Option(pro, pro));
        }
    }
});

// 3. Evento Cambio de Provincia
cboPro.addEventListener('change', function() {
    const dep = cboDep.value;
    const pro = this.value;
    cboDis.innerHTML = '<option value="">-- Seleccionar --</option>';

    if (dep && pro && ubigeoPeru[dep] && ubigeoPeru[dep][pro]) {
        ubigeoPeru[dep][pro].forEach(dis => {
            cboDis.add(new Option(dis, dis));
        });
    }
});

// 4. Función para seleccionar o agregar valores traídos de la API
function establecerUbicacion(dep, pro, dis) {
    if (!dep) return;
    dep = dep.trim().toUpperCase();
    pro = pro ? pro.trim().toUpperCase() : '';
    dis = dis ? dis.trim().toUpperCase() : '';

    if (![...cboDep.options].some(o => o.value === dep)) {
        cboDep.add(new Option(dep, dep));
    }
    cboDep.value = dep;
    cboDep.dispatchEvent(new Event('change'));

    if (pro) {
        if (![...cboPro.options].some(o => o.value === pro)) {
            cboPro.add(new Option(pro, pro));
        }
        cboPro.value = pro;
        cboPro.dispatchEvent(new Event('change'));
    }

    if (dis) {
        if (![...cboDis.options].some(o => o.value === dis)) {
            cboDis.add(new Option(dis, dis));
        }
        cboDis.value = dis;
    }
}

// Validaciones de tipo de documento
function aplicarReglasDocumento() {
    const tipo = selectTipo.value;
    inputDoc.value = '';
    
    if (tipo === 'DNI') {
        inputDoc.maxLength = 8;
        btnBuscar.style.display = 'block';
    } else if (tipo === 'RUC') {
        inputDoc.maxLength = 11;
        btnBuscar.style.display = 'block';
    } else {
        inputDoc.maxLength = 15;
        btnBuscar.style.display = 'none';
    }
}

inputDoc.addEventListener('input', function() {
    const tipo = selectTipo.value;
    if (tipo === 'DNI' || tipo === 'RUC') {
        this.value = this.value.replace(/[^0-9]/g, '');
    }
});

// Búsqueda en SUNAT / RENIEC
function consultarDocumento() {
    const tipo = selectTipo.value;
    const doc  = inputDoc.value.trim();

    if (tipo === 'DNI' && doc.length !== 8) return alert('DNI debe tener 8 dígitos.');
    if (tipo === 'RUC' && doc.length !== 11) return alert('RUC debe tener 11 dígitos.');

    btnBuscar.disabled = true;
    document.getElementById('iconBuscar').className = 'fa-solid fa-spinner fa-spin';

    fetch(`consultar_doc.php?tipo=${tipo}&documento=${doc}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('razon_social').value = data.razon_social;
                document.getElementById('direccion').value    = data.direccion || '';
                document.getElementById('ubigeo').value       = data.ubigeo    || '';
                
                establecerUbicacion(data.departamento, data.provincia, data.distrito);
            } else {
                alert(data.message || 'Error al obtener datos.');
            }
        })
        .catch(err => alert('Error en el servidor al consultar el documento.'))
        .finally(() => {
            btnBuscar.disabled = false;
            document.getElementById('iconBuscar').className = 'fa-solid fa-magnifying-glass';
        });
}

btnBuscar.addEventListener('click', consultarDocumento);

inputDoc.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        consultarDocumento();
    }
});

selectTipo.addEventListener('change', aplicarReglasDocumento);

// Inicializar desplegables
cargarDepartamentos();
aplicarReglasDocumento();
</script>

<?php require_once '../../includes/footer.php'; ?>