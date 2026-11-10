@extends('layouts.app')
@section('title', 'Catálogo de Campos - HelpDesk')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="mb-0"><i class="fas fa-list-check me-2"></i>Catálogo de Campos</h4>
        <span class="text-muted small">Define primero tus campos aquí y después asígnalos a cada Tipo de Equipo.</span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('inventario.tipoEquipo.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Tipos de Equipo
        </a>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#campoModal" onclick="nuevoCampo()">
            <i class="fas fa-plus me-1"></i>Nuevo Campo
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-1"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card mb-4">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="table-search">
                <i class="fas fa-search"></i>
                <input type="text" id="buscador" placeholder="Buscar campo por nombre o etiqueta...">
            </div>
            <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>
                Los campos <strong>columna</strong> guardan en una columna real de tblEquipo;
                los <strong>personalizados</strong> se guardan en el JSON de atributos.
            </small>
        </div>
    </div>
</div>

@forelse($secciones as $seccion)
<div class="mb-4 seccion-bloque">
    <div class="seccion-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="{{ $seccion->icon ?: 'fas fa-folder' }} text-primary fa-lg"></i>
            <div>
                <h6 class="mb-0 fw-bold">{{ $seccion->title }}</h6>
                <small class="text-muted">{{ $seccion->description }}</small>
            </div>
        </div>
        <span class="badge bg-secondary">{{ $seccion->fields->count() }} campos</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th style="width:60px">Orden</th>
                    <th>Etiqueta</th>
                    <th>Nombre interno</th>
                    <th>Tipo</th>
                    <th>Origen</th>
                    <th style="width:150px">Estatus</th>
                    <th style="width:170px">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($seccion->fields as $campo)
                <tr class="fila-campo" data-buscar="{{ strtolower($campo->label . ' ' . $campo->name) }}">
                    <td class="text-muted">{{ $campo->order }}</td>
                    <td>
                        <strong>{{ $campo->label }}</strong>
                        @if($campo->help)<br><small class="text-muted">{{ $campo->help }}</small>@endif
                    </td>
                    <td><code>{{ $campo->name }}</code></td>
                    <td><span class="badge bg-light text-dark border">{{ $tipos[$campo->type] ?? $campo->type }}</span>
                    </td>
                    <td>
                        @if($campo->isCustom())
                        <span class="badge bg-info text-dark"><i
                                class="fas fa-wand-magic-sparkles me-1"></i>Personalizado</span>
                        @else
                        <span class="badge bg-secondary">Columna</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-{{ $campo->is_active ? 'success' : 'danger' }}">
                            {{ $campo->is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary btn-editar" data-campo='@json($campo)'
                                title="Editar"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-outline-{{ $campo->is_active ? 'danger' : 'success' }} btn-toggle"
                                data-id="{{ $campo->id }}" title="Cambiar estatus">
                                <i class="fas fa-{{ $campo->is_active ? 'ban' : 'check' }}"></i>
                            </button>
                            <button class="btn btn-outline-danger btn-eliminar" data-id="{{ $campo->id }}"
                                data-label="{{ $campo->label }}" data-en-uso="{{ isset($enUso[$campo->name]) ? 1 : 0 }}"
                                title="Eliminar"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted">Sin campos en esta sección</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@empty
<div class="alert alert-warning">El catálogo está vacío. Ejecuta el seeder o crea un campo nuevo.</div>
@endforelse

<!-- Modal crear/editar -->
<div class="modal fade" id="campoModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="formCampo">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Nuevo Campo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Sección *</label>
                        <select name="section_id" class="form-select" required>
                            @foreach($secciones as $s)
                            <option value="{{ $s->id }}">{{ $s->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Etiqueta (nombre visible) *</label>
                        <input type="text" name="label" class="form-control" required maxlength="100"
                            placeholder="Ej. Voltaje de Entrada">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre interno *</label>
                        <input type="text" name="name" class="form-control" required maxlength="60"
                            pattern="[a-z][a-zA-Z0-9_]*" placeholder="Ej. voltajeEntrada">
                        <div class="form-text">Sin espacios ni acentos. Se usa como clave del dato.</div>
                        <div class="form-text text-warning" id="avisoColumna" style="display:none">
                            <i class="fas fa-lock me-1"></i>Este campo apunta a una columna real de tblEquipo: su nombre
                            no puede cambiar.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de dato *</label>
                        <select name="type" class="form-select" required id="selectTipo">
                            @foreach($tipos as $clave => $texto)
                            <option value="{{ $clave }}">{{ $texto }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3" id="bloqueOpciones" style="display:none">
                        <label class="form-label">Opciones (separadas por coma)</label>
                        <input type="text" name="opciones" class="form-control"
                            placeholder="Opción 1, Opción 2, Opción 3">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Ayuda / descripción</label>
                        <input type="text" name="help" class="form-control" maxlength="255"
                            placeholder="Texto que se muestra junto al campo">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('styles')
<style>
    .seccion-header {
        background: var(--surface-strong, #f8fafc);
        border: 1px solid var(--border);
        border-radius: var(--radius-md, 8px);
        padding: 12px 18px;
        margin-bottom: 12px;
    }

</style>
@endsection

@section('scripts')
<script>
    const RUTA_BASE = '{{ route("inventario.campos.index") }}';

    function nuevoCampo() {
        const form = document.getElementById('formCampo');
        form.reset();
        form.action = RUTA_BASE;
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('modalTitle').textContent = 'Nuevo Campo';
        document.getElementById('avisoColumna').style.display = 'none';
        form.name.disabled = false;
        form.name.required = true;
        actualizarBloqueOpciones();
    }

    document.querySelectorAll('.btn-editar').forEach(btn => {
        btn.addEventListener('click', function () {
            const c = JSON.parse(this.dataset.campo);
            const form = document.getElementById('formCampo');
            form.reset();
            form.action = RUTA_BASE + '/' + c.id;
            document.getElementById('formMethod').value = 'PUT';
            document.getElementById('modalTitle').textContent = 'Editar Campo';

            form.section_id.value = c.section_id;
            form.label.value = c.label;
            form.name.value = c.name;
            form.type.value = c.type;
            form.help.value = c.help || '';
            form.opciones.value = (c.validation_rules && c.validation_rules.opciones) ?
                c.validation_rules.opciones.join(', ') : '';

            // Los campos de columna real no pueden renombrarse
            const esColumna = c.source === 'column';
            document.getElementById('avisoColumna').style.display = esColumna ? 'block' : 'none';
            form.name.disabled = esColumna;
            form.name.required = !esColumna;

            actualizarBloqueOpciones();
            new bootstrap.Modal(document.getElementById('campoModal')).show();
        });
    });

    function actualizarBloqueOpciones() {
        const tipo = document.getElementById('selectTipo').value;
        document.getElementById('bloqueOpciones').style.display =
            (tipo === 'select' || tipo === 'tags') ? 'block' : 'none';
    }
    document.getElementById('selectTipo').addEventListener('change', actualizarBloqueOpciones);

    document.querySelectorAll('.btn-toggle').forEach(btn => {
        btn.addEventListener('click', function () {
            if (!confirm('¿Cambiar estatus del campo?')) return;
            fetch(RUTA_BASE + '/toggle/' + this.dataset.id, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }).then(() => location.reload());
        });
    });

    document.querySelectorAll('.btn-eliminar').forEach(btn => {
        btn.addEventListener('click', function () {
            if (this.dataset.enUso === '1') {
                alert('Este campo está asignado a uno o más tipos de equipo. Desactívelo en su lugar.');
                return;
            }
            if (!confirm('¿Eliminar el campo "' + this.dataset.label + '" del catálogo?')) return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = RUTA_BASE + '/' + this.dataset.id;
            form.innerHTML = '@csrf<input type="hidden" name="_method" value="DELETE">';
            document.body.appendChild(form);
            form.submit();
        });
    });

    document.getElementById('buscador').addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        document.querySelectorAll('.fila-campo').forEach(fila => {
            fila.style.display = fila.dataset.buscar.includes(q) ? '' : 'none';
        });
    });

</script>
@endsection
