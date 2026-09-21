@extends('layouts.app')
@section('title', 'Historial de Equipos - HelpDesk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-history me-2"></i>Historial de Asignaciones</h4>
</div>

<div class="card">
    <div class="card-body">
        <input type="text" class="form-control mb-3" id="searchInput" placeholder="Buscar por equipo, usuario o acción...">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Fecha Acción</th>
                        <th>Acción</th>
                        <th>Equipo</th>
                        <th>Marca/Modelo</th>
                        <th>Serie</th>
                        <th>Usuario</th>
                        <th>Realizado por</th>
                        <th>Motivo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($historial as $h)
                    <tr>
                        <td><small>{{ $h->fechaAccion?->format('d/m/Y H:i') ?? 'N/A' }}</small></td>
                        <td>
                            @switch($h->accion)
                                @case('Vincular')
                                    <span class="badge bg-success">Vincular</span>
                                    @break
                                @case('Re-vincular')
                                    <span class="badge bg-warning">Re-vincular</span>
                                    @break
                                @case('Desvincular')
                                    <span class="badge bg-danger">Desvincular</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">{{ $h->accion }}</span>
                            @endswitch
                        </td>
                        <td>{{ $h->equipoNombre ?? 'N/A' }}</td>
                        <td>{{ $h->equipoMarca }} {{ $h->equipoModelo }}</td>
                        <td><small>{{ $h->equipoNumeroSerie ?? 'N/A' }}</small></td>
                        <td>{{ $h->usuario->nombre ?? 'N/A' }} {{ $h->usuario->app ?? '' }}</td>
                        <td>{{ $h->usuarioAccion->nombre ?? 'N/A' }} {{ $h->usuarioAccion->app ?? '' }}</td>
                        <td><small>{{ $h->motivo ?? '-' }}</small></td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted">No hay historial</td></tr>
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
</script>
@endsection
