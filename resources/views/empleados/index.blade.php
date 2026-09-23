@extends('layouts.app')
@section('title', 'Empleados - HelpDesk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-users me-2"></i>Empleados</h4>
    @if($esAdmin)
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="fas fa-plus me-1"></i>Nuevo Empleado
    </button>
    @endif
</div>

<div class="card">
    <div class="card-body">
        <div data-table>
            <div class="table-toolbar">
                <div class="table-toolbar-left">
                    <div class="table-search">
                        <i class="fas fa-search"></i>
                        <input type="text" data-table-search placeholder="Buscar por nombre, correo, departamento...">
                    </div>
                    <select class="table-filter" data-table-filter="tecnico">
                        <option value="">Todos</option>
                        <option value="sí">Técnicos</option>
                        <option value="no">No técnicos</option>
                    </select>
                    <select class="table-filter" data-table-filter="estatus">
                        <option value="">Todos los estatus</option>
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Departamento</th>
                            <th>Área</th>
                            <th>Tipo</th>
                            <th>Técnico</th>
                            <th>Usuario</th>
                            <th>Estatus</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody data-table-body>
                        @forelse($usuarios as $u)
                        <tr data-row>
                            <td data-field="nombre">{{ $u->nombre }} {{ $u->app }} {{ $u->apm }}</td>
                            <td data-field="correo">{{ $u->correo }}</td>
                            <td data-field="departamento">{{ $u->departamento->nombre ?? 'N/A' }}</td>
                            <td data-field="area">{{ $u->area->nombre ?? 'N/A' }}</td>
                            <td data-field="tipo">{{ $u->tipoEmpleado->nombre ?? 'N/A' }}</td>
                            <td data-field="tecnico"><span class="badge bg-{{ $u->esTecnico ? 'success' : 'secondary' }}">{{ $u->esTecnico ? 'Sí' : 'No' }}</span></td>
                            <td data-field="usuario">{{ $u->login->usuario ?? 'Sin acceso' }}</td>
                            <td data-field="estatus"><span class="badge bg-{{ $u->activo ? 'success' : 'danger' }}">{{ $u->activo ? 'Activo' : 'Inactivo' }}</span></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary btn-edit" data-id="{{ $u->id_Usuario }}"
                                        data-nombre="{{ $u->nombre }}" data-app="{{ $u->app }}" data-apm="{{ $u->apm }}"
                                        data-correo="{{ $u->correo }}" data-telefono="{{ $u->telefono }}"
                                        data-depto="{{ $u->id_Depto }}" data-area="{{ $u->id_Area }}"
                                        data-oficina="{{ $u->id_Oficinas }}" data-tipo="{{ $u->id_TipoEmpleado }}"
                                        data-tecnico="{{ $u->esTecnico }}" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-warning btn-access" data-id="{{ $u->id_Usuario }}"
                                        data-usuario="{{ $u->login->usuario ?? '' }}" title="Acceso">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    <button class="btn btn-outline-info btn-reset" data-id="{{ $u->id_Usuario }}" title="Restaurar contraseña">
                                        <i class="fas fa-lock"></i>
                                    </button>
                                    <button class="btn btn-outline-{{ $u->activo ? 'danger' : 'success' }} btn-toggle" data-id="{{ $u->id_Usuario }}" title="{{ $u->activo ? 'Desactivar' : 'Activar' }}">
                                        <i class="fas fa-{{ $u->activo ? 'ban' : 'check' }}"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center text-muted">No hay empleados</td></tr>
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

<!-- Modal Crear/Editar -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="formEmpleado">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle"><i class="fas fa-user-plus me-2"></i>Nuevo Empleado</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Apellido Paterno *</label>
                            <input type="text" name="app" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Apellido Materno</label>
                            <input type="text" name="apm" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Correo *</label>
                            <input type="email" name="correo" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Departamento *</label>
                            <select name="id_Depto" class="form-select" required>
                                @foreach($deptos as $d)
                                <option value="{{ $d->id_Depto }}">{{ $d->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Área *</label>
                            <select name="id_Area" class="form-select" required>
                                @foreach($areas as $a)
                                <option value="{{ $a->id_Area }}">{{ $a->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Oficina</label>
                            <select name="id_Oficinas" class="form-select">
                                <option value="">Sin oficina</option>
                                @foreach($oficinas as $o)
                                <option value="{{ $o->id_Oficinas }}">{{ $o->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tipo Empleado *</label>
                            <select name="id_TipoEmpleado" class="form-select" required>
                                @foreach($tiposEmpleado as $t)
                                <option value="{{ $t->id_TipoEmpleado }}">{{ $t->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="esTecnico" id="chkTecnico" value="1">
                                <label class="form-check-label" for="chkTecnico">Es Técnico</label>
                            </div>
                        </div>
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

<!-- Modal Acceso -->
<div class="modal fade" id="accessModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('empleados.acceso') }}">
            @csrf
            <input type="hidden" name="id_Usuario" id="accessUserId">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-key me-2"></i>Gestionar Acceso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Usuario *</label>
                        <input type="text" name="usuario" id="accessUsuario" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña * (mínimo 6 caracteres)</label>
                        <input type="password" name="pass" class="form-control" minlength="6" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Guardar Acceso</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const form = document.getElementById('formEmpleado');
            form.action = '/empleados/' + this.dataset.id;
            document.getElementById('formMethod').value = 'PUT';
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit me-2"></i>Editar Empleado';
            form.nombre.value = this.dataset.nombre;
            form.app.value = this.dataset.app;
            form.apm.value = this.dataset.apm || '';
            form.correo.value = this.dataset.correo;
            form.telefono.value = this.dataset.telefono || '';
            form.id_Depto.value = this.dataset.depto;
            form.id_Area.value = this.dataset.area;
            form.id_Oficinas.value = this.dataset.oficina || '';
            form.id_TipoEmpleado.value = this.dataset.tipo;
            document.getElementById('chkTecnico').checked = this.dataset.tecnico == 1;
            new bootstrap.Modal(document.getElementById('createModal')).show();
        });
    });

    // Access
    document.querySelectorAll('.btn-access').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('accessUserId').value = this.dataset.id;
            document.getElementById('accessUsuario').value = this.dataset.usuario;
            new bootstrap.Modal(document.getElementById('accessModal')).show();
        });
    });

    // Reset password
    document.querySelectorAll('.btn-reset').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('¿Restaurar contraseña? Se generará una contraseña temporal.')) {
                fetch(`/empleados/restaurar-password/${this.dataset.id}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                }).then(() => location.reload());
            }
        });
    });

    // Toggle
    document.querySelectorAll('.btn-toggle').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('¿Cambiar estatus de este empleado?')) {
                fetch(`/empleados/toggle/${this.dataset.id}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                }).then(() => location.reload());
            }
        });
    });
});
</script>
@endsection
