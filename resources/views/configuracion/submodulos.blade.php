@extends('layouts.app')
@section('title', 'SubMódulos - HelpDesk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-puzzle-piece me-2"></i>SubMódulos</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal"><i class="fas fa-plus me-1"></i>Nuevo SubMódulo</button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>ID</th><th>Nombre</th><th>Módulo</th><th>Controller</th><th>Action</th><th>Orden</th><th>Estatus</th><th>Acciones</th></tr></thead>
                <tbody>
                    @forelse($submodulos as $s)
                    <tr>
                        <td>{{ $s->id_SubModulos }}</td>
                        <td>{{ $s->nombre }}</td>
                        <td><span class="badge bg-secondary">{{ $s->modulo->nombre ?? $s->id_Modulos }}</span></td>
                        <td><code>{{ $s->controller }}</code></td>
                        <td>{{ $s->action ?? 'N/A' }}</td>
                        <td>{{ $s->orden ?? 0 }}</td>
                        <td><span class="badge bg-{{ $s->activo ? 'success' : 'danger' }}">{{ $s->activo ? 'Activo' : 'Inactivo' }}</span></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary btn-edit" data-id="{{ $s->id_SubModulos }}"
                                    data-modulo="{{ $s->id_Modulos }}" data-nombre="{{ $s->nombre }}"
                                    data-controller="{{ $s->controller }}" data-action="{{ $s->action ?? '' }}"
                                    data-orden="{{ $s->orden ?? 0 }}"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-outline-{{ $s->activo ? 'danger' : 'success' }} btn-toggle" data-id="{{ $s->id_SubModulos }}"><i class="fas fa-{{ $s->activo ? 'ban' : 'check' }}"></i></button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted">No hay submódulos</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="formSub">@csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Nuevo SubMódulo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Módulo *</label>
                        <select name="id_Modulos" class="form-select" required>
                            @foreach($modulos as $m)
                            <option value="{{ $m->id_Modulos }}">{{ $m->nombre ?? 'Módulo ' . $m->id_Modulos }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Nombre *</label><input type="text" name="nombre" class="form-control" required maxlength="50"></div>
                    <div class="mb-3"><label class="form-label">Controller *</label><input type="text" name="controller" class="form-control" required maxlength="100"></div>
                    <div class="mb-3"><label class="form-label">Action</label><input type="text" name="action" class="form-control" maxlength="50"></div>
                    <div class="mb-3"><label class="form-label">Orden</label><input type="number" name="orden" class="form-control" value="0"></div>
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
        const form = document.getElementById('formSub');
        form.action = '/configuracion/submodulos/' + this.dataset.id;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('modalTitle').textContent = 'Editar SubMódulo';
        form.id_Modulos.value = this.dataset.modulo;
        form.nombre.value = this.dataset.nombre;
        form.controller.value = this.dataset.controller;
        form.action.value = this.dataset.action;
        form.orden.value = this.dataset.orden;
        new bootstrap.Modal(document.getElementById('createModal')).show();
    });
});
document.querySelectorAll('.btn-toggle').forEach(btn => {
    btn.addEventListener('click', function() {
        if (confirm('¿Cambiar estatus?')) {
            fetch(`/configuracion/submodulos/toggle/${this.dataset.id}`, {
                method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            }).then(() => location.reload());
        }
    });
});
</script>
@endsection
