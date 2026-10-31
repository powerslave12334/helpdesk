@extends('layouts.app')
@section('title', 'Equipos - HelpDesk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-laptop me-2"></i>Equipos</h4>
    <a href="{{ route('inventario.equipo.tipos') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Nuevo Equipo</a>
</div>

<div class="card">
    <div class="card-body">
        <div data-table>
            <div class="table-toolbar">
                <div class="table-toolbar-left">
                    <div class="table-search">
                        <i class="fas fa-search"></i>
                        <input type="text" data-table-search placeholder="Buscar por nombre, marca, modelo o serie...">
                    </div>
                    <select class="table-filter" data-table-filter="tipo">
                        <option value="">Todos los tipos</option>
                        @foreach($equipos->pluck('tipoEquipo.descripcion')->unique()->filter() as $tipo)
                            <option value="{{ $tipo }}">{{ $tipo }}</option>
                        @endforeach
                    </select>
                    <select class="table-filter" data-table-filter="estatus">
                        <option value="">Todos los estatus</option>
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                    <select class="table-filter" data-table-filter="asignacion">
                        <option value="">Todos</option>
                        <option value="asignado">Asignados</option>
                        <option value="disponible">Disponibles</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Equipo</th>
                            <th>Tipo</th>
                            <th>Serie</th>
                            <th>Estatus</th>
                            <th>Ubicación</th>
                            <th>Asignación</th>
                            <th>Activo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody data-table-body>
                        @forelse($equipos as $eq)
                        <tr data-row>
                            <td data-field="equipo">
                                <div><strong>Nombre:</strong> {{ $eq->nombreEquipo ?? 'N/A' }}</div>
                                <div><strong>Marca:</strong> {{ $eq->marca ?? 'N/A' }}</div>
                                <div><strong>Modelo:</strong> {{ $eq->modelo ?? 'N/A' }}</div>
                            </td>
                            <td data-field="tipo"><span class="badge bg-info">{{ $eq->tipoEquipo->descripcion ?? 'N/A' }}</span></td>
                            <td data-field="serie"><small>{{ $eq->numeroSerie ?? 'N/A' }}</small></td>
                            <td data-field="estatus"><span class="badge bg-{{ optional($eq->estatus)->id_Estatus == 1 ? 'success' : 'secondary' }}">{{ optional($eq->estatus)->descripcion ?? 'N/A' }}</span></td>
                            <td data-field="ubicacion">{{ $eq->ubicacion ?? 'N/A' }}</td>
                            <td data-field="asignacion">
                                @if($eq->asignado_a)
                                    <span class="badge bg-primary" title="Asignado a {{ $eq->asignado_a }}">Asignado</span>
                                    <small class="text-muted d-block">{{ $eq->asignado_a }}</small>
                                @else
                                    <span class="badge bg-success">Disponible</span>
                                @endif
                            </td>
                            <td data-field="activo"><span class="badge bg-{{ $eq->activo ? 'success' : 'danger' }}">{{ $eq->activo ? 'Activo' : 'Inactivo' }}</span></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('inventario.equipo.edit', $eq->id_Equipo) }}" class="btn btn-outline-primary" title="Editar"><i class="fas fa-edit"></i></a>
                                    <button class="btn btn-outline-{{ $eq->activo ? 'danger' : 'success' }} btn-toggle" data-id="{{ $eq->id_Equipo }}" data-url="{{ route('inventario.equipo.toggle', $eq->id_Equipo) }}"><i class="fas fa-{{ $eq->activo ? 'ban' : 'check' }}"></i></button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center text-muted">No hay equipos</td></tr>
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
@endsection

@section('scripts')
<script>
document.querySelectorAll('.btn-toggle').forEach(btn => {
    btn.addEventListener('click', function() {
        if (confirm('¿Cambiar estatus?')) {
            fetch(this.dataset.url, {
                method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            }).then(() => location.reload());
        }
    });
});
</script>
@endsection
