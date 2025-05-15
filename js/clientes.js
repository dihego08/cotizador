let current = null;
let id_selected = null;
$(document).ready(function () {
    lista_clientes();
    lista_sucursales();
    lista_tipos_clientes();
    $("#id_tipo_cliente").on("change", function () {
        if ($(this).val() == 1) {
            $("#div-tipo-cliente").attr("hidden", false);
        } else {
            $("#div-tipo-cliente").attr("hidden", true);
        }
    });
    lista_formas_pagos();
    var today = new Date();
    var dd = String(today.getDate()).padStart(2, '0');
    var mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
    var yyyy = today.getFullYear();

    today = yyyy + '-' + mm + '-' + dd;
    $("#fecha_hasta").val(today);
    today = new Date();
    today.setMonth(today.getMonth() - 1); // Restar 1 mes

    var dd = String(today.getDate()).padStart(2, '0');
    var mm = String(today.getMonth() + 1).padStart(2, '0'); // Enero es 0
    var yyyy = today.getFullYear();
    let last_month = yyyy + '-' + mm + '-' + dd;

    $("#fecha_desde").val(last_month);

    $(".fecha").datetimepicker({
        format: "Y-m-d",
        timepicker: false
    });
    $.datetimepicker.setLocale('es');
});
function lista_formas_pagos() {
    $.post("ws/service.php?parAccion=lista_formas_pagos", {
        id_sucursal: 2
    }, function (response) {
        var obj = JSON.parse(response);
        $.each(obj, function (index, val) {
            $("#id_forma_pago").append(`<option value="${val.id}">${val.forma_pago}</option>`);
        });
    });
}
function lista_tipos_clientes() {
    $.post("ws/service.php?parAccion=lista_tipos_clientes", function (response) {
        var obj = JSON.parse(response);
        $.each(obj, function (index, val) {
            $("#id_tipo_cliente").append(`<option value="${val.id}">${val.tipo_cliente}</option>`);
        });
    });
}
function lista_sucursales() {
    $.post("ws/service.php?parAccion=lista_sucursales", function (response) {
        var obj = JSON.parse(response);
        $("#id_sucursal").append(`<option value="0">--SELECCIONE--</option>`);
        $.each(obj, function (index, val) {
            $("#id_sucursal").append(`<option value="${val.id}">${val.sucursal}</option>`);
        });
    });
} function insertar_saldo(id) {
    $.post("ws/service.php?parAccion=insertar_saldo", {
        id_cliente: id,
        monto: $("#monto").val(),
        id_forma_pago: $("#id_forma_pago").val(),
        fecha: $("#fecha").val(),
    }, function (response) {
        var obj = JSON.parse(response);
        if (obj.Result == "OK") {
            alertify.success("Se agregó correctamente.");
            buscar_saldos();
            limpiar_formulario();
            lista_clientes();
        } else {
            alertify.error("Algo ha salido terriblemente mal.");
        }
    });
}
function abrir_movimientos(id) {
    id_selected = id;
    buscar_saldos();
    $("#btn-accion-saldo").attr("onclick", "insertar_saldo(" + id + ");");
}
function detalle_venta(id) {
    $('#formulario_detalle_venta').on('shown.bs.modal', function () {
        $('.modal-backdrop').last().css('z-index', '1055');
        $(this).css('z-index', '1060');
    });
    $.post("ws/service.php?parAccion=detalle_venta", {
        id: id
    }, function (response) {
        var obj = JSON.parse(response);
        $("#tabla-detalle-venta").find("tbody").empty();
        $.each(obj, function (index, val) {
            $("#tabla-detalle-venta").find("tbody").append(`<tr>
                <td>${val.producto}</td>
                <td>${val.cantidad}</td>
                <td>${val.precio_unitario}</td>
                <td>${val.total}</td>
            </tr>`);
        });
    });
    $('#formulario_detalle_venta').on('hidden.bs.modal', function () {
        if ($('.modal.show').length) {
            $('body').addClass('modal-open'); // Restaura el scroll si aún hay un modal abierto
        }
    });
}
function buscar_saldos() {
    $('#tabla-saldos').DataTable().clear().destroy();
    $.post("ws/service.php?parAccion=buscar_saldos_cliente", {
        id_cliente: id_selected,
        fecha_desde: $("#fecha_desde").val(),
        fecha_hasta: $("#fecha_hasta").val(),
    }, function (response) {
        var obj = JSON.parse(response);
        $("#tabla-saldos").find("tbody").empty();
        $.each(obj, function (index, val) {
            let tipo = '';
            let i = 1;
            let btn_ver_venta = '';
            if (val.tipo == 'V') {
                tipo = "<span class='badge badge-danger'>Venta</span>";
                i = -1;
                btn_ver_venta = `<span class="btn btn-outline-info btn-sm d-block mb-1" data-toggle="modal" data-target="#formulario_detalle_venta" onclick="detalle_venta(${val.id});"><i class="fa fa-eye"></i></span>`;
            } else {
                tipo = "<span class='badge badge-success'>Recarga</span>";
            }
            $("#tabla-saldos").find("tbody").append(`
                <tr>
                    <td>${tipo}</td>
                    <td>${val.monto * i}</td>
                    <td>${val.fecha}</td>
                    <td>${btn_ver_venta}</td>
                </tr>
            `);
        });
        $("#tabla-saldos").DataTable({
            scrollX: true,       // Habilita el desplazamiento horizontal
            autoWidth: false,    // Evita que DataTables ajuste el ancho automáticamente
            responsive: true,    // Permite que la tabla se adapte
            searching: true,     // Habilita el buscador
            paging: true,        // Habilita paginación
            ordering: true,      // Habilita ordenación
            info: true,
            dom: 'Brftip',
            "language": {
                "url": "./js/Spanish.json"
            },
            buttons: [
                'excel'
            ]
        });
    });
}
function lista_clientes() {
    $('#tabla-clientes').DataTable().clear().destroy();
    $.post("ws/service.php?parAccion=lista_clientes", function (response) {
        var obj = JSON.parse(response);
        $("#tabla-clientes").find("tbody").empty();
        $.each(obj, function (index, val) {
            $("#tabla-clientes").find("tbody").append(`<tr>
                <td>${val.id}</td>
                <td>${val.razon_social}</td>
                <td>${val.n_documento}</td>
                <td>${val.celular}</td>
                <td>${val.correo}</td>
                <td>
                    <span class="btn btn-outline-info btn-sm d-block mb-1" data-toggle="modal" data-target="#movimientos" onclick="abrir_movimientos(${val.id});"><i class="fa fa-money-bill"></i></span>    
                    <span class="btn btn-outline-warning btn-sm d-block mb-1" data-toggle="modal" data-target="#formulario" onclick="editar_cliente(${val.id});"><i class="fa fa-edit"></i></span>
                    <span  class="btn btn-outline-danger btn-sm d-block" onclick="eliminar_cliente(${val.id});"><i class="fa fa-trash"></i></span>
                </td>
                </tr>`);
        });
        $("#tabla-clientes").DataTable({
            scrollX: true,       // Habilita el desplazamiento horizontal
            autoWidth: false,    // Evita que DataTables ajuste el ancho automáticamente
            responsive: true,    // Permite que la tabla se adapte
            searching: true,     // Habilita el buscador
            paging: true,        // Habilita paginación
            ordering: true,      // Habilita ordenación
            info: true,
            dom: 'Brftip',
            "language": {
                "url": "./js/Spanish.json"
            },
            buttons: [
                'excel'
            ]
        });
    });
}
function actualizar_cliente(id) {
    $.post("ws/service.php?parAccion=actualizar_cliente", {
        nombres: $("#nombres").val(),
        dni: $("#dni").val(),
        id_sucursal: $("#id_sucursal").val(),
        grado: $("#grado").val(),
        seccion: $("#seccion").val(),
        id_tipo_cliente: $("#id_tipo_cliente").val(),
        padre: $("#padre").val(),
        celular: $("#celular").val(),
        saldo: $("#saldo").val(),
        correo: $("#correo").val(),
        id: id,
        fecha_creacion: current.fecha_creacion,
        id_usuario_creacion: current.id_usuario_creacion
    }, function (response) {
        var obj = JSON.parse(response);
        if (obj.Result == "OK") {
            alertify.success("Se modificó correctamente.");
            lista_clientes();
            limpiar_formulario();
        } else {
            alertify.error("Algo ha salido terriblemente mal.");
        }
    });
}
function insertar_cliente() {
    $.post("ws/service.php?parAccion=insertar_cliente", {
        nombres: $("#nombres").val(),
        dni: $("#dni").val(),
        id_sucursal: $("#id_sucursal").val(),
        grado: $("#grado").val(),
        seccion: $("#seccion").val(),
        id_tipo_cliente: $("#id_tipo_cliente").val(),
        padre: $("#padre").val(),
        celular: $("#celular").val(),
        saldo: $("#saldo").val(),
        correo: $("#correo").val(),
    }, function (response) {
        var obj = JSON.parse(response);
        if (obj.Result == "OK") {
            alertify.success("Se agregó correctamente.");
            lista_clientes();
            limpiar_formulario();
        } else {
            alertify.error("Algo ha salido terriblemente mal.");
        }
    });
}
function nuevo_cliente() {
    $("#clienteModalLabel").text("Nuevo Cliente");
    $("#btn-accion-cliente").text("Guardar");
    $("#btn-accion-cliente").attr("onclick", "insertar_cliente();");
}
function editar_cliente(id) {
    $("#clienteModalLabel").text("Editar Cliente");
    $("#btn-accion-cliente").text("Actualizar");
    $("#btn-accion-cliente").attr("onclick", "actualizar_cliente(" + id + ");");
    $.post("ws/service.php?parAccion=editar_cliente", {
        id: id
    }, function (response) {
        var obj = JSON.parse(response);
        current = obj;

        $("#nombres").val(obj.nombres)
        $("#dni").val(obj.dni)
        $("#id_sucursal").val(obj.id_sucursal)
        $("#grado").val(obj.grado)
        $("#seccion").val(obj.seccion)
        $("#id_tipo_cliente").val(obj.id_tipo_cliente)
        $("#padre").val(obj.padre)
        $("#celular").val(obj.celular)
        $("#saldo").val(obj.saldo)
        $("#correo").val(obj.correo)
    });
}
function limpiar_formulario() {
    $(".form-control").val('');
    $("select").val(0);
}
function eliminar_cliente(id) {
    alertify.confirm('¿Eliminar este Cliente?', 'Esta acción no se puede deshacer', function () {
        $.post("ws/service.php?parAccion=eliminar_cliente", {
            id: id
        }, function (response) {
            var obj = JSON.parse(response);
            if (obj.Result == "OK") {
                alertify.success("Se agregó correctamente.");
                lista_clientes();
            } else {
                alertify.error("Algo ha salido terriblemente mal.");
            }
        });
    }, function () {
        alertify.error('Cancel')
    });
}