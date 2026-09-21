@extends('layouts.app')
@section('title', 'Oficinas - HelpDesk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-building me-2"></i>Oficinas</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal"><i class="fas fa-plus me-1"></i>Nueva Oficina</button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Estatus</th><th>Acciones</th></tr></thead>
                <tbody>
                    @forelse($oficinas as $o)
                    <tr>
                        <td>{{ $o->id_Oficinas }}</td>
                        <td><span class="badge bg-secondary">{{ $o->nombre }}</span></td>
                        <td>{{ $o->descripcion ?? 'N/A' }}</td>
                        <td><span class="badge bg-{{ $o->activo ? 'success' : 'danger' }}">{{ $o->activo ? 'Activa' : 'Inactiva' }}</span></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary btn-edit" data-id="{{ $o->id_Oficinas }}" data-nombre="{{ $o->nombre }}" data-desc="{{ $o->descripcion ?? '' }}"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-outline-{{ $o->activo ? 'danger' : 'success' }} btn-toggle" data-id="{{ $o->id_Oficinas }}"><i class="fas fa-{{ $o->activo ? 'ban' : 'check' }}"></i></button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted">No hay oficinas</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="formOficina">@csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Nueva Oficina</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Nombre (máx. 5 caracteres) *</label><input type="text" name="nombre" class="form-control" required maxlength="5" style="text-transform:uppercase"></div>
                    <div class="mb-3"><label class="form-label">Descripción</label><input type="text" name="descripcion" class="form-control" maxlength="250"></div>
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
        const form = document.getElementById('formOficina');
        form.action = '/configuracion/oficinas/' + this.dataset.id;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('modalTitle').textContent = 'Editar Oficina';
        form.nombre.value = this.dataset.nombre;
        form.descripcion.value = this.dataset.desc;
        new bootstrap.Modal(document.getElementById('createModal')).show();
    });
});
document.querySelectorAll('.btn-toggle').forEach(btn => {
    btn.addEventListener('click', function() {
        if (confirm('¿Cambiar estatus?')) {
            fetch(`/configuracion/oficinas/toggle/${this.dataset.id}`, {
                method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            }).then(() => location.reload());
        }
    });
});
</script>
@endsection
