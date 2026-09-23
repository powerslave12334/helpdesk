@extends('layouts.app')
@section('title', 'SubMódulos Sistemas - HelpDesk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-puzzle-piece me-2"></i>SubMódulos Sistemas</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal"><i class="fas fa-plus me-1"></i>Nuevo SubMódulo</button>
</div>

<div class="card">
    <div class="card-body">
        <div data-table>
            <div class="table-toolbar">
                <div class="table-toolbar-left">
                    <div class="table-search">
                        <i class="fas fa-search"></i>
                        <input type="text" data-table-search placeholder="Buscar por nombre...">
                    </div>
                    <select class="table-filter" data-table-filter="estatus">
                        <option value="">Todos los estatus</option>
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>ID</th><th>Nombre</th><th>Módulo Sistema</th><th>Estatus</th><th>Acciones</th></tr></thead>
                    <tbody data-table-body>
                        @forelse($submodulosSistemas as $s)
                        <tr data-row>
                            <td data-field="id">{{ $s->id_SubModulosSistemas }}</td>
                            <td data-field="nombre">{{ $s->nombre }}</td>
                            <td data-field="modulo"><span class="badge bg-secondary">{{ $s->moduloSistema->nombre ?? 'N/A' }}</span></td>
                            <td data-field="estatus"><span class="badge bg-{{ $s->enabled ? 'success' : 'danger' }}">{{ $s->enabled ? 'Activo' : 'Inactivo' }}</span></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary btn-edit" data-id="{{ $s->id_SubModulosSistemas }}"
                                        data-nombre="{{ $s->nombre }}" data-modulo="{{ $s->id_ModulosSistemas }}"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-outline-{{ $s->enabled ? 'danger' : 'success' }} btn-toggle" data-id="{{ $s->id_SubModulosSistemas }}"><i class="fas fa-{{ $s->enabled ? 'ban' : 'check' }}"></i></button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted">No hay submódulos</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="table-footer">
                <span class="table-info" data-table-info></span>
                <div class="pagination" data-table-pagination></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="formSubModulo">@csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Nuevo SubMódulo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Nombre *</label><input type="text" name="nombre" class="form-control" required maxlength="150"></div>
                    <div class="mb-3">
                        <label class="form-label">Módulo Sistema *</label>
                        <select name="id_ModulosSistemas" class="form-select" required>
                            @foreach($modulosSistemas as $m)
                            <option value="{{ $m->id_ModulosSistemas }}">{{ $m->nombre }}</option>
                            @endforeach
                        </select>
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
        const form = document.getElementById('formSubModulo');
        form.action = '/ticketconfig/submodulos-sistemas/' + this.dataset.id;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('modalTitle').textContent = 'Editar SubMódulo';
        form.nombre.value = this.dataset.nombre;
        form.id_ModulosSistemas.value = this.dataset.modulo;
        new bootstrap.Modal(document.getElementById('createModal')).show();
    });
});
document.querySelectorAll('.btn-toggle').forEach(btn => {
    btn.addEventListener('click', function() {
        if (confirm('¿Cambiar estatus?')) {
            fetch(`/ticketconfig/submodulos-sistemas/toggle/${this.dataset.id}`, {
                method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            }).then(() => location.reload());
        }
    });
});
</script>
@endsection
