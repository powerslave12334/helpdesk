@extends('layouts.app')
@section('title', 'Historial de Equipos - HelpDesk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-history me-2"></i>Historial de Asignaciones</h4>
</div>

<div class="card">
    <div class="card-body">
        <div data-table>
            <div class="table-toolbar">
                <div class="table-toolbar-left">
                    <div class="table-search">
                        <i class="fas fa-search"></i>
                        <input type="text" data-table-search placeholder="Buscar por equipo, usuario o acción...">
                    </div>
                    <select class="table-filter" data-table-filter="accion">
                        <option value="">Todas las acciones</option>
                        <option value="vincular">Vincular</option>
                        <option value="re-vincular">Re-vincular</option>
                        <option value="desvincular">Desvincular</option>
                    </select>
                </div>
            </div>
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
                    <tbody data-table-body>
                        @forelse($historial as $h)
                        <tr data-row>
                            <td data-field="fecha"><small>{{ $h->fechaAccion?->format('d/m/Y H:i') ?? 'N/A' }}</small></td>
                            <td data-field="accion">
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
                            <td data-field="equipo">{{ $h->equipoNombre ?? 'N/A' }}</td>
                            <td data-field="modelo">{{ $h->equipoMarca }} {{ $h->equipoModelo }}</td>
                            <td data-field="serie"><small>{{ $h->equipoNumeroSerie ?? 'N/A' }}</small></td>
                            <td data-field="usuario">{{ $h->usuario->nombre ?? 'N/A' }} {{ $h->usuario->app ?? '' }}</td>
                            <td data-field="realizadopor">{{ $h->usuarioAccion->nombre ?? 'N/A' }} {{ $h->usuarioAccion->app ?? '' }}</td>
                            <td data-field="motivo"><small>{{ $h->motivo ?? '-' }}</small></td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center text-muted">No hay historial</td></tr>
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
