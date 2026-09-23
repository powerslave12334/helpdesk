<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Asunto</th>
                <th>Tipo</th>
                <th>Técnico</th>
                <th>Estatus</th>
                <th>Fecha</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody data-table-body>
            @forelse($tickets as $ticket)
            <tr data-row>
                <td><span class="ticket-folio" data-field="folio">#{{ $ticket->id_Ticket }}</span></td>
                <td class="ticket-asunto" data-field="asunto">{{ Str::limit($ticket->asunto, 40) }}</td>
                <td data-field="tipo"><span class="badge bg-info">{{ $ticket->tipoSoporte->descripcion ?? 'N/A' }}</span></td>
                <td data-field="tecnico">{{ $ticket->tecnico ? $ticket->tecnico->nombre . ' ' . $ticket->tecnico->app : 'Sin asignar' }}</td>
                <td data-field="estatus">
                    @php
                        $estatusColor = 'secondary';
                        if ($ticket->estatus) {
                            $estatusColor = match($ticket->estatus->descripcion) {
                                'Abierto' => 'primary',
                                'En Proceso' => 'warning',
                                'Resuelto' => 'success',
                                'Cerrado' => 'secondary',
                                default => 'secondary',
                            };
                        }
                    @endphp
                    <span class="badge bg-{{ $estatusColor }}">{{ $ticket->estatus->descripcion ?? 'N/A' }}</span>
                </td>
                <td data-field="fecha"><small>{{ $ticket->fechaAlta->format('d/m/Y H:i') }}</small></td>
                <td class="text-center">
                    <button class="btn btn-outline-primary btn-action btn-view-ticket" data-id="{{ $ticket->id_Ticket }}" title="Ver detalle">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>No hay tickets en esta categoría
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="table-footer">
    <span class="table-info" data-table-info></span>
    <div class="pagination" data-table-pagination></div>
</div>
