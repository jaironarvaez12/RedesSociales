<!-- Theme Customization Structure End -->
<style>
/* Fondo y borde como btn-primary para el contenedor */
.panel-primary{
  --skin-bg: var(--bs-primary, #0d6efd);
  background-color: var(--skin-bg);
  border: 1px solid var(--skin-bg);
  border-radius: .5rem; /* por si radius-8 no existe */
  color: #fff;
}

/* Label en blanco sobre fondo primario */
.panel-primary label{ color:#fff; }

/* Select legible sobre fondo primario (blanco) */
.select-on-primary{
  background-color:#fff;
  color:#000000;
  border-color: #fff;
}
.select-on-primary:focus{
  border-color:#fff;
    background-color:#fff  !important;
  box-shadow: 0 0 0 .25rem rgba(13,110,253,.25);
}
.select-on-primary:focus{
  border-color:#fff;
    background-color:#fff  !important;
    color:#000000;
  box-shadow:0 0 0 .25rem rgba(13,110,253,.25);

}
/* ===== Estilo del MENU desplegado (options) ===== */
/* Fondo azul y texto blanco para todas las opciones */
.form-select.select-on-primary option,
.form-select.select-on-primary optgroup{
  background-color:#fff;
  color:#000000;
}


/* Opción resaltada/seleccionada con un azul un poco más oscuro */
.form-select.select-on-primary option:hover,
.form-select.select-on-primary option:checked,
.form-select.select-on-primary option:focus {
  background-color:#fff  !important;
  color:#fff !important;
}


</style>
<aside class="sidebar">
  <button type="button" class="sidebar-close-btn">
    <iconify-icon icon="radix-icons:cross-2"></iconify-icon>
  </button>
  <div>
    <a href="{{ url('/') }}" class="sidebar-logo">
      <img src="{{ asset('assets/images/tecno.png') }}" alt="site logo" class="light-logo">
      <img src="{{ asset('assets/images/logo-light.png') }}" alt="site logo" class="dark-logo">
      <img src="{{ asset('assets/images/logo-ico.png') }}" alt="site logo" class="logo-icon">

    </a>
  </div>
   {{-- Selector de tienda del usuario --}}
  @auth
    @if(!empty($tiendasUsuario) && count($tiendasUsuario) > 0)
      <div class="px-16 py-12 radius-8 panel-primary">
        <form method="POST" action="{{ route('cambiar.tienda') }}"
              class="d-flex flex-column align-items-start gap-2 w-100">
          @csrf

          <label for="tienda_id" class="text-sm fw-semibold mb-1 d-block text-white">
            Tienda:
          </label>

          <select id="tienda_id" name="tienda_id"
                  class="form-select  w-100 select-on-primary radius-8"
                  onchange="this.form.submit()">
            @foreach ($tiendasUsuario as $t)
              <option value="{{ $t['id'] }}"
                {{ (string)($tiendaActual ?? '') === (string)$t['id'] ? 'selected' : '' }}>
                {{ $t['nombre'] }}
              </option>
            @endforeach
          </select>
        </form>
      </div>
    @endif
  @endauth
  <div class="sidebar-menu-area">
    <ul class="sidebar-menu" id="sidebar-menu">
      {{-- <li class="dropdown">
        <a href="javascript:void(0)">
          <iconify-icon icon="solar:home-smile-angle-outline" class="menu-icon"></iconify-icon>
          <span>Dashboard</span>
        </a>
        <ul class="sidebar-submenu">
          <li>
            <a href="index.html"><i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> AI</a>
          </li>
          <li>
            <a href="index-2.html"><i class="ri-circle-fill circle-icon text-warning-main w-auto"></i> CRM</a>
          </li>
          <li>
            <a href="index-3.html"><i class="ri-circle-fill circle-icon text-info-main w-auto"></i> eCommerce</a>
          </li>
          <li>
            <a href="index-4.html"><i class="ri-circle-fill circle-icon text-danger-main w-auto"></i> Cryptocurrency</a>
          </li>
          <li>
            <a href="index-5.html"><i class="ri-circle-fill circle-icon text-success-main w-auto"></i> Investment</a>
          </li>
          <li>
            <a href="index-6.html"><i class="ri-circle-fill circle-icon text-purple w-auto"></i> LMS</a>
          </li>
          <li>
            <a href="index-7.html"><i class="ri-circle-fill circle-icon text-info-main w-auto"></i> NFT & Gaming</a>
          </li>
          <li>
            <a href="index-8.html"><i class="ri-circle-fill circle-icon text-danger-main w-auto"></i> Medical</a>
          </li>
          <li>
            <a href="index-9.html"><i class="ri-circle-fill circle-icon text-purple w-auto"></i> Analytics</a>
          </li>
          <li>
            <a href="index-10.html"><i class="ri-circle-fill circle-icon text-warning-main w-auto"></i> POS & Inventory
            </a>
          </li>
          <li>
            <a href="index-11.html"><i class="ri-circle-fill circle-icon text-success-main w-auto"></i> Finance &
              Banking </a>
          </li>
          <li>
            <a href="index-12.html"><i class="ri-circle-fill circle-icon text-danger-main w-auto"></i> Booking
              System</a>
          </li>
          <li>
            <a href="index-13.html"><i class="ri-circle-fill circle-icon text-info-main w-auto"></i> Help Desk</a>
          </li>
          <li>
            <a href="index-14.html"><i class="ri-circle-fill circle-icon text-warning-main w-auto"></i> Podcast </a>
          </li>
          <li>
            <a href="index-15.html"><i class="ri-circle-fill circle-icon text-purple w-auto"></i> Project Management
            </a>
          </li>
          <li>
            <a href="index-16.html"><i class="ri-circle-fill circle-icon text-success-main w-auto"></i> Call Center</a>
          </li>
          <li>
            <a href="index-17.html"><i class="ri-circle-fill circle-icon text-danger-main w-auto"></i> Sass</a>
          </li>
        </ul>
      </li> --}}
    

     
      
      
     
      <li class="dropdown">
        <a href="javascript:void(0)">
          <iconify-icon icon="icon-park-outline:setting-two" class="menu-icon"></iconify-icon>
          <span>Ajustes</span>
        </a>
        <ul class="sidebar-submenu">
          {{-- <li>
            <a href="{{ route('empresas.index') }}"><i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Empresas</a>
          </li>
          <li>
            <a href="{{ route('tiendas.index') }}"><i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Tiendas</a>
          </li>
          <li>
            <a href="{{ route('almacenes.index') }}"><i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Almacen</a>
          </li>
          
          <li>
            <a href="{{ route('metodos_pagos.index') }}"><i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Metodos de Pago</a>
          </li> --}}



          {{-- <li>
            <a href="users-grid.html"><i class="ri-circle-fill circle-icon text-warning-main w-auto"></i> Users Grid</a>
          </li>
          <li>
            <a href="add-user.html"><i class="ri-circle-fill circle-icon text-info-main w-auto"></i> Add User</a>
          </li>
          <li>
            <a href="view-profile.html"><i class="ri-circle-fill circle-icon text-danger-main w-auto"></i> View
              Profile</a>
          </li>
          <li>
            <a href="users-role-permission.html"><i class="ri-circle-fill circle-icon text-info-main w-auto"></i> User
              Role & Permission</a>
          </li> --}}
        </ul>
      </li>
      <li class="dropdown">
        <a href="javascript:void(0)">
          <iconify-icon icon="flowbite:users-group-outline" class="menu-icon"></iconify-icon>
          <span>Compras</span>
        </a>
        <ul class="sidebar-submenu">
          {{-- <li>
            <a href="{{ route('proveedores.index') }}"><i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i>Proveedores</a>
          </li>
          <li>
            <a href="{{ route('comppedidos.index') }}"><i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i>Pedidos</a>
          </li>
          <li>
            <a href="{{ route('albaranes.index') }}"><i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Albaranes</a>
          </li> --}}
         
        </ul>
      </li>
      <li class="dropdown">
        <a href="javascript:void(0)">
          <iconify-icon icon="flowbite:users-group-outline" class="menu-icon"></iconify-icon>
          <span>Ventas</span>
        </a>
        <ul class="sidebar-submenu">
          {{-- <li>
            <a href="{{ route('clientes.index') }}"><i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i>Clientes</a>
          </li>
        
          <li>
            <a href="{{ route('pedidos.index') }}"><i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Pedidos</a>
          </li>
         
          <li>
            <a href="{{ route('presupuestos.index') }}"><i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i>Presupuestos</a>
          </li> --}}

        </ul>
      </li>
      <li class="dropdown">
        <a href="javascript:void(0)">
          <iconify-icon icon="flowbite:users-group-outline" class="menu-icon"></iconify-icon>
          <span>Articulos</span>
        </a>
        <ul class="sidebar-submenu">
          {{-- <li>
            <a href="{{ route('articulos.index') }}"><i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Articulos</a>
          </li>
          <li>
            <a href="{{ route('categorias.index') }}"><i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Categorias</a>
          </li>
          <li>
            <a href="{{ route('unidades.index') }}"><i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Unidades</a>
          </li> --}}



          {{-- <li>
            <a href="users-grid.html"><i class="ri-circle-fill circle-icon text-warning-main w-auto"></i> Users Grid</a>
          </li>
          <li>
            <a href="add-user.html"><i class="ri-circle-fill circle-icon text-info-main w-auto"></i> Add User</a>
          </li>
          <li>
            <a href="view-profile.html"><i class="ri-circle-fill circle-icon text-danger-main w-auto"></i> View
              Profile</a>
          </li>
          <li>
            <a href="users-role-permission.html"><i class="ri-circle-fill circle-icon text-info-main w-auto"></i> User
              Role & Permission</a>
          </li> --}}
        </ul>
      </li>
      
      <li class="dropdown">
        <a href="javascript:void(0)">
          <iconify-icon icon="flowbite:users-group-outline" class="menu-icon"></iconify-icon>
          <span>Usuarios</span>
        </a>
        <ul class="sidebar-submenu">
          {{-- <li>
            <a href="{{ route('usuarios.index') }}"><i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Lista de Usuarios</a>
          </li>
          <li>
            <a href="{{ route('roles.index') }}"><i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Roles</a>
          </li>
          <li>
            <a href="{{ route('permisos.index') }}"><i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Permisos</a>
          </li> --}}
         
          {{-- <li>
            <a href="{{ route('metodos_pago.index') }}"><i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> Metodos de Pago</a>
          </li> --}}


          {{-- <li>
            <a href="users-grid.html"><i class="ri-circle-fill circle-icon text-warning-main w-auto"></i> Users Grid</a>
          </li>
          <li>
            <a href="add-user.html"><i class="ri-circle-fill circle-icon text-info-main w-auto"></i> Add User</a>
          </li>
          <li>
            <a href="view-profile.html"><i class="ri-circle-fill circle-icon text-danger-main w-auto"></i> View
              Profile</a>
          </li>
          <li>
            <a href="users-role-permission.html"><i class="ri-circle-fill circle-icon text-info-main w-auto"></i> User
              Role & Permission</a>
          </li> --}}
        </ul>
      </li>

 

  

    
    </ul>
  </div>
</aside>