//LLENA EL SELECT DE ALMACENES
const FormatoMoneda = (numero) => {
        var valor = numero.toString(); 
        var miles = valor.replace('.', ''); // quita los puntos
        var decimales = miles.replace(',', '.') //cambia coma por punto;
        return decimales;
    }







function CargarTablaAlmacen() {
    var almacen = $("#id_almacen option:selected").text();
    var idalmacen =$('#id_almacen').val();

    var stock = $('#stock').val();
    if(idalmacen== 0 || almacen=='')
        {
            alert('DEBE SELECCIONAR UN Almacen');
            return; 
        }
      
    if(stock<= 0 || stock=='')
    {
        alert('DEBE INGRESAR UN STOCK VALIDO');
        return; 
    }
  
    
 
    var ListaAlmacen = CapturarDatosTabla();
   
    
    for (var i = 0; i < ListaAlmacen.length; i++) {
    
      let IdAlmacen = String(ListaAlmacen[i].id_almacen).trim();
  

        if (idalmacen == IdAlmacen ) {
            alert('EL ALMACEN YA ESTA CARGADO, DEBE SELECCIONAR OTRO');
            return; 
        }
    }


        $("#tabla_almacenes>tbody").append(
            "<tr>"
            + "<td id='id_almacen' style='display: none'>" + idalmacen + "</td>"
            + "<td id='nombre_almacen'>" + almacen.split('-')[1] + "</td>"
            + "<td id='stock'>" + stock + "</td>"
          
            + "<th><button type='button' class='remove-item-btn bg-danger-focus bg-hover-danger-200 text-danger-600 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle'> <iconify-icon icon='fluent:delete-24-regular' class='menu-icon'></iconify-icon></button></th></tr>"
        );
   

 
       
    // Actualiza los datos después de agregar un nuevo vehículo o equipo
    
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









