@extends('layouts.app')
@section('title', 'Equipos - HelpDesk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-laptop me-2"></i>Equipos</h4>
    <a href="{{ route('inventario.equipo.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Nuevo Equipo</a>
</div>

<div class="card">
    <div class="card-body">
        <input type="text" class="form-control mb-3" id="searchInput" placeholder="Buscar por nombre, marca, modelo o serie...">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Tipo</th>
                        <th>Serie</th>
                        <th>Estatus</th>
                        <th>Ubicación</th>
                        <th>Activo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($equipos as $eq)
                    <tr>
                        <td>{{ $eq->nombreEquipo ?? 'N/A' }}</td>
                        <td>{{ $eq->marca }}</td>
                        <td>{{ $eq->modelo }}</td>
                        <td><span class="badge bg-info">{{ $eq->tipoEquipo->descripcion ?? 'N/A' }}</span></td>
                        <td><small>{{ $eq->numeroSerie ?? 'N/A' }}</small></td>
                        <td><span class="badge bg-{{ optional($eq->estatus)->id_Estatus == 1 ? 'success' : 'secondary' }}">{{ optional($eq->estatus)->descripcion ?? 'N/A' }}</span></td>
                        <td>{{ $eq->ubicacion ?? 'N/A' }}</td>
                        <td><span class="badge bg-{{ $eq->activo ? 'success' : 'danger' }}">{{ $eq->activo ? 'Activo' : 'Inactivo' }}</span></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('inventario.equipo.edit', $eq->id_Equipo) }}" class="btn btn-outline-primary" title="Editar"><i class="fas fa-edit"></i></a>
                                <button class="btn btn-outline-{{ $eq->activo ? 'danger' : 'success' }} btn-toggle" data-id="{{ $eq->id_Equipo }}" data-url="{{ route('inventario.equipo.toggle', $eq->id_Equipo) }}"><i class="fas fa-{{ $eq->activo ? 'ban' : 'check' }}"></i></button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted">No hay equipos</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.getElementById('searchInput')?.addEventListener('input', function() {
    const term = this.value.toLowerCase();
    document.querySelectorAll('tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
});
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
