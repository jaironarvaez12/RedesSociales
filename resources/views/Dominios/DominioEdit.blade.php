@extends('layouts.master')

@section('titulo', 'Editar Dominios')


@section('contenido')
@include('mensajes.MsjExitoso')
@include('mensajes.MsjError')
@include('mensajes.MsjAlerta')
@include('mensajes.MsjValidacion')
<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Editar Dominios</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="{!! route('dominios.index') !!}" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dominios
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Editar Dominios</li>
        </ul>
    </div>

       <div class="card h-100 p-0 radius-12">
        <div class="card-body p-24">
            <div class="row justify-content-center">
                <div class="col-xxl-6 col-xl-8 col-lg-10">
                    <div class="card border">
                        <div class="card-body">

                           <form method="POST" action="{{ route('dominios.update', $dominio->id_dominio) }}">
                                 @csrf 
                                @method('put')

                                <div class="mb-20">
                                    <label for="nombre" class="form-label fw-semibold text-primary-light text-sm mb-8">
                                       Nombre del Dominio<span class="text-danger-600">*</span>
                                    </label>
                                    <input type="text" class="form-control radius-8" id="nombre" name="nombre"
                                        value=" {{ old('name', $dominio->nombre ?? '') }}"
                                        placeholder="Ej: IdeiWeb.com" readonly>
                                </div>

                                <div class="mb-20">
                                    <label for="url" class="form-label fw-semibold text-primary-light text-sm mb-8">
                                        Url
                                    </label>
                                    <textarea class="form-control radius-8" id="url" name="url" readonly
                                            rows="2" placeholder="https://ideiweb.com/">{{ old('url', $dominio->url ?? '') }}</textarea >
                                </div>
                                 <div class="mb-20">
                                    <label for="nombre" class="form-label fw-semibold text-primary-light text-sm mb-8">
                                       Usuario<span class="text-danger-600">*</span>
                                    </label>
                                    <input type="text" class="form-control radius-8" id="usuario" name="usuario"
                                       value=" {{ old('usuario', $dominio->usuario ?? '') }}"
                                        >
                                </div>
                                 <div class="mb-20">
                                    <label for="nombre" class="form-label fw-semibold text-primary-light text-sm mb-8">
                                       Contraseña<span class="text-danger-600">*</span>
                                    </label>
                                      <input type="password" class="form-control radius-8" id="password" name="password" placeholder="Ingrese su contraseña ">
                                </div>
                                @php
                                // Opciones: puedes moverlo a config/elementor.php si quieres.
                                $plantillas = [
                                    'elementor/elementor-64.json' => 'Plantilla 64 (Servicios + FAQ)',
                                    'elementor/elementor-65.json' => 'Plantilla 65 (Corporativa)',
                                    'elementor/elementor-landing.json' => 'Landing (CTA fuerte)',
                                ];

                                $selectedTpl = old('elementor_template_path', $dominio->elementor_template_path ?? '');
                                @endphp

                                <div class="mb-20">
                                    <label for="elementor_template_path" class="form-label fw-semibold text-primary-light text-sm mb-8">
                                        Plantilla Elementor
                                    </label>

                                    <select class="form-control radius-8" id="elementor_template_path" name="elementor_template_path">
                                        <option value="">(Usar plantilla por defecto del sistema)</option>
                                        @foreach($plantillas as $path => $label)
                                            <option value="{{ $path }}" {{ $selectedTpl === $path ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <small class="text-muted">
                                        Se usará esta plantilla cuando el Job genere contenido para este dominio.
                                    </small>
                                </div>
                                <div class="d-flex align-items-center justify-content-center gap-3 mt-4">
                                    <button type="button"
                                            onclick="window.location.href='{{ route('inicio') }}'"
                                            class="border border-danger-600 bg-hover-danger-200 text-danger-600 text-md px-56 py-11 radius-8">
                                        Cancelar
                                    </button>

                                    <button type="submit"
                                            class="btn btn-primary border border-primary-600 text-md px-56 py-12 radius-8">
                                        Guardar
                                    </button>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>


@endsection


@section('scripts')
<script type="text/javascript" src="{{ asset('assets\js\Articulos.js') }}"></script>
<script src="{{ asset('assets/js/lib/file-upload.js') }}"></script>
<script>
  document.getElementById('imagen')?.addEventListener('change', function (e) {
    const file = e.target.files && e.target.files[0];
    if (!file) return;

    const url = URL.createObjectURL(file);
    const imgTag = document.getElementById('avatar-img');
    const link = document.querySelector('.popup-img');

    if (imgTag) {
      imgTag.src = url;
    }
    if (link) {
      link.href = url;
    }

    const img = new Image();
    img.onload = () => URL.revokeObjectURL(url);
    img.src = url;
  });
</script>
<script src="{{ asset('assets/js/lib/magnifc-popup.min.js') }}"></script>

<script>
    $('.popup-img').magnificPopup({
        type: 'image',
        gallery: { enabled: true }
    });
</script>
@endsection


    
  

  