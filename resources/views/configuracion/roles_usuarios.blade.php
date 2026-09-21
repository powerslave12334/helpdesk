@extends('layouts.app')
@section('title', 'Asignación de Roles - HelpDesk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-user-tag me-2"></i>Asignación de Roles a Usuarios</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal"><i class="fas fa-plus me-1"></i>Nueva Asignación</button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Usuario</th><th>Rol</th><th>Fecha Alta</th><th>Estatus</th><th>Acciones</th></tr></thead>
                <tbody>
                    @forelse($asignaciones as $a)
                    <tr>
                        <td>{{ $a->usuario->nombre ?? 'N/A' }} {{ $a->usuario->app ?? '' }}</td>
                        <td><span class="badge bg-primary">{{ $a->rol->nombre ?? 'N/A' }}</span></td>
                        <td>{{ $a->fechaAlta?->format('d/m/Y') ?? 'N/A' }}</td>
                        <td><span class="badge bg-{{ $a->activo ? 'success' : 'danger' }}">{{ $a->activo ? 'Activo' : 'Inactivo' }}</span></td>
                        <td>
                            <button class="btn btn-outline-{{ $a->activo ? 'danger' : 'success' }} btn-sm btn-toggle" data-id="{{ $a->id_RolesUsuario }}">
                                <i class="fas fa-{{ $a->activo ? 'ban' : 'check' }}"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted">No hay asignaciones</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('configuracion.rolesUsuarios.store') }}">@csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Asignar Rol</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Usuario *</label>
                        <select name="id_Usuario" class="form-select" required>
                            @foreach($usuarios as $u)
                            <option value="{{ $u->id_Usuario }}">{{ $u->nombre }} {{ $u->app }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rol *</label>
                        <select name="id_Rol" class="form-select" required>
                            @foreach($roles as $r)
                            <option value="{{ $r->id_Rol }}">{{ $r->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Asignar</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.querySelectorAll('.btn-toggle').forEach(btn => {
    btn.addEventListener('click', function() {
        if (confirm('¿Cambiar estatus?')) {
            fetch(`/configuracion/roles-usuarios/toggle/${this.dataset.id}`, {
                method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            }).then(() => location.reload());
        }
    });
});
</script>
@endsection
