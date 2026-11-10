@extends('layouts.app')
@section('title', 'Configuración del Sistema - HelpDesk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-cog me-2"></i>Configuración del Sistema</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal"><i class="fas fa-plus me-1"></i>Nueva Configuración</button>
</div>

@php($infoActiva = $infoConexiones[$conexionActiva])
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h6 class="mb-1"><i class="fas fa-database me-2"></i>Base de datos activa</h6>
                <small class="text-muted">Conmuta entre la BD local de desarrollo y la BD productiva.</small>
            </div>
            <span class="badge {{ $conexionActiva === 'mysql' ? 'bg-success' : 'bg-danger' }}" style="font-size:13px;">
                {{ $infoActiva['etiqueta'] }} — {{ $infoActiva['host'] }} / {{ $infoActiva['database'] }}
            </span>
        </div>
        <hr class="my-3">
        <form method="POST" action="{{ route('configuracion.baseDatos.cambiar') }}" id="formBaseDatos" class="d-flex flex-wrap align-items-end gap-3">
            @csrf
            <div class="flex-grow-1" style="min-width:280px;">
                <label class="form-label small mb-1" for="selectConexion">Conexión destino</label>
                <select name="conexion" id="selectConexion" class="form-select form-select-sm">
                    @foreach($infoConexiones as $clave => $info)
                        <option value="{{ $clave }}" @selected($clave === $conexionActiva)>
                            {{ $info['etiqueta'] }} — {{ $info['host'] }}/{{ $info['database'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" formaction="{{ route('configuracion.baseDatos.probar') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-plug me-1"></i>Probar conexión
            </button>
            <button type="submit" formaction="{{ route('configuracion.baseDatos.cambiar') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-exchange-alt me-1"></i>Cambiar
            </button>
        </form>
        <script>
            document.getElementById('formBaseDatos').addEventListener('submit', function (e) {
                const destino = this.querySelector('[name=conexion]').value;
                const boton = e.submitter;
                const esCambio = boton && boton.formAction && boton.formAction.includes('cambiar');
                if (esCambio && destino === 'sqlsrv_productivo') {
                    e.preventDefault();
                    Swal.fire({
                        title: '¿Cambiar a la BD productiva?',
                        text: 'La aplicación operará sobre 192.168.11.20 (HELPDESK). Todas las escrituras irán a productivo.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, cambiar',
                        cancelButtonText: 'Cancelar'
                    }).then((r) => { if (r.isConfirmed) { this.submit(); } });
                }
            });
        </script>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div style="max-width: 640px;">
                <h6 class="mb-1"><i class="fas fa-envelope-open-text me-2"></i>Correos automáticos</h6>
                <small class="text-muted">
                    Correos que el propio sistema dispara sin intervención de nadie: tickets asignados o
                    reasignados al técnico y mantenimientos programados al dueño del equipo.
                    No afecta a los correos que solicita el usuario, como la recuperación de contraseña.
                </small>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge {{ $correosAutomaticos ? 'bg-success' : 'bg-secondary' }}" id="badgeCorreos" style="font-size:13px;">
                    {{ $correosAutomaticos ? 'Activados' : 'Desactivados' }}
                </span>
                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="switchCorreos"
                        style="width:2.6em; height:1.4em; cursor:pointer;" {{ $correosAutomaticos ? 'checked' : '' }}>
                    <label class="form-check-label visually-hidden" for="switchCorreos">Enviar correos automáticos</label>
                </div>
            </div>
        </div>
        <hr class="my-3">
        <div class="d-flex flex-wrap gap-4 small">
            <span class="text-muted">
                <i class="fas fa-server me-1"></i>SMTP:
                @if($smtp['completo'])
                    <span class="text-success">{{ $smtp['servidor'] }}:{{ $smtp['puerto'] }} → {{ $smtp['remitente'] }}</span>
                @else
                    <span class="text-danger">incompleto (falta {{ implode(', ', $smtp['faltantes']) }})</span>
                @endif
            </span>
            <span class="text-muted">
                <i class="fas fa-bell me-1"></i>Notificaciones in-app: <span class="text-success">siempre activas</span>
            </span>
        </div>
        <script>
            document.getElementById('switchCorreos').addEventListener('change', function () {
                const activar = this.checked;
                const sw = this;

                Swal.fire({
                    title: activar ? '¿Activar correos automáticos?' : '¿Desactivar correos automáticos?',
                    text: activar
                        ? 'El sistema volverá a enviar notificaciones por correo de tickets y mantenimientos.'
                        : 'El sistema dejará de enviar correos de tickets y mantenimientos. Las notificaciones in-app seguirán funcionando.',
                    icon: activar ? 'question' : 'warning',
                    showCancelButton: true,
                    confirmButtonText: activar ? 'Sí, activar' : 'Sí, desactivar',
                    cancelButtonText: 'Cancelar'
                }).then((r) => {
                    if (!r.isConfirmed) {
                        sw.checked = !activar;
                        return;
                    }

                    fetch('{{ route('configuracion.configGeneral.correosAutomaticos') }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                    })
                    .then((res) => {
                        if (!res.ok) throw new Error('No se pudo actualizar la configuración.');
                        location.reload();
                    })
                    .catch((err) => {
                        sw.checked = !activar;
                        Swal.fire('Error', err.message, 'error');
                    });
                });
            });
        </script>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div data-table>
            <div class="table-toolbar">
                <div class="table-toolbar-left">
                    <div class="table-search">
                        <i class="fas fa-search"></i>
                        <input type="text" data-table-search placeholder="Buscar por clave o descripción...">
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
                    <thead><tr><th>Clave</th><th>Valor</th><th>Descripción</th><th>Estatus</th><th>Acciones</th></tr></thead>
                    <tbody data-table-body>
                        @forelse($configuraciones as $c)
                        <tr data-row>
                            <td data-field="clave"><code>{{ $c->clave }}</code></td>
                            <td data-field="valor"><small>{{ Str::limit($c->valor, 50) }}</small></td>
                            <td data-field="descripcion">{{ $c->descripcion ?? 'N/A' }}</td>
                            <td data-field="estatus"><span class="badge bg-{{ $c->activo ? 'success' : 'danger' }}">{{ $c->activo ? 'Activo' : 'Inactivo' }}</span></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary btn-edit" data-id="{{ $c->id_ConfiguracionSistema }}"
                                        data-clave="{{ $c->clave }}" data-valor="{{ $c->valor }}" data-desc="{{ $c->descripcion ?? '' }}"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-outline-{{ $c->activo ? 'danger' : 'success' }} btn-toggle" data-id="{{ $c->id_ConfiguracionSistema }}"><i class="fas fa-{{ $c->activo ? 'ban' : 'check' }}"></i></button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted">No hay configuraciones</td></tr>
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
    <div class="modal-dialog modal-lg">
        <form method="POST" id="formConfig">@csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Nueva Configuración</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Clave *</label><input type="text" name="clave" class="form-control" required maxlength="100"></div>
                    <div class="mb-3"><label class="form-label">Valor *</label><textarea name="valor" class="form-control" rows="3" required></textarea></div>
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
        const form = document.getElementById('formConfig');
        form.action = '/configuracion/config-general/' + this.dataset.id;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('modalTitle').textContent = 'Editar Configuración';
        form.clave.value = this.dataset.clave;
        form.valor.value = this.dataset.valor;
        form.descripcion.value = this.dataset.desc;
        new bootstrap.Modal(document.getElementById('createModal')).show();
    });
});
document.querySelectorAll('.btn-toggle').forEach(btn => {
    btn.addEventListener('click', function() {
        if (confirm('¿Cambiar estatus?')) {
            fetch(`/configuracion/config-general/toggle/${this.dataset.id}`, {
                method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            }).then(() => location.reload());
        }
    });
});
</script>
@endsection
