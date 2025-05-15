var codigos_carro = [];
var cantidades = [];
var precios = [];
let id_cliente_general = 0;
$(document).ready(function () {

    lista_formas_pagos();

    $("#guardar_venta").on("click", function () {
        if ($("#id_cliente").val() == 0 || $("#id_cliente").val() == "") {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: "Se debe de seleccionar un cliente!",
            });
        } else if ($("#id_forma_pago").val() == 0 || $("#id_forma_pago").val() == "") {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: "Se debe de seleccionar una Forma de Pago!",
            });
        } else if (codigos_carro.length == 0) {
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: "Se debe de añadir al menos un producto!",
            });
        } else {
            $.post("ws/service.php?parAccion=guardar_venta", {
                codigos_carro: codigos_carro,
                cantidades: cantidades,
                precios: precios,
                id_cliente: $("#id_cliente").val(),
                id_forma_pago: $("#id_forma_pago").val(),
                monto: $("#total").val()
            }, function (response) {
                var obj = JSON.parse(response);

                if (obj.Result == "OK") {
                    alertify.success("Venta Registrada Correctamente.");
                    limpiar_formulario();
                } else {
                    if (obj.Message !== undefined) {
                        bootbox.alert({
                            message: "ERROR: " + obj.Message,
                            size: 'small'
                        });
                    } else {
                        alertify.error("Algo ha salido terriblemente mal.");
                    }
                }
            });
        }
    });
    limpiar_campos();
    $("#producto").autocomplete({
        source: function (request, response) {
            $.ajax({
                url: "ws/service.php?parAccion=autocomplete",
                type: "GET",
                dataType: "json",
                data: {
                    search: request.term
                },
                success: function (data) {
                    response($.map(data, function (item) {
                        return {
                            label: item.producto + " S/ " + item.precio_unitario,
                            value: item.producto + " S/ " + item.precio_unitario,
                            id: item.id,
                            producto: item.producto,
                            precio_unitario: parseFloat(item.precio_unitario).toFixed(2)
                        }
                    }))
                }
            });
        },
        select: function (event, ui) {
            $("#add-item").attr("onclick", `anadirItem(${ui.item.id}, '${ui.item.producto}', '${ui.item.precio_unitario}')`);
            $("#cantidad").trigger("focus");
        }
    });
    $("#cantidad").on("keydown", function () {
        if (event.key === "Enter" || event.keyCode === 13) {
            $("#add-item").click();
        }
    });
});
function limpiar_campos() {
    $("#producto").trigger("focus");
    $("#producto").val("");
    $("#cantidad").val("");
}
function limpiar_formulario() {
    $(".form-control").val('');
    $("select").val(0);
    $('.item-carrito').remove();
    $(".order-empty").show();
    codigos_carro = [];
    cantidades = [];
    precios = [];
    calcularTodo();
    $("#producto").trigger("focus");
}
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
function buscar_ruc() {
    $("#resultado_ruc").empty();
    $("#resultado_ruc").append('<span class="badge bg-warning">Buscando</span>');

    var ruc = $("#n_documento").val();
    var token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJlbWFpbCI6ImFyYW5pYmFyLjA4ZGllZ29AZ21haWwuY29tIn0.dhWaDdyyyFkPDAYdTHhliwqa7i3J_royA_5fSJLQ9mE";
    if ($("#n_documento").val().length == 8) {
        $.get(`https://dniruc.apisperu.com/api/v1/dni/${ruc}?token=${token}`)
            .done(function (response) {
                // Procesar la respuesta JSON
                try {
                    var obj = response; // No es necesario parsear si ya es un objeto
                    if (obj.success) {
                        $("#resultado_ruc").empty();
                        $("#resultado_ruc").append('<span class="badge bg-success">' + ruc + ' - ' + obj.nombres + ' ' + obj.apellidoPaterno + ' ' + obj.apellidoMaterno + '</span>');
                        $("#razon_social").val(obj.nombres + ' ' + obj.apellidoPaterno + ' ' + obj.apellidoMaterno);
                        $("#direccion").val("");
                    } else {
                        $.get(`util.php?dni=${ruc}`)
                            .done(function (response) {
                                try {
                                    var obj = JSON.parse(response);
                                    $("#resultado_ruc").empty();
                                    $("#resultado_ruc").append('<span class="badge bg-success">' + $("#n_documento").val() + ' - ' + obj.nombre + '</span>');
                                    console.log("DOS " + obj.nombre);
                                    $("#razon_social").val(obj.nombre);
                                    $("#direccion").val(obj.direccion);
                                } catch (e) {
                                    console.error("Error al procesar la respuesta2:", e);
                                    alert("Hubo un problema al procesar la respuesta2 del servidor.");
                                }
                            })
                            .fail(function (jqXHR, textStatus, errorThrown) {
                                // Manejar errores
                                console.error("Error en la solicitud2:", textStatus, errorThrown);
                                alert("Hubo un error al consultar el RUC. Por favor, inténtalo nuevamente.");
                            });
                    }

                } catch (e) {
                    console.error("Error al procesar la respuesta:", e);
                    alert("Hubo un problema al procesar la respuesta del servidor.");
                }
            })
            .fail(function (jqXHR, textStatus, errorThrown) {
                // Manejar errores
                console.error("Error en la solicitud:", textStatus, errorThrown);
                alert("Hubo un error al consultar el RUC. Por favor, inténtalo nuevamente.");
            });
    } else {
        $.get(`https://dniruc.apisperu.com/api/v1/ruc/${ruc}?token=${token}`)
            .done(function (response) {
                // Procesar la respuesta JSON
                try {
                    var obj = response; // No es necesario parsear si ya es un objeto
                    $("#resultado_ruc").empty();
                    $("#resultado_ruc").append('<span class="badge bg-success">' + ruc + ' - ' + obj.razonSocial + '</span>');
                    console.log("UNO " + obj.razonSocial);
                    $("#razon_social").val(obj.razonSocial || "No disponible");
                    $("#direccion").val(obj.direccion || "No disponible");

                    if ($("#razon_social").val() == "No disponible" /*1 == 1*/) {
                        console.log("Entro a no disponile");
                        $.get(`util.php?ruc=${ruc}`)
                            .done(function (response) {
                                try {
                                    var obj = JSON.parse(response);
                                    $("#resultado_ruc").empty();
                                    $("#resultado_ruc").append('<span class="badge bg-success">' + $("#n_documento").val() + ' - ' + obj.nombre + '</span>');
                                    console.log("DOS " + obj.nombre);
                                    $("#razon_social").val(obj.nombre);
                                    $("#direccion").val(obj.direccion);
                                } catch (e) {
                                    console.error("Error al procesar la respuesta2:", e);
                                    alert("Hubo un problema al procesar la respuesta2 del servidor.");
                                }
                            })
                            .fail(function (jqXHR, textStatus, errorThrown) {
                                // Manejar errores
                                console.error("Error en la solicitud2:", textStatus, errorThrown);
                                alert("Hubo un error al consultar el RUC. Por favor, inténtalo nuevamente.");
                            });
                    }
                } catch (e) {
                    console.error("Error al procesar la respuesta:", e);
                    alert("Hubo un problema al procesar la respuesta del servidor.");
                }
            })
            .fail(function (jqXHR, textStatus, errorThrown) {
                // Manejar errores
                console.error("Error en la solicitud:", textStatus, errorThrown);
                alert("Hubo un error al consultar el RUC. Por favor, inténtalo nuevamente.");
            });
    }
}
function anadirItem(id, nombre, precio) {
    console.log($("#producto"));
    console.log(nombre);
    console.log(precio);
    var flag = 0;
    var iterador = 0;
    var item_encontrado = 0;
    for (var i = 0; i < codigos_carro.length; i++) {
        if (id == codigos_carro[i]) {
            flag = 1;
            iterador = i;
            item_encontrado = i;
        }
    }
    let total_item = precio * $("#cantidad").val();
    if (flag == 0) {
        codigos_carro.push(id);
        cantidades.push($("#cantidad").val());
        precios.push(precio);

        $(".order-empty").hide();
        $("#div_lista_ventas").append(`
            <div id="item${id}" class="row item-carrito" style="color: #555555; background: rgba(140,143,183,0.2); border-bottom: solid 1px #313131;">
                <table class="table mb-0">
                    <tr>
                        <td width="10%">
                            <strong id="cant${id}">${$("#cantidad").val()}</strong>
                        </td>
                        <td width="30%">
                            <p style="font-weight: bold; display: block; font-size: 13px; text-align: left;" class="w-100 mb-0">${nombre}</p>
                            <p class="w-100 mt-0" style="font-size: 13px; text-align: left;"> <span class="badge badge-primary">S/ ${precio}</span></p>
                        </td>
                        <td width="30%">
                            <strong>S/ </strong><strong id="precio${id}">${total_item}</strong>
                        </td>
                        <td width="30%">
                            <span class="btn btn-danger btn-sm d-block" onclick="quitar_carro(${id},${total_item})"><i class="fa fa-trash"></i></span>
                        </td>
                    </tr>
                </table>
            </div>
        `);

        $("#div_engloba").empty();
        $("#div_engloba").append(`
            <div class="row">
                <div class="col-6">
                </div>
                <div class="col-6 text-center" style="border-top: solid 2px #313131;">
                    <p>Total: S/ <strong id="subtotal"></strong> </p>
                </div>
            </div>
        `);
    }
    else {
        var cant_actual = parseInt(cantidades[item_encontrado]) + parseInt($("#cantidad").val());
        var precio_nuevo = precio * (cant_actual);
        $("#cant" + id).text(cant_actual);
        $("#precio" + id).text(redondear(precio_nuevo, 2));

        cantidades[item_encontrado] = cant_actual;
        precios[item_encontrado] = precio;
    }
    calcularTodo();

    limpiar_campos();

    console.log(cantidades);
    console.log(precios);
    console.log(codigos_carro);
}
function calcularTodo() {
    var total = 0;
    for (var i = 0; i < codigos_carro.length; i++) {
        total += parseFloat($("#precio" + codigos_carro[i]).html() || 0);
    }
    total = redondear(total, 2);
    var sub_total = redondear(total / 1.18);
    var igv = redondear(total - sub_total, 2);
    $("#total").val(total);
    $("#total_detalle_span").text("S/ " + total)

    console.log("El total: " + total);
}
function redondear(num, decimales = 2) {
    var signo = (num >= 0 ? 1 : -1);
    num = num * signo;
    if (decimales === 0)
        return signo * Math.round(num);
    num = num.toString().split("e");
    num = Math.round(+(num[0] + "e" + (num[1] ? (+num[1] + decimales) : decimales)));
    num = num.toString().split("e");
    return signo * (num[0] + "e" + (num[1] ? (+num[1] - decimales) : -decimales));
}
function quitar_carro(id, precio) {
    var iterador = 0;//para saber en que posicion esta el articulo y su cantidad
    for (var i = 0; i < codigos_carro.length; i++) {
        if (id == codigos_carro[i]) {
            iterador = i;
        }
    }
    var precio_a_dos = redondear(precio, 2);
    var cantidad_anterior = parseInt($("#cant" + id).val());
    var precio_anterior = parseFloat($("#precio" + id).html());
    if (cantidad_anterior > 1) {
        var nueva_cantidad = cantidad_anterior - 1;
        var nuevo_precio = precio_anterior - precio_a_dos;
        $("#cant" + id).val(nueva_cantidad);
        $("#precio" + id).html(redondear(nuevo_precio, 2));
        cantidades[iterador]--;
    } else {
        console.log("para borrar pues");
        //borrar tanto de tabla como de sus arreglos sabiendo su posicion
        $("#item" + id).remove();
        //codigos_carro.pop(id);
        codigos_carro.splice(iterador, 1);//el 1 es cuantas veces hara la funcion 
        cantidades.splice(iterador, 1);
    }
    calcularTodo();
}