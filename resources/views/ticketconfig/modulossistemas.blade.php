@extends('layouts.app')
@section('title', 'Módulos Sistemas - HelpDesk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-puzzle-piece me-2"></i>Módulos Sistemas</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal"><i class="fas fa-plus me-1"></i>Nuevo Módulo</button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>ID</th><th>Nombre</th><th>Sistema</th><th>Estatus</th><th>Acciones</th></tr></thead>
                <tbody>
                    @forelse($modulosSistemas as $m)
                    <tr>
                        <td>{{ $m->id_ModulosSistemas }}</td>
                        <td>{{ $m->nombre }}</td>
                        <td><span class="badge bg-secondary">{{ $m->sistema->nombre ?? 'N/A' }}</span></td>
                        <td><span class="badge bg-{{ $m->enabled ? 'success' : 'danger' }}">{{ $m->enabled ? 'Activo' : 'Inactivo' }}</span></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary btn-edit" data-id="{{ $m->id_ModulosSistemas }}"
                                    data-nombre="{{ $m->nombre }}" data-sistema="{{ $m->id_sistemas }}"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-outline-{{ $m->enabled ? 'danger' : 'success' }} btn-toggle" data-id="{{ $m->id_ModulosSistemas }}"><i class="fas fa-{{ $m->enabled ? 'ban' : 'check' }}"></i></button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted">No hay módulos</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="formModulo">@csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Nuevo Módulo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Nombre *</label><input type="text" name="nombre" class="form-control" required maxlength="150"></div>
                    <div class="mb-3">
                        <label class="form-label">Sistema *</label>
                        <select name="id_sistemas" class="form-select" required>
                            @foreach($sistemas as $s)
                            <option value="{{ $s->id_sistemas }}">{{ $s->nombre }}</option>
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
        const form = document.getElementById('formModulo');
        form.action = '/ticketconfig/modulos-sistemas/' + this.dataset.id;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('modalTitle').textContent = 'Editar Módulo';
        form.nombre.value = this.dataset.nombre;
        form.id_sistemas.value = this.dataset.sistema;
        new bootstrap.Modal(document.getElementById('createModal')).show();
    });
});
document.querySelectorAll('.btn-toggle').forEach(btn => {
    btn.addEventListener('click', function() {
        if (confirm('¿Cambiar estatus?')) {
            fetch(`/ticketconfig/modulos-sistemas/toggle/${this.dataset.id}`, {
                method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            }).then(() => location.reload());
        }
    });
});
</script>
@endsection
