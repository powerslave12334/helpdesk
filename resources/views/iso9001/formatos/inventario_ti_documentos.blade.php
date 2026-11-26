@extends('layouts.app')

@section('title', $formato['clave'] . ' - ' . $formato['nombre'] . ' - ISO 9001')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0">
            <i class="{{ $formato['icono'] ?? 'fas fa-file' }} me-2"></i>{{ $formato['nombre'] }}
            <span class="badge bg-primary ms-2">{{ $formato['clave'] }}</span>
        </h4>
        <p class="text-muted mb-0">Inventario de activos de TI</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('iso9001.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Volver
        </a>
        <a href="{{ route('iso9001.create', ['clave' => $formato['clave']]) }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Nuevo activo
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
                <label class="form-label">Usuario</label>
                <input type="text" name="usuario" class="form-control" placeholder="Usuario"
                       value="{{ old('usuario', $filtros['usuario'] ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Departamento</label>
                <input type="text" name="departamento" class="form-control" placeholder="Departamento"
                       value="{{ old('departamento', $filtros['departamento'] ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Marca</label>
                <input type="text" name="marca" class="form-control" placeholder="Marca"
                       value="{{ old('marca', $filtros['marca'] ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Condición</label>
                <select name="condicion" class="form-select">
                    <option value="">-- Todas --</option>
                    @foreach($estatus ?? \App\Models\Estatus::where('activo', true)->get() as $e)
                    <option value="{{ $e->id_Estatus }}" {{ (old('condicion', $filtros['condicion'] ?? '') == $e->id_Estatus) ? 'selected' : '' }}>
                        {{ $e->descripcion }}
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
                        <th class="text-nowrap">Folio</th>
                        <th>Usuario</th>
                        <th>Departamento</th>
                        <th>Ubicación</th>
                        <th>Tipo de Equipo</th>
                        <th class="text-nowrap">No. Serie</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Condición</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbodyInventarioTi">
                    @forelse($equipos as $equipo)
                    <tr>
                        <td class="text-nowrap"><strong>{{ $equipo->folio }}</strong></td>
                        <td>{{ $equipo->usuario_asignado?->nombreCompleto() ?? '—' }}</td>
                        <td>{{ $equipo->departamento ?? '—' }}</td>
                        <td>{{ $equipo->ubicacion }}</td>
                        <td>{{ $equipo->tipoEquipo->descripcion ?? '—' }}</td>
                        <td class="text-nowrap">{{ $equipo->numeroSerie }}</td>
                        <td>{{ $equipo->marca }}</td>
                        <td>{{ $equipo->modelo }}</td>
                        <td>
                            <span class="badge bg-{{ $equipo->estatus->descripcion === 'Activo' ? 'success' : ($equipo->estatus->descripcion === 'Inactivo' ? 'secondary' : 'warning') }}">
                                {{ $equipo->estatus->descripcion }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('iso9001.pdf', ['clave' => $formato['clave'], 'id' => $equipo->id_Equipo]) }}" class="btn btn-sm btn-outline-primary" title="Ver PDF">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted">
                            <i class="fas fa-laptop fa-2x mb-2 d-block"></i>
                            No se encontraron activos de TI con los filtros aplicados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $equipos->links() }}
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
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const nuevoTbody = doc.getElementById('tbodyInventarioTi');
                const viejosEnlaces = doc.querySelector('.pagination');

                if (nuevoTbody) {
                    document.getElementById('tbodyInventarioTi').innerHTML = nuevoTbody.innerHTML;
                }
                if (viejosEnlaces) {
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
