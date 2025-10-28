function CargarTablaProductos(nombre, precio, id, stock) {
  var $tbody = $("#tabla_productos>tbody");
  precio = Number(precio) || 0;
  stock = parseInt(stock, 10) || 0;
 console.log(id);
  // Si ya existe el producto, sumamos 1 pero sin pasar del stock
  var $existe = $tbody.find('tr[data-id="'+id+'"]');
  if ($existe.length) {
    var $qty = $existe.find('input.qty');
    var nueva = Math.min((parseInt($qty.val(), 10) || 0) + 1, stock);
    $qty.val(nueva).trigger('input'); // recalculamos subtotal si tienes lógica
    return;
  }

  // Nueva fila (la validación se resuelve sola con max en el input)
  var fila =
    "<tr data-id='"+id+"'>"
      + "<td style='display:none' id='id_articulo'>" + id + "</td>"
      + "<td id='nombre_articulo'>" + nombre + "</td>"
      + "<td class='text-center'>"
     + "<input type='number' id='cantidad' class='form-control form-control-sm text-center qty' "
      + "value='1' min='1' max='"+stock+"' style='max-width:90px;' "
      + "oninput='if(this.value===\"\") { this.value=1; return; } this.value = Math.min(Math.max(1, +this.value), +this.max);'>"
      + "</td>"
      + "<td class='precio text-end' id='precio'>" + precio.toFixed(2) + "</td>"
      + "<td class='subtotal text-end' id='total'>" + precio.toFixed(2) + "</td>"
  
    + "<th><button type='button' class='borrar remove-item-btn bg-danger-focus bg-hover-danger-200 text-danger-600 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle'><iconify-icon icon='fluent:delete-24-regular' class='menu-icon'></iconify-icon></button></th>"


    + "</tr>";

  $tbody.append(fila);
  recalcularTotales();
}

// Subtotal de la fila
$(document).on('input change', '#tabla_productos .qty', function () {
  var $row   = $(this).closest('tr');
  var precio = Number($row.find('.precio').text()) || 0;
  var qty    = parseInt($(this).val(), 10) || 0;
  if (qty < 0) qty = 0;
  $(this).val(qty);
  var sub    = precio * qty;
  $row.find('.subtotal').text(sub.toFixed(2));
  recalcularTotales();
});

// Recalcular cuando cambian impuestos, descuentos, envío o el tipo
$(document).on('input change', '#impuesto, #descuento, #envio, #descuento_tipo', function () {
  normalizarInputs();
  recalcularTotales();
});

function normalizarInputs() {
  // No negativos
  ['#impuesto', '#descuento', '#envio'].forEach(function(sel){
    var v = Number($(sel).val());
    if (v < 0 || isNaN(v)) v = 0;
    $(sel).val(v);
  });

  // Limitar impuesto a 0..100
  var imp = parseInt($('#impuesto').val(), 10) || 0;
  if (imp < 0) imp = 0;
  if (imp > 100) imp = 100;
  $('#impuesto').val(imp);

  // Si descuento es %, limitar a 0..100
  if ($('#descuento_tipo').val() === 'porcentaje') {
    var d = parseInt($('#descuento').val(), 10) || 0;
    if (d < 0) d = 0;
    if (d > 100) d = 100;
    $('#descuento').val(d);
  }
}

function recalcularTotales() {
  var cantidadTotal = 0;
  var subtotal = 0;

  $('#tabla_productos tbody tr').each(function() {
    var qty  = parseInt($(this).find('.qty').val(), 10) || 0;
    if (qty < 0) qty = 0;
    var sub  = parseFloat($(this).find('.subtotal').text()) || 0;
    cantidadTotal += qty;
    subtotal += sub;
  });

  // Inputs
  var descuentoInput = Number($('#descuento').val()) || 0;
  var envioMonto     = Number($('#envio').val()) || 0;
  if (envioMonto < 0) envioMonto = 0;

  // Descuento
  var tipoDesc = $('#descuento_tipo').val();
  var descuentoMonto = 0;
  if (tipoDesc === 'porcentaje') {
    var pct = parseInt(descuentoInput, 10) || 0;
    descuentoMonto = subtotal * (pct / 100);
  } else {
    descuentoMonto = descuentoInput < 0 ? 0 : descuentoInput;
  }
  if (descuentoMonto > subtotal) descuentoMonto = subtotal;

  // Impuesto en % sobre (subtotal - descuento)
  var impuestoPct   = parseInt($('#impuesto').val(), 10) || 0;
  var baseImponible = subtotal - descuentoMonto;
  if (baseImponible < 0) baseImponible = 0;
  var impuestoMonto = baseImponible * (impuestoPct / 100);

  // Total
  var total = baseImponible + impuestoMonto + envioMonto;

  // Actualizar
  $('#cantidad_total').text(cantidadTotal);
  $('#subtotal_general').text(subtotal.toFixed(2));
  $('#descuento_aplicado').text(descuentoMonto.toFixed(2));
  $('#impuesto_monto').text(impuestoMonto.toFixed(2));
  $('#envio_monto').text(envioMonto.toFixed(2));
  $('#total_general').text(total.toFixed(2));
}

//articulos:
// Utilidad para evitar inyecciones al pintar texto
function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

function renderArticulos(data) {
  const $grid = $('#grid_articulos');
  if (!data || !data.length) {
    $grid.html(`
      <div class="col-12">
        <div class="alert alert-warning mb-0">No hay artículos para este almacén.</div>
      </div>
    `);
    return;
  }

  let html = '';
  data.forEach(a => {
    const id      = a.id_articulo;
    const nombre  = a.nombre_articulo ?? '';
    const desc    = a.descripcion_articulo ?? 'Sin descripción';
    const precioN = Number(a.precio || 0);
    const precioV = precioN.toFixed(2); // para mostrar
    const img     = a.imagen_articulo || 'assets/images/user-grid/user-grid-bg1.png';
    const stock     = a.stock_actual ;

    html += `
      <div class="col-xxl-3 col-md-6 user-grid-card">
        <div class="position-relative border radius-16 overflow-hidden">
          <img src="${img}" alt="" class="w-100 object-fit-cover" style="height:200px;">
          <div class="ps-16 pb-16 pe-16 text-center">
            <h6 class="text-lg mb-0 mt-4">${escapeHtml(nombre)}</h6>
            <span class="text-secondary-light d-block mb-2">${escapeHtml(desc)}</span>
            <span class="text-secondary-light d-block mb-2"> Cantidad Disponible:${stock}</span>
            <h6 class="text-lg">${precioV} $</h6>
             
    

            <button type="button"
              class="bg-primary-50 text-primary-600 bg-hover-primary-600 hover-text-white 
                     p-10 text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center 
                     justify-content-center mt-16 fw-medium gap-2 w-100"
              onclick='CargarTablaProductos(${JSON.stringify(nombre)}, ${precioN}, ${id},${stock})'>
              Agregar
              <iconify-icon icon="solar:alt-arrow-right-linear" class="icon text-xl line-height-1"></iconify-icon>
            </button>
          </div>
        </div>
      </div>
    `;
  });

  $grid.html(html);
}

function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}


// Cargar artículos por almacén (AJAX)
function CargarArticulosPorAlmacen() {
  const idAlm = $('#id_almacen').val();
  const base  = $('#id_almacen').data('url'); // ej: /obtener-articulos

  $('#grid_articulos').html(`
    <div class="col-12"><div class="text-center py-4">Cargando artículos...</div></div>
  `);

  if (!idAlm || idAlm === '0') {
    $('#grid_articulos').html(`
      <div class="col-12"><div class="alert alert-info mb-0">Seleccione un depósito.</div></div>
    `);
    return;
  }

  $.get(`${base}/${idAlm}`)
    .done(function(res){
      renderArticulos(res);
    })
    .fail(function(){
      $('#grid_articulos').html(`
        <div class="col-12"><div class="alert alert-danger mb-0">Error cargando artículos.</div></div>
      `);
    });
}

// Bind change y carga inicial si hay old()
$('#id_almacen').on('change', CargarArticulosPorAlmacen);
$(function(){
  if ($('#id_almacen').val() && $('#id_almacen').val() !== '0') {
    CargarArticulosPorAlmacen();
  }
});




//OBTENER DATOS DE LA TABLA 
function CapturarDatosTabla()
{
    var idalmacen =$('#id_almacen').val();
    var idcliente =$('#id_cliente').val();

      // Validaciones básicas

    if (idalmacen == 0 || idalmacen === '') {
        alert('DEBE SELECCIONAR UN ALMACEN');
        return;
    }
    if (idcliente == 0 || idcliente === '') {
        alert('DEBE SELECCIONAR UN CLIENTE');
        return;
    }
    $('#datos_cliente').val(idcliente); //PASA DATOS DE LA TABLA A CAMPO OCULTO APRA ENVIAR POR POST
    $('#datos_almacen').val(idalmacen); //PASA DATOS DE LA TABLA A CAMPO OCULTO APRA ENVIAR POR POST


    let lista_ventas = [];
    
    document.querySelectorAll('#tabla_productos tbody tr').forEach(function(e){
        let fila = {
            id_articulo: e.querySelector('#id_articulo').innerText,
            nombre_articulo: e.querySelector('#nombre_articulo').innerText,
            precio: e.querySelector('#precio').innerText,
            cantidad: e.querySelector('#cantidad')?.value ,
            total: e.querySelector('#total').innerText,
            
        };

        lista_ventas.push(fila);
    });

    $('#datos_venta').val(JSON.stringify(lista_ventas)); //PASA DATOS DE LA TABLA A CAMPO OCULTO APRA ENVIAR POR POST
    console.log(lista_ventas)


    
    
    const lista_totales = [{
    subtotal : document.querySelector('#subtotal_general')?.textContent.trim()  || '0.00',
    descuento: document.querySelector('#descuento_aplicado')?.textContent.trim()|| '0.00',
    impuesto : document.querySelector('#impuesto_monto')?.textContent.trim()    || '0.00',
    envio    : document.querySelector('#envio_monto')?.textContent.trim()       || '0.00',
    total    : document.querySelector('#total_general')?.textContent.trim()     || '0.00',
  }];


    $('#datos_totales').val(JSON.stringify(lista_totales)); //PASA DATOS DE LA TABLA A CAMPO OCULTO APRA ENVIAR POR POST
    console.log(lista_totales)

   

let lista_pagos = [];
    
    document.querySelectorAll('#tabla_pagos tbody tr').forEach(function(e){
        let fila = {
            id_metodo: e.querySelector('#id_metodo').innerText,
            nombre_metodo: e.querySelector('#nombre_metodo').innerText,
            cantidad: e.querySelector('#cantidad').value,   
        };

        lista_pagos.push(fila);
    });

    
    $('#datos_pagos').val(JSON.stringify(lista_pagos)); //PASA DATOS DE LA TABLA A CAMPO OCULTO APRA ENVIAR POR POST
    console.log(lista_pagos)

    return lista_pagos,lista_totales,lista_ventas;

}
// prueba


// Utilidad
function toNumber(v) { v = Number(v); return isNaN(v) ? 0 : v; }
function fmt(n) { return (toNumber(n)).toFixed(2); }

// Al abrir el modal: setear Total a pagar a partir del total general de la vista
document.getElementById('exampleModal').addEventListener('show.bs.modal', function () {
  const totalGeneral = document.querySelector('#total_general')?.textContent || '0';
  document.querySelector('#total_general_modal').textContent = fmt(totalGeneral);
  recalcularPagosModal(); // reinicia pagado/por pagar/cambio en base a lo que ya hubiera
});


function CargarTablaMetodos() {
    var metodo = $("#mp_metodo option:selected").text();
   
    var Idmetodo =$('#mp_metodo').val();
    var monto =$('#mp_monto').val();

  
    if(Idmetodo== 0 || metodo=='')
        {
            alert('DEBE SELECCIONAR UN METODO');
            return; 
        }
      
    if(monto<= 0 || monto=='')
    {
        alert('DEBE INGRESAR UN MONTO VALIDO');
        return; 
    }
  


      $("#tabla_pagos>tbody").append(
    "<tr>"
    + "<td id='id_metodo' style='display: none' > " + Idmetodo + "</td>"
    + "<td id='nombre_metodo' class='mp-metodo'>" + metodo + "</td>"
    + "<td id='monto' class='text-end'>"
        + "<input type='number' id='cantidad' name='cantidad'  class='form-control form-control-sm text-end mp-monto' "
        + "min='1' step='0.001' value='" + monto + "' style='max-width:120px;'>"
    + "</td>"
    + "<th class='text-center'><button type='button' class='btn btn-sm btn-outline-danger mp-del'>Quitar</button></th>"
    + "</tr>"
);
   


  recalcularPagosModal();
       
   
    
}





// Mientras escribe
document.addEventListener('input', function (e) {
  if (e.target.matches('.mp-monto')) {
    // Si está vacío, no forzamos nada (dejamos escribir)
    if (e.target.value.trim() === '') {
      return;
    }

    let v = toNumber(e.target.value);
    if (v < 1) v = 1;   // mínimo 1
    e.target.value = v; // dejamos sin decimales mientras escribe
    recalcularPagosModal();
  }
});

// Cuando pierde el foco
document.addEventListener('blur', function (e) {
  if (e.target.matches('.mp-monto')) {
    let v = toNumber(e.target.value);
    if (v < 1) v = 1;   // mínimo 1
    e.target.value = fmt(v); // ya formateado (ej: 1.00)
    recalcularPagosModal();
  }
}, true);

// Helpers
function toNumber(v){ v = Number(v); return isNaN(v) ? 0 : v; }
function fmt(n){ return (toNumber(n)).toFixed(2); }

// Quitar pago
document.addEventListener('click', function (e) {
  if (e.target.matches('.mp-del')) {
    e.target.closest('tr').remove();
    recalcularPagosModal();
  }
});

function recalcularPagosModal() {
  const total = toNumber(document.querySelector('#total_general_modal')?.textContent || 0);

  // Sumar pagos
  let pagado = 0;
  document.querySelectorAll('#tabla_pagos tbody .mp-monto').forEach(inp => {
    pagado += toNumber(inp.value);
  });

  // Por pagar (si negativo, será 0 y el resto va a "cambio")
  const porPagar = Math.max(0, total - pagado);
  const cambio   = Math.max(0, pagado - total);

  // Mostrar en pantalla
  document.querySelector('#pagado_modal').textContent    = fmt(pagado);
  document.querySelector('#por_pagar_modal').textContent = fmt(porPagar);
  document.querySelector('#cambio_modal').textContent    = fmt(cambio);

  // Guardar en input oculto
  document.getElementById('datos_pagados').value = JSON.stringify({
    total: total,
    pagado: pagado,
    por_pagar: porPagar,
    cambio: cambio
  });
}
// Al enviar el form, asegurar que los pagos están guardados
document.querySelector('#exampleModal form').addEventListener('submit', function(){
  recalcularPagosModal();
});


$(document).on('click', '.borrar', function(event) {

     
     $(this).closest('tr').remove();
    recalcularTotales()
});



// ---------- Config ----------
const RUTA_STOCK = 'stockarticulos/'; // GET /stockarticulos/{id_articulo}/{id_almacen}

// Cambia esto por tu select/variable real de almacén:
function getIdAlmacenActual() {
  return $('#id_almacen').val() || 1; // por ejemplo
}

// ---------- Lector ----------
// function iniciarLectorBarras() {
//   let $scan = $('#barcode_scanner');
//   if (!$scan.length) {
//     $('body').append("<input id='barcode_scanner' type='text' autocomplete='off' style='position:fixed;left:-9999px;opacity:0;'>");
//     $scan = $('#barcode_scanner');
//   }

//   const refocus = () => { if (!$scan.is(':focus')) $scan.focus(); };
//   refocus();
//   setInterval(refocus, 1500); // mantiene el foco

//   Cuando el lector envía Enter, buscamos y cargamos
//   $scan.on('keydown', function (e) {
//     if (e.key === 'Enter') {
//       const codigo = this.value.trim();
//       this.value = '';
//       if (!codigo) return;
//       buscarYAgregarPorCodigo(codigo, getIdAlmacenActual()); // <-- pasar almacén
//     }
//   });
// }

// Busca y agrega con tu función CargarTablaProductos(...)
function buscarYAgregarPorCodigo(idArticulo, idAlmacen) {
  obtenerProductoPorCodigo(idArticulo, idAlmacen)
    .done(function(p) {
      // Caso 1: el backend devuelve null (p == null) cuando stock = 0
      if (!p) { 
        avisarSinStock('Artículo'); 
        return; 
      }

      // Caso 2: vino objeto pero sin id (no encontrado real)
      if (!p.id) { 
        avisarNoEncontrado(idArticulo); 
        return; 
      }

      // Caso 3: hay objeto, pero stock <= 0
      const stock = Number(p.stock ?? p.stock_actual ?? 0);
      if (stock <= 0) { 
        avisarSinStock(p.nombre || 'Artículo'); 
        return; 
      }

      // OK, hay stock
      CargarTablaProductos(p.nombre, Number(p.precio), p.id, stock);
    })
    .fail(function(xhr) {
      // Si decides devolver 404 en backend cuando no hay stock, avisamos aquí:
      if (xhr && xhr.status === 404) {
        avisarSinStock('Artículo');
        return;
      }
      // Otros errores de red/servidor:
      avisarErrorConexion(idArticulo);
      console.error('Error consultando el artículo:', idArticulo, '| status:', xhr?.status);
    });
}


// Llama a tu endpoint Laravel /stockarticulos/{id_articulo}/{id_almacen}
function obtenerProductoPorCodigo(idArticulo, idAlmacen) {
  console.log(idAlmacen);
  return $.ajax({
    url: RUTA_STOCK + encodeURIComponent(idArticulo) + '/' + encodeURIComponent(idAlmacen),
    method: 'GET',
    dataType: 'json',
    cache: false
  });
}

// Opcional: mensajes
function avisarNoEncontrado(codigo) {
  console.warn('Artículo no encontrado para código:', codigo);
}
function avisarErrorConexion(codigo) {
  console.error('Error consultando el artículo con código:', codigo);
}

// // Arranque
// $(function () {
//   iniciarLectorBarras();
// });

// --------- Tester opcional (si lo usas) ---------
$(function () {
  $('#btn_test_scan').on('click', function () {
    const code = $('#barcode_test').val().trim();
    if (!code) return;
    buscarYAgregarPorCodigo(code, getIdAlmacenActual());
    $('#barcode_test').val('');
  });
  $('#barcode_test').on('keydown', function (e) {
    if (e.key === 'Enter') $('#btn_test_scan').click();
  });
});

function avisarSinStock(nombre) {
  alert((nombre || 'Artículo') + ' sin stock disponible');
}