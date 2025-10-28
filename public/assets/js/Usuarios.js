//LLENA EL SELECT DE TIENDAS

function CargarTienda() {
    //console.log(e);
    var IdEmpresa = $('#id_empresa').val();
    console.log(IdEmpresa);
    $.get(obtenertienda+ '/' + IdEmpresa, function(data) {
        var old = $('#id_tienda').data('old') != '' ? $('#id_tienda').data('old') : '';
        $('#id_tienda').empty();
        $('#id_tienda').append('<option value="0">Seleccione la Tienda</option>');

        $.each(data, function(fetch, tiendas) {
            console.log(data);
            for (i = 0; i < tiendas.length; i++) {
                $('#id_tienda').append('<option value="' + tiendas[i].id_tienda + '"   ' + (old ==
                    tiendas[i].id_tienda ? "selected" : "") + ' >'+ tiendas[i]
                    .id_tienda + ' - ' + tiendas[i]
                    .nombre + '</option>');
            }
        })

    })
}
CargarTienda();
$('#id_empresa').on('change', CargarTienda);





function CargarTablaTiendas() {
    var tienda = $("#id_tienda option:selected").text();
    var idtienda =$('#id_tienda').val();

    
    if(idtienda== 0 || tienda=='')
        {
            alert('DEBE SELECCIONAR UNA TIENDA');
            return; 
        }
    var ListaTienda = CapturarDatosTabla();
   
    
    for (var i = 0; i < ListaTienda.length; i++) {
    
      let IdTienda = String(ListaTienda[i].id_tienda).trim();
  

        if (idtienda == IdTienda ) {
            alert('LA TIENDA YA ESTA CARGADA, DEBE SELECCIONAR OTRA');
            return; 
        }
    }


        $("#tabla_tiendas>tbody").append(
            "<tr>"
            + "<td id='id_tienda' style='display: none'>" + idtienda + "</td>"
            + "<td id='nombre_tienda'>" + tienda.split('-')[1] + "</td>"
          
          
            + "<th><button type='button' class='remove-item-btn bg-danger-focus bg-hover-danger-200 text-danger-600 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle borrar'> <iconify-icon icon='fluent:delete-24-regular' class='menu-icon'></iconify-icon></button></th></tr>"
        );
   

 
       
    // Actualiza los datos después de agregar un nuevo vehículo o equipo
    
}




//OBTENER DATOS DE LA TABLA 
function CapturarDatosTabla()
{
    let lista_tiendas = [];
    
    document.querySelectorAll('#tabla_tiendas tbody tr').forEach(function(e){
        let fila = {
            id_tienda: e.querySelector('#id_tienda').innerText,
            nombre: e.querySelector('#nombre_tienda').innerText,
            
        };

        lista_tiendas.push(fila);
    });

    $('#datos_tiendas').val(JSON.stringify(lista_tiendas)); //PASA DATOS DE LA TABLA A CAMPO OCULTO APRA ENVIAR POR POST
    console.log(lista_tiendas)

    return lista_tiendas;

}


$(document).on('click', '.borrar', function(event) {
    event.preventDefault();
    $(this).closest('tr').remove();
});