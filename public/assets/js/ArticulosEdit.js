//LLENA EL SELECT DE ALMACENES
const FormatoMoneda = (numero) => {
        var valor = numero.toString(); 
        var miles = valor.replace('.', ''); // quita los puntos
        var decimales = miles.replace(',', '.') //cambia coma por punto;
        return decimales;
    }







function CargarTablaAlmacen() {
    var almacen   = $("#id_almacen option:selected").text();
    var idalmacen = $('#id_almacen').val();
    var stockStr  = $('#stock').val();

    // Validaciones básicas
    if (idalmacen == 0 || almacen === '') {
        alert('DEBE SELECCIONAR UN Almacen');
        return;
    }
    var stock = parseFloat(stockStr);
    if (isNaN(stock) || stock <= 0) {
        alert('DEBE INGRESAR UN STOCK VALIDO');
        return;
    }

    // ¿Ya existe ese almacen en la tabla?
    var $filaExistente = $("#tabla_almacenes tbody tr").filter(function () {
        return $(this).find("td#id_almacen").text().trim() === String(idalmacen).trim();
    });

    if ($filaExistente.length) {
        // Sumar al stock existente
        var $celdaStock = $filaExistente.find("td#stock");
        var stockActual = parseFloat($celdaStock.text().trim()) + stock|| 0;
        //console.log(stockActual);

        $celdaStock.text(stockActual);

        // Limpieza de inputs (opcional)
        $('#stock').val('');
        $('#id_almacen').val('0').change();
        return;
    }

    // Si no existe, agregamos la fila
    // Nombre del almacén (si viene "id - nombre", tomar la parte derecha)
    var nombreAlmacen = almacen.includes('-') ? almacen.split('-')[1].trim() : almacen.trim();

    $("#tabla_almacenes>tbody").append(
        "<tr>"
        + "<td id='id_almacen' style='display:none;'>" + idalmacen + "</td>"
        + "<td id='nombre_almacen'>" + nombreAlmacen + "</td>"
        + "<td id='stock'>" + stock + "</td>"

        + "<th><button type='button' class='remove-item-btn bg-danger-focus bg-hover-danger-200 text-danger-600 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle'><iconify-icon icon='fluent:delete-24-regular' class='menu-icon'></iconify-icon></button></th>"
        + "</tr>"
    );

    // Limpieza de inputs (opcional)
    $('#stock').val('');
    $('#id_almacen').val('0').change();
}




//OBTENER DATOS DE LA TABLA 
function CapturarDatosTabla()
{
    let lista_almacenes = [];
    
    document.querySelectorAll('#tabla_almacenes tbody tr').forEach(function(e){
        let fila = {
            id_almacen: e.querySelector('#id_almacen').innerText,
            nombre_almacen: e.querySelector('#nombre_almacen').innerText,
            stock: e.querySelector('#stock').innerText,
           
       
           
        };

        lista_almacenes.push(fila);
    });

    
    $('#datos_articulos').val(JSON.stringify(lista_almacenes)); //PASA DATOS DE LA TABLA A CAMPO OCULTO APRA ENVIAR POR POST
    console.log(lista_almacenes)

    return lista_almacenes;

}






$(document).on('click', '.borrar', function(event) {
    event.preventDefault();
    $(this).closest('tr').remove();
});









