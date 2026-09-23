@extends('layouts.app')
@section('title', 'Tickets - HelpDesk')

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<style>
    .ticket-folio { font-weight: 700; color: #2d3748; }
    .ticket-asunto { font-weight: 500; }
    .sin-actividad-0, .sin-actividad-1 { background: #c6f6d5; color: #22543d; }
    .sin-actividad-2 { background: #fefcbf; color: #744210; }
    .sin-actividad-3 { background: #fed7d7; color: #742a2a; }
    .tab-content { min-height: 400px; }
    .timeline { padding-left: 20px; border-left: 3px solid #e2e8f0; }
    .timeline-item { margin-bottom: 15px; position: relative; }
    .timeline-item::before { content: ''; width: 10px; height: 10px; background: #4299e1; border-radius: 50%; position: absolute; left: -27px; top: 5px; }
    .evidence-thumb { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; cursor: pointer; margin: 3px; }
</style>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-ticket me-2"></i>Seguimiento de Tickets</h4>
    <div class="d-flex gap-2">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTicketModal">
            <i class="fas fa-headset me-1"></i> Soporte
        </button>
        <button class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#developmentTicketModal">
            <i class="fas fa-code me-1"></i> Desarrollo
        </button>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#tab-mis-tickets">
                    <i class="fas fa-user me-1"></i>Mis Tickets <span class="badge bg-primary">{{ $misTickets->count() }}</span>
                </a>
            </li>
            @if($esAdmin)
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tab-asignados">
                    <i class="fas fa-user-check me-1"></i>Asignados a Mí <span class="badge bg-warning">{{ $asignados->count() }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tab-todos">
                    <i class="fas fa-list me-1"></i>Todos <span class="badge bg-secondary">{{ $todos->count() }}</span>
                </a>
            </li>
            @endif
        </ul>

        <div class="tab-content mt-3">
            <div class="tab-pane fade show active" id="tab-mis-tickets">
                <div data-table>
                    <div class="table-toolbar">
                        <div class="table-toolbar-left">
                            <div class="table-search">
                                <i class="fas fa-search"></i>
                                <input type="text" data-table-search placeholder="Buscar por folio, asunto, técnico...">
                            </div>
                            <select class="table-filter" data-table-filter="estatus">
                                <option value="">Todos los estatus</option>
                                <option value="abierto">Abierto</option>
                                <option value="en proceso">En Proceso</option>
                                <option value="resuelto">Resuelto</option>
                                <option value="cerrado">Cerrado</option>
                            </select>
                        </div>
                    </div>
                    @include('tickets._table', ['tickets' => $misTickets, 'tabId' => 'mis'])
                </div>
            </div>
            @if($esAdmin)
            <div class="tab-pane fade" id="tab-asignados">
                <div data-table>
                    <div class="table-toolbar">
                        <div class="table-toolbar-left">
                            <div class="table-search">
                                <i class="fas fa-search"></i>
                                <input type="text" data-table-search placeholder="Buscar por folio, asunto, técnico...">
                            </div>
                            <select class="table-filter" data-table-filter="estatus">
                                <option value="">Todos los estatus</option>
                                <option value="abierto">Abierto</option>
                                <option value="en proceso">En Proceso</option>
                                <option value="resuelto">Resuelto</option>
                                <option value="cerrado">Cerrado</option>
                            </select>
                        </div>
                    </div>
                    @include('tickets._table', ['tickets' => $asignados, 'tabId' => 'asignados'])
                </div>
            </div>
            <div class="tab-pane fade" id="tab-todos">
                <div data-table>
                    <div class="table-toolbar">
                        <div class="table-toolbar-left">
                            <div class="table-search">
                                <i class="fas fa-search"></i>
                                <input type="text" data-table-search placeholder="Buscar por folio, asunto, técnico...">
                            </div>
                            <select class="table-filter" data-table-filter="estatus">
                                <option value="">Todos los estatus</option>
                                <option value="abierto">Abierto</option>
                                <option value="en proceso">En Proceso</option>
                                <option value="resuelto">Resuelto</option>
                                <option value="cerrado">Cerrado</option>
                            </select>
                        </div>
                    </div>
                    @include('tickets._table', ['tickets' => $todos, 'tabId' => 'todos'])
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Crear Ticket Soporte -->
<div class="modal fade" id="createTicketModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-headset me-2"></i>Crear Ticket de Soporte</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tipo Soporte *</label>
                        <select name="id_TipoSoporte" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            @foreach($tiposSoporte as $ts)
                                @if($ts->id_TipoSoporte != 1)
                                <option value="{{ $ts->id_TipoSoporte }}">{{ $ts->descripcion }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Equipo</label>
                        <select name="id_Equipo" class="form-select">
                            <option value="">Sin equipo</option>
                            @foreach($equipos as $eq)
                            <option value="{{ $eq->id_Equipo }}">{{ $eq->nombreEquipo }} - {{ $eq->marca }} {{ $eq->modelo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Evidencia Inicial</label>
                        <input type="file" name="evidencia" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Asunto *</label>
                        <input type="text" name="asunto" class="form-control" maxlength="200" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Problema *</label>
                        <textarea name="problema" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Ticket</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Crear Ticket Desarrollo -->
<div class="modal fade" id="developmentTicketModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('tickets.storeDesarrollo') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-code me-2"></i>Crear Ticket de Desarrollo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tipo Soporte</label>
                        <input type="text" class="form-control" value="Desarrollo" readonly>
                        <input type="hidden" name="id_TipoSoporte" value="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quien Solicita</label>
                        <input type="text" class="form-control" value="{{ session('NombreUsuario') }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de Requerimiento *</label>
                        <select name="id_TipoDesarrollo" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            @foreach($tiposDesarrollo as $td)
                            <option value="{{ $td->id_TipoDesarrollo }}">{{ $td->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de Sistema *</label>
                        <select name="id_Sistemas" id="selSistemas" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            @foreach($sistemas as $s)
                            <option value="{{ $s->id_sistemas }}">{{ $s->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Módulo *</label>
                        <select name="id_Modulo" id="selModulos" class="form-select" required disabled>
                            <option value="">Seleccionar sistema primero...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Desarrollador *</label>
                        <select name="id_Tecnico" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            @foreach($tecnicos as $t)
                            <option value="{{ $t->id_Usuario }}">{{ $t->nombre }} {{ $t->app }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Asunto *</label>
                        <input type="text" name="asunto" class="form-control" maxlength="100" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción del Desarrollo *</label>
                        <textarea name="descripcionTicket" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info text-white">Crear Ticket</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Detalle Ticket -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-ticket me-2"></i>Detalle del Ticket <span id="detailFolio"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8">
                        <h6 class="text-muted mb-1">Asunto</h6>
                        <p id="detailAsunto" class="fw-bold"></p>
                    </div>
                    <div class="col-md-4 text-end">
                        <span id="detailEstatus" class="badge"></span>
                    </div>
                </div>
                <hr>
                <div class="row mb-3">
                    <div class="col-md-3"><small class="text-muted">Tipo Soporte</small><br><span id="detailTipo"></span></div>
                    <div class="col-md-3"><small class="text-muted">Área</small><br><span id="detailArea"></span></div>
                    <div class="col-md-3"><small class="text-muted">Técnico</small><br><span id="detailTecnico"></span></div>
                    <div class="col-md-3"><small class="text-muted">Equipo</small><br><span id="detailEquipo"></span></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4"><small class="text-muted">Fecha Alta</small><br><span id="detailFecha"></span></div>
                    <div class="col-md-4"><small class="text-muted">Fecha Cierre</small><br><span id="detailFechaCierre"></span></div>
                </div>

                <div class="mb-4">
                    <h6><i class="fas fa-exclamation-circle me-1"></i>Problema</h6>
                    <div id="detailProblema" class="bg-light p-3 rounded"></div>
                </div>

                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-evidences"><i class="fas fa-image me-1"></i>Evidencias</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-history"><i class="fas fa-history me-1"></i>Historial</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-comment"><i class="fas fa-comment me-1"></i>Comentario</a></li>
                    @if($esAdmin)
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-management"><i class="fas fa-cog me-1"></i>Gestión</a></li>
                    @endif
                </ul>
                <div class="tab-content mt-3">
                    <div class="tab-pane fade show active" id="tab-evidences">
                        <div id="evidenceGallery" class="d-flex flex-wrap"></div>
                        <div class="mt-3">
                            <form id="formEvidence" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="id_Ticket" id="evidenceTicketId">
                                <div class="input-group">
                                    <input type="file" name="evidencia" class="form-control" accept="image/*,.pdf,.doc,.docx" required>
                                    <button type="submit" class="btn btn-outline-primary"><i class="fas fa-upload me-1"></i>Guardar Evidencia</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tab-history">
                        <div id="historyTimeline" class="timeline"></div>
                    </div>
                    <div class="tab-pane fade" id="tab-comment">
                        <form id="formComment">
                            @csrf
                            <input type="hidden" name="id_Ticket" id="commentTicketId">
                            <div class="mb-3">
                                <textarea name="comentario" class="form-control" rows="3" placeholder="Escribir comentario..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i>Agregar Comentario</button>
                        </form>
                    </div>
                    @if($esAdmin)
                    <div class="tab-pane fade" id="tab-management">
                        <form id="formManagement">
                            @csrf
                            <input type="hidden" name="id_Ticket" id="mgmtTicketId">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Estatus</label>
                                    <select name="id_Estatus" id="mgmtEstatus" class="form-select">
                                        @foreach($estatus as $e)
                                            @if($e->descripcion != 'Cerrado')
                                            <option value="{{ $e->id_Estatus }}">{{ $e->descripcion }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Técnico</label>
                                    <select name="id_Tecnico" id="mgmtTecnico" class="form-select">
                                        @foreach($tecnicos as $t)
                                        <option value="{{ $t->id_Usuario }}">{{ $t->nombre }} {{ $t->app }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>Guardar Gestión</button>
                        </form>
                        <hr>
                        <div id="closeSection" class="d-none">
                            <button type="button" id="btnClose" class="btn btn-success"><i class="fas fa-check-double me-1"></i>Dar visto bueno y cerrar</button>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cascading: Sistemas -> Modulos
    const selSistemas = document.getElementById('selSistemas');
    const selModulos = document.getElementById('selModulos');
    if (selSistemas) {
        selSistemas.addEventListener('change', function() {
            const id = this.value;
            selModulos.innerHTML = '<option value="">Cargando...</option>';
            selModulos.disabled = true;
            if (id) {
                fetch(`/tickets/modulos/${id}`)
                    .then(r => r.json())
                    .then(data => {
                        selModulos.innerHTML = '<option value="">Seleccionar...</option>';
                        data.forEach(m => {
                            selModulos.innerHTML += `<option value="${m.id_ModulosSistemas}">${m.nombre}</option>`;
                        });
                        selModulos.disabled = false;
                    });
            } else {
                selModulos.innerHTML = '<option value="">Seleccionar sistema primero...</option>';
            }
        });
    }

    // View ticket detail
    document.querySelectorAll('.btn-view-ticket').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            fetch(`/tickets/${id}`)
                .then(r => r.json())
                .then(data => {
                    const t = data.ticket;
                    document.getElementById('detailFolio').textContent = '#' + t.id_Ticket;
                    document.getElementById('detailAsunto').textContent = t.asunto;
                    document.getElementById('detailTipo').textContent = t.tipo_soporte?.descripcion || 'N/A';
                    document.getElementById('detailArea').textContent = t.tipo_soporte?.area?.nombre || 'N/A';
                    document.getElementById('detailTecnico').textContent = t.tecnico ? t.tecnico.nombre + ' ' + t.tecnico.app : 'Sin asignar';
                    document.getElementById('detailEquipo').textContent = t.equipo ? t.equipo.nombreEquipo : 'Sin equipo';
                    document.getElementById('detailFecha').textContent = new Date(t.fecha_alta).toLocaleString('es-MX');
                    document.getElementById('detailFechaCierre').textContent = t.fecha_cierre ? new Date(t.fecha_cierre).toLocaleString('es-MX') : 'N/A';
                    document.getElementById('detailProblema').textContent = t.problema;

                    const estBadge = document.getElementById('detailEstatus');
                    estBadge.textContent = t.estatus?.descripcion || 'N/A';
                    estBadge.className = 'badge bg-' + (t.estatus?.descripcion == 'Cerrado' ? 'secondary' : t.estatus?.descripcion == 'Resuelto' ? 'success' : 'primary');

                    // Evidences
                    const gallery = document.getElementById('evidenceGallery');
                    gallery.innerHTML = '';
                    (t.evidencias || []).forEach(e => {
                        if (['jpg','jpeg','png','gif'].includes(e.extension?.toLowerCase())) {
                            gallery.innerHTML += `<img src="/${e.ruta_archivo}" class="evidence-thumb" onclick="window.open('/${e.ruta_archivo}')">`;
                        } else {
                            gallery.innerHTML += `<a href="/${e.ruta_archivo}" target="_blank" class="btn btn-outline-secondary btn-sm me-1"><i class="fas fa-file me-1"></i>${e.nombre_archivo}</a>`;
                        }
                    });

                    // History
                    const timeline = document.getElementById('historyTimeline');
                    timeline.innerHTML = '';
                    (t.comentarios || []).forEach(c => {
                        timeline.innerHTML += `
                            <div class="timeline-item">
                                <div class="d-flex justify-content-between">
                                    <strong>${c.usuario?.nombre || 'Sistema'}</strong>
                                    <small class="text-muted">${new Date(c.fecha_comentario).toLocaleString('es-MX')}</small>
                                </div>
                                <span class="badge bg-secondary mb-1">${c.estatus?.descripcion || ''}</span>
                                <p class="mb-0">${c.comentario}</p>
                            </div>`;
                    });

                    // IDs for forms
                    document.getElementById('commentTicketId').value = t.id_Ticket;
                    document.getElementById('evidenceTicketId').value = t.id_Ticket;
                    document.getElementById('mgmtTicketId').value = t.id_Ticket;

                    // Management
                    if (data.esAdmin) {
                        document.getElementById('mgmtEstatus').value = t.id_Estatus;
                        document.getElementById('mgmtTecnico').value = t.id_Tecnico || '';
                        const closeSection = document.getElementById('closeSection');
                        if (t.estatus?.descripcion == 'Resuelto' && data.esPropietario) {
                            closeSection.classList.remove('d-none');
                        } else {
                            closeSection.classList.add('d-none');
                        }
                    }

                    new bootstrap.Modal(document.getElementById('viewModal')).show();
                });
        });
    });

    // Comment form
    document.getElementById('formComment')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('/tickets/comentario', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: formData
        }).then(r => r.json()).then(data => {
            if (data.success) location.reload();
            else alert(data.error || 'Error');
        });
    });

    // Evidence form
    document.getElementById('formEvidence')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('/tickets/evidencia', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: formData
        }).then(r => r.json()).then(data => {
            if (data.success) location.reload();
            else alert(data.error || 'Error');
        });
    });

    // Management form
    document.getElementById('formManagement')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('/tickets/gestion', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: formData
        }).then(r => r.json()).then(data => {
            if (data.success) location.reload();
            else alert(data.error || 'Error');
        });
    });

    // Close ticket
    document.getElementById('btnClose')?.addEventListener('click', function() {
        const id = document.getElementById('mgmtTicketId').value;
        if (confirm('¿Dar visto bueno y cerrar este ticket?')) {
            fetch(`/tickets/cerrar/${id}`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            }).then(r => r.json()).then(data => {
                if (data.success) location.reload();
                else alert(data.error || 'Error');
            });
        }
    });
});
</script>
@endsection
