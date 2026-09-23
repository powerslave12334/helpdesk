@extends('layouts.app')
@section('title', 'Tipos de Desarrollo - HelpDesk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-code me-2"></i>Tipos de Desarrollo</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal"><i class="fas fa-plus me-1"></i>Nuevo Tipo</button>
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
                    <thead><tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Estatus</th><th>Acciones</th></tr></thead>
                    <tbody data-table-body>
                        @forelse($tiposDesarrollo as $td)
                        <tr data-row>
                            <td data-field="id">{{ $td->id_TipoDesarrollo }}</td>
                            <td data-field="nombre">{{ $td->nombre }}</td>
                            <td data-field="descripcion">{{ $td->descripcion ?? 'N/A' }}</td>
                            <td data-field="estatus"><span class="badge bg-{{ $td->enabled ? 'success' : 'danger' }}">{{ $td->enabled ? 'Activo' : 'Inactivo' }}</span></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary btn-edit" data-id="{{ $td->id_TipoDesarrollo }}"
                                        data-nombre="{{ $td->nombre }}" data-descripcion="{{ $td->descripcion ?? '' }}"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-outline-{{ $td->enabled ? 'danger' : 'success' }} btn-toggle" data-id="{{ $td->id_TipoDesarrollo }}"><i class="fas fa-{{ $td->enabled ? 'ban' : 'check' }}"></i></button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted">No hay tipos de desarrollo</td></tr>
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
        <form method="POST" id="formTipoDesarrollo">@csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Nuevo Tipo de Desarrollo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Nombre *</label><input type="text" name="nombre" class="form-control" required maxlength="100"></div>
                    <div class="mb-3"><label class="form-label">Descripción</label><textarea name="descripcion" class="form-control" maxlength="500" rows="3"></textarea></div>
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
        const form = document.getElementById('formTipoDesarrollo');
        form.action = '/ticketconfig/tipos-desarrollo/' + this.dataset.id;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('modalTitle').textContent = 'Editar Tipo de Desarrollo';
        form.nombre.value = this.dataset.nombre;
        form.descripcion.value = this.dataset.descripcion;
        new bootstrap.Modal(document.getElementById('createModal')).show();
    });
});
document.querySelectorAll('.btn-toggle').forEach(btn => {
    btn.addEventListener('click', function() {
        if (confirm('¿Cambiar estatus?')) {
            fetch(`/ticketconfig/tipos-desarrollo/toggle/${this.dataset.id}`, {
                method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            }).then(() => location.reload());
        }
    });
});
</script>
@endsection
