// ------------------------------------------------------
// OVERRIDE DE DATATABLES: IDIOMA + ESTILOS DE ENCABEZADOS
// ------------------------------------------------------
(function () {
  // Detecta DataTables en vanilla o jQuery
  const DTGlobal = (window.DataTable) ? window.DataTable : (window.jQuery && window.jQuery.fn && window.jQuery.fn.dataTable);
  if (!DTGlobal) return;

  // ✅ Traducción al español
  const langES = {
    decimal: "",
    emptyTable: "No hay datos disponibles en la tabla",
    info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
    infoEmpty: "Mostrando 0 a 0 de 0 registros",
    infoFiltered: "(filtrado de _MAX_ registros totales)",
    infoPostFix: "",
    thousands: ",",
    lengthMenu: "Mostrar _MENU_ registros",
    loadingRecords: "Cargando...",
    processing: "Procesando...",
    search: "Buscar:",
    zeroRecords: "No se encontraron registros coincidentes",
    aria: {
      sortAscending: ": activar para ordenar la columna de forma ascendente",
      sortDescending: ": activar para ordenar la columna de forma descendente"
    },
    select: {
      rows: {
        _: "%d filas seleccionadas",
        0: "Haz clic en una fila para seleccionarla",
        1: "1 fila seleccionada"
      }
    },
    buttons: {
      copy: "Copiar",
      copyTitle: "Copiado al portapapeles",
      copySuccess: { _: "Copiadas %d filas", 1: "Copiada 1 fila" },
      colvis: "Columnas",
      print: "Imprimir",
      excel: "Excel",
      pdf: "PDF",
      csv: "CSV",
      pageLength: {
        "-1": "Mostrar todo",
        "_": "Mostrar %d"
      }
    },
    searchBuilder: {
      add: "Agregar condición",
      clearAll: "Limpiar todo",
      condition: "Condición",
      data: "Columna",
      deleteTitle: "Eliminar regla",
      leftTitle: "Agrupar",
      logicAnd: "Y",
      logicOr: "O",
      rightTitle: "Desagrupar",
      title: "Constructor de búsqueda",
      value: "Valor"
    }
  };

  // Aplicar lenguaje
  if (window.DataTable && window.DataTable.defaults) {
    window.DataTable.defaults.language = langES;
  }
  if (window.jQuery && window.jQuery.fn && window.jQuery.fn.dataTable && window.jQuery.fn.dataTable.defaults) {
    window.jQuery.fn.dataTable.defaults.language = langES;
  }

  // ✅ Inyectar estilos globales para encabezados alineados
  const css = `
    table.dataTable thead th {
      text-align: left !important;
      position: relative;
      padding-right: 2rem;
      vertical-align: middle;
      white-space: nowrap;
    }
    table.dataTable thead th.text-center { 
      text-align: center !important;
    }
    table.dataTable thead th.dt-type-numeric,
    table.dataTable thead th.dt-type-date,
    table.dataTable thead th.dt-type-date-time {
      text-align: left !important;
    }
    table.dataTable tbody td.dt-type-numeric {
      text-align: left !important;
    }
    table.dataTable thead th.sorting:before,
    table.dataTable thead th.sorting:after,
    table.dataTable thead th.sorting_asc:before,
    table.dataTable thead th.sorting_asc:after,
    table.dataTable thead th.sorting_desc:before,
    table.dataTable thead th.sorting_desc:after {
      position: absolute !important;
      right: .5rem !important;
      top: 50% !important;
      transform: translateY(-50%) !important;
      margin: 0 !important;
      pointer-events: none;
      z-index: 1;
    }
  `;
  const style = document.createElement('style');
  style.innerHTML = css;
  document.head.appendChild(style);

})();
