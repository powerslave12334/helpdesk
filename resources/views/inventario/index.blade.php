@extends('layouts.app')
@section('title', 'Inventario - HelpDesk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-boxes-stacked me-2"></i>Inventario de Equipos</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="fas fa-plus me-1"></i>Nuevo Equipo
    </button>
</div>

<div class="card">
    <div class="card-body">
        <input type="text" class="form-control mb-3" id="searchInput" placeholder="Buscar por nombre, marca, modelo o serie...">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Tipo</th>
                        <th>Serie</th>
                        <th>Estatus</th>
                        <th>Ubicación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($equipos as $eq)
                    <tr>
                        <td>{{ $eq->nombreEquipo ?? 'N/A' }}</td>
                        <td>{{ $eq->marca }}</td>
                        <td>{{ $eq->modelo }}</td>
                        <td>{{ $eq->tipoEquipo->descripcion ?? 'N/A' }}</td>
                        <td><small>{{ $eq->numeroSerie ?? 'N/A' }}</small></td>
                        <td><span class="badge bg-{{ $eq->estatus->id_Estatus == 1 ? 'success' : 'secondary' }}">{{ $eq->estatus->descripcion ?? 'N/A' }}</span></td>
                        <td>{{ $eq->ubicacion ?? 'N/A' }}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary btn-edit" data-id="{{ $eq->id_Equipo }}"
                                    data-marca="{{ $eq->marca }}" data-modelo="{{ $eq->modelo }}"
                                    data-tipo="{{ $eq->id_TipoEquipo }}" data-estatus="{{ $eq->id_Estatus }}"
                                    data-serie="{{ $eq->numeroSerie }}" data-nombre="{{ $eq->nombreEquipo }}"
                                    data-ubicacion="{{ $eq->ubicacion }}" data-observaciones="{{ $eq->observaciones }}"
                                    data-oficina="{{ $eq->id_Oficinas }}" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-{{ $eq->activo ? 'danger' : 'success' }} btn-toggle" data-id="{{ $eq->id_Equipo }}" title="{{ $eq->activo ? 'Desactivar' : 'Activar' }}">
                                    <i class="fas fa-{{ $eq->activo ? 'ban' : 'check' }}"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted">No hay equipos registrados</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Crear/Editar Equipo -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="formEquipo">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle"><i class="fas fa-plus me-2"></i>Nuevo Equipo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Marca *</label>
                            <input type="text" name="marca" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Modelo *</label>
                            <input type="text" name="modelo" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nombre Equipo</label>
                            <input type="text" name="nombreEquipo" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tipo *</label>
                            <select name="id_TipoEquipo" id="selTipoEquipo" class="form-select" required>
                                @foreach($tiposEquipo as $t)
                                <option value="{{ $t->id_TipoEquipo }}">{{ $t->descripcion }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Estatus *</label>
                            <select name="id_Estatus" class="form-select" required>
                                @foreach($estatus as $e)
                                <option value="{{ $e->id_Estatus }}">{{ $e->descripcion }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Oficina</label>
                            <select name="id_Oficinas" class="form-select">
                                <option value="">Sin oficina</option>
                                @foreach($oficinas as $o)
                                <option value="{{ $o->id_Oficinas }}">{{ $o->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Número de Serie</label>
                            <input type="text" name="numeroSerie" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ubicación</label>
                            <input type="text" name="ubicacion" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="2"></textarea>
                    </div>
                    <div id="camposDinamicos"></div>
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

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('searchInput')?.addEventListener('input', function() {
        const term = this.value.toLowerCase();
        document.querySelectorAll('tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
    });

    // Load dynamic fields when type changes
    document.getElementById('selTipoEquipo')?.addEventListener('change', function() {
        const container = document.getElementById('camposDinamicos');
        container.innerHTML = '<p class="text-muted">Cargando campos...</p>';
        fetch(`/inventario/campos/${this.value}`)
            .then(r => r.json())
            .then(data => {
                container.innerHTML = '';
                data.forEach(c => {
                    container.innerHTML += `
                        <div class="mb-3">
                            <label class="form-label">${c.campo?.nombre || 'Campo'} ${c.requerido ? '*' : ''}</label>
                            <input type="text" name="campos_dinamicos[${c.id_EquipoCampo}]" class="form-control" ${c.requerido ? 'required' : ''}>
                        </div>`;
                });
            });
    });

    // Edit
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const form = document.getElementById('formEquipo');
            form.action = '/inventario/' + this.dataset.id;
            document.getElementById('formMethod').value = 'PUT';
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editar Equipo';
            form.marca.value = this.dataset.marca;
            form.modelo.value = this.dataset.modelo;
            form.nombreEquipo.value = this.dataset.nombre || '';
            form.id_TipoEquipo.value = this.dataset.tipo;
            form.id_Estatus.value = this.dataset.estatus;
            form.numeroSerie.value = this.dataset.serie || '';
            form.ubicacion.value = this.dataset.ubicacion || '';
            form.observaciones.value = this.dataset.observaciones || '';
            form.id_Oficinas.value = this.dataset.oficina || '';
            document.getElementById('selTipoEquipo').dispatchEvent(new Event('change'));
            new bootstrap.Modal(document.getElementById('createModal')).show();
        });
    });

    // Toggle
    document.querySelectorAll('.btn-toggle').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('¿Cambiar estatus de este equipo?')) {
                fetch(`/inventario/toggle/${this.dataset.id}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                }).then(() => location.reload());
            }
        });
    });
});
</script>
@endsection
