@extends('layouts.app')
@section('title', 'Dashboard - HelpDesk')

@section('content')
@if($esAdmin)
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-ticket fa-2x text-primary mb-2"></i>
                <h3>{{ $stats['total_tickets'] ?? 0 }}</h3>
                <small class="text-muted">Total Tickets</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-folder-open fa-2x text-warning mb-2"></i>
                <h3>{{ $stats['abiertos'] ?? 0 }}</h3>
                <small class="text-muted">Abiertos</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                <h3>{{ $stats['resueltos'] ?? 0 }}</h3>
                <small class="text-muted">Resueltos</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-times-circle fa-2x text-secondary mb-2"></i>
                <h3>{{ $stats['cerrados'] ?? 0 }}</h3>
                <small class="text-muted">Cerrados</small>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-laptop fa-2x text-info mb-2"></i>
                <h3>{{ $stats['total_equipos'] ?? 0 }}</h3>
                <small class="text-muted">Equipos Registrados</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-users fa-2x text-primary mb-2"></i>
                <h3>{{ $stats['total_usuarios'] ?? 0 }}</h3>
                <small class="text-muted">Usuarios Activos</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-user-check fa-2x text-warning mb-2"></i>
                <h3>{{ $stats['asignados'] ?? 0 }}</h3>
                <small class="text-muted">Asignados a Mí</small>
            </div>
        </div>
    </div>
</div>
@else
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-ticket fa-2x text-primary mb-2"></i>
                <h3>{{ $stats['mis_tickets'] ?? 0 }}</h3>
                <small class="text-muted">Mis Tickets</small>
            </div>
        </div>
    </div>
    @if($esTecnico)
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-user-check fa-2x text-warning mb-2"></i>
                <h3>{{ $stats['asignados'] ?? 0 }}</h3>
                <small class="text-muted">Asignados a Mí</small>
            </div>
        </div>
    </div>
    @endif
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-folder-open fa-2x text-info mb-2"></i>
                <h3>{{ $stats['abiertos'] ?? 0 }}</h3>
                <small class="text-muted">Abiertos</small>
            </div>
        </div>
    </div>
</div>
@endif

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Tickets Recientes</h5>
        <a href="{{ route('tickets.index') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i>Ver Todos
        </a>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Asunto</th>
                    <th>Tipo</th>
                    @if($esAdmin)<th>Asignado a</th>@endif
                    <th>Estatus</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentTickets as $ticket)
                <tr>
                    <td><strong>#{{ $ticket->id_Ticket }}</strong></td>
                    <td>{{ $ticket->asunto }}</td>
                    <td>{{ $ticket->tipoSoporte->descripcion ?? 'N/A' }}</td>
                    @if($esAdmin)
                    <td>{{ $ticket->tecnico ? $ticket->tecnico->nombreCompleto() : 'Sin asignar' }}</td>
                    @endif
                    <td>
                        <span class="badge bg-{{ $ticket->estatus->descripcion == 'Cerrado' ? 'secondary' : ($ticket->estatus->descripcion == 'Resuelto' ? 'success' : 'primary') }}">
                            {{ $ticket->estatus->descripcion ?? 'Sin estatus' }}
                        </span>
                    </td>
                    <td>{{ $ticket->fechaAlta->format('d/m/Y H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $esAdmin ? 6 : 5 }}" class="text-center text-muted py-4">No hay tickets recientes</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
