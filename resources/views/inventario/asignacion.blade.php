@extends('layouts.app')
@section('title', 'Asignaciones - HelpDesk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-user-tag me-2"></i>Asignación de Equipos</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal"><i class="fas fa-plus me-1"></i>Nueva Asignación</button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>Equipo</th>
                        <th>Marca/Modelo</th>
                        <th>Usuario</th>
                        <th>Área</th>
                        <th>Fecha Asignación</th>
                        <th>Fecha Baja</th>
                        <th>Estatus</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($asignaciones as $a)
                    <tr>
                        <td><input type="checkbox" class="select-row" value="{{ $a->id_EquipoUsuario }}"></td>
                        <td>{{ $a->equipo->nombreEquipo ?? 'N/A' }}</td>
                        <td>{{ $a->equipo->marca }} {{ $a->equipo->modelo }}</td>
                        <td>{{ $a->usuario->nombre }} {{ $a->usuario->app }}</td>
                        <td>{{ $a->area->nombre ?? 'N/A' }}</td>
                        <td>{{ $a->fechaAsignacion?->format('d/m/Y') ?? 'N/A' }}</td>
                        <td>{{ $a->fechaBaja?->format('d/m/Y') ?? 'N/A' }}</td>
                        <td><span class="badge bg-{{ $a->activo ? 'success' : 'danger' }}">{{ $a->activo ? 'Activa' : 'Inactiva' }}</span></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary btn-edit" data-id="{{ $a->id_EquipoUsuario }}"
                                    data-equipo="{{ $a->id_Equipo }}" data-usuario="{{ $a->id_Usuario }}"
                                    data-area="{{ $a->id_Area }}" data-fecha="{{ $a->fechaAsignacion?->format('Y-m-d') }}"
                                    data-obs="{{ $a->observaciones ?? '' }}"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-outline-danger btn-desvincular" data-id="{{ $a->id_EquipoUsuario }}" title="Desvincular"><i class="fas fa-unlink"></i></button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted">No hay asignaciones</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            <button class="btn btn-danger btn-sm" id="btnDesvincularSeleccionados" disabled>
                <i class="fas fa-unlink me-1"></i>Desvincular Seleccionados
            </button>
        </div>
    </div>
</div>

<!-- Modal Crear/Editar Asignación -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="formAsignacion">@csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Nueva Asignación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Equipo *</label>
                        <select name="id_Equipo" class="form-select" required>
                            @foreach($equiposDisponibles as $e)
                            <option value="{{ $e->id_Equipo }}">{{ $e->nombreEquipo ?? $e->marca }} - {{ $e->modelo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Usuario *</label>
                        <select name="id_Usuario" class="form-select" required>
                            @foreach($usuarios as $u)
                            <option value="{{ $u->id_Usuario }}">{{ $u->nombre }} {{ $u->app }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Área</label>
                        <select name="id_Area" class="form-select">
                            <option value="">Sin área</option>
                            @foreach($areas as $a)
                            <option value="{{ $a->id_Area }}">{{ $a->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Fecha Asignación *</label><input type="date" name="fechaAsignacion" class="form-control" value="{{ date('Y-m-d') }}" required></div>
                    <div class="mb-3"><label class="form-label">Observaciones</label><textarea name="observaciones" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Desvincular -->
<div class="modal fade" id="desvincularModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('inventario.asignacion.desvincular') }}">@csrf
            <input type="hidden" name="id_EquipoUsuario" id="desvincularId">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-unlink me-2"></i>Desvincular Equipo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de desvincular este equipo del usuario?</p>
                    <div class="mb-3"><label class="form-label">Motivo (opcional)</label><textarea name="motivo" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Desvincular</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Desvincular Seleccionados -->
<div class="modal fade" id="desvincularSeleccionadosModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('inventario.asignacion.desvincularSeleccionados') }}">@csrf
            <input type="hidden" name="ids[]" id="selectedIds">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-unlink me-2"></i>Desvincular Seleccionados</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de desvincular los equipos seleccionados?</p>
                    <div class="mb-3"><label class="form-label">Motivo (opcional)</label><textarea name="motivo" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Desvincular Todos</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Edit
document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', function() {
        const form = document.getElementById('formAsignacion');
        form.action = '/inventario/asignacion/' + this.dataset.id;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('modalTitle').textContent = 'Editar Asignación';
        form.id_Equipo.value = this.dataset.equipo;
        form.id_Usuario.value = this.dataset.usuario;
        form.id_Area.value = this.dataset.area || '';
        form.fechaAsignacion.value = this.dataset.fecha;
        form.observaciones.value = this.dataset.obs;
        new bootstrap.Modal(document.getElementById('createModal')).show();
    });
});

// Desvincular individual
document.querySelectorAll('.btn-desvincular').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('desvincularId').value = this.dataset.id;
        new bootstrap.Modal(document.getElementById('desvincularModal')).show();
    });
});

// Select all
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.select-row').forEach(cb => cb.checked = this.checked);
    updateDesvincularBtn();
});

document.querySelectorAll('.select-row').forEach(cb => {
    cb.addEventListener('change', updateDesvincularBtn);
});

function updateDesvincularBtn() {
    const checked = document.querySelectorAll('.select-row:checked').length;
    document.getElementById('btnDesvincularSeleccionados').disabled = checked === 0;
}

// Desvincular seleccionados
document.getElementById('btnDesvincularSeleccionados')?.addEventListener('click', function() {
    const ids = [];
    document.querySelectorAll('.select-row:checked').forEach(cb => ids.push(cb.value));
    document.getElementById('selectedIds').value = ids.join(',');
    new bootstrap.Modal(document.getElementById('desvincularSeleccionadosModal')).show();
});
</script>
@endsection
