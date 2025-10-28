<!-- meta tags and other links -->
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>{{config('app.name')}} - Login </title>
  <link rel="icon" type="image/png" href="assets/images/favi.png" sizes="16x16">
  <!-- remix icon font css  -->
  <link rel="stylesheet" href="assets/css/remixicon.css">
  <!-- BootStrap css -->
  <link rel="stylesheet" href="assets/css/lib/bootstrap.min.css">
  <!-- Apex Chart css -->
  <link rel="stylesheet" href="assets/css/lib/apexcharts.css">
  <!-- Data Table css -->
  <link rel="stylesheet" href="assets/css/lib/dataTables.min.css">
  <!-- Text Editor css -->
  <link rel="stylesheet" href="assets/css/lib/editor-katex.min.css">
  <link rel="stylesheet" href="assets/css/lib/editor.atom-one-dark.min.css">
  <link rel="stylesheet" href="assets/css/lib/editor.quill.snow.css">
  <!-- Date picker css -->
  <link rel="stylesheet" href="assets/css/lib/flatpickr.min.css">
  <!-- Calendar css -->
  <link rel="stylesheet" href="assets/css/lib/full-calendar.css">
  <!-- Vector Map css -->
  <link rel="stylesheet" href="assets/css/lib/jquery-jvectormap-2.0.5.css">
  <!-- Popup css -->
  <link rel="stylesheet" href="assets/css/lib/magnific-popup.css">
  <!-- Slick Slider css -->
  <link rel="stylesheet" href="assets/css/lib/slick.css">
  <!-- prism css -->
  <link rel="stylesheet" href="assets/css/lib/prism.css">
  <!-- file upload css -->
  <link rel="stylesheet" href="assets/css/lib/file-upload.css">

  <link rel="stylesheet" href="assets/css/lib/audioplayer.css">
  <!-- main css -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

  <!-- Theme Customization Structure Start -->

<!-- </div> -->

<!-- Theme Customization Structure End -->

<section class="auth bg-base d-flex flex-wrap">  
    <div class="auth-left d-lg-block d-none">
        <div class="d-flex align-items-center flex-column h-100 justify-content-center">
            <img src="assets/images/tecno.png" alt="">
        </div>
    </div>
    <div class="auth-right py-32 px-24 d-flex flex-column justify-content-center">
        <div class="max-w-464-px mx-auto w-100">
            <div>
                <a href="index.html" class="mb-40 max-w-290-px">
                    <img src="assets/images/tecno.png" alt="">
                </a>
                <h4 class="mb-12">Inicio de Sesion</h4>

            </div>
            <form class="" method="POST" action="{{ route('auth.store') }}">
                 @csrf
                <div class="icon-field mb-16">
                    <span class="icon top-50 translate-middle-y">
                        <iconify-icon icon="mage:email"></iconify-icon>
                    </span>
                    <input type="email" name="email" id="email" class="form-control h-56-px bg-neutral-50 radius-12" placeholder="Correo">
                </div>
                <div class="position-relative mb-20">
                    <div class="icon-field">
                        <span class="icon top-50 translate-middle-y">
                            <iconify-icon icon="solar:lock-password-outline"></iconify-icon>
                        </span> 
                        <input type="password" name="password" id="password" class="form-control h-56-px bg-neutral-50 radius-12"  placeholder="Contraseña">
                    </div>
                    <span class="toggle-password ri-eye-line cursor-pointer position-absolute end-0 top-50 translate-middle-y me-16 text-secondary-light" data-toggle="#password"></span>
                </div>
               

                <button type="submit" class="btn btn-primary text-sm btn-sm px-12 py-16 w-100 radius-12 mt-32"> Ingresar</button>

              
                
            </form>
        </div>
    </div>
</section>

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

<script>
      // ================== Password Show Hide Js Start ==========
      function initializePasswordToggle(toggleSelector) {
        $(toggleSelector).on('click', function() {
            $(this).toggleClass("ri-eye-off-line");
            var input = $($(this).attr("data-toggle"));
            if (input.attr("type") === "password") {
                input.attr("type", "text");
            } else {
                input.attr("type", "password");
            }
        });
    }
    // Call the function
    initializePasswordToggle('.toggle-password');
  // ========================= Password Show Hide Js End ===========================
</script>

</body>
</html>
