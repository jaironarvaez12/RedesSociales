<!-- meta tags and other links -->
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
  
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{config('app.name')}} - @yield('titulo') </title>
 <link rel="icon" type="image/png" href="{{ asset('assets/images/favi.png') }}" sizes="16x16">

<!-- remix icon font css  -->
<link rel="stylesheet" href="{{ asset('assets/css/remixicon.css') }}">
<!-- BootStrap css -->
<link rel="stylesheet" href="{{ asset('assets/css/lib/bootstrap.min.css') }}">
<!-- Apex Chart css -->
<link rel="stylesheet" href="{{ asset('assets/css/lib/apexcharts.css') }}">
<!-- Data Table css -->
<link rel="stylesheet" href="{{ asset('assets/css/lib/dataTables.min.css') }}">
<!-- Text Editor css -->
<link rel="stylesheet" href="{{ asset('assets/css/lib/editor-katex.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/lib/editor.atom-one-dark.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/lib/editor.quill.snow.css') }}">
<!-- Date picker css -->
<link rel="stylesheet" href="{{ asset('assets/css/lib/flatpickr.min.css') }}">
<!-- Calendar css -->
<link rel="stylesheet" href="{{ asset('assets/css/lib/full-calendar.css') }}">
<!-- Vector Map css -->
<link rel="stylesheet" href="{{ asset('assets/css/lib/jquery-jvectormap-2.0.5.css') }}">
<!-- Popup css -->
<link rel="stylesheet" href="{{ asset('assets/css/lib/magnific-popup.css') }}">
<!-- Slick Slider css -->
<link rel="stylesheet" href="{{ asset('assets/css/lib/slick.css') }}">
<!-- prism css -->
<link rel="stylesheet" href="{{ asset('assets/css/lib/prism.css') }}">
<!-- file upload css -->
<link rel="stylesheet" href="{{ asset('assets/css/lib/file-upload.css') }}">
<!-- audioplayer css -->
<link rel="stylesheet" href="{{ asset('assets/css/lib/audioplayer.css') }}">
<!-- main css -->
<link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>

<body>

  <!-- Theme Customization Structure Start -->
<!-- <div class="theme-customization"> -->
<div class="body-overlay"></div>

{{-- <button type="button"
    class="theme-customization__button w-48-px h-48-px bg-primary-600 text-white rounded-circle d-flex justify-content-center align-items-center position-fixed end-0 bottom-0 mb-40 me-40 text-2xxl bg-hover-primary-700">
    <i class="ri-settings-3-line animate-spin"></i>
</button> --}}
<!-- </div> -->
{{-- <div class="theme-customization-sidebar w-100 bg-base h-100vh overflow-y-auto position-fixed end-0 top-0 shadow-lg">
    <div class="d-flex align-items-center gap-3 py-16 px-24 justify-content-between border-bottom">
        <div>
            <h6 class="text-sm dark:text-white">Ajustes del Tema</h6>
            
        </div>
        <button data-slot="button"
            class="theme-customization-sidebar__close text-neutral-900 bg-transparent text-hover-primary-600 d-flex text-xl">
            <i class="ri-close-fill"></i>
        </button>
    </div>

    <div class="d-flex flex-column gap-48 p-24 overflow-y-auto flex-grow-1">

        <div class="theme-setting-item">
            <h6 class="fw-medium text-primary-light text-md mb-3">Color del Tema</h6>
            <div class="d-grid grid-cols-3 gap-3 dark-light-mode">
                <button type="button"
                    class="theme-btn theme-setting-item__btn d-flex align-items-center justify-content-center h-64-px rounded-3 text-xl active"
                    data-theme="light">
                    <i class="ri-sun-line"></i>
                </button>
                <button type="button"
                    class="theme-btn theme-setting-item__btn d-flex align-items-center justify-content-center h-64-px rounded-3 text-xl"
                    data-theme="dark">
                    <i class="ri-moon-line"></i>
                </button>
                <button type="button"
                    class="theme-btn theme-setting-item__btn d-flex align-items-center justify-content-center h-64-px rounded-3 text-xl"
                    data-theme="system">
                    <i class="ri-computer-line"></i>
                </button>
            </div>
        </div>

        <div class="theme-setting-item">
            <h6 class="fw-medium text-primary-light text-md mb-3">Posicion del Menu</h6>
            <div class="d-grid grid-cols-2 gap-3">
                <button type="button"
                    class="theme-setting-item__btn ltr-mode-btn d-flex align-items-center justify-content-center gap-2 h-56-px rounded-3 text-xl">
                    <div><i class="ri-align-item-left-line"></i></div>
                    <h6 class="text-sm font-medium mb-0">Izquierda</h6>
                </button>

                <button type="button"
                    class="theme-setting-item__btn rtl-mode-btn d-flex align-items-center justify-content-center gap-2 h-56-px rounded-3 text-xl">
                    <h6 class="text-sm font-medium mb-0">Derecha</h6>
                    <div><i class="ri-align-item-right-line"></i></div>
                </button>
            </div>
        </div>

        <div class="theme-setting-item">
            <h6 class="fw-medium text-primary-light text-md mb-3">Colores</h6>
            <div class="d-grid grid-cols-3 gap-3">
                <button type="button"
                    class="color-picker-btn d-flex flex-column justify-content-center align-items-center"
                    data-color="blue">
                    <span class="color-picker-btn__box h-40-px w-100 rounded-3"
                        style="background-color: #2563eb;"></span>
                    <span class="fw-medium mt-1" style="color: #2563eb;">Azul</span>
                </button>
                <button type="button"
                    class="color-picker-btn d-flex flex-column justify-content-center align-items-center"
                    data-color="red">
                    <span class="color-picker-btn__box h-40-px w-100 rounded-3"
                        style="background-color: #dc2626;"></span>
                    <span class="fw-medium mt-1" style="color: #dc2626;">Rojo</span>
                </button>
                <button type="button"
                    class="color-picker-btn d-flex flex-column justify-content-center align-items-center"
                    data-color="green">
                    <span class="color-picker-btn__box h-40-px w-100 rounded-3"
                        style="background-color: #16a34a;"></span>
                    <span class="fw-medium mt-1" style="color: #16a34a;">Verde</span>
                </button>
                <button type="button"
                    class="color-picker-btn d-flex flex-column justify-content-center align-items-center"
                    data-color="yellow">
                    <span class="color-picker-btn__box h-40-px w-100 rounded-3"
                        style="background-color: #ff9f29;"></span>
                    <span class="fw-medium mt-1" style="color: #ff9f29;">Amarrillo</span>
                </button>
                <button type="button"
                    class="color-picker-btn d-flex flex-column justify-content-center align-items-center"
                    data-color="cyan">
                    <span class="color-picker-btn__box h-40-px w-100 rounded-3"
                        style="background-color: #00b8f2;"></span>
                    <span class="fw-medium mt-1" style="color: #00b8f2;">Cyan</span>
                </button>
                <button type="button"
                    class="color-picker-btn d-flex flex-column justify-content-center align-items-center"
                    data-color="violet">
                    <span class="color-picker-btn__box h-40-px w-100 rounded-3"
                        style="background-color: #7c3aed;"></span>
                    <span class="fw-medium mt-1" style="color: #7c3aed;">Violeta</span>
                </button>
            </div>
        </div>

    </div>
</div> --}}
 @include('menus.menulat')

<main class="dashboard-main">
  <div class="navbar-header">
  <div class="row align-items-center justify-content-between">
    <div class="col-auto">
      <div class="d-flex flex-wrap align-items-center gap-4">
        <button type="button" class="sidebar-toggle">
          <iconify-icon icon="heroicons:bars-3-solid" class="icon text-2xl non-active"></iconify-icon>
          <iconify-icon icon="iconoir:arrow-right" class="icon text-2xl active"></iconify-icon>
        </button>
        <button type="button" class="sidebar-mobile-toggle">
          <iconify-icon icon="heroicons:bars-3-solid" class="icon"></iconify-icon>
        </button>
        {{-- <form class="navbar-search">
          <input type="text" name="search" placeholder="Search">
          <iconify-icon icon="ion:search-outline" class="icon"></iconify-icon>
        </form> --}}
      </div>
    </div>
    <div class="col-auto">
      <div class="d-flex flex-wrap align-items-center gap-3">
        {{-- <a href="{!! route('punto.index') !!}" class="btn btn-primary text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2"> 
                    
                    Punto de Venta
                </a> --}}
        {{-- <button type="button" data-theme-toggle
          class="w-40-px h-40-px bg-neutral-200 rounded-circle d-flex justify-content-center align-items-center"></button> --}}
      

        <div class="dropdown">
          <button class="d-flex justify-content-center align-items-center rounded-circle" type="button"
            data-bs-toggle="dropdown">
         
            <img src="{{ asset('assets/images/use.png') }}" alt="image" class="w-40-px h-40-px object-fit-cover rounded-circle">

          </button>
          <div class="dropdown-menu to-top dropdown-menu-sm">
            <div
              class="py-12 px-16 radius-8 bg-primary-50 mb-16 d-flex align-items-center justify-content-between gap-2">
              <div>
                <h6 class="text-lg text-primary-light fw-semibold mb-2">{!! Auth::user()->name !!}</h6>
                {{-- <span class="text-secondary-light fw-medium text-sm">Admin</span> --}}
              </div>
              <button type="button" class="hover-text-danger">
                <iconify-icon icon="radix-icons:cross-1" class="icon text-xl"></iconify-icon>
              </button>
            </div>
            <ul class="to-top-list">
              
             
              
              <form action="{{ route('logout') }}" method="POST">
                  @csrf
                <li>
                  <a class="dropdown-item text-black px-0 py-8 hover-bg-transparent hover-text-danger d-flex align-items-center gap-3"
                    href="#" onclick="this.closest('form').submit()" role="button">
                    <iconify-icon icon="lucide:power" class="icon text-xl"></iconify-icon> Cerrar Sesion</a>
                </li>
              </form>
             
            </ul>
          </div>
        </div><!-- Profile dropdown end -->
      </div>
    </div>
  </div>
</div>
  
 
      @yield('contenido')

</main>
  
 <!-- jQuery library js -->
<script src="{{ asset('assets/js/lib/jquery-3.7.1.min.js') }}"></script>
<!-- Bootstrap js -->
<script src="{{ asset('assets/js/lib/bootstrap.bundle.min.js') }}"></script>
<!-- Apex Chart js -->
<script src="{{ asset('assets/js/lib/apexcharts.min.js') }}"></script>
<!-- Data Table js -->
<script src="{{ asset('assets/js/lib/dataTables.min.js') }}"></script>
<!-- Iconify Font js -->
<script src="{{ asset('assets/js/lib/iconify-icon.min.js') }}"></script>
<!-- jQuery UI js -->
<script src="{{ asset('assets/js/lib/jquery-ui.min.js') }}"></script>
<!-- Vector Map js -->
<script src="{{ asset('assets/js/lib/jquery-jvectormap-2.0.5.min.js') }}"></script>
<script src="{{ asset('assets/js/lib/jquery-jvectormap-world-mill-en.js') }}"></script>
<!-- Popup js -->
<script src="{{ asset('assets/js/lib/magnifc-popup.min.js') }}"></script>
<!-- Slick Slider js -->
<script src="{{ asset('assets/js/lib/slick.min.js') }}"></script>
<!-- prism js -->
<script src="{{ asset('assets/js/lib/prism.js') }}"></script>
<!-- file upload js -->
<script src="{{ asset('assets/js/lib/file-upload.js') }}"></script>
<!-- audioplayer -->
<script src="{{ asset('assets/js/lib/audioplayer.js') }}"></script>

<!-- main js -->
<script src="{{ asset('assets/js/app.js') }}"></script>

<script src="{{ asset('assets/js/homeOneChart.js') }}"></script>

@yield('scripts')
</body>
</html>