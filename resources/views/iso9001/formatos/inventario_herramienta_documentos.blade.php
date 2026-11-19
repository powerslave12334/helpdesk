@extends('layouts.app')

@section('title', $formato['clave'] . ' - ' . $formato['nombre'] . ' - ISO 9001')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0">
            <i class="{{ $formato['icono'] ?? 'fas fa-file' }} me-2"></i>{{ $formato['nombre'] }}
            <span class="badge bg-primary ms-2">{{ $formato['clave'] }}</span>
        </h4>
        <p class="text-muted mb-0">Inventario de herramientas</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('iso9001.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Volver
        </a>
        <a href="{{ route('iso9001.create', ['clave' => $formato['clave']]) }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Nueva herramienta
        </a>
        <a href="{{ route('iso9001.pdf', ['clave' => $formato['clave'], 'id' => 'reporte']) }}" class="btn btn-outline-success" target="_blank">
            <i class="fas fa-download me-1"></i>Reporte
        </a>
    </div>
</div>

<!-- Filtros en tiempo real -->
<div class="card mb-4">
    <div class="card-body">
        <form id="formFiltros" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Código</label>
                <input type="text" name="codigo" class="form-control" placeholder="Código"
                       value="{{ old('codigo', $filtros['codigo'] ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" class="form-control" placeholder="Nombre"
                       value="{{ old('nombre', $filtros['nombre'] ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Marca</label>
                <input type="text" name="marca" class="form-control" placeholder="Marca"
                       value="{{ old('marca', $filtros['marca'] ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">-- Todos --</option>
                    @foreach(\App\Models\IsoInventarioHerramienta::ESTADOS as $clave => $etiqueta)
                        <option value="{{ $clave }}" {{ (old('estado', $filtros['estado'] ?? '') === $clave) ? 'selected' : '' }}>
                            {{ $etiqueta }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Serie</th>
                        <th>Inventario</th>
                        <th>Ubicación</th>
                        <th>Estado</th>
                        <th>Creado por</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbodyHerramientas">
                    @forelse($herramientas as $herramienta)
                    <tr>
                        <td><strong>{{ $herramienta->folio }}</strong></td>
                        <td>{{ $herramienta->codigo }}</td>
                        <td>{{ $herramienta->nombre }}</td>
                        <td>{{ $herramienta->marca }}</td>
                        <td>{{ $herramienta->modelo }}</td>
                        <td>{{ $herramienta->numeroSerie ?? '—' }}</td>
                        <td>{{ $herramienta->numeroInventario ?? '—' }}</td>
                        <td>{{ $herramienta->ubicacion }}</td>
                        <td>
                            <span class="badge bg-{{ $herramienta->estado === 'bueno' ? 'success' : ($herramienta->estado === 'regular' ? 'warning' : ($herramienta->estado === 'malo' ? 'danger' : 'secondary')) }}">
                                {{ $herramienta->estado_label }}
                            </span>
                        </td>
                        <td>
                            {{ $herramienta->creador?->nombreCompleto() ?? '—' }}
                            <div class="text-muted" style="font-size: 11px;">{{ $herramienta->fechaCreacion->format('d/m/Y H:i') }}</div>
                        </td>
                        <td class="text-end">
                            <a href="#" class="btn btn-sm btn-outline-primary" title="Ver PDF">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center py-5 text-muted">
                            <i class="fas fa-tools fa-2x mb-2 d-block"></i>
                            No se encontraron herramientas con los filtros aplicados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $herramientas->links() }}
    </div>
</div>

@endsection

@section('scripts')
<script>
    (function () {
        const form = document.getElementById('formFiltros');
        const inputs = form.querySelectorAll('input, select');
        let temporizador = null;

        function filtrar() {
            const params = new URLSearchParams();
            inputs.forEach(function (input) {
                if (input.value !== '') {
                    params.set(input.name, input.value);
                }
            });

            // Mantener la página actual
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('page')) {
                params.set('page', urlParams.get('page'));
            }

            history.pushState(null, '', '?' + params.toString());

            fetch('?' + params.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html, application/xhtml+xml'
                }
            })
            .then(function (respuesta) {
                return respuesta.text();
            })
            .then(function (html) {
                // Extraer solo el tbody del HTML recibido
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const nuevoTbody = doc.getElementById('tbodyHerramientas');
                const viejosEnlaces = doc.querySelector('.pagination');

                if (nuevoTbody) {
                    document.getElementById('tbodyHerramientas').innerHTML = nuevoTbody.innerHTML;
                }
                if (viejosEnlaces) {
                    const contenedor = document.querySelector('.card-body .pagination')?.parentElement ||
                        document.querySelector('.card-body');
                    // Reemplazar paginación si existe
                    const paginacionExistente = document.querySelector('.pagination');
                    if (paginacionExistente && viejosEnlaces) {
                        paginacionExistente.replaceWith(viejosEnlaces);
                    }
                }
            })
            .catch(function (error) {
                console.error('Error al filtrar:', error);
            });
        }

        inputs.forEach(function (input) {
            input.addEventListener('input', function () {
                clearTimeout(temporizador);
                temporizador = setTimeout(filtrar, 400);
            });
            input.addEventListener('change', function () {
                clearTimeout(temporizador);
                temporizador = setTimeout(filtrar, 400);
            });
        });
    })();
</script>
@endsection
