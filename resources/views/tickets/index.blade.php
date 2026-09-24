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

    /* ── Modal Detalle Ticket ── */
    .ticket-modal .modal-content { border: none; border-radius: var(--radius-lg); overflow: hidden; }
    .ticket-modal .modal-body { padding: 0; }

    /* Header */
    .ticket-modal-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 28px; border-bottom: 1px solid var(--border); background: var(--surface); }
    .ticket-modal-header-left { display: flex; align-items: center; gap: 16px; }
    .ticket-modal-folio { font-size: 28px; font-weight: 700; color: var(--text); letter-spacing: -0.02em; line-height: 1; }
    .ticket-modal-asunto { font-size: 14px; color: var(--muted); font-weight: 500; margin-top: 2px; }
    .ticket-modal-close { background: none; border: 1px solid var(--border); color: var(--muted); width: 32px; height: 32px; border-radius: var(--radius-sm); display: grid; place-items: center; cursor: pointer; transition: all 0.15s; font-size: 14px; }
    .ticket-modal-close:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-soft); }

    /* Layout */
    .ticket-modal-layout { display: grid; grid-template-columns: 1fr 260px; min-height: 520px; }
    .ticket-modal-main { padding: 24px 28px; overflow-y: auto; }
    .ticket-modal-sidebar { background: var(--surface-strong); border-left: 1px solid var(--border); padding: 24px 20px; overflow-y: auto; }

    /* Sidebar metadata */
    .ticket-meta-section { margin-bottom: 20px; }
    .ticket-meta-section:last-child { margin-bottom: 0; }
    .ticket-meta-label { font-size: 10px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 4px; }
    .ticket-meta-value { font-size: 13px; font-weight: 500; color: var(--text); line-height: 1.4; }

    /* Estatus badge en sidebar */
    .ticket-status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .ticket-status-badge.abierto { background: rgba(37, 99, 235, 0.1); color: #2563eb; }
    .ticket-status-badge.en-proceso { background: rgba(217, 119, 6, 0.1); color: #d97706; }
    .ticket-status-badge.resuelto { background: rgba(5, 150, 105, 0.1); color: #059669; }
    .ticket-status-badge.cerrado { background: rgba(100, 116, 139, 0.1); color: #64748b; }
    .ticket-status-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

    /* Problema */
    .ticket-problema { border-left: 3px solid var(--primary); background: var(--surface-strong); padding: 16px 20px; border-radius: 0 var(--radius-sm) var(--radius-sm) 0; margin-bottom: 24px; }
    .ticket-problema-title { font-size: 11px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
    .ticket-problema-title i { color: var(--primary); font-size: 12px; }
    .ticket-problema-text { font-size: 13px; color: var(--text); line-height: 1.6; white-space: pre-wrap; }

    /* Pestañas */
    .ticket-tabs { display: flex; gap: 0; border-bottom: 1px solid var(--border); margin-bottom: 20px; }
    .ticket-tab { padding: 10px 16px; font-size: 13px; font-weight: 500; color: var(--muted); cursor: pointer; border-bottom: 2px solid transparent; transition: all 0.15s; background: none; border-top: none; border-left: none; border-right: none; display: flex; align-items: center; gap: 6px; }
    .ticket-tab:hover { color: var(--text); }
    .ticket-tab.active { color: var(--primary); border-bottom-color: var(--primary); font-weight: 600; }
    .ticket-tab i { font-size: 13px; }
    .ticket-tab-content { display: none; }
    .ticket-tab-content.active { display: block; }

    /* Evidencias grid */
    .ticket-evidence-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(90px, 1fr)); gap: 8px; margin-bottom: 16px; }
    .ticket-evidence-grid:empty { display: none; }
    .ticket-evidence-item { position: relative; border-radius: var(--radius-sm); overflow: hidden; aspect-ratio: 1; border: 1px solid var(--border); background: var(--surface-strong); }
    .ticket-evidence-item img { width: 100%; height: 100%; object-fit: cover; cursor: pointer; transition: transform 0.2s; }
    .ticket-evidence-item img:hover { transform: scale(1.05); }
    .ticket-evidence-file { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 8px; text-decoration: none; color: var(--muted); gap: 4px; height: 100%; }
    .ticket-evidence-file i { font-size: 20px; color: var(--primary); }
    .ticket-evidence-file span { font-size: 10px; text-align: center; word-break: break-all; line-height: 1.2; }

    /* Timeline */
    .ticket-timeline { display: flex; flex-direction: column; gap: 12px; }
    .ticket-timeline:empty::after { content: 'Sin actividad aún'; display: block; text-align: center; color: var(--muted); font-size: 13px; padding: 32px 0; }
    .ticket-timeline-item { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 14px 16px; }
    .ticket-timeline-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }
    .ticket-timeline-user { display: flex; align-items: center; gap: 8px; }
    .ticket-timeline-avatar { width: 28px; height: 28px; border-radius: 50%; background: var(--primary); color: #fff; display: grid; place-items: center; font-size: 10px; font-weight: 700; flex-shrink: 0; }
    .ticket-timeline-name { font-size: 13px; font-weight: 600; color: var(--text); }
    .ticket-timeline-date { font-size: 11px; color: var(--muted); }
    .ticket-timeline-status { display: inline-block; font-size: 11px; font-weight: 500; padding: 2px 8px; border-radius: 10px; background: var(--surface-strong); color: var(--muted); margin-bottom: 6px; }
    .ticket-timeline-text { font-size: 13px; color: var(--text); line-height: 1.5; margin: 0; }

    /* Formularios dentro del modal */
    .ticket-form-group { margin-bottom: 14px; }
    .ticket-form-group:last-child { margin-bottom: 0; }
    .ticket-form-label { font-size: 12px; font-weight: 600; color: var(--muted); margin-bottom: 4px; display: block; }
    .ticket-form-input, .ticket-form-textarea, .ticket-form-select { width: 100%; padding: 9px 12px; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 13px; color: var(--text); background: var(--surface); transition: border-color 0.15s, box-shadow 0.15s; font-family: var(--font); }
    .ticket-form-input:focus, .ticket-form-textarea:focus, .ticket-form-select:focus { outline: none; border-color: var(--border-focus); box-shadow: 0 0 0 3px var(--primary-soft); }
    .ticket-form-textarea { resize: vertical; min-height: 80px; }
    .ticket-form-upload { display: flex; gap: 8px; align-items: center; }
    .ticket-form-upload .ticket-form-input { flex: 1; }
    .ticket-btn { padding: 8px 16px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 500; cursor: pointer; border: none; transition: all 0.15s; display: inline-flex; align-items: center; gap: 6px; font-family: var(--font); }
    .ticket-btn-primary { background: var(--primary); color: #fff; }
    .ticket-btn-primary:hover { background: var(--primary-hover); }
    .ticket-btn-outline { background: transparent; color: var(--primary); border: 1px solid var(--primary); }
    .ticket-btn-outline:hover { background: var(--primary-soft); }
    .ticket-btn-success { background: #059669; color: #fff; }
    .ticket-btn-success:hover { background: #047857; }
    .ticket-btn-warning { background: #d97706; color: #fff; }
    .ticket-btn-warning:hover { background: #b45309; }

    /* Empty states */
    .ticket-empty { text-align: center; padding: 32px 0; color: var(--muted); }
    .ticket-empty i { font-size: 24px; margin-bottom: 8px; display: block; opacity: 0.5; }
    .ticket-empty span { font-size: 13px; }

    /* Dark mode */
    body.dark-mode .ticket-modal-header { background: var(--surface); border-color: var(--border); }
    body.dark-mode .ticket-modal-sidebar { background: var(--surface-strong); border-color: var(--border); }
    body.dark-mode .ticket-problema { background: var(--surface-strong); }
    body.dark-mode .ticket-timeline-item { background: var(--surface); border-color: var(--border); }
    body.dark-mode .ticket-evidence-item { background: var(--surface-strong); border-color: var(--border); }

    @media (max-width: 768px) {
        .ticket-modal-layout { grid-template-columns: 1fr; }
        .ticket-modal-sidebar { border-left: none; border-top: 1px solid var(--border); }
        .ticket-modal-folio { font-size: 22px; }
    }
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
<div class="modal fade ticket-modal" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="ticket-modal-header">
                <div class="ticket-modal-header-left">
                    <div>
                        <div class="ticket-modal-folio" id="detailFolio"></div>
                        <div class="ticket-modal-asunto" id="detailAsunto"></div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span id="detailEstatus" class="ticket-status-badge"></span>
                    <button type="button" class="ticket-modal-close" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="ticket-modal-layout">
                <div class="ticket-modal-main">
                    <div class="ticket-problema">
                        <div class="ticket-problema-title"><i class="fas fa-exclamation-circle"></i>Problema reportado</div>
                        <div class="ticket-problema-text" id="detailProblema"></div>
                    </div>

                    <div class="ticket-tabs" role="tablist">
                        <button class="ticket-tab active" data-tab="tab-evidences"><i class="fas fa-image"></i>Evidencias</button>
                        <button class="ticket-tab" data-tab="tab-history"><i class="fas fa-history"></i>Historial</button>
                        <button class="ticket-tab" data-tab="tab-comment"><i class="fas fa-comment"></i>Comentario</button>
                        @if($esAdmin)
                        <button class="ticket-tab" data-tab="tab-management"><i class="fas fa-cog"></i>Gestión</button>
                        @endif
                    </div>

                    <div class="ticket-tab-content active" id="tab-evidences">
                        <div id="evidenceGallery" class="ticket-evidence-grid"></div>
                        <form id="formEvidence" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="id_Ticket" id="evidenceTicketId">
                            <div class="ticket-form-upload">
                                <input type="file" name="evidencia" class="ticket-form-input" accept="image/*,.pdf,.doc,.docx" required>
                                <button type="submit" class="ticket-btn ticket-btn-outline"><i class="fas fa-upload"></i>Guardar</button>
                            </div>
                        </form>
                    </div>

                    <div class="ticket-tab-content" id="tab-history">
                        <div id="historyTimeline" class="ticket-timeline"></div>
                    </div>

                    <div class="ticket-tab-content" id="tab-comment">
                        <form id="formComment">
                            @csrf
                            <input type="hidden" name="id_Ticket" id="commentTicketId">
                            <div class="ticket-form-group">
                                <textarea name="comentario" class="ticket-form-textarea" placeholder="Escribir comentario..." required></textarea>
                            </div>
                            <button type="submit" class="ticket-btn ticket-btn-primary"><i class="fas fa-paper-plane"></i>Enviar comentario</button>
                        </form>
                    </div>

                    @if($esAdmin)
                    <div class="ticket-tab-content" id="tab-management">
                        <form id="formManagement">
                            @csrf
                            <input type="hidden" name="id_Ticket" id="mgmtTicketId">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="ticket-form-group">
                                        <label class="ticket-form-label">Estatus</label>
                                        <select name="id_Estatus" id="mgmtEstatus" class="ticket-form-select">
                                            @foreach($estatus as $e)
                                                @if($e->descripcion != 'Cerrado')
                                                <option value="{{ $e->id_Estatus }}">{{ $e->descripcion }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="ticket-form-group">
                                        <label class="ticket-form-label">Técnico</label>
                                        <select name="id_Tecnico" id="mgmtTecnico" class="ticket-form-select">
                                            @foreach($tecnicos as $t)
                                            <option value="{{ $t->id_Usuario }}">{{ $t->nombre }} {{ $t->app }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 mt-3">
                                <button type="submit" class="ticket-btn ticket-btn-warning"><i class="fas fa-save"></i>Guardar gestión</button>
                                <div id="closeSection" class="d-none">
                                    <button type="button" id="btnClose" class="ticket-btn ticket-btn-success"><i class="fas fa-check-double"></i>Dar visto bueno y cerrar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    @endif
                </div>
                <div class="ticket-modal-sidebar">
                    <div class="ticket-meta-section">
                        <div class="ticket-meta-label">Tipo de soporte</div>
                        <div class="ticket-meta-value" id="detailTipo"></div>
                    </div>
                    <div class="ticket-meta-section">
                        <div class="ticket-meta-label">Área</div>
                        <div class="ticket-meta-value" id="detailArea"></div>
                    </div>
                    <div class="ticket-meta-section">
                        <div class="ticket-meta-label">Técnico asignado</div>
                        <div class="ticket-meta-value" id="detailTecnico"></div>
                    </div>
                    <div class="ticket-meta-section">
                        <div class="ticket-meta-label">Equipo</div>
                        <div class="ticket-meta-value" id="detailEquipo"></div>
                    </div>
                    <div class="ticket-meta-section">
                        <div class="ticket-meta-label">Fecha de alta</div>
                        <div class="ticket-meta-value" id="detailFecha"></div>
                    </div>
                    <div class="ticket-meta-section">
                        <div class="ticket-meta-label">Fecha de cierre</div>
                        <div class="ticket-meta-value" id="detailFechaCierre"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Custom tabs
    document.querySelectorAll('.ticket-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const target = this.dataset.tab;
            this.closest('.ticket-modal-main').querySelectorAll('.ticket-tab').forEach(t => t.classList.remove('active'));
            this.closest('.ticket-modal-main').querySelectorAll('.ticket-tab-content').forEach(tc => tc.classList.remove('active'));
            this.classList.add('active');
            document.getElementById(target).classList.add('active');
        });
    });

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
                    document.getElementById('detailTipo').textContent = t.tipoSoporte?.descripcion || 'N/A';
                    document.getElementById('detailArea').textContent = t.tipoSoporte?.area?.nombre || 'N/A';
                    document.getElementById('detailTecnico').textContent = t.tecnico ? t.tecnico.nombre + ' ' + t.tecnico.app : 'Sin asignar';
                    document.getElementById('detailEquipo').textContent = t.equipo ? t.equipo.nombreEquipo : 'Sin equipo';
                    document.getElementById('detailFecha').textContent = new Date(t.fechaAlta).toLocaleString('es-MX');
                    document.getElementById('detailFechaCierre').textContent = t.fechaCierre ? new Date(t.fechaCierre).toLocaleString('es-MX') : 'N/A';
                    document.getElementById('detailProblema').textContent = t.problema;

                    const estBadge = document.getElementById('detailEstatus');
                    estBadge.innerHTML = '<span class="ticket-status-dot"></span>' + (t.estatus?.descripcion || 'N/A');
                    const statusClass = (t.estatus?.descripcion || '').toLowerCase().replace(/\s+/g, '-');
                    estBadge.className = 'ticket-status-badge ' + statusClass;

                    // Reset tabs
                    document.querySelectorAll('.ticket-tab').forEach(tab => tab.classList.remove('active'));
                    document.querySelectorAll('.ticket-tab-content').forEach(tc => tc.classList.remove('active'));
                    document.querySelector('.ticket-tab[data-tab="tab-evidences"]').classList.add('active');
                    document.getElementById('tab-evidences').classList.add('active');

                    // Evidences
                    const gallery = document.getElementById('evidenceGallery');
                    gallery.innerHTML = '';
                    (t.evidencias || []).forEach(e => {
                        if (['jpg','jpeg','png','gif'].includes(e.extension?.toLowerCase())) {
                            gallery.innerHTML += `<div class="ticket-evidence-item"><img src="/${e.rutaArchivo}" onclick="window.open('/${e.rutaArchivo}')"></div>`;
                        } else {
                            gallery.innerHTML += `<a href="/${e.rutaArchivo}" target="_blank" class="ticket-evidence-item ticket-evidence-file"><i class="fas fa-file"></i><span>${e.nombreArchivo}</span></a>`;
                        }
                    });
                    if (!t.evidencias || t.evidencias.length === 0) {
                        gallery.innerHTML = '<div class="ticket-empty" style="grid-column:1/-1"><i class="fas fa-image"></i><span>Sin evidencias</span></div>';
                    }

                    // History
                    const timeline = document.getElementById('historyTimeline');
                    timeline.innerHTML = '';
                    (t.comentarios || []).forEach(c => {
                        const initials = (c.usuario?.nombre || 'S').substring(0, 2).toUpperCase();
                        timeline.innerHTML += `
                            <div class="ticket-timeline-item">
                                <div class="ticket-timeline-header">
                                    <div class="ticket-timeline-user">
                                        <div class="ticket-timeline-avatar">${initials}</div>
                                        <span class="ticket-timeline-name">${c.usuario?.nombre || 'Sistema'}</span>
                                    </div>
                                    <span class="ticket-timeline-date">${new Date(c.fechaComentario).toLocaleString('es-MX')}</span>
                                </div>
                                ${c.estatus?.descripcion ? '<span class="ticket-timeline-status">' + c.estatus.descripcion + '</span>' : ''}
                                <p class="ticket-timeline-text">${c.comentario}</p>
                            </div>`;
                    });
                    if (!t.comentarios || t.comentarios.length === 0) {
                        timeline.innerHTML = '<div class="ticket-empty"><i class="fas fa-comments"></i><span>Sin actividad aún</span></div>';
                    }

                    // IDs for forms
                    document.getElementById('commentTicketId').value = t.id_Ticket;
                    document.getElementById('evidenceTicketId').value = t.id_Ticket;
                    const mgmtId = document.getElementById('mgmtTicketId');
                    if (mgmtId) mgmtId.value = t.id_Ticket;

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
