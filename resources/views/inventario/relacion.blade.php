@extends('layouts.app')
@section('title', 'Relaciones de Equipo - HelpDesk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-link me-2"></i>Relaciones Padre-Hijo</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal"><i class="fas fa-plus me-1"></i>Nueva Relación</button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Equipo Padre</th><th>Equipo Hijo</th><th>Tipo Relación</th><th>Fecha Asignación</th><th>Fecha Baja</th><th>Estatus</th><th>Acciones</th></tr></thead>
                <tbody>
                    @forelse($relaciones as $r)
                    <tr>
                        <td>{{ $r->equipoPadre->nombreEquipo ?? $r->equipoPadre->marca ?? 'N/A' }}</td>
                        <td>{{ $r->equipoHijo->nombreEquipo ?? $r->equipoHijo->marca ?? 'N/A' }}</td>
                        <td><span class="badge bg-info">{{ $r->tipoRelacion->descripcion ?? 'N/A' }}</span></td>
                        <td>{{ $r->fechaAsignacion?->format('d/m/Y') ?? 'N/A' }}</td>
                        <td>{{ $r->fechaBaja?->format('d/m/Y') ?? 'N/A' }}</td>
                        <td><span class="badge bg-{{ $r->activo ? 'success' : 'danger' }}">{{ $r->activo ? 'Activa' : 'Inactiva' }}</span></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary btn-edit" data-id="{{ $r->id_EquipoRelacion }}"
                                    data-padre="{{ $r->id_EquipoPadre }}" data-hijo="{{ $r->id_EquipoHijo }}"
                                    data-tipo="{{ $r->id_TipoRelacionEquipo }}" data-fecha="{{ $r->fechaAsignacion?->format('Y-m-d') }}"
                                    data-obs="{{ $r->observaciones ?? '' }}"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-outline-{{ $r->activo ? 'danger' : 'success' }} btn-toggle" data-id="{{ $r->id_EquipoRelacion }}"><i class="fas fa-{{ $r->activo ? 'ban' : 'check' }}"></i></button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted">No hay relaciones</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="formRelacion">@csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Nueva Relación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Equipo Padre *</label>
                        <select name="id_EquipoPadre" class="form-select" required>
                            @foreach($equipos as $e)
                            <option value="{{ $e->id_Equipo }}">{{ $e->nombreEquipo ?? $e->marca }} - {{ $e->modelo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Equipo Hijo *</label>
                        <select name="id_EquipoHijo" class="form-select" required>
                            @foreach($equipos as $e)
                            <option value="{{ $e->id_Equipo }}">{{ $e->nombreEquipo ?? $e->marca }} - {{ $e->modelo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de Relación *</label>
                        <select name="id_TipoRelacionEquipo" class="form-select" required>
                            @foreach($tiposRelacion as $t)
                            <option value="{{ $t->id_TipoRelacionEquipo }}">{{ $t->descripcion }}</option>
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
@endsection

@section('scripts')
<script>
document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', function() {
        const form = document.getElementById('formRelacion');
        form.action = '/inventario/relacion/' + this.dataset.id;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('modalTitle').textContent = 'Editar Relación';
        form.id_EquipoPadre.value = this.dataset.padre;
        form.id_EquipoHijo.value = this.dataset.hijo;
        form.id_TipoRelacionEquipo.value = this.dataset.tipo;
        form.fechaAsignacion.value = this.dataset.fecha;
        form.observaciones.value = this.dataset.obs;
        new bootstrap.Modal(document.getElementById('createModal')).show();
    });
});
document.querySelectorAll('.btn-toggle').forEach(btn => {
    btn.addEventListener('click', function() {
        if (confirm('¿Cambiar estatus?')) {
            fetch(`/inventario/relacion/toggle/${this.dataset.id}`, {
                method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            }).then(() => location.reload());
        }
    });
});
</script>
@endsection
