@extends('layouts.app')
@section('title', 'Calendario de Mantenimientos - HelpDesk')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.css" rel="stylesheet">
<style>
    .fc { font-family: var(--font); }
    .fc .fc-toolbar-title { font-size: 17px; font-weight: 600; color: var(--text); }
    .fc .fc-button { background: var(--surface); border: 1px solid var(--border); color: var(--text); border-radius: var(--radius-sm); font-size: 12px; font-weight: 500; padding: 5px 10px; transition: all 0.15s; }
    .fc .fc-button:hover { background: var(--primary-soft); border-color: var(--primary); color: var(--primary); }
    .fc .fc-button-active { background: var(--primary) !important; border-color: var(--primary) !important; color: #fff !important; box-shadow: 0 1px 2px rgba(37,99,235,0.2); }
    .fc .fc-button:focus { box-shadow: 0 0 0 2px var(--primary-soft); }
    .fc .fc-daygrid-day-number { font-size: 12px; padding: 5px 8px; color: var(--text); font-weight: 500; }
    .fc .fc-col-header-cell-cushion { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: var(--muted); padding: 8px 0; }
    .fc .fc-daygrid-day { border: 1px solid var(--border); transition: background 0.1s; }
    .fc .fc-daygrid-day:hover { background: var(--primary-soft); }
    .fc .fc-event { border-radius: 4px; padding: 2px 6px; font-size: 11px; font-weight: 500; cursor: pointer; border: none; transition: transform 0.1s, box-shadow 0.1s; }
    .fc .fc-event:hover { transform: translateY(-1px); box-shadow: 0 2px 4px rgba(0,0,0,0.15); }
    .fc .fc-day-today { background: var(--primary-soft) !important; }
    .fc .fc-scrollgrid { border-color: var(--border); border-radius: var(--radius); overflow: hidden; }

    .stat-card { display: flex; align-items: center; gap: 14px; padding: 16px 20px; border-radius: var(--radius); background: var(--surface); border: 1px solid var(--border); transition: box-shadow 0.15s; }
    .stat-card:hover { box-shadow: var(--shadow-md); }
    .stat-icon { width: 42px; height: 42px; border-radius: var(--radius-sm); display: grid; place-items: center; font-size: 18px; flex-shrink: 0; }
    .stat-icon.pendiente { background: rgba(217, 119, 6, 0.1); color: #d97706; }
    .stat-icon.vencido { background: rgba(220, 38, 38, 0.1); color: #dc2626; }
    .stat-icon.completado { background: rgba(5, 150, 105, 0.1); color: #059669; }
    .stat-icon.total { background: var(--primary-soft); color: var(--primary); }
    .stat-info span { display: block; font-size: 11px; font-weight: 500; color: var(--muted); text-transform: uppercase; letter-spacing: 0.04em; }
    .stat-info strong { display: block; font-size: 22px; font-weight: 700; color: var(--text); line-height: 1.1; }

    .legend { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 16px; padding: 10px 14px; background: var(--surface-strong); border-radius: var(--radius-sm); }
    .legend-item { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 500; color: var(--muted); }
    .legend-dot { width: 8px; height: 8px; border-radius: 2px; }

    .modal-detail { padding: 0; }
    .modal-detail dt { font-size: 11px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 2px; }
    .modal-detail dd { font-size: 14px; font-weight: 500; color: var(--text); margin-bottom: 14px; }

    .modal-content { border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; }
    .modal-header { padding: 20px 24px; border-bottom: 1px solid var(--border); }
    .modal-body { padding: 24px; }
    .modal-footer { padding: 16px 24px; border-top: 1px solid var(--border); background: var(--surface-strong); }

    .detail-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .detail-badge.pendiente { background: rgba(217, 119, 6, 0.1); color: #d97706; }
    .detail-badge.vencido { background: rgba(220, 38, 38, 0.1); color: #dc2626; }
    .detail-badge.completado { background: rgba(5, 150, 105, 0.1); color: #059669; }

    .detail-field { padding: 12px 14px; background: var(--surface-strong); border-radius: var(--radius-sm); margin-bottom: 10px; }
    .detail-field-label { font-size: 11px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 4px; }
    .detail-field-value { font-size: 14px; font-weight: 500; color: var(--text); }

    .equipo-search-wrap { position: relative; }
    .equipo-search-results { position: absolute; top: 100%; left: 0; right: 0; z-index: 1050; background: var(--surface); border: 1px solid var(--border); border-radius: 0 0 var(--radius-sm) var(--radius-sm); max-height: 240px; overflow-y: auto; display: none; box-shadow: var(--shadow-md); }
    .equipo-search-results.show { display: block; }
    .equipo-search-item { padding: 10px 14px; cursor: pointer; border-bottom: 1px solid var(--border); transition: background 0.1s; }
    .equipo-search-item:last-child { border-bottom: none; }
    .equipo-search-item:hover { background: var(--primary-soft); }
    .equipo-search-item-name { font-size: 13px; font-weight: 600; color: var(--text); }
    .equipo-search-item-detail { font-size: 11px; color: var(--muted); margin-top: 2px; }
    .equipo-search-selected { display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: var(--primary-soft); border: 1px solid var(--primary); border-radius: var(--radius-sm); margin-top: 6px; }
    .equipo-search-selected-name { font-size: 13px; font-weight: 600; color: var(--primary); }
    .equipo-search-selected-detail { font-size: 11px; color: var(--muted); }
    .equipo-search-selected .btn-remove-equipo { margin-left: auto; background: none; border: none; color: var(--muted); cursor: pointer; font-size: 14px; padding: 2px 6px; border-radius: 4px; }
    .equipo-search-selected .btn-remove-equipo:hover { color: #dc2626; background: rgba(220,38,38,0.1); }

    .create-form-section { margin-bottom: 20px; }
    .create-form-section-title { font-size: 11px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid var(--border); }
    .form-floating-label { font-size: 13px; font-weight: 500; color: var(--text); margin-bottom: 6px; display: block; }

    .reporte-alert { background: rgba(217, 119, 6, 0.1); color: #d97706; border: 1px solid rgba(217, 119, 6, 0.3); padding: 10px 14px; border-radius: var(--radius-sm); font-size: 12px; font-weight: 500; display: none; align-items: center; gap: 8px; margin-top: 12px; }
    .reporte-alert.show { display: flex; }

    @media (max-width: 768px) {
        .page-header { flex-direction: column; align-items: stretch !important; gap: 10px; }
        .page-header .d-flex { justify-content: flex-end; }
        .fc .fc-toolbar { flex-direction: column; gap: 8px; }
        .fc .fc-toolbar-row { display: flex; justify-content: space-between; width: 100%; }
        .fc .fc-toolbar-chunk { display: flex; gap: 4px; }
        .fc .fc-button { padding: 4px 7px; font-size: 11px; }
        .fc .fc-daygrid-day-number { font-size: 10px; padding: 3px 5px; }
        .fc .fc-col-header-cell-cushion { font-size: 9px; padding: 5px 0; }
        .fc .fc-event { font-size: 9px; padding: 1px 3px; }
        .stat-card { padding: 12px 14px; }
        .stat-info strong { font-size: 18px; }
        .legend { gap: 10px; padding: 8px 12px; }
        .legend-item { font-size: 11px; }
        .modal-footer { flex-wrap: wrap; gap: 6px; }
        .modal-footer .btn { flex: 1; min-width: 0; }
    }

    @media (max-width: 480px) {
        .fc .fc-button { padding: 3px 5px; font-size: 10px; }
        .fc .fc-daygrid-day-number { font-size: 9px; padding: 2px 4px; }
        .fc .fc-event { font-size: 8px; padding: 1px 3px; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    }
</style>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 page-header">
    <h4 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Calendario de Mantenimientos</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('inventario.mantenimientos.reporte-general') }}" target="_blank" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-file-pdf me-1"></i><span class="d-none d-sm-inline">Reporte General</span><span class="d-sm-none">Reporte</span>
        </a>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="fas fa-plus me-1"></i><span class="d-none d-sm-inline">Nuevo Mantenimiento</span><span class="d-sm-none">Nuevo</span>
        </button>
    </div>
</div>

@php
    $pendientes = \App\Models\Mantenimiento::where('activo', true)->whereNull('fechaEjecucion')->where('fechaProgramada', '>=', now())->count();
    $vencidos = \App\Models\Mantenimiento::where('activo', true)->whereNull('fechaEjecucion')->where('fechaProgramada', '<', now())->count();
    $completados = \App\Models\Mantenimiento::where('activo', true)->whereNotNull('fechaEjecucion')->count();
    $total = \App\Models\Mantenimiento::where('activo', true)->count();
@endphp

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon pendiente"><i class="fas fa-clock"></i></div>
            <div class="stat-info">
                <span>Pendientes</span>
                <strong>{{ $pendientes }}</strong>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon vencido"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-info">
                <span>Vencidos</span>
                <strong>{{ $vencidos }}</strong>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon completado"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <span>Completados</span>
                <strong>{{ $completados }}</strong>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon total"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-info">
                <span>Total</span>
                <strong>{{ $total }}</strong>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-floating-label">Buscar equipo o usuario</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text" style="border-radius:var(--radius-sm) 0 0 var(--radius-sm);background:var(--surface);border-color:var(--border)"><i class="fas fa-search" style="color:var(--muted);font-size:13px"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Escribe nombre del equipo o usuario..." style="border-radius:0 var(--radius-sm) var(--radius-sm) 0;font-size:13px">
                    <button type="button" class="btn btn-primary btn-sm" id="btnBuscar" style="border-radius:0 var(--radius-sm) var(--radius-sm) 0">
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
                <div id="searchResults" class="mt-1" style="display:none"></div>
            </div>
            <div class="col-6 col-sm-4 col-md-2">
                <label class="form-floating-label">Técnico</label>
                <select id="filterTecnico" class="form-select form-select-sm" style="border-radius:var(--radius-sm)">
                    <option value="">Todos</option>
                    @foreach($tecnicos as $t)
                    <option value="{{ $t->id_Usuario }}">{{ $t->nombre }} {{ $t->app }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-4 col-md-2">
                <label class="form-floating-label">Equipo</label>
                <select id="filterEquipo" class="form-select form-select-sm" style="border-radius:var(--radius-sm)">
                    <option value="">Todos</option>
                    @foreach(\App\Models\Equipo::where('activo', true)->orderBy('nombreEquipo')->get() as $eq)
                    <option value="{{ $eq->id_Equipo }}">{{ $eq->nombreEquipo ?? $eq->marca }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-4 col-md-2">
                <label class="form-floating-label">Estado</label>
                <select id="filterEstado" class="form-select form-select-sm" style="border-radius:var(--radius-sm)">
                    <option value="">Todos</option>
                    <option value="Pendiente">Pendiente</option>
                    <option value="Vencido">Vencido</option>
                    <option value="Completado">Completado</option>
                </select>
            </div>
            <div class="col-6 col-md-1">
                <label class="form-floating-label">Tipo</label>
                <select id="filterTipo" class="form-select form-select-sm" style="border-radius:var(--radius-sm)">
                    <option value="">Todos</option>
                    <option value="Preventivo">Prev.</option>
                    <option value="Correctivo">Correc.</option>
                </select>
            </div>
            <div class="col-6 col-md-1">
                <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="btnLimpiarFiltros" title="Limpiar filtros">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="legend">
            <div class="legend-item"><div class="legend-dot" style="background:#d97706"></div>Pendiente</div>
            <div class="legend-item"><div class="legend-dot" style="background:#dc2626"></div>Vencido</div>
            <div class="legend-item"><div class="legend-dot" style="background:#059669"></div>Completado</div>
        </div>
        <div id="calendar"></div>
    </div>
</div>

<!-- Modal Detalle -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" style="font-size:16px;font-weight:700"><i class="fas fa-wrench me-2" style="color:var(--primary)"></i>Detalle de Mantenimiento</h5>
                    <div id="detailBadgeContainer" class="mt-1"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="detail-field">
                    <div class="detail-field-label">Equipo</div>
                    <div class="detail-field-value" id="detailEquipo"></div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <div class="detail-field">
                            <div class="detail-field-label">Usuario Asignado</div>
                            <div class="detail-field-value" id="detailUsuario"></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="detail-field">
                            <div class="detail-field-label">Técnico</div>
                            <div class="detail-field-value" id="detailTecnico"></div>
                        </div>
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <div class="detail-field">
                            <div class="detail-field-label">Tipo</div>
                            <div class="detail-field-value" id="detailTipo"></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="detail-field">
                            <div class="detail-field-label">Fecha Ejecución</div>
                            <div class="detail-field-value" id="detailEjecucion"></div>
                        </div>
                    </div>
                </div>
                <div class="detail-field">
                    <div class="detail-field-label">Observaciones</div>
                    <div class="detail-field-value" id="detailObs"></div>
                </div>

                <div id="reporteAlert" class="reporte-alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Este mantenimiento está completado pero el reporte aún no se ha llenado.</span>
                </div>

                <div id="seccionFecha" style="display:none;border-top:1px solid var(--border);margin:20px 0 16px;padding-top:16px">
                    <div class="form-floating-label">Cambiar fecha programada</div>
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-sm-7">
                            <input type="date" id="editFecha" class="form-control form-control-sm" style="border-radius:var(--radius-sm)">
                        </div>
                        <div class="col-12 col-sm-5">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="editPropagar" checked>
                                <label class="form-check-label" for="editPropagar" style="font-size:12px">Propagar a futuros</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-outline-danger btn-sm" id="btnEliminar">
                    <i class="fas fa-trash me-1"></i>Eliminar
                </button>
                <a href="#" class="btn btn-outline-primary btn-sm" id="btnReporte">
                    <i class="fas fa-file-pdf me-1"></i>Llenar Reporte
                </a>
                <a href="#" class="btn btn-success btn-sm" id="btnDescargarReporte" target="_blank" style="display:none">
                    <i class="fas fa-download me-1"></i>Descargar Reporte
                </a>
                <button type="button" class="btn btn-success btn-sm" id="btnCompletar">
                    <i class="fas fa-check me-1"></i>Marcar Completado
                </button>
                <button type="button" class="btn btn-outline-warning btn-sm" id="btnReagendar">
                    <i class="fas fa-calendar-alt me-1"></i>Reagendar
                </button>
                <button type="button" class="btn btn-primary btn-sm" id="btnGuardarFecha" style="display:none">
                    <i class="fas fa-save me-1"></i>Guardar Fecha
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <form method="POST" action="{{ route('inventario.mantenimientos.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header" style="background:var(--primary);border-bottom:none">
                    <div>
                        <h5 class="modal-title" style="font-size:16px;font-weight:700;color:#fff"><i class="fas fa-wrench me-2"></i>Nuevo Mantenimiento</h5>
                        <p style="font-size:12px;color:rgba(255,255,255,0.7);margin:4px 0 0">Programa un mantenimiento preventivo o correctivo</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="create-form-section">
                        <div class="create-form-section-title">Asignación</div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-floating-label">Equipo *</label>
                                <input type="hidden" name="id_Equipo" id="equipoId" required>
                                <div class="equipo-search-wrap">
                                    <input type="text" id="equipoSearchInput" class="form-control form-control-sm" placeholder="Buscar por nombre, usuario o área..." autocomplete="off" style="border-radius:var(--radius-sm)">
                                    <div class="equipo-search-results" id="equipoSearchResults"></div>
                                </div>
                                <div id="equipoSeleccionado" style="display:none"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-floating-label">Técnico asignado *</label>
                                <select name="id_UsuarioTecnico" class="form-select form-select-sm" required style="border-radius:var(--radius-sm)">
                                    <option value="">Seleccionar técnico...</option>
                                    @foreach($tecnicos as $t)
                                    <option value="{{ $t->id_Usuario }}">{{ $t->nombre }} {{ $t->app }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="create-form-section">
                        <div class="create-form-section-title">Programación</div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-floating-label">Fecha y hora *</label>
                                <input type="datetime-local" name="fechaProgramada" class="form-control form-control-sm" required style="border-radius:var(--radius-sm)">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-floating-label">Tipo de mantenimiento *</label>
                                <select name="tipoMantenimiento" class="form-select form-select-sm" required style="border-radius:var(--radius-sm)">
                                    <option value="Preventivo">Preventivo</option>
                                    <option value="Correctivo">Correctivo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="create-form-section" style="margin-bottom:0">
                        <div class="create-form-section-title">Detalles</div>
                        <label class="form-floating-label">Observaciones</label>
                        <textarea name="observaciones" class="form-control form-control-sm" rows="2" placeholder="Notas sobre el mantenimiento..." style="border-radius:var(--radius-sm)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Guardar Mantenimiento
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.9/locales/es.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    let selectedEvent = null;

    const equipoSearchInput = document.getElementById('equipoSearchInput');
    const equipoSearchResults = document.getElementById('equipoSearchResults');
    const equipoIdInput = document.getElementById('equipoId');
    const equipoSeleccionado = document.getElementById('equipoSeleccionado');
    let equipoSearchTimeout = null;

    equipoSearchInput.addEventListener('input', function() {
        clearTimeout(equipoSearchTimeout);
        const termino = this.value.trim();

        if (termino.length < 2) {
            equipoSearchResults.classList.remove('show');
            return;
        }

        equipoSearchTimeout = setTimeout(function() {
            fetch('{{ route("inventario.mantenimientos.buscar-equipo") }}?q=' + encodeURIComponent(termino))
                .then(r => r.json())
                .then(data => {
                    if (data.length === 0) {
                        equipoSearchResults.innerHTML = '<div class="equipo-search-item"><div class="equipo-search-item-name" style="color:var(--muted)">No se encontraron equipos</div></div>';
                        equipoSearchResults.classList.add('show');
                        return;
                    }

                    let html = '';
                    data.forEach(function(eq) {
                        html += '<div class="equipo-search-item" data-id="' + eq.id + '" data-nombre="' + eq.nombre + '" data-marca="' + eq.marca_modelo + '" data-usuario="' + eq.usuario + '" data-area="' + eq.area + '">';
                        html += '<div class="equipo-search-item-name">' + eq.nombre + '</div>';
                        html += '<div class="equipo-search-item-detail">' + eq.marca_modelo + ' · ' + eq.usuario + ' · ' + eq.area + '</div>';
                        html += '</div>';
                    });
                    equipoSearchResults.innerHTML = html;
                    equipoSearchResults.classList.add('show');

                    equipoSearchResults.querySelectorAll('.equipo-search-item[data-id]').forEach(function(el) {
                        el.addEventListener('click', function() {
                            const id = this.getAttribute('data-id');
                            const nombre = this.getAttribute('data-nombre');
                            const marca = this.getAttribute('data-marca');
                            const usuario = this.getAttribute('data-usuario');
                            const area = this.getAttribute('data-area');

                            equipoIdInput.value = id;
                            equipoSearchInput.value = '';
                            equipoSearchResults.classList.remove('show');

                            equipoSeleccionado.style.display = 'block';
                            equipoSeleccionado.innerHTML = '<div class="equipo-search-selected"><div><div class="equipo-search-selected-name">' + nombre + '</div><div class="equipo-search-selected-detail">' + marca + ' · ' + usuario + ' · ' + area + '</div></div><button type="button" class="btn-remove-equipo" id="btnRemoveEquipo"><i class="fas fa-times"></i></button></div>';

                            document.getElementById('btnRemoveEquipo').addEventListener('click', function() {
                                equipoIdInput.value = '';
                                equipoSeleccionado.style.display = 'none';
                                equipoSeleccionado.innerHTML = '';
                            });
                        });
                    });
                });
        }, 300);
    });

    equipoSearchInput.addEventListener('blur', function() {
        setTimeout(function() {
            equipoSearchResults.classList.remove('show');
        }, 200);
    });

    document.getElementById('createModal').addEventListener('hidden.bs.modal', function() {
        equipoIdInput.value = '';
        equipoSearchInput.value = '';
        equipoSeleccionado.style.display = 'none';
        equipoSeleccionado.innerHTML = '';
        equipoSearchResults.classList.remove('show');
    });

    function getInitialView() {
        return window.innerWidth <= 768 ? 'dayGridWeek' : 'dayGridMonth';
    }

    function getHeaderToolbar() {
        if (window.innerWidth <= 480) {
            return { left: 'prev,next', center: 'title', right: 'today' };
        }
        return { left: 'prev,next today', center: 'title', right: 'dayGridMonth,dayGridWeek' };
    }

    const calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'es',
        initialView: getInitialView(),
        headerToolbar: getHeaderToolbar(),
        height: window.innerWidth <= 768 ? 'auto' : 620,
        events: function(info, successCallback) {
            const params = new URLSearchParams({
                start: info.startStr,
                end: info.endStr
            });

            const tecnico = document.getElementById('filterTecnico').value;
            const equipo = document.getElementById('filterEquipo').value;
            const tipo = document.getElementById('filterTipo').value;
            const estado = document.getElementById('filterEstado').value;

            if (tecnico) params.append('tecnico', tecnico);
            if (equipo) params.append('equipo', equipo);
            if (tipo) params.append('tipo', tipo);
            if (estado) params.append('estado', estado);

            fetch('{{ route("inventario.mantenimientos.api") }}?' + params.toString())
                .then(r => {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.json();
                })
                .then(data => successCallback(data))
                .catch(err => {
                    // No tragar el error en silencio: se registra para poder diagnosticar
                    // por que el calendario no muestra eventos.
                    console.error('Error cargando eventos del calendario:', err);
                    successCallback([]);
                });
        },
        eventClick: function(info) {
            selectedEvent = info.event;
            const props = info.event.extendedProps;

            document.getElementById('detailEquipo').textContent = props.equipo;
            document.getElementById('detailUsuario').textContent = props.usuarioAsignado;
            document.getElementById('detailTipo').textContent = props.tipo;
            document.getElementById('detailTecnico').textContent = props.tecnico;
            document.getElementById('detailEjecucion').textContent = props.fechaEjecucion || 'Pendiente';
            document.getElementById('detailObs').textContent = props.observaciones || 'Sin observaciones';

            document.getElementById('btnReporte').href = '/inventario/mantenimientos/' + selectedEvent.id + '/editar-reporte';

            const badgeContainer = document.getElementById('detailBadgeContainer');
            const badgeClass = props.estado === 'Completado' ? 'completado' : (props.estado === 'Vencido' ? 'vencido' : 'pendiente');
            const badgeIcon = props.estado === 'Completado' ? 'fa-check-circle' : (props.estado === 'Vencido' ? 'fa-exclamation-triangle' : 'fa-clock');
            badgeContainer.innerHTML = '<span class="detail-badge ' + badgeClass + '"><i class="fas ' + badgeIcon + '"></i>' + props.estado + '</span>';

            const reporteAlert = document.getElementById('reporteAlert');
            const btnReporte = document.getElementById('btnReporte');
            const btnDescargar = document.getElementById('btnDescargarReporte');
            const btnReagendar = document.getElementById('btnReagendar');
            if (props.reportePendiente) {
                reporteAlert.classList.add('show');
                btnReporte.classList.remove('btn-outline-primary');
                btnReporte.classList.add('btn-warning');
                btnReporte.innerHTML = '<i class="fas fa-file-pdf me-1"></i>Llenar Reporte';
                btnDescargar.style.display = 'none';
                btnReagendar.style.display = '';
            } else {
                reporteAlert.classList.remove('show');
                btnReporte.classList.remove('btn-warning');
                btnReporte.classList.add('btn-outline-primary');
                btnReporte.innerHTML = '<i class="fas fa-file-pdf me-1"></i>Ver Reporte';
                btnDescargar.href = '/inventario/mantenimientos/' + selectedEvent.id + '/reporte';
                btnDescargar.style.display = '';
                btnReagendar.style.display = props.reporteCerrado ? 'none' : '';
            }

            const fechaInput = document.getElementById('editFecha');
            fechaInput.value = info.event.start.toISOString().split('T')[0];

            const btnCompletar = document.getElementById('btnCompletar');
            btnCompletar.style.display = props.estado === 'Completado' ? 'none' : '';
            btnCompletar.onclick = function() {
                if (confirm('¿Marcar este mantenimiento como completado?')) {
                    fetch('/inventario/mantenimientos/' + selectedEvent.id + '/completar', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json'
                        }
                    }).then(r => r.json()).then(data => {
                        if (data.success) {
                            bootstrap.Modal.getInstance(document.getElementById('detailModal')).hide();
                            calendar.refetchEvents();
                        }
                    });
                }
            };

            const btnEliminar = document.getElementById('btnEliminar');
            btnEliminar.onclick = function() {
                Swal.fire({
                    title: '¿Eliminar mantenimiento?',
                    text: 'Esta acción no se puede deshacer.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('/inventario/mantenimientos/' + selectedEvent.id, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json'
                            }
                        }).then(r => r.json()).then(data => {
                            if (data.success) {
                                bootstrap.Modal.getInstance(document.getElementById('detailModal')).hide();
                                calendar.refetchEvents();
                                Swal.fire('Eliminado', 'El mantenimiento fue eliminado correctamente.', 'success');
                            }
                        });
                    }
                });
            };

            new bootstrap.Modal(document.getElementById('detailModal')).show();

            document.getElementById('seccionFecha').style.display = 'none';
            document.getElementById('btnGuardarFecha').style.display = 'none';
            document.getElementById('btnReagendar').innerHTML = '<i class="fas fa-calendar-alt me-1"></i>Reagendar';
        },
        eventDidMount: function(info) {
            info.el.title = info.event.title + ' - ' + info.event.extendedProps.estado;
        }
    });

    calendar.render();

    document.getElementById('btnReagendar').addEventListener('click', function() {
        const seccionFecha = document.getElementById('seccionFecha');
        const btnGuardar = document.getElementById('btnGuardarFecha');
        const visible = seccionFecha.style.display !== 'none';
        seccionFecha.style.display = visible ? 'none' : 'block';
        btnGuardar.style.display = visible ? 'none' : '';
        this.textContent = visible ? 'Reagendar' : 'Cancelar';
    });

    window.addEventListener('resize', function() {
        calendar.changeView(getInitialView());
        calendar.setOption('headerToolbar', getHeaderToolbar());
        calendar.setOption('height', window.innerWidth <= 768 ? 'auto' : 620);
    });

    document.getElementById('btnGuardarFecha').addEventListener('click', function() {
        if (!selectedEvent) return;

        const nuevaFecha = document.getElementById('editFecha').value;
        const propagar = document.getElementById('editPropagar').checked;

        if (!nuevaFecha) {
            alert('Selecciona una fecha');
            return;
        }

        if (!confirm('¿Cambiar la fecha de este mantenimiento' + (propagar ? ' y todos los futuros de este equipo' : '') + '?')) {
            return;
        }

        fetch('/inventario/mantenimientos/' + selectedEvent.id + '/cambiar-fecha', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                nueva_fecha: nuevaFecha,
                propagar: propagar
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('detailModal')).hide();
                calendar.refetchEvents();
                alert(data.message);
            } else {
                alert(data.message || 'Error al guardar');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error de conexión');
        });
    });

    ['filterTecnico', 'filterEquipo', 'filterEstado', 'filterTipo'].forEach(function(id) {
        document.getElementById(id).addEventListener('change', function() {
            calendar.refetchEvents();
        });
    });

    document.getElementById('btnLimpiarFiltros').addEventListener('click', function() {
        document.getElementById('searchInput').value = '';
        document.getElementById('searchResults').style.display = 'none';
        document.getElementById('filterTecnico').value = '';
        document.getElementById('filterEquipo').value = '';
        document.getElementById('filterEstado').value = '';
        document.getElementById('filterTipo').value = '';
        calendar.refetchEvents();
    });

    let searchTimeout = null;

    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const termino = this.value.trim();
        const resultsDiv = document.getElementById('searchResults');

        if (termino.length < 2) {
            resultsDiv.style.display = 'none';
            return;
        }

        searchTimeout = setTimeout(function() {
            fetch('{{ route("inventario.mantenimientos.buscar") }}?q=' + encodeURIComponent(termino))
                .then(r => r.json())
                .then(data => {
                    if (data.length === 0) {
                        resultsDiv.innerHTML = '<div style="padding:8px 12px;font-size:12px;color:var(--muted);background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-sm)">No se encontraron resultados</div>';
                        resultsDiv.style.display = 'block';
                        return;
                    }

                    let html = '<div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-sm);max-height:200px;overflow-y:auto">';
                    data.forEach(function(item) {
                        html += '<div class="search-result-item" data-fecha="' + item.fecha_iso + '" style="padding:8px 12px;cursor:pointer;border-bottom:1px solid var(--border);font-size:13px;transition:background 0.1s" onmouseover="this.style.background=\'var(--primary-soft)\'" onmouseout="this.style.background=\'transparent\'">';
                        html += '<div style="font-weight:600;color:var(--text)">' + item.equipo + '</div>';
                        html += '<div style="font-size:11px;color:var(--muted)">' + item.tecnico + ' · ' + item.fecha + ' · ' + item.tipo + '</div>';
                        html += '</div>';
                    });
                    html += '</div>';
                    resultsDiv.innerHTML = html;
                    resultsDiv.style.display = 'block';

                    resultsDiv.querySelectorAll('.search-result-item').forEach(function(el) {
                        el.addEventListener('click', function() {
                            const fecha = this.getAttribute('data-fecha');
                            calendar.gotoDate(fecha);
                            document.getElementById('searchInput').value = '';
                            resultsDiv.style.display = 'none';
                        });
                    });
                });
        }, 300);
    });

    document.getElementById('btnBuscar').addEventListener('click', function() {
        const termino = document.getElementById('searchInput').value.trim();
        if (termino.length < 2) return;

        fetch('{{ route("inventario.mantenimientos.buscar") }}?q=' + encodeURIComponent(termino))
            .then(r => r.json())
            .then(data => {
                if (data.length > 0) {
                    calendar.gotoDate(data[0].fecha_iso);
                    document.getElementById('searchInput').value = '';
                    document.getElementById('searchResults').style.display = 'none';
                } else {
                    alert('No se encontraron mantenimientos para "' + termino + '"');
                }
            });
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('#searchInput') && !e.target.closest('#searchResults') && !e.target.closest('#btnBuscar')) {
            document.getElementById('searchResults').style.display = 'none';
        }
    });
});
</script>
@endsection
