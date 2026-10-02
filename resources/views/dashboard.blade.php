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
    if ($filtros['tecnico']) { $t = $catalogos['tecnicos']->firstWhere('id_Usuario', $filtros['tecnico']); $chips[] = 'Técnico: ' . ($t ? $t->nombre . ' ' . $t->app : $filtros['tecnico']); }
    if ($filtros['depto']) $chips[] = 'Depto: ' . optional($catalogos['deptos']->firstWhere('id_Depto', $filtros['depto']))->nombre;
    if ($filtros['area']) $chips[] = 'Área: ' . optional($catalogos['areas']->firstWhere('id_Area', $filtros['area']))->nombre;
    $chips = array_values(array_filter($chips));
@endphp

@section('content')
<div class="page-head">
    <div>
        <h1>Panel</h1>
        <div class="sub">{{ $rol }} · {{ now()->format('d/m/Y') }}</div>
    </div>
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
            <div class="col-6 col-md-2">
                <label class="form-label">Técnico</label>
                <select name="tecnico" class="form-select form-select-sm" data-autosubmit>
                    <option value="">Todos</option>
                    @foreach($catalogos['tecnicos'] as $o)
                        <option value="{{ $o->id_Usuario }}" {{ (string)$filtros['tecnico'] === (string)$o->id_Usuario ? 'selected' : '' }}>{{ $o->nombre }} {{ $o->app }}</option>
                    @endforeach
                </select>
            </div>
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
                <div class="kpi kpi-red"><div class="kpi-icon"><i class="fas fa-user-slash" aria-hidden="true"></i></div><div><div class="kpi-value">{{ $t['sin_asignar'] }}</div><div class="kpi-label">Sin Asignar</div></div></div>
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
