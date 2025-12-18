@extends('layouts.master')

@section('titulo', 'Contenido WordPress')

@section('contenido')
@include('mensajes.MsjExitoso')
@include('mensajes.MsjError')
@include('mensajes.MsjAlerta')
@include('mensajes.MsjValidacion')

<div class="dashboard-main-body">

  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
    <h6 class="fw-semibold mb-0">Contenido generado - Dominio {{ $dominio->url }}</h6>
    <ul class="d-flex align-items-center gap-2">
      <li class="fw-medium">
        <a href="{{ url('dominios') }}" class="d-flex align-items-center gap-1 hover-text-primary">
          <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
          Dominios
        </a>
      </li>
      <li>-</li>
      <li class="fw-medium">Contenido WordPress</li>
    </ul>
  </div>

  {{-- Filtros --}}
  <div class="card mb-24">
    <div class="card-header">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="fw-semibold">Filtros</div>
        <a href="{{ url('dominios') }}"
           class="btn btn-outline-secondary text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2">
          <iconify-icon icon="ic:round-arrow-back" class="icon text-xl line-height-1"></iconify-icon>
          Volver
        </a>
      </div>
    </div>

    <div class="card-body">
      <form method="GET">
        <div class="row g-2">
          <div class="col-md-3">
            <select name="tipo" class="form-select">
              <option value="">Tipo (todos)</option>
              <option value="post" {{ ($tipo==='post')?'selected':'' }}>post</option>
              <option value="page" {{ ($tipo==='page')?'selected':'' }}>page</option>
              
            </select>
          </div>

          <div class="col-md-3">
            <select name="estatus" class="form-select">
              <option value="">Estatus (todos)</option>
              <option value="pendiente" {{ ($estatus==='pendiente')?'selected':'' }}>pendiente</option>
              <option value="en_proceso" {{ ($estatus==='en_proceso')?'selected':'' }}>en_proceso</option>
              <option value="generado" {{ ($estatus==='generado')?'selected':'' }}>generado</option>
              <option value="publicado" {{ ($estatus==='publicado')?'selected':'' }}>publicado</option>
              <option value="error" {{ ($estatus==='error')?'selected':'' }}>error</option>
              <option value="programado" {{ ($estatus==='programado')?'selected':'' }}>programado</option>

            </select>
          </div>

          <div class="col-md-3">
            <button class="btn btn-primary text-sm btn-sm px-12 py-12 radius-8 w-100 d-flex align-items-center justify-content-center gap-2">
              <iconify-icon icon="mdi:filter" class="icon text-xl line-height-1"></iconify-icon>
              Filtrar
            </button>
          </div>

          <div class="col-md-3">
            <a href="{{ route('dominios.contenido_generado', $IdDominio) }}"
               class="btn btn-outline-secondary text-sm btn-sm px-12 py-12 radius-8 w-100 d-flex align-items-center justify-content-center gap-2">
              <iconify-icon icon="ic:round-refresh" class="icon text-xl line-height-1"></iconify-icon>
              Limpiar
            </a>
          </div>
        </div>
      </form>
    </div>
  </div>

  {{-- Tabla --}}
  <div class="card basic-data-table">
    <div class="card-body">
      <div class="table-responsive scroll-sm">
        <table class="table bordered-table mb-0" id="dataTable" data-page-length="10">
          <thead>
            <tr>
              <th>ID</th>
              <th>Tipo</th>
              <th>Estatus</th>
              <th>Título</th>
              <th>Keyword</th>
              <th>Fecha</th>
              <th>Programado para</th>
              <th class="text-center">Acción</th>
            </tr>
          </thead>
          <tbody>
            @foreach($items as $it)
              <tr>
                <td>{{ $it->id_dominio_contenido_detalle }}</td>
                <td><span class="fw-medium text-secondary-light">{{ $it->tipo }}</span></td>
                <td>
                  @php
                    $badgeClass = match($it->estatus) {
                      'publicado' => 'bg-success-focus text-success-600 border border-success-main',
                      'generado' => 'bg-info-focus text-info-600 border border-info-main',
                      'en_proceso' => 'bg-warning-focus text-warning-600 border border-warning-main',
                      'pendiente' => 'bg-secondary-focus text-secondary-600 border border-secondary-main',
                      'error' => 'bg-danger-focus text-danger-600 border border-danger-main',
                      'programado' => 'bg-info-focus text-info-600 border border-info-main',
                      default => 'bg-secondary-focus text-secondary-600 border border-secondary-main'
                    };
                  @endphp
                  <span class="{{ $badgeClass }} px-24 py-4 radius-4 fw-medium text-sm">
                    {{ $it->estatus }}
                  </span>
                </td>
                <td>{{ $it->title ?: '(Sin título)' }}</td>
                <td>{{ $it->keyword }}</td>
                <td>{{ $it->created_at }}</td>
                <td>
                  @if($it->estatus === 'programado')
                    <span class="fw-medium">
                      {{ $it->scheduled_at ? $it->scheduled_at->format('Y-m-d H:i') : '—' }}
                    </span>
                  @else
                    —
                  @endif
                </td>
                <td class="text-center">
                  <div class="d-flex align-items-center gap-10 justify-content-center">
                    <button type="button"
                      class="bg-info-focus text-info-600 bg-hover-info-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle border-0 btn-view-html"
                      title="Ver HTML"
                      data-title="{{ e($it->title ?: '(Sin título)') }}"
                      data-html="{{ e($it->contenido_html) }}"
                      data-error="{{ e($it->error ?? '') }}">
                      <iconify-icon icon="majesticons:eye-line" class="menu-icon"></iconify-icon>
                    </button>

                    {{-- Publicar --}}
                    <form method="POST"
                          action="{{ route('dominios.contenido.publicar', [$IdDominio, $it->id_dominio_contenido_detalle]) }}"
                          class="m-0 form-publish">
                      @csrf

                      <button type="submit"
                        class="bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle border-0"
                        title="Publicar"
                        {{ in_array($it->estatus, ['publicado','en_proceso','programado']) ? 'disabled' : '' }}>
                        <iconify-icon icon="mdi:publish" class="menu-icon"></iconify-icon>
                      </button>
                    </form>
                    <button type="button"
                      class="bg-info-focus text-info-600 bg-hover-info-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle border-0 btn-schedule"
                      title="Programar"
                      data-id="{{ $it->id_dominio_contenido_detalle }}"
                      data-title="{{ e($it->title ?: '(Sin título)') }}"
                      {{ in_array($it->estatus, ['publicado','en_proceso','programado']) ? 'disabled' : '' }}>
                      <iconify-icon icon="mdi:calendar-clock" class="menu-icon"></iconify-icon>
                    </button>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

{{-- Modal Ver HTML --}}
<div class="modal fade" id="modalHtml" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title fw-semibold" id="modalHtmlTitle">HTML</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div id="modalHtmlError" class="d-none mb-12 p-12 radius-8 bg-danger-focus border border-danger-main text-danger-600">
          <span class="fw-semibold">Error:</span> <span id="modalHtmlErrorText"></span>
        </div>

        <div class="p-16 radius-8 border mb-16">
          <div class="fw-semibold mb-8">Vista render</div>
          <div id="modalHtmlRender"></div>
        </div>

        <div>
          <label class="form-label fw-semibold">HTML crudo</label>
          <textarea class="form-control" id="modalHtmlRaw" rows="10" readonly></textarea>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('assets/js/lib/datatables.override.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  new DataTable('#dataTable', {
    columnDefs: [{ targets: -1, orderable: false, searchable: false }],
    order: [[0,'desc']]
  });

  const modalEl = document.getElementById('modalHtml');
  const modal = new bootstrap.Modal(modalEl);

  const titleEl = document.getElementById('modalHtmlTitle');
  const renderEl = document.getElementById('modalHtmlRender');
  const rawEl = document.getElementById('modalHtmlRaw');

  const errorBox = document.getElementById('modalHtmlError');
  const errorText = document.getElementById('modalHtmlErrorText');

  document.querySelectorAll('.btn-view-html').forEach(btn => {
    btn.addEventListener('click', () => {
      const title = btn.dataset.title || 'HTML';
      const html = btn.dataset.html || '';
      const error = btn.dataset.error || '';

      titleEl.textContent = title;

      // ojo: dataset trae HTML escapado con e()
      // Para renderizar, lo interpretamos:
      const decoded = new DOMParser().parseFromString(html, 'text/html').documentElement.textContent;

      renderEl.innerHTML = decoded;
      rawEl.value = decoded;

      if (error.trim().length) {
        errorBox.classList.remove('d-none');
        errorText.textContent = error;
      } else {
        errorBox.classList.add('d-none');
        errorText.textContent = '';
      }

      modal.show();
    });
  });




    // ---- Programar ----
  const scheduleModalEl = document.getElementById('modalSchedule');
  const scheduleModal = new bootstrap.Modal(scheduleModalEl);
  const scheduleForm = document.getElementById('scheduleForm');
  const scheduleTitle = document.getElementById('scheduleTitle');
  const scheduleAt = document.getElementById('scheduleAt');

  document.querySelectorAll('.btn-schedule').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.id;
      const title = btn.dataset.title || '';

      scheduleTitle.textContent = title;

      // action a la ruta nueva
      scheduleForm.action = `{{ url('dominios/'.$IdDominio.'/contenido') }}/${id}/programar`;

      // default: +30min
      const now = new Date();
      now.setMinutes(now.getMinutes() + 10);
      const pad = n => String(n).padStart(2,'0');
      const val = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
      scheduleAt.value = val;

      scheduleModal.show();
    });
  });

  // evitar doble submit
  scheduleForm.addEventListener('submit', (e) => {
    if (!confirm('¿Seguro que deseas PROGRAMAR este contenido en WordPress?')) {
      e.preventDefault();
      return;
    }
    const btn = scheduleForm.querySelector('button[type="submit"]');
    if (btn) btn.disabled = true;
  });



  const scheduleAtIso = document.getElementById('scheduleAtIso');

scheduleForm.addEventListener('submit', (e) => {
  // toma lo que elegiste en tu hora local
  const localVal = scheduleAt.value; // ej: 2025-12-19T10:30
  if (!localVal) {
    e.preventDefault();
    alert('Selecciona fecha y hora');
    return;
  }

  // ✅ convierte a una fecha real en tu zona horaria (la del sistema)
  const dt = new Date(localVal);

  // ✅ mandamos UTC ISO a Laravel (WordPress lo interpretará perfecto)
  scheduleAtIso.value = dt.toISOString(); // ej: 2025-12-19T16:30:00.000Z

  if (!confirm('¿Seguro que deseas PROGRAMAR este contenido en WordPress?')) {
    e.preventDefault();
    return;
  }

  const btn = scheduleForm.querySelector('button[type="submit"]');
  if (btn) btn.disabled = true;
});
});
</script>
<script>
  document.querySelectorAll('.form-publish').forEach(f => {
  f.addEventListener('submit', (e) => {
    if (!confirm('¿Seguro que deseas PUBLICAR este contenido en WordPress?')) {
      e.preventDefault();
      return;
    }
    const btn = f.querySelector('button[type="submit"]');
    if (btn) btn.disabled = true;
  });
});
</script>
{{-- Modal Programar --}}
<div class="modal fade" id="modalSchedule" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" id="scheduleForm" class="modal-content">
      @csrf
      <div class="modal-header">
        <h6 class="modal-title fw-semibold">Programar publicación</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <div class="mb-8 fw-semibold" id="scheduleTitle"></div>

        <label class="form-label fw-semibold">Fecha y hora</label>
       <input type="datetime-local" name="schedule_at_local" id="scheduleAt" class="form-control" required>

        {{-- ✅ esto es lo que realmente enviamos a Laravel --}}
        <input type="hidden" name="schedule_at" id="scheduleAtIso">

        <div class="text-sm text-secondary-light mt-8">
          Se enviará a WordPress. Si la fecha está en el futuro, quedará como <strong>Programado</strong>.
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Programar</button>
      </div>
    </form>
  </div>
</div>
@endsection