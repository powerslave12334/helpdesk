@extends('layouts.app')

@section('title', 'Vehículos - Inventario')

@section('content')
<div class="content-area">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Catálogo de Vehículos</h4>
            <p class="text-muted mb-0">Gestión de unidades físicas y placas</p>
        </div>
        <button class="btn btn-primary" onclick="openCreateModal()">
            <i class="fas fa-plus me-2"></i> Nuevo Vehículo
        </button>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-white py-3">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-auto">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Buscar unidad, marca, placas..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-auto">
                    <select name="estado" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos los estados</option>
                        <option value="Activo" {{ request('estado') == 'Activo' ? 'selected' : '' }}>Activo</option>
                        <option value="Inactivo" {{ request('estado') == 'Inactivo' ? 'selected' : '' }}>Inactivo</option>
                    </select>
                </div>
                <div class="col-auto ms-auto">
                    <a href="{{ route('inventario.vehiculo.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>No. Unidad</th>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>Estado</th>
                            <th>Placa Estatal</th>
                            <th>Placa Federal</th>
                            <th>Placa Puesta</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vehiculos as $v)
                        <tr>
                            <td><strong>{{ $v->numeroUnidad }}</strong></td>
                            <td>{{ $v->marca }}</td>
                            <td>{{ $v->modelo }}</td>
                            <td>
                                <span class="badge bg-info">{{ $v->estado ?? 'N/A' }}</span>
                            </td>
                            <td>{{ $v->placa_Estatal }}</td>
                            <td>{{ $v->placa_Federal }}</td>
                            <td>{{ $v->placa_Puesta }}</td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-secondary" onclick="openEditModal({{ json_encode($v) }})" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteVehiculo({{ $v->id_Vehiculo }})" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                No se encontraron vehículos registrados.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white py-3">
            {{ $vehiculos->links() }}
        </div>
    </div>
</div>

<!-- Modal Crear/Editar -->
<div class="modal fade" id="vehiculoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="vehiculoForm">
                @csrf
                <input type="hidden" name="id" id="vehiculo_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nuevo Vehículo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">No. Unidad <span class="text-danger">*</span></label>
                            <input type="text" name="numeroUnidad" id="numeroUnidad" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <input type="text" name="estado" id="estado" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Marca <span class="text-danger">*</span></label>
                            <input type="text" name="marca" id="marca" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Modelo <span class="text-danger">*</span></label>
                            <input type="text" name="modelo" id="modelo" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Placa Estatal</label>
                            <input type="text" name="placa_Estatal" id="placa_Estatal" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Placa Federal</label>
                            <input type="text" name="placa_Federal" id="placa_Federal" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Placa Puesta</label>
                            <input type="text" name="placa_Puesta" id="placa_Puesta" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Vehículo</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const modal = new bootstrap.Modal(document.getElementById('vehiculoModal'));
    const form = document.getElementById('vehiculoForm');

    function openCreateModal() {
        form.reset();
        document.getElementById('vehiculo_id').value = '';
        document.getElementById('modalTitle').innerText = 'Nuevo Vehículo';
        modal.show();
    }

    function openEditModal(data) {
        document.getElementById('modalTitle').innerText = 'Editar Vehículo';
        document.getElementById('vehiculo_id').value = data.id_Vehiculo;
        document.getElementById('numeroUnidad').value = data.numeroUnidad;
        document.getElementById('estado').value = data.estado;
        document.getElementById('marca').value = data.marca;
        document.getElementById('modelo').value = data.modelo;
        document.getElementById('placa_Estatal').value = data.placa_Estatal;
        document.getElementById('placa_Federal').value = data.placa_Federal;
        document.getElementById('placa_Puesta').value = data.placa_Puesta;
        modal.show();
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        const id = document.getElementById('vehiculo_id').value;
        const url = id ? `{{ route('inventario.vehiculo.update', ':id') }}`.replace(':id', id) : `{{ route('inventario.vehiculo.store') }}`;
        const method = id ? 'PUT' : 'POST';

        try {
            const response = await fetch(url, {
                method: 'POST', // Laravel requires POST for PUT with FormData
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: formData
            });

            // Para PUT en Laravel con FormData, a veces es necesario añadir _method
            // Si falla, agregamos el campo _method manualmente.

            if (id) {
                // Simular PUT usando _method
                const data = Object.fromEntries(formData);
                const responsePut = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ...data, _method: 'PUT' })
                });
                if (responsePut.ok) {
                    Swal.fire('Éxito', 'Vehículo actualizado correctamente', 'success');
                    modal.hide();
                    location.reload();
                } else {
                    throw new Error('Error al actualizar');
                }
            } else {
                if (response.ok) {
                    Swal.fire('Éxito', 'Vehículo registrado correctamente', 'success');
                    modal.hide();
                    location.reload();
                } else {
                    throw new Error('Error al registrar');
                }
            }
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        }
    });

    async function deleteVehiculo(id) {
        if (!confirm('¿Estás seguro de que deseas dar de baja este vehículo?')) return;

        try {
            const response = await fetch(`{{ route('inventario.vehiculo.destroy', ':id') }}`.replace(':id', id), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            });

            if (response.ok) {
                Swal.fire('Eliminado', 'Vehículo desactivado correctamente', 'success');
                location.reload();
            } else {
                throw new Error('Error al eliminar');
            }
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        }
    }
</script>
@endsection
