@extends('layouts.app')
@section('title', 'Panel - HelpDesk')

@section('styles')
<style>
    :root { --space-2: 8px; --space-3: 12px; --space-4: 16px; }

    .page-head { display: flex; align-items: flex-end; justify-content: space-between; gap: var(--space-3); flex-wrap: wrap; margin-bottom: var(--space-4); }
    .page-head h1 { font-size: 20px; font-weight: 700; letter-spacing: -0.01em; margin: 0; text-wrap: balance; }
    .page-head .sub { font-size: 12px; color: var(--muted); margin-top: 2px; }

    /* KPI */
    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: var(--space-3); }
    .kpi { --kpi-accent: var(--primary); --kpi-tint: var(--primary-soft); display: flex; align-items: center; gap: var(--space-3); padding: var(--space-4); background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow-sm); min-height: 82px; transition: transform 0.15s ease, box-shadow 0.15s ease; }
    .kpi:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
    .kpi-icon { width: 42px; height: 42px; flex-shrink: 0; border-radius: var(--radius-sm); display: grid; place-items: center; background: var(--kpi-tint); color: var(--kpi-accent); font-size: 16px; }
    .kpi-value { font-size: 26px; font-weight: 700; line-height: 1.05; letter-spacing: -0.02em; color: var(--text); font-variant-numeric: tabular-nums; }
    .kpi-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); margin-top: 2px; }
    .kpi-sub { font-size: 11px; color: var(--muted); margin-top: 2px; }

    .kpi-blue { --kpi-accent: #2563eb; --kpi-tint: rgba(37, 99, 235, 0.10); }
    .kpi-amber { --kpi-accent: #d97706; --kpi-tint: rgba(217, 119, 6, 0.12); }
    .kpi-green { --kpi-accent: #059669; --kpi-tint: rgba(5, 150, 105, 0.12); }
    .kpi-slate { --kpi-accent: #64748b; --kpi-tint: rgba(100, 116, 139, 0.12); }
    .kpi-red { --kpi-accent: #dc2626; --kpi-tint: rgba(220, 38, 38, 0.12); }
    .kpi-cyan { --kpi-accent: #0891b2; --kpi-tint: rgba(8, 145, 178, 0.12); }
    body.dark-mode .kpi-blue { --kpi-accent: #60a5fa; --kpi-tint: rgba(96, 165, 250, 0.15); }
    body.dark-mode .kpi-amber { --kpi-accent: #fbbf24; --kpi-tint: rgba(251, 191, 36, 0.16); }
    body.dark-mode .kpi-green { --kpi-accent: #34d399; --kpi-tint: rgba(52, 211, 153, 0.16); }
    body.dark-mode .kpi-slate { --kpi-accent: #94a3b8; --kpi-tint: rgba(148, 163, 184, 0.16); }
    body.dark-mode .kpi-red { --kpi-accent: #f87171; --kpi-tint: rgba(248, 113, 113, 0.16); }
    body.dark-mode .kpi-cyan { --kpi-accent: #22d3ee; --kpi-tint: rgba(34, 211, 238, 0.16); }

    /* Tabs (links) */
    .nav-tabs { border-bottom: 1px solid var(--border); gap: 4px; }
    .nav-tabs .nav-link { border: none; color: var(--muted); font-weight: 600; font-size: 13px; padding: 10px 16px; border-radius: var(--radius-sm) var(--radius-sm) 0 0; }
    .nav-tabs .nav-link:hover { color: var(--text); background: var(--primary-soft); }
    .nav-tabs .nav-link.active { color: var(--primary); background: transparent; border-bottom: 2px solid var(--primary); }

    /* Filter bar */
    .filter-bar { border: 1px solid var(--border); border-radius: var(--radius); background: var(--surface); padding: var(--space-3) var(--space-4); margin-bottom: var(--space-4); }
    .filter-bar .form-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: var(--muted); font-weight: 600; margin-bottom: 4px; }
    .filter-bar .form-control, .filter-bar .form-select { font-size: 13px; }
    .filter-chips .badge { font-weight: 500; }

    .chart-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 180px; color: var(--muted); font-size: 13px; gap: 6px; }
    .chart-empty i { font-size: 22px; opacity: 0.6; }

    @media (max-width: 576px) {
        .nav-tabs { flex-wrap: nowrap; overflow-x: auto; }
        .nav-tabs .nav-link { white-space: nowrap; }
        .kpi-value { font-size: 22px; }
    }
    @media (prefers-reduced-motion: reduce) { .kpi { transition: none; } .kpi:hover { transform: none; } }
</style>
@endsection

@php
    $fmtTiempo = function ($horas) {
        if ($horas === null) return 'N/A';
        return $horas >= 48 ? round($horas / 24, 1) . ' días' : round($horas, 1) . ' h';
    };
    $rol = $esAdmin ? 'Administrador' : ($esTecnico ? 'Técnico' : 'Usuario');

    // Selectores del reporte KPI (mes/año).
    $mesesKpi = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
    $anioKpi = request()->filled('anio') ? request()->integer('anio') : now()->year;
    $mesKpi = request()->filled('mes') ? request()->integer('mes') : now()->month;

    $presets = ['hoy' => 'Hoy', '7d' => 'Últimos 7 días', '30d' => 'Últimos 30 días', 'mes' => 'Este mes', 'anio' => 'Este año', 'todo' => 'Todo', 'custom' => 'Personalizado'];
    $params = request()->except(['tab']);
    $tabUrl = fn($t) => route('dashboard', array_merge($params, ['tab' => $t]));

    // Chips de filtros activos
    $chips = [];
    if (!in_array($filtros['preset'], [], true)) {
        $chips[] = 'Rango: ' . ($presets[$filtros['preset']] ?? $filtros['preset']);
    }
    if ($filtros['tipo']) $chips[] = 'Tipo: ' . optional($catalogos['tiposSoporte']->firstWhere('id_TipoSoporte', $filtros['tipo']))->descripcion;
    if ($filtros['estatus']) $chips[] = 'Estatus: ' . optional($catalogos['estatusTicket']->firstWhere('id_Estatus', $filtros['estatus']))->descripcion;
    // El filtro de técnico está fijo al dueño de la sesión (el admin no puede abrir otro).
    if ($esAdmin) {
        $chips[] = ($esTecnico ? 'Técnico: ' : 'Creador: ') . ($usuario ? $usuario->nombreCompleto() : 'Sesión') . ' (fijo)';
    } elseif ($filtros['tecnico']) {
        $t = $catalogos['tecnicos']->firstWhere('id_Usuario', $filtros['tecnico']);
        $chips[] = 'Técnico: ' . ($t ? $t->nombre . ' ' . $t->app : $filtros['tecnico']);
    }
    if ($filtros['depto']) $chips[] = 'Depto: ' . optional($catalogos['deptos']->firstWhere('id_Depto', $filtros['depto']))->nombre;
    if ($filtros['area']) $chips[] = 'Área: ' . optional($catalogos['areas']->firstWhere('id_Area', $filtros['area']))->nombre;
    $chips = array_values(array_filter($chips));
@endphp

@section('content')
<div class="page-head">
    <div>
        <h1>Panel</h1>
        <div class="sub">
            {{ $rol }}@if($usuario) · {{ $usuario->nombreCompleto() }}@endif
            @if($esAdmin) · Mis tickets (sesión) @endif
            · {{ now()->format('d/m/Y') }}
        </div>
    </div>
    {{-- Descarga de reportes KPI: cada área ve el suyo (ver config/kpi.php). --}}
    @if($puedeDescargarKpi || $puedeDescargarKpiDesarrollo || $puedeDescargarKpiGps)
        <div class="d-flex align-items-end gap-3 flex-wrap">
        @if($puedeDescargarKpi)
            <form action="{{ route('dashboard.reporte-kpi') }}" method="GET" class="d-flex align-items-end gap-2 flex-wrap" aria-label="Descargar reporte KPI Tickets">
                <div>
                    <label for="kpiMes" class="form-label mb-1" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: var(--muted); font-weight: 600;">Mes</label>
                    <select name="mes" id="kpiMes" class="form-select form-select-sm">
                        @foreach($mesesKpi as $num => $nombre)
                            <option value="{{ $num }}" @selected($num === $mesKpi)>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="kpiAnio" class="form-label mb-1" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: var(--muted); font-weight: 600;">Año</label>
                    <select name="anio" id="kpiAnio" class="form-select form-select-sm">
                        @for($y = now()->year; $y >= 2020; $y--)
                            <option value="{{ $y }}" @selected($y === $anioKpi)>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm" title="Descargar reporte KPI Tickets en Excel">
                    <i class="fas fa-file-excel me-1" aria-hidden="true"></i>Descargar KPI
                </button>
            </form>
        @endif
        @if($puedeDescargarKpiDesarrollo)
            <form action="{{ route('dashboard.reporte-kpi-desarrollo') }}" method="GET" class="d-flex align-items-end gap-2 flex-wrap" aria-label="Descargar reporte KPI Desarrollo">
                <div>
                    <label for="kpiMesDes" class="form-label mb-1" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: var(--muted); font-weight: 600;">Mes</label>
                    <select name="mes" id="kpiMesDes" class="form-select form-select-sm">
                        @foreach($mesesKpi as $num => $nombre)
                            <option value="{{ $num }}" @selected($num === $mesKpi)>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="kpiAnioDes" class="form-label mb-1" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: var(--muted); font-weight: 600;">Año</label>
                    <select name="anio" id="kpiAnioDes" class="form-select form-select-sm">
                        @for($y = now()->year; $y >= 2020; $y--)
                            <option value="{{ $y }}" @selected($y === $anioKpi)>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <button type="submit" class="btn btn-info btn-sm text-white" title="Descargar reporte KPI Desarrollo en Excel">
                    <i class="fas fa-file-excel me-1" aria-hidden="true"></i>Descargar KPI Desarrollo
                </button>
            </form>
        @endif
        @if($puedeDescargarKpiGps)
            <form action="{{ route('dashboard.reporte-kpi-gps') }}" method="GET" class="d-flex align-items-end gap-2 flex-wrap" aria-label="Descargar reporte KPI GPS">
                <div>
                    <label for="kpiMesGps" class="form-label mb-1" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: var(--muted); font-weight: 600;">Mes</label>
                    <select name="mes" id="kpiMesGps" class="form-select form-select-sm">
                        @foreach($mesesKpi as $num => $nombre)
                            <option value="{{ $num }}" @selected($num === $mesKpi)>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="kpiAnioGps" class="form-label mb-1" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: var(--muted); font-weight: 600;">Año</label>
                    <select name="anio" id="kpiAnioGps" class="form-select form-select-sm">
                        @for($y = now()->year; $y >= 2020; $y--)
                            <option value="{{ $y }}" @selected($y === $anioKpi)>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary btn-sm" title="Descargar reporte KPI GPS en Excel">
                    <i class="fas fa-gps me-1" aria-hidden="true"></i>Descargar KPI GPS
                </button>
            </form>
        @endif
        </div>
    @endif
</div>

{{-- ───────────── Barra de filtros (solo admin, pestaña Tickets) ───────────── --}}
@if($esAdmin && $tab === 'tickets')
<form method="GET" action="{{ route('dashboard') }}" class="filter-bar" id="filterForm">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <div class="row g-2 align-items-end">
        <div class="col-6 col-md-2">
            <label class="form-label">Rango</label>
            <select name="rango" class="form-select form-select-sm" id="selRango" data-autosubmit>
                @foreach($presets as $k => $v)
                    <option value="{{ $k }}" {{ $filtros['preset'] === $k ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2 {{ $filtros['preset'] === 'custom' ? '' : 'd-none' }}" id="wrapDesde">
            <label class="form-label">Desde</label>
            <input type="date" name="desde" class="form-control form-control-sm" value="{{ request('desde') }}" data-autosubmit>
        </div>
        <div class="col-6 col-md-2 {{ $filtros['preset'] === 'custom' ? '' : 'd-none' }}" id="wrapHasta">
            <label class="form-label">Hasta</label>
            <input type="date" name="hasta" class="form-control form-control-sm" value="{{ request('hasta') }}" data-autosubmit>
        </div>

        @if($tab === 'tickets')
            <div class="col-6 col-md-2">
                <label class="form-label">Tipo de soporte</label>
                <select name="tipo" class="form-select form-select-sm" data-autosubmit>
                    <option value="">Todos</option>
                    @foreach($catalogos['tiposSoporte'] as $o)
                        <option value="{{ $o->id_TipoSoporte }}" {{ (string)$filtros['tipo'] === (string)$o->id_TipoSoporte ? 'selected' : '' }}>{{ $o->descripcion }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Estatus</label>
                <select name="estatus" class="form-select form-select-sm" data-autosubmit>
                    <option value="">Todos</option>
                    @foreach($catalogos['estatusTicket'] as $o)
                        <option value="{{ $o->id_Estatus }}" {{ (string)$filtros['estatus'] === (string)$o->id_Estatus ? 'selected' : '' }}>{{ $o->descripcion }}</option>
                    @endforeach
                </select>
            </div>
            {{-- El filtro de técnico se omite: siempre es el dueño de la sesión --}}
            <div class="col-6 col-md-2">
                <label class="form-label">Departamento</label>
                <select name="depto" class="form-select form-select-sm" data-autosubmit>
                    <option value="">Todos</option>
                    @foreach($catalogos['deptos'] as $o)
                        <option value="{{ $o->id_Depto }}" {{ (string)$filtros['depto'] === (string)$o->id_Depto ? 'selected' : '' }}>{{ $o->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Área</label>
                <select name="area" class="form-select form-select-sm" data-autosubmit>
                    <option value="">Todas</option>
                    @foreach($catalogos['areas'] as $o)
                        <option value="{{ $o->id_Area }}" {{ (string)$filtros['area'] === (string)$o->id_Area ? 'selected' : '' }}>{{ $o->nombre }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="col-12 col-md-auto d-flex gap-2 ms-md-auto">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i>Aplicar</button>
            <a href="{{ route('dashboard', ['tab' => $tab]) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-eraser me-1"></i>Limpiar</a>
        </div>
    </div>

    @if(count($chips))
    <div class="filter-chips mt-2 d-flex flex-wrap gap-1">
        @foreach($chips as $chip)
            <span class="badge bg-secondary">{{ $chip }}</span>
        @endforeach
    </div>
    @endif
</form>
@endif

@if($esAdmin)
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link {{ $tab === 'tickets' ? 'active' : '' }}" href="{{ $tabUrl('tickets') }}"><i class="fas fa-ticket me-1"></i>Tickets</a></li>
        <li class="nav-item"><a class="nav-link {{ $tab === 'equipos' ? 'active' : '' }}" href="{{ $tabUrl('equipos') }}"><i class="fas fa-laptop me-1"></i>Equipos</a></li>
        <li class="nav-item"><a class="nav-link {{ $tab === 'mantenimientos' ? 'active' : '' }}" href="{{ $tabUrl('mantenimientos') }}"><i class="fas fa-calendar-alt me-1"></i>Mantenimientos</a></li>
    </ul>

    @php
        $t = $kpis['tickets'];
        $pe = $t['por_estatus'];
        $cnt = fn($n) => (int) ($pe[$n] ?? 0);
    @endphp

    @if($tab === 'tickets')
        <section aria-label="KPIs de tickets">
            <div class="kpi-grid mb-3">
                <div class="kpi kpi-blue"><div class="kpi-icon"><i class="fas fa-ticket" aria-hidden="true"></i></div><div><div class="kpi-value">{{ $t['total'] }}</div><div class="kpi-label">Total Tickets</div></div></div>
                <div class="kpi kpi-blue"><div class="kpi-icon"><i class="fas fa-folder-open" aria-hidden="true"></i></div><div><div class="kpi-value">{{ $cnt('Abierto') }}</div><div class="kpi-label">Abiertos</div></div></div>
                <div class="kpi kpi-amber"><div class="kpi-icon"><i class="fas fa-spinner" aria-hidden="true"></i></div><div><div class="kpi-value">{{ $cnt('En proceso') }}</div><div class="kpi-label">En Proceso</div></div></div>
                <div class="kpi kpi-green"><div class="kpi-icon"><i class="fas fa-check-circle" aria-hidden="true"></i></div><div><div class="kpi-value">{{ $cnt('Resuelto') }}</div><div class="kpi-label">Resueltos</div></div></div>
                <div class="kpi kpi-slate"><div class="kpi-icon"><i class="fas fa-times-circle" aria-hidden="true"></i></div><div><div class="kpi-value">{{ $cnt('Cerrado') }}</div><div class="kpi-label">Cerrados</div></div></div>
                <div class="kpi kpi-red"><div class="kpi-icon"><i class="fas fa-hourglass-half" aria-hidden="true"></i></div><div><div class="kpi-value">{{ $t['vencidos'] }}</div><div class="kpi-label">Vencidos (&gt;7d)</div></div></div>
                <div class="kpi kpi-green"><div class="kpi-icon"><i class="fas fa-percentage" aria-hidden="true"></i></div><div><div class="kpi-value">{{ $t['tasa_resolucion'] }}%</div><div class="kpi-label">Tasa Resolución</div></div></div>
                <div class="kpi kpi-cyan"><div class="kpi-icon"><i class="fas fa-stopwatch" aria-hidden="true"></i></div><div><div class="kpi-value">{{ $fmtTiempo($t['tiempo_promedio_horas']) }}</div><div class="kpi-label">Tiempo Prom.</div></div></div>
                <div class="kpi kpi-blue"><div class="kpi-icon"><i class="fas fa-calendar-plus" aria-hidden="true"></i></div><div><div class="kpi-value">{{ $t['creados_mes'] }}</div><div class="kpi-label">Creados (mes)</div><div class="kpi-sub">{{ $t['creados_semana'] }} sem · {{ $t['creados_hoy'] }} hoy</div></div></div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4 mb-3"><div class="card h-100"><div class="card-header"><i class="fas fa-chart-pie me-2" aria-hidden="true"></i>Tickets por Estatus</div><div class="card-body"><canvas id="chartEstatus" role="img" aria-label="Tickets por estatus"></canvas></div></div></div>
                <div class="col-md-8 mb-3"><div class="card h-100"><div class="card-header"><i class="fas fa-chart-line me-2" aria-hidden="true"></i>Tickets creados</div><div class="card-body"><canvas id="chartTendencia" role="img" aria-label="Tendencia de tickets"></canvas></div></div></div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-clock me-2" aria-hidden="true"></i>Tickets Recientes</h5>
                    <a href="{{ route('tickets.index') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1" aria-hidden="true"></i>Ver Todos</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Folio</th><th>Asunto</th><th>Tipo</th><th>Asignado a</th><th>Estatus</th><th>Fecha</th></tr></thead>
                            <tbody>
                                @forelse($recentTickets as $ticket)
                                <tr>
                                    <td><strong>#{{ $ticket->id_Ticket }}</strong></td>
                                    <td>{{ $ticket->asunto }}</td>
                                    <td>{{ $ticket->tipoSoporte->descripcion ?? 'N/A' }}</td>
                                    <td>{{ $ticket->tecnico ? $ticket->tecnico->nombreCompleto() : 'Sin asignar' }}</td>
                                    <td><span class="badge bg-{{ $ticket->estatus->descripcion == 'Cerrado' ? 'secondary' : ($ticket->estatus->descripcion == 'Resuelto' ? 'success' : 'primary') }}">{{ $ticket->estatus->descripcion ?? 'Sin estatus' }}</span></td>
                                    <td>{{ $ticket->fechaAlta->format('d/m/Y H:i') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No hay tickets con estos filtros</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    @elseif($tab === 'equipos')
        <section aria-label="KPIs de equipos">
            <div class="kpi-grid mb-3">
                <div class="kpi kpi-cyan"><div class="kpi-icon"><i class="fas fa-laptop" aria-hidden="true"></i></div><div><div class="kpi-value">{{ $kpis['inventario']['total'] }}</div><div class="kpi-label">Equipos Registrados</div></div></div>
                <div class="kpi kpi-amber"><div class="kpi-icon"><i class="fas fa-unlink" aria-hidden="true"></i></div><div><div class="kpi-value">{{ $kpis['inventario']['sin_asignar'] }}</div><div class="kpi-label">Sin asignar</div></div></div>
                <div class="kpi kpi-amber"><div class="kpi-icon"><i class="fas fa-shield-alt" aria-hidden="true"></i></div><div><div class="kpi-value">{{ $kpis['inventario']['garantias_por_vencer'] }}</div><div class="kpi-label">Garantías por vencer (30d)</div></div></div>
                <div class="kpi kpi-red"><div class="kpi-icon"><i class="fas fa-shield-virus" aria-hidden="true"></i></div><div><div class="kpi-value">{{ $kpis['inventario']['garantias_vencidas'] }}</div><div class="kpi-label">Garantías vencidas</div></div></div>
            </div>
            <div class="row mb-3">
                <div class="col-md-7 mb-3"><div class="card h-100"><div class="card-header"><i class="fas fa-chart-bar me-2" aria-hidden="true"></i>Equipos por Tipo</div><div class="card-body"><canvas id="chartEquiposTipo" role="img" aria-label="Equipos por tipo"></canvas></div></div></div>
                <div class="col-md-5 mb-3"><div class="card h-100"><div class="card-header"><i class="fas fa-chart-pie me-2" aria-hidden="true"></i>Equipos por Estatus</div><div class="card-body"><canvas id="chartEquiposEstatus" role="img" aria-label="Equipos por estatus"></canvas></div></div></div>
            </div>

            {{-- Sub-tabs de inventario: Sin asignar / Asignados --}}
            <ul class="nav nav-tabs mb-3" id="tabsInventario" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-sin-asignar" data-bs-toggle="tab" data-bs-target="#panelSinAsignar" type="button" role="tab" aria-controls="panelSinAsignar" aria-selected="true">
                        <i class="fas fa-unlink me-1" aria-hidden="true"></i>Sin Asignar
                        <span class="badge bg-secondary ms-1">{{ $kpis['inventario']['sin_asignar'] }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-asignados" data-bs-toggle="tab" data-bs-target="#panelAsignados" type="button" role="tab" aria-controls="panelAsignados" aria-selected="false">
                        <i class="fas fa-user-tag me-1" aria-hidden="true"></i>Asignados
                        <span class="badge bg-secondary ms-1">{{ $kpis['inventario']['asignados'] }}</span>
                    </button>
                </li>
            </ul>
            <div class="tab-content mb-3" id="tabsInventarioContent">
                <div class="tab-pane fade show active" id="panelSinAsignar" role="tabpanel" aria-labelledby="tab-sin-asignar" tabindex="0">
            <div class="card mb-3" data-table>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-unlink me-2" aria-hidden="true"></i>Equipos Sin Asignar</h5>
                    <span class="badge bg-secondary">{{ $kpis['inventario']['sin_asignar_lista']->count() }}</span>
                </div>
                <div class="card-body">
                    <div class="table-toolbar">
                        <div class="table-toolbar-left">
                            <div class="table-search">
                                <i class="fas fa-search"></i>
                                <input type="text" data-table-search placeholder="Buscar por equipo, serie, ubicación...">
                            </div>
                            <select class="table-filter" data-table-filter="tipoEquipo">
                                <option value="">Todos los tipos</option>
                                @foreach($kpis['inventario']['sin_asignar_lista']->pluck('tipoEquipo')->unique()->filter()->sort() as $tipo)
                                <option value="{{ $tipo }}">{{ $tipo }}</option>
                                @endforeach
                            </select>
                            <select class="table-filter" data-table-filter="estatusEquipo">
                                <option value="">Todos los estatus</option>
                                @foreach($kpis['inventario']['sin_asignar_lista']->map(fn($x) => $x->estatusEquipo ?? 'Sin estatus')->unique()->sort() as $est)
                                <option value="{{ $est }}">{{ $est }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="table-toolbar-right">
                            <span class="table-info">Por página</span>
                            <select class="table-filter" data-table-per-page>
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="10000">Todos</option>
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Tipo de Equipo</th>
                                    <th>Nombre</th>
                                    <th>Marca / Modelo</th>
                                    <th>No. Serie</th>
                                    <th>Sistema Operativo</th>
                                    <th>Ubicación</th>
                                    <th>Estatus</th>
                                    <th>Garantía</th>
                                    @if($puedeAsignarEquipos)<th>Acciones</th>@endif
                                </tr>
                            </thead>
                            <tbody data-table-body>
                                @forelse($kpis['inventario']['sin_asignar_lista'] as $eq)
                                <tr data-row>
                                    <td><strong>#{{ $eq->id_Equipo }}</strong></td>
                                    <td data-field="tipoEquipo"><span class="badge bg-light text-dark border">{{ $eq->tipoEquipo ?? 'N/A' }}</span></td>
                                    <td>{{ $eq->nombreEquipo ?: '—' }}</td>
                                    <td>{{ trim(($eq->marca ?? '') . ' ' . ($eq->modelo ?? '')) ?: '—' }}</td>
                                    <td>{{ $eq->numeroSerie ?: '—' }}</td>
                                    <td>{{ $eq->sistemaOperativo ?: '—' }}</td>
                                    <td>{{ $eq->ubicacion ?: '—' }}</td>
                                    <td data-field="estatusEquipo"><span class="badge bg-secondary">{{ $eq->estatusEquipo ?? 'Sin estatus' }}</span></td>
                                    <td>
                                        @if($eq->fechaFinGarantia)
                                            @if(\Illuminate\Support\Carbon::parse($eq->fechaFinGarantia)->isPast())
                                                <span class="badge bg-danger">Vencida {{ \Illuminate\Support\Carbon::parse($eq->fechaFinGarantia)->format('d/m/Y') }}</span>
                                            @else
                                                <span class="badge bg-success">Vence {{ \Illuminate\Support\Carbon::parse($eq->fechaFinGarantia)->format('d/m/Y') }}</span>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    @if($puedeAsignarEquipos)
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary btn-asignacion-rapida"
                                            data-id="{{ $eq->id_Equipo }}"
                                            data-nombre="{{ $eq->nombreEquipo }}"
                                            data-descripcion="{{ trim(($eq->nombreEquipo ?: '#' . $eq->id_Equipo) . ' · ' . $eq->marca . ' ' . $eq->modelo . ' · ' . ($eq->tipoEquipo ?? '')) }}"
                                            title="Asignación rápida">
                                            <i class="fas fa-user-plus me-1" aria-hidden="true"></i>Asignar
                                        </button>
                                    </td>
                                    @endif
                                </tr>
                                @empty
                                <tr><td colspan="{{ $puedeAsignarEquipos ? 10 : 9 }}" class="text-center text-muted py-4">No hay equipos sin asignar</td></tr>
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
                </div>{{-- /panelSinAsignar --}}

                <div class="tab-pane fade" id="panelAsignados" role="tabpanel" aria-labelledby="tab-asignados" tabindex="0">
                    <div class="card mb-3" data-table>
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-user-tag me-2" aria-hidden="true"></i>Equipos Asignados</h5>
                            <span class="badge bg-secondary">{{ $kpis['inventario']['asignados_lista']->count() }}</span>
                        </div>
                        <div class="card-body">
                            <div class="table-toolbar">
                                <div class="table-toolbar-left">
                                    <div class="table-search">
                                        <i class="fas fa-search"></i>
                                        <input type="text" data-table-search placeholder="Buscar por equipo, usuario, serie, área...">
                                    </div>
                                    <select class="table-filter" data-table-filter="tipoEquipo">
                                        <option value="">Todos los tipos</option>
                                        @foreach($kpis['inventario']['asignados_lista']->pluck('tipoEquipo')->unique()->filter()->sort() as $tipo)
                                        <option value="{{ $tipo }}">{{ $tipo }}</option>
                                        @endforeach
                                    </select>
                                    <select class="table-filter" data-table-filter="areaAsignada">
                                        <option value="">Todas las áreas</option>
                                        @foreach($kpis['inventario']['asignados_lista']->pluck('areaAsignada')->unique()->filter()->sort() as $ar)
                                        <option value="{{ $ar }}">{{ $ar }}</option>
                                        @endforeach
                                    </select>
                                    <select class="table-filter" data-table-filter="estatusEquipo">
                                        <option value="">Todos los estatus</option>
                                        @foreach($kpis['inventario']['asignados_lista']->map(fn($x) => $x->estatusEquipo ?? 'Sin estatus')->unique()->sort() as $est)
                                        <option value="{{ $est }}">{{ $est }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="table-toolbar-right">
                                    <span class="table-info">Por página</span>
                                    <select class="table-filter" data-table-per-page>
                                        <option value="5">5</option>
                                        <option value="10" selected>10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="10000">Todos</option>
                                    </select>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Folio</th>
                                            <th>Tipo de Equipo</th>
                                            <th>Nombre</th>
                                            <th>Marca / Modelo</th>
                                            <th>No. Serie</th>
                                            <th>Usuario Asignado</th>
                                            <th>Área</th>
                                            <th>Fecha Asignación</th>
                                            <th>Estatus</th>
                                            <th>Garantía</th>
                                        </tr>
                                    </thead>
                                    <tbody data-table-body>
                                        @forelse($kpis['inventario']['asignados_lista'] as $as)
                                        <tr data-row>
                                            <td><strong>#{{ $as->id_Equipo }}</strong></td>
                                            <td data-field="tipoEquipo"><span class="badge bg-light text-dark border">{{ $as->tipoEquipo ?? 'N/A' }}</span></td>
                                            <td>{{ $as->nombreEquipo ?: '—' }}</td>
                                            <td>{{ trim(($as->marca ?? '') . ' ' . ($as->modelo ?? '')) ?: '—' }}</td>
                                            <td>{{ $as->numeroSerie ?: '—' }}</td>
                                            <td>{{ trim(($as->usuarioNombre ?? '') . ' ' . ($as->usuarioApp ?? '') . ' ' . ($as->usuarioApm ?? '')) }}</td>
                                            <td data-field="areaAsignada">{{ $as->areaAsignada ?: '—' }}</td>
                                            <td>{{ $as->fechaAsignacion ? \Illuminate\Support\Carbon::parse($as->fechaAsignacion)->format('d/m/Y') : '—' }}</td>
                                            <td data-field="estatusEquipo"><span class="badge bg-secondary">{{ $as->estatusEquipo ?? 'Sin estatus' }}</span></td>
                                            <td>
                                                @if($as->fechaFinGarantia)
                                                    @if(\Illuminate\Support\Carbon::parse($as->fechaFinGarantia)->isPast())
                                                        <span class="badge bg-danger">Vencida {{ \Illuminate\Support\Carbon::parse($as->fechaFinGarantia)->format('d/m/Y') }}</span>
                                                    @else
                                                        <span class="badge bg-success">Vence {{ \Illuminate\Support\Carbon::parse($as->fechaFinGarantia)->format('d/m/Y') }}</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="10" class="text-center text-muted py-4">No hay equipos asignados</td></tr>
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
                </div>{{-- /panelAsignados --}}
            </div>{{-- /tabsInventarioContent --}}

            @if($puedeAsignarEquipos)
            <!-- Modal Asignación Rápida -->
            <div class="modal fade" id="asignacionRapidaModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <form method="POST" action="{{ route('inventario.asignacion.rapida') }}">
                        @csrf
                        <input type="hidden" name="id_Equipo" id="rapidaIdEquipo">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title"><i class="fas fa-user-plus me-2" aria-hidden="true"></i>Asignación Rápida</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-3" id="rapidaEquipoInfo"></p>
                                <div class="mb-3">
                                    <label class="form-label">Usuario *</label>
                                    <select name="id_Usuario" class="form-select" required>
                                        <option value="">Seleccione un usuario...</option>
                                        @foreach($catalogos['usuariosAsignacion'] as $u)
                                        <option value="{{ $u->id_Usuario }}">{{ trim($u->nombre . ' ' . $u->app . ' ' . $u->apm) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Área</label>
                                    <select name="id_Area" class="form-select">
                                        <option value="">Sin área</option>
                                        @foreach($catalogos['areas'] as $a)
                                        <option value="{{ $a->id_Area }}">{{ $a->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Fecha de Asignación *</label>
                                    <input type="date" name="fechaAsignacion" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Observaciones</label>
                                    <textarea name="observaciones" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1" aria-hidden="true"></i>Asignar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @endif
        </section>
    @else
        <section aria-label="KPIs de mantenimiento">
            <div class="kpi-grid mb-3">
                <div class="kpi kpi-cyan"><div class="kpi-icon"><i class="fas fa-calendar-check" aria-hidden="true"></i></div><div><div class="kpi-value">{{ $kpis['mantenimiento']['proximos'] }}</div><div class="kpi-label">Próximos (30d)</div></div></div>
                <div class="kpi kpi-red"><div class="kpi-icon"><i class="fas fa-calendar-times" aria-hidden="true"></i></div><div><div class="kpi-value">{{ $kpis['mantenimiento']['vencidos'] }}</div><div class="kpi-label">Vencidos</div></div></div>
                <div class="kpi kpi-green"><div class="kpi-icon"><i class="fas fa-clipboard-check" aria-hidden="true"></i></div><div><div class="kpi-value">{{ $kpis['mantenimiento']['pct_reporte_cerrado'] }}%</div><div class="kpi-label">Reportes cerrados</div></div></div>
                <div class="kpi kpi-blue"><div class="kpi-icon"><i class="fas fa-calendar-alt" aria-hidden="true"></i></div><div><div class="kpi-value">{{ $kpis['mantenimiento']['total'] }}</div><div class="kpi-label">Total en rango</div></div></div>
            </div>
            <div class="row mb-3">
                <div class="col-12"><div class="card"><div class="card-header"><i class="fas fa-chart-bar me-2" aria-hidden="true"></i>Mantenimientos por estatus</div><div class="card-body"><canvas id="chartMantEstatus" role="img" aria-label="Mantenimientos próximos y vencidos"></canvas></div></div></div>
            </div>

            {{-- Sub-tabs de mantenimiento: Pendientes / Vencidos / Completados --}}
            @php
                $mantTabs = [
                    'pendientes' => ['lista' => $kpis['mantenimiento']['pendientes_lista'], 'total' => $kpis['mantenimiento']['pendientes'], 'titulo' => 'Mantenimientos Pendientes', 'icono' => 'fas fa-clock', 'vacio' => 'No hay mantenimientos pendientes'],
                    'vencidos' => ['lista' => $kpis['mantenimiento']['vencidos_lista'], 'total' => $kpis['mantenimiento']['vencidos'], 'titulo' => 'Mantenimientos Vencidos', 'icono' => 'fas fa-calendar-times', 'vacio' => 'No hay mantenimientos vencidos'],
                    'completados' => ['lista' => $kpis['mantenimiento']['completados_lista'], 'total' => $kpis['mantenimiento']['completados'], 'titulo' => 'Mantenimientos Completados', 'icono' => 'fas fa-check-double', 'vacio' => 'No hay mantenimientos completados'],
                ];
            @endphp

            <ul class="nav nav-tabs mb-3" id="tabsMantenimiento" role="tablist">
                @foreach($mantTabs as $clave => $t)
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="tab-mant-{{ $clave }}" data-bs-toggle="tab" data-bs-target="#panelMant{{ ucfirst($clave) }}" type="button" role="tab" aria-controls="panelMant{{ ucfirst($clave) }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                        <i class="{{ $t['icono'] }} me-1" aria-hidden="true"></i>{{ str_replace('Mantenimientos ', '', $t['titulo']) }}
                        <span class="badge bg-secondary ms-1">{{ $t['total'] }}</span>
                    </button>
                </li>
                @endforeach
            </ul>

            <div class="tab-content mb-3" id="tabsMantenimientoContent">
                @foreach($mantTabs as $clave => $t)
                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="panelMant{{ ucfirst($clave) }}" role="tabpanel" aria-labelledby="tab-mant-{{ $clave }}" tabindex="0">
                    <div class="card mb-3" data-table>
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="{{ $t['icono'] }} me-2" aria-hidden="true"></i>{{ $t['titulo'] }}</h5>
                            <span class="badge bg-secondary">{{ $t['lista']->count() }}</span>
                        </div>
                        <div class="card-body">
                            <div class="table-toolbar">
                                <div class="table-toolbar-left">
                                    <div class="table-search">
                                        <i class="fas fa-search"></i>
                                        <input type="text" data-table-search placeholder="Buscar por equipo, técnico, serie...">
                                    </div>
                                    <select class="table-filter" data-table-filter="tipoEquipo">
                                        <option value="">Todos los tipos</option>
                                        @foreach($t['lista']->pluck('tipoEquipo')->unique()->filter()->sort() as $tipo)
                                        <option value="{{ $tipo }}">{{ $tipo }}</option>
                                        @endforeach
                                    </select>
                                    <select class="table-filter" data-table-filter="tecnico">
                                        <option value="">Todos los técnicos</option>
                                        @foreach($t['lista']->map(fn($x) => trim(($x->tecnicoNombre ?? '') . ' ' . ($x->tecnicoApp ?? '')))->unique()->filter()->sort() as $tec)
                                        <option value="{{ $tec }}">{{ $tec }}</option>
                                        @endforeach
                                    </select>
                                    <select class="table-filter" data-table-filter="tipoMantenimiento">
                                        <option value="">Todos los mantenimientos</option>
                                        @foreach($t['lista']->pluck('tipoMantenimiento')->unique()->filter()->sort() as $tm)
                                        <option value="{{ $tm }}">{{ $tm }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="table-toolbar-right">
                                    <span class="table-info">Por página</span>
                                    <select class="table-filter" data-table-per-page>
                                        <option value="5">5</option>
                                        <option value="10" selected>10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="10000">Todos</option>
                                    </select>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Folio</th>
                                            <th>Tipo Equipo</th>
                                            <th>Equipo</th>
                                            <th>No. Serie</th>
                                            <th>Técnico</th>
                                            <th>Tipo Mant.</th>
                                            <th>F. Programada</th>
                                            <th>F. Ejecución</th>
                                            <th>Reporte</th>
                                        </tr>
                                    </thead>
                                    <tbody data-table-body>
                                        @forelse($t['lista'] as $m)
                                        <tr data-row>
                                            <td><strong>#{{ $m->id_Mantenimiento }}</strong></td>
                                            <td data-field="tipoEquipo"><span class="badge bg-light text-dark border">{{ $m->tipoEquipo ?? 'N/A' }}</span></td>
                                            <td>{{ trim(($m->nombreEquipo ?: '') . ' · ' . $m->marca . ' ' . $m->modelo) }}</td>
                                            <td>{{ $m->numeroSerie ?: '—' }}</td>
                                            <td data-field="tecnico">{{ trim(($m->tecnicoNombre ?? '') . ' ' . ($m->tecnicoApp ?? '') . ' ' . ($m->tecnicoApm ?? '')) }}</td>
                                            <td data-field="tipoMantenimiento"><span class="badge bg-info text-dark">{{ $m->tipoMantenimiento }}</span></td>
                                            <td>{{ $m->fechaProgramada ? \Illuminate\Support\Carbon::parse($m->fechaProgramada)->format('d/m/Y') : '—' }}</td>
                                            <td>{{ $m->fechaEjecucion ? \Illuminate\Support\Carbon::parse($m->fechaEjecucion)->format('d/m/Y') : '—' }}</td>
                                            <td>
                                                @if($m->reporteCerrado)
                                                    <span class="badge bg-success">Cerrado</span>
                                                @elseif($m->fechaEjecucion)
                                                    <span class="badge bg-warning text-dark">Ejecutado</span>
                                                @else
                                                    <span class="badge bg-secondary">Pendiente</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="9" class="text-center text-muted py-4">{{ $t['vacio'] }}</td></tr>
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
                @endforeach
            </div>{{-- /tabsMantenimientoContent --}}
        </section>
    @endif
@else
    @php
        $pe = $kpis['por_estatus'];
        $cnt = fn($n) => (int) ($pe[$n] ?? 0);
    @endphp
    <section aria-label="Resumen personal">
        <div class="kpi-grid mb-3">
            <div class="kpi kpi-blue"><div class="kpi-icon"><i class="fas fa-ticket" aria-hidden="true"></i></div><div><div class="kpi-value">{{ $kpis['total'] }}</div><div class="kpi-label">Mis Tickets</div></div></div>
            <div class="kpi kpi-blue"><div class="kpi-icon"><i class="fas fa-folder-open" aria-hidden="true"></i></div><div><div class="kpi-value">{{ $cnt('Abierto') }}</div><div class="kpi-label">Abiertos</div></div></div>
            <div class="kpi kpi-green"><div class="kpi-icon"><i class="fas fa-check-double" aria-hidden="true"></i></div><div><div class="kpi-value">{{ $kpis['pendientes_visto_bueno'] }}</div><div class="kpi-label">Pendientes de Visto Bueno</div></div></div>
            @if($esTecnico)
                <div class="kpi kpi-amber"><div class="kpi-icon"><i class="fas fa-user-check" aria-hidden="true"></i></div><div><div class="kpi-value">{{ $kpis['asignados'] }}</div><div class="kpi-label">Asignados a Mí</div></div></div>
            @endif
        </div>
    </section>

    <div class="row mb-3">
        <div class="col-md-6 mb-3"><div class="card h-100"><div class="card-header"><i class="fas fa-chart-pie me-2" aria-hidden="true"></i>Mis Tickets por Estatus</div><div class="card-body"><canvas id="chartPersonalEstatus" role="img" aria-label="Mis tickets por estatus"></canvas></div></div></div></div>
        <div class="col-md-6 mb-3"><div class="card"><div class="card-header d-flex justify-content-between align-items-center"><h5 class="mb-0"><i class="fas fa-clock me-2" aria-hidden="true"></i>Tickets Recientes</h5><a href="{{ route('tickets.index') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1" aria-hidden="true"></i>Ver Todos</a></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Folio</th><th>Asunto</th><th>Tipo</th><th>Estatus</th><th>Fecha</th></tr></thead><tbody>@forelse($recentTickets as $ticket)<tr><td><strong>#{{ $ticket->id_Ticket }}</strong></td><td>{{ $ticket->asunto }}</td><td>{{ $ticket->tipoSoporte->descripcion ?? 'N/A' }}</td><td><span class="badge bg-{{ $ticket->estatus->descripcion == 'Cerrado' ? 'secondary' : ($ticket->estatus->descripcion == 'Resuelto' ? 'success' : 'primary') }}">{{ $ticket->estatus->descripcion ?? 'Sin estatus' }}</span></td><td>{{ $ticket->fechaAlta->format('d/m/Y H:i') }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-4">No hay tickets recientes</td></tr>@endforelse</tbody></table></div></div></div></div>
    </div>
@endif
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function() {
    // Asignación rápida: abrir modal con los datos del equipo
    var modalRapida = document.getElementById('asignacionRapidaModal');
    if (modalRapida) {
        var infoEquipo = document.getElementById('rapidaEquipoInfo');
        var inputEquipo = document.getElementById('rapidaIdEquipo');
        document.querySelectorAll('.btn-asignacion-rapida').forEach(function(btn) {
            btn.addEventListener('click', function() {
                inputEquipo.value = this.dataset.id;
                infoEquipo.innerHTML = '<strong>Equipo:</strong> ' + (this.dataset.descripcion || ('#' + this.dataset.id));
                var form = modalRapida.querySelector('form');
                form.reset();
                inputEquipo.value = this.dataset.id;
                var fecha = form.querySelector('input[name="fechaAsignacion"]');
                if (fecha) fecha.value = '{{ date('Y-m-d') }}';
                new bootstrap.Modal(modalRapida).show();
            });
        });
    }

    // Autosubmit de filtros
    document.querySelectorAll('[data-autosubmit]').forEach(function(el) {
        el.addEventListener('change', function() { document.getElementById('filterForm').submit(); });
    });
    var selRango = document.getElementById('selRango');
    if (selRango) {
        selRango.addEventListener('change', function() {
            var custom = this.value === 'custom';
            document.getElementById('wrapDesde').classList.toggle('d-none', !custom);
            document.getElementById('wrapHasta').classList.toggle('d-none', !custom);
        });
    }

    if (typeof Chart === 'undefined') return;

    const paleta = ['#2563eb', '#059669', '#d97706', '#dc2626', '#7c3aed', '#0891b2', '#64748b', '#db2777', '#65a30d'];
    const colorEstatus = { 'Abierto': '#2563eb', 'En proceso': '#d97706', 'Pendiente de usuario': '#0891b2', 'Resuelto': '#059669', 'Cerrado': '#64748b' };
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function cssVar(name, fallback) { const v = getComputedStyle(document.body).getPropertyValue(name).trim(); return v || fallback; }
    function allZero(data) { return !data || data.length === 0 || data.every(function(v) { return !v; }); }
    function showEmpty(el, mensaje) {
        el.style.display = 'none';
        const card = el.closest('.card-body');
        if (card && !card.querySelector('.chart-empty')) {
            const div = document.createElement('div');
            div.className = 'chart-empty';
            div.innerHTML = '<i class="fas fa-chart-simple" aria-hidden="true"></i><span>' + mensaje + '</span>';
            card.appendChild(div);
        }
    }
    function baseOptions(hideLegend) {
        return {
            responsive: true, maintainAspectRatio: true,
            animation: reduceMotion ? false : { duration: 400 },
            plugins: {
                legend: { display: !hideLegend, position: 'bottom', labels: { color: cssVar('--muted', '#64748b'), boxWidth: 12, font: { size: 11 } } },
                tooltip: { backgroundColor: cssVar('--text', '#0f172a') }
            },
            scales: {
                x: { grid: { color: cssVar('--border', '#e2e8f0') }, ticks: { color: cssVar('--muted', '#64748b'), precision: 0 } },
                y: { grid: { color: cssVar('--border', '#e2e8f0') }, ticks: { color: cssVar('--muted', '#64748b'), precision: 0 }, beginAtZero: true }
            }
        };
    }
    function dona(id, labels, data, colorMap) {
        const el = document.getElementById(id); if (!el) return;
        if (allZero(data)) { showEmpty(el, 'Sin datos'); return; }
        const colors = labels.map((l, i) => (colorMap && colorMap[l]) ? colorMap[l] : paleta[i % paleta.length]);
        const opts = baseOptions(false); delete opts.scales; opts.aspectRatio = 1.4;
        new Chart(el, { type: 'doughnut', data: { labels: labels, datasets: [{ data: data, backgroundColor: colors, borderWidth: 0 }] }, options: opts });
    }
    function barras(id, labels, data, horizontal) {
        const el = document.getElementById(id); if (!el) return;
        if (allZero(data)) { showEmpty(el, 'Sin datos'); return; }
        const opts = baseOptions(true); opts.indexAxis = horizontal ? 'y' : 'x'; opts.aspectRatio = horizontal ? 2.2 : 2.5;
        new Chart(el, { type: 'bar', data: { labels: labels, datasets: [{ data: data, backgroundColor: cssVar('--primary', '#2563eb'), borderRadius: 4 }] }, options: opts });
    }
    function linea(id, labels, data) {
        const el = document.getElementById(id); if (!el) return;
        if (allZero(data)) { showEmpty(el, 'Sin datos'); return; }
        const opts = baseOptions(true); opts.aspectRatio = 3;
        new Chart(el, { type: 'line', data: { labels: labels, datasets: [{ data: data, borderColor: cssVar('--primary', '#2563eb'), backgroundColor: 'rgba(37,99,235,0.10)', fill: true, tension: 0.3, pointRadius: 3 }] }, options: opts });
    }

    @if($esAdmin)
        @if($tab === 'tickets')
            dona('chartEstatus', @json($kpis['tickets']['por_estatus']->keys()), @json($kpis['tickets']['por_estatus']->values()), colorEstatus);
            linea('chartTendencia', @json($kpis['tendencia']['labels']), @json($kpis['tendencia']['data']));
        @elseif($tab === 'equipos')
            barras('chartEquiposTipo', @json($kpis['inventario']['por_tipo']->keys()), @json($kpis['inventario']['por_tipo']->values()), true);
            dona('chartEquiposEstatus', @json($kpis['inventario']['por_estatus']->keys()), @json($kpis['inventario']['por_estatus']->values()), null);
        @else
            barras('chartMantEstatus', @json(['Próximos (30d)', 'Vencidos']), @json([$kpis['mantenimiento']['proximos'], $kpis['mantenimiento']['vencidos']]), false);
        @endif
    @else
        dona('chartPersonalEstatus', @json($kpis['por_estatus']->keys()), @json($kpis['por_estatus']->values()), colorEstatus);
    @endif
})();
</script>
@endsection
