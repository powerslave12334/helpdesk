@extends('layouts.app')
@section('title', 'Instalaciones GPS - HelpDesk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-gps-measurement me-2"></i>Instalaciones GPS</h4> <button class="btn btn-primary"
        data-bs-toggle="modal" data-bs-target="#createModal"><i class="fas fa-plus me-1"></i>Nueva Instalación</button>
</div>

<div class="card">
    <div class="card-body">
        <div data-table>
            <div class="table-toolbar">
                <div class="table-toolbar-left">
                    <div class="table-search"> <i class="fas fa-search"></i> <input type="text" data-table-search
                            placeholder="Buscar por unidad o marca..."> </div> <select class="table-filter"
                        data-table-filter="estatus">
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
                            <th>ID</th>
                            <th>Unidad</th>
                            <th>Marca</th>
                            <th>Placas</th>
                            <th>Tipo de accion</th>
                            <th>Fecha de la accion</th>
                            <th>IMEI</th>
                            <th>Numero</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody data-table-body> @forelse($instalaciones as $i) <tr data-row>
                            <td data-field="id">{{ $i->id_InstalacionGPS }}</td>
                            <td class="fw-semibold" data-field="unidad">{{ $i->unidad }}</td>
                            <td data-field="marca">{{ $i->marca }}</td>
                            <td data-field="placas">{{ $i->placas }}</td>
                            <td data-field="Accion">
                                @if ($i->sim_cambiado == 1 && $i->reinstalacion == 0)
                                    <span class="badge bg-primary">Cambio de sim</span>
                                @elseif ($i->reinstalacion == 1)
                                    <span class="badge bg-warning text-dark">Reinstalacion</span>
                                @else
                                    <span class="text-muted">Sin Movimientos</span>
                                @endif
                            </td>
                            <td data-field="fechaAccion">
                                 @if ($i->sim_cambiado == 1 && $i->reinstalacion == 0)
                                    <span>{{\Carbon\Carbon::parse($i->fecha_cambio_sim)->format('d/m/y')}}</span>
                                @elseif ($i->reinstalacion == 1)
                                    <span>{{\Carbon\Carbon::parse($i->fecha_reinstalacion)->format('d/m/y')}}</span>
                                @else
                                    <span class="text-muted">Sin Fecha</span>
                                @endif
                            </td>
                            <td data-field="imei">{{$i->imei}}</td>
                            <td data-field="estatus">{{$i->numero_actual}}</td>
                            <td>
                                <div class="btn-group btn-group-sm"> <button class="btn btn-outline-primary btn-edit"
                                        data-id="{{ $i->id_InstalacionGPS }}" data-unidad="{{ $i->unidad }}"
                                        data-marca="{{ $i->marca }}"><i class="fas fa-edit"></i></button> <button
                                        class="btn btn-outline-{{ $i->activo ? 'danger' : 'success' }} btn-toggle"
                                        data-id="{{ $i->id_InstalacionGPS }}"><i
                                            class="fas fa-{{ $i->activo ? 'ban' : 'check' }}"></i></button> <button
                                        class="btn btn-outline-danger btn-delete"
                                        data-id="{{ $i->id_InstalacionGPS }}"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr> @empty <tr>
                            <td colspan="7" class="text-center text-muted">No hay instalaciones GPS registradas</td>
                        </tr> @endforelse </tbody>
                </table>
                <div class="table-footer"> <span class="table-info" data-table-info></span>
                    <div class="pagination" data-table-pagination></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="formInstalacionGPS">@csrf <input type="hidden" name="_method" id="formMethod"
                value="POST">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Nueva Instalación GPS</h5> <button type="button"
                        class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="container">
                        <div class="row">
                            <div class="col-3 mb-3"> <label class="form-label">Unidad *</label> <input type="text"
                                    name="unidad" class="form-control" maxlength="50" required> </div>
                            <div class="col-3 mb-3"> <label class="form-label">Marca *</label> <input type="text"
                                    name="marca" class="form-control" maxlength="100" required> </div>
                            <div class="col-3 mb-3"> <label class="form-label">Placas *</label> <input type="text"
                                    name="placas" class="form-control" maxlength="20" required> </div>
                            <div class="col-3 mb-3"> <label class="form-label">Placas Federal</label> <input type="text"
                                    name="placas_federal" class="form-control" maxlength="20"> </div>
                        </div>
                        <div class="row">
                            <div class="col-3 mb-3"> <label class="form-label">Fecha cambio SIM</label> <input
                                    type="date" name="fecha_cambio_sim" class="form-control"> </div>
                            <div class="col-3 mb-3"> <label class="form-label">SIM cambiado *</label> <select
                                    name="sim_cambiado" class="form-select" required>
                                    <option value="1">SI</option>
                                    <option value="0">NO</option>
                                </select> </div>
                            <div class="col-3 mb-3"> <label class="form-label">Reinstalación *</label> <select
                                    name="reinstalacion" class="form-select" required>
                                    <option value="1">SI</option>
                                    <option value="0">NO</option>
                                </select> </div>
                            <div class="col-3 mb-3"> <label class="form-label">Fecha reinstalación</label> <input
                                    type="date" name="fecha_reinstalacion" class="form-control"> </div>
                        </div>
                        <div class="row">
                            <div class="col-4 mb-3"> <label class="form-label">Marca GPS</label> <input type="text"
                                    name="marca_gps" class="form-control"> </div>
                            <div class="col-4 mb-3"> <label class="form-label">Modelo GPS</label> <input type="text"
                                    name="modelo_gps" class="form-control"> </div>
                            <div class="col-4 mb-3"> <label class="form-label">IMEI</label> <input type="text"
                                    name="imei" class="form-control"> </div>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3"> <label class="form-label">Número Anterior</label> <input
                                    type="text" name="numero_anterior" class="form-control"> </div>
                            <div class="col-6 mb-3"> <label class="form-label">Número Actual *</label> <input type="text"
                                    name="numero_actual" class="form-control" required> </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"> <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Cancelar</button> <button type="submit"
                        class="btn btn-primary">Guardar</button> </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function () {
            const form = document.getElementById('formInstalacionGPS');
            form.action = '/inventario/instalaciones-gps/' + this.dataset.id;
            document.getElementById('formMethod').value = 'PUT';
            document.getElementById('modalTitle').textContent = 'Editar Instalación GPS';
            form.unidad.value = this.dataset.unidad;
            form.marca.value = this.dataset.marca;
            form.placas.value = this.dataset.placas;
            form.placas_federal.value = this.dataset.placas_federal || '';
            form.sim_cambiado = this.dataset.sim_cambiado === '1' ? 1 : 0;
            form.reinstalacion = this.dataset.reinstalacion === '1' ? 1 : 0;
            form.fecha_reinstalacion.value = this.dataset.fecha_reinstalacion || '';
            form.marca_gps.value = this.dataset.marca_gps || '';
            form.modelo_gps.value = this.dataset.modelo_gps || '';
            form.imei.value = this.dataset.imei || '';
            form.numero_anterior.value = this.dataset.numero_anterior || '';
            form.numero_actual.value = this.dataset.numero_actual || '';
            new bootstrap.Modal(document.getElementById('createModal')).show();
        });
    });
    document.querySelectorAll('.btn-toggle').forEach(btn => {
        btn.addEventListener('click', function () {
            if (confirm('¿Cambiar estatus?')) {
                fetch(`/inventario/instalaciones-gps/toggle/${this.dataset.id}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                }).then(() => location.reload());
            }
        });
    });

</script>
@endsection
