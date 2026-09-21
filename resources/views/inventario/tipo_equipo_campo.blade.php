@extends('layouts.app')
@section('title', 'Tipo-Equipo-Campo - HelpDesk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-link me-2"></i>Relación Tipo ↔ Campo</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal"><i class="fas fa-plus me-1"></i>Nueva Relación</button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Tipo Equipo</th><th>Campo</th><th>Requerido</th><th>Estatus</th><th>Acciones</th></tr></thead>
                <tbody>
                    @forelse($relaciones as $r)
                    <tr>
                        <td>{{ $r->tipoEquipo->descripcion ?? 'N/A' }}</td>
                        <td>{{ $r->campo->nombre ?? 'N/A' }}</td>
                        <td><span class="badge bg-{{ $r->requerido ? 'warning' : 'secondary' }}">{{ $r->requerido ? 'Sí' : 'No' }}</span></td>
                        <td><span class="badge bg-{{ $r->activo ? 'success' : 'danger' }}">{{ $r->activo ? 'Activo' : 'Inactivo' }}</span></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary btn-edit" data-id="{{ $r->id_TipoEquipoCampo }}" data-tipo="{{ $r->id_TipoEquipo }}" data-campo="{{ $r->id_EquipoCampo }}" data-requerido="{{ $r->requerido ? 1 : 0 }}"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-outline-{{ $r->activo ? 'danger' : 'success' }} btn-toggle" data-id="{{ $r->id_TipoEquipoCampo }}"><i class="fas fa-{{ $r->activo ? 'ban' : 'check' }}"></i></button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted">No hay relaciones</td></tr>
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
                        <label class="form-label">Tipo de Equipo *</label>
                        <select name="id_TipoEquipo" class="form-select" required>
                            @foreach($tiposEquipo as $t)
                            <option value="{{ $t->id_TipoEquipo }}">{{ $t->descripcion }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Campo *</label>
                        <select name="id_EquipoCampo" class="form-select" required>
                            @foreach($campos as $c)
                            <option value="{{ $c->id_EquipoCampo }}">{{ $c->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="requerido" id="chkRequerido" value="1">
                        <label class="form-check-label" for="chkRequerido">Requerido</label>
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

@section('scripts')
<script>
document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', function() {
        const form = document.getElementById('formRelacion');
        form.action = '/inventario/tipo-equipo-campo/' + this.dataset.id;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('modalTitle').textContent = 'Editar Relación';
        form.id_TipoEquipo.value = this.dataset.tipo;
        form.id_EquipoCampo.value = this.dataset.campo;
        document.getElementById('chkRequerido').checked = this.dataset.requerido == 1;
        new bootstrap.Modal(document.getElementById('createModal')).show();
    });
});
document.querySelectorAll('.btn-toggle').forEach(btn => {
    btn.addEventListener('click', function() {
        if (confirm('¿Cambiar estatus?')) {
            fetch(`/inventario/tipo-equipo-campo/toggle/${this.dataset.id}`, {
                method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            }).then(() => location.reload());
        }
    });
});
</script>
@endsection
