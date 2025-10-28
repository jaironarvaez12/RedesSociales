
function getArticuloSeleccionado() {
  const sel = $('#id_articulo').select2('data');
  if (!sel || !sel.length) return null;
  const d = sel[0];
  return {
    id: d.id,
    nombre: d.text,
    precio: d.precio,
    unidad: d.unidad,
    precio_compra: d.precio_compra,
    stock_actual: d.stock_actual
  };
}


function CargarTabla() {
  const art = getArticuloSeleccionado();

  if (!art) {
    alert('Seleccione un artículo');
    return;
  }

  console.log('precio:', art.precio, 'unidad:', art.unidad, 'stock:', art.stock_actual);

  var cantidad = parseInt($('#cantidad').val(), 10) || 1;

  if (cantidad <= 0) {
    alert('DEBE INGRESAR LA CANTIDAD A SOLICITAR');
    return;
  }

  // ✅ VALIDACIÓN DE STOCK
  if (art.stock_actual !== undefined && cantidad > art.stock_actual) {
    alert(`La cantidad (${cantidad}) no puede ser mayor al stock disponible (${art.stock_actual}).`);
    return;
  }

  var ListaArticulos = CapturarDatosTabla();

  for (var i = 0; i < ListaArticulos.length; i++) {
    let IdArticulo = String(ListaArticulos[i].id_articulo).trim();

    if (IdArticulo == art.id) {
      alert('EL ARTICULO YA ESTA CARGADO, DEBE SELECCIONAR OTRO');
      return;
    }
  }

  $("#tabla_articulos>tbody").append(
       "<tr data-stock='" + art.stock_actual + "'>"
    + "<td id='id_pedido' style='display: none'></td>"
    + "<td id='id_articulo' style='display: none'>" + art.id + "</td>"
    + "<td id='nombre_articulo'>" + art.nombre + "</td>"
    + "<td id='precio_compra' class='text-end'>" + art.precio.toFixed(2) + "</td>"
    + "<td id='unidad'>" + art.unidad + "</td>"
    + "<td class='text-center' style='width:110px;'>"
        + "<input type='number' name='cantidad' id='cantidad' min='1' value='" + cantidad + "' "
        + "class='form-control form-control-sm text-center qty' "
        + "style='max-width:90px;'>"
    + "</td>"
    + "<td id='total' class='text-end'>" + (cantidad * art.precio).toFixed(2) + "</td>"
    + "<th><button type='button' class='text-end borrar remove-item-btn bg-danger-focus bg-hover-danger-200 text-danger-600 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle'><iconify-icon icon='fluent:delete-24-regular' class='menu-icon'></iconify-icon></button></th>"
    + "</tr>"
  );

  actualizarTotales();
}

$(document).on('input change', '#tabla_articulos .qty', function () {
    var $row      = $(this).closest('tr');
    var precio    = parseFloat($row.find('#precio_compra').text()) || 0;
    var cantidad  = Math.max(1, parseInt($(this).val(), 10) || 1);
    var stockMax  = parseInt($row.data('stock'), 10) || 0; // ← STOCK_ACTUAL guardado en el <tr>

    // ✅ Validar que no exceda el stock
    if (cantidad > stockMax) {
        alert(`La cantidad ingresada (${cantidad}) supera el stock disponible (${stockMax}).`);
        cantidad = stockMax; // Ajustar a lo máximo permitido
        $(this).val(cantidad);
    }

    // Evitar valores vacíos o menores a 1
    $(this).val(cantidad);

    var total = precio * cantidad;
    $row.find('#total').text(total.toFixed(2));
});
function actualizarTotales() {
  // Subtotal: suma de la columna "Total Parcial" de tabla_articulos
  let subtotal = 0;
   document.querySelectorAll('#tabla_articulos tbody td#total').forEach(td => {
    subtotal += parseFloat(td.textContent) || 0;
  });
  console.log(subtotal);

  let pagado = 0;
    document.querySelectorAll('#tabla_metodos tbody td#monto').forEach(td => {
    pagado += parseFloat(td.textContent) || 0;
    });
    console.log(pagado);

  // Inputs
  let impuesto  = parseFloat(document.getElementById('impuesto').value) || 0; // %
  let descuento = parseFloat(document.getElementById('descuento').value) || 0;
  let envio     = parseFloat(document.getElementById('envio').value) || 0;

  // Calcular
  let impuestoMonto = subtotal * (impuesto / 100);
  let total = subtotal + impuestoMonto + envio - descuento;
  let porpagar= total-pagado;
  // Llenar en tabla_totales (th vacíos)
  let filas = document.querySelectorAll('#tabla_totales thead tr');
  if (filas.length >= 4) {
    filas[0].lastElementChild.textContent = impuestoMonto.toFixed(2);
    filas[1].lastElementChild.textContent = descuento.toFixed(2);
    filas[2].lastElementChild.textContent = envio.toFixed(2);
    filas[3].lastElementChild.textContent = total.toFixed(2);
    filas[4].lastElementChild.textContent = pagado.toFixed(2);
    filas[5].lastElementChild.textContent = porpagar.toFixed(2);
  }
}

// Escuchar cambios
document.addEventListener('input', function(e){
  if (['impuesto','descuento','envio'].includes(e.target.id) || e.target.closest('#tabla_articulos')) {
    actualizarTotales();
  }
});

// Inicializar
document.addEventListener('DOMContentLoaded', actualizarTotales);
$(document).on('click', '.borrar', function(event) {

     
     $(this).closest('tr').remove();
     actualizarTotales()
});







function EstadoPago() {
   var Pago = $('#estado_pago').val();

   if (Pago === 'PAGADO') {
       $('#grupo_metodo').show();
       $('#grupo_cantidad').hide();
   } 
   else if (Pago === 'PARCIAL') {
       $('#grupo_metodo').show();
       $('#grupo_cantidad').show();
   } 
   else if (Pago === 'NO PAGO') { // 👈 corrige el valor para que coincida
       $('#grupo_metodo').hide();
       $('#grupo_cantidad').hide();
   }
}

// Inicializa al cargar
$(document).ready(function() {
   EstadoPago();
   // Escuchar cada cambio del select
   $('#estado_pago').on('change', EstadoPago);
});





//OBTENER DATOS DE LA TABLA 
function CapturarDatosTabla(validar = false)
{
    let lista_articulos = [];
    
    document.querySelectorAll('#tabla_articulos tbody tr').forEach(function(e){
        let fila = {
            id_articulo: e.querySelector('#id_articulo').innerText,
            precio_compra: e.querySelector('#precio_compra').innerText,
            cantidad: e.querySelector('#cantidad').value,
            total: e.querySelector('#total').innerText,
        };
        lista_articulos.push(fila);
    });

    // Pasa datos al hidden
    $('#datos_articulos').val(JSON.stringify(lista_articulos));
    console.log(lista_articulos);

    let lista_totales = [{
        impuesto: document.getElementById('impuesto1').innerText,
        descuento: document.getElementById('descuento1').innerText,
        envio: document.getElementById('envio1').innerText,
        total: document.getElementById('total1').innerText
    }];

    $('#datos_totales').val(JSON.stringify(lista_totales));
    console.log(lista_totales);

    // ✅ Solo valida cuando se pide (ej. al enviar el form)
    if (validar === true) {
         let lista_pagos = [];
    
        document.querySelectorAll('#tabla_metodos tbody tr').forEach(function(e){
            let fila = {
                id_metodo: e.querySelector('#id_metodo').innerText,
                nombre_metodo: e.querySelector('#nombre_metodo').innerText,
                monto: e.querySelector('#monto').innerText,
               
            };
            lista_pagos.push(fila);
        });

         $('#datos_pagos').val(JSON.stringify(lista_pagos));





        let total  = parseFloat(document.getElementById('total1').innerText);
        let pagado = parseFloat(document.getElementById('pagado1').innerText);

        // Tu misma lógica:
        if (pagado > total || pagado < total) {
            alert('El monto pagado debe ser igual al total.');
            return false; // detiene el submit
        }
        if (pagado <= 0) {
            alert('El monto pagado debe ser mayor.');
            return false; // detiene el submit
        }
    }

    // Por defecto, devuelve el array (lo necesita CargarTabla)
    return lista_articulos;
}



function CargarMetodos() {

  
    var metodo = $("#id_metodo option:selected").text();
    var idmetodo = $('#id_metodo').val();
    var monto = $('#monto').val();

  

    if (idmetodo == 0 || idmetodo== '') {
        alert('DEBE SELECCIONAR UN METODO DE PAGO');
        return;
    }
    if (monto == 0 || monto== '') {
        alert('DEBE INGRESAR UN MONTO VALIDO');
        return;
    }
   

    // var ListaArticulos = CapturarDatosTabla();
   
    
    // for (var i = 0; i < ListaArticulos.length; i++) {
    
    //   let IdArticulo = String(ListaArticulos[i].id_articulo).trim();

    //     if (IdArticulo  == idarticulo ) {
    //         alert('EL ARTICULO YA ESTA CARGADO, DEBE SELECCIONAR OTRO');
    //         return; 
    //     }
    // }
    $("#tabla_metodos>tbody").append(
        "<tr>"
        + "<td id='id_metodo' style='display: none'>" + idmetodo + "</td>"
        + "<td id='nombre_metodo'>" + metodo.split('-')[1] + "</td>"
        + "<td id='monto'>" +monto + "</td>"
      
       
        + "<th><button type='button' class='text-end borrar remove-item-btn bg-danger-focus bg-hover-danger-200 text-danger-600 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle'><iconify-icon icon='fluent:delete-24-regular' class='menu-icon'></iconify-icon></button></th>"

        + "</tr>"
    );
     actualizarTotales()

}

