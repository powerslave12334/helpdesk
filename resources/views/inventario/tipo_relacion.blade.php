@extends('layouts.app')
@section('title', 'Tipos de Relación - HelpDesk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-project-diagram me-2"></i>Tipos de Relación</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal"><i class="fas fa-plus me-1"></i>Nuevo Tipo</button>
</div>

<div class="card">
    <div class="card-body">
        <div data-table>
            <div class="table-toolbar">
                <div class="table-toolbar-left">
                    <div class="table-search">
                        <i class="fas fa-search"></i>
                        <input type="text" data-table-search placeholder="Buscar por descripción...">
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
                    <thead><tr><th>ID</th><th>Descripción</th><th>Estatus</th><th>Acciones</th></tr></thead>
                    <tbody data-table-body>
                        @forelse($tipos as $t)
                        <tr data-row>
                            <td data-field="id">{{ $t->id_TipoRelacionEquipo }}</td>
                            <td data-field="descripcion">{{ $t->descripcion }}</td>
                            <td data-field="estatus"><span class="badge bg-{{ $t->activo ? 'success' : 'danger' }}">{{ $t->activo ? 'Activo' : 'Inactivo' }}</span></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary btn-edit" data-id="{{ $t->id_TipoRelacionEquipo }}" data-desc="{{ $t->descripcion }}"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-outline-{{ $t->activo ? 'danger' : 'success' }} btn-toggle" data-id="{{ $t->id_TipoRelacionEquipo }}"><i class="fas fa-{{ $t->activo ? 'ban' : 'check' }}"></i></button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted">No hay tipos de relación</td></tr>
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
        <form method="POST" id="formTipo">@csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Nuevo Tipo de Relación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Descripción *</label><input type="text" name="descripcion" class="form-control" required></div>
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
        const form = document.getElementById('formTipo');
        form.action = '/inventario/tipo-relacion/' + this.dataset.id;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('modalTitle').textContent = 'Editar Tipo de Relación';
        form.descripcion.value = this.dataset.desc;
        new bootstrap.Modal(document.getElementById('createModal')).show();
    });
});
document.querySelectorAll('.btn-toggle').forEach(btn => {
    btn.addEventListener('click', function() {
        if (confirm('¿Cambiar estatus?')) {
            fetch(`/inventario/tipo-relacion/toggle/${this.dataset.id}`, {
                method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            }).then(() => location.reload());
        }
    });
});
</script>
@endsection
