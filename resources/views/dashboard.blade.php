@extends('layouts.app')
@section('title', 'Dashboard - HelpDesk')

@section('content')
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-ticket fa-2x text-primary mb-2"></i>
                <h3>{{ $stats['mis_tickets'] ?? 0 }}</h3>
                <small class="text-muted">Mis Tickets</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-user-check fa-2x text-warning mb-2"></i>
                <h3>{{ $stats['asignados'] ?? 0 }}</h3>
                <small class="text-muted">Asignados a Mí</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-folder-open fa-2x text-info mb-2"></i>
                <h3>{{ $stats['abiertos'] ?? 0 }}</h3>
                <small class="text-muted">Abiertos</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                <h3>{{ $stats['cerrados'] ?? 0 }}</h3>
                <small class="text-muted">Cerrados</small>
            </div>
        </div>
    </div>
</div>

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
                    <td>
                        <span class="badge bg-{{ $ticket->estatus->descripcion == 'Cerrado' ? 'secondary' : ($ticket->estatus->descripcion == 'Resuelto' ? 'success' : 'primary') }}">
                            {{ $ticket->estatus->descripcion ?? 'Sin estatus' }}
                        </span>
                    </td>
                    <td>{{ $ticket->fechaAlta->format('d/m/Y H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">No hay tickets recientes</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
