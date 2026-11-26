@extends('layouts.app')

@section('title', 'Monitoreo - HelpDesk')

@section('styles')
<style>
    /* KPI — mismo estilo del Panel (dashboard) */
    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: var(--space-3, 12px); }
    .kpi { --kpi-accent: var(--primary); --kpi-tint: var(--primary-soft); display: flex; align-items: center; gap: 12px; padding: 16px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow-sm); min-height: 82px; transition: transform 0.15s ease, box-shadow 0.15s ease; }
    .kpi:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
    .kpi-icon { width: 42px; height: 42px; flex-shrink: 0; border-radius: var(--radius-sm); display: grid; place-items: center; background: var(--kpi-tint); color: var(--kpi-accent); font-size: 16px; }
    .kpi-value { font-size: 26px; font-weight: 700; line-height: 1.05; letter-spacing: -0.02em; color: var(--text); font-variant-numeric: tabular-nums; }
    .kpi-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); margin-top: 2px; }
    .kpi-blue { --kpi-accent: #2563eb; --kpi-tint: rgba(37, 99, 235, 0.10); }
    .kpi-amber { --kpi-accent: #d97706; --kpi-tint: rgba(217, 119, 6, 0.12); }
    .kpi-green { --kpi-accent: #059669; --kpi-tint: rgba(5, 150, 105, 0.12); }
    .kpi-red { --kpi-accent: #dc2626; --kpi-tint: rgba(220, 38, 38, 0.12); }
    body.dark-mode .kpi-blue { --kpi-accent: #60a5fa; --kpi-tint: rgba(96, 165, 250, 0.15); }
    body.dark-mode .kpi-amber { --kpi-accent: #fbbf24; --kpi-tint: rgba(251, 191, 36, 0.16); }
    body.dark-mode .kpi-green { --kpi-accent: #34d399; --kpi-tint: rgba(52, 211, 153, 0.16); }
    body.dark-mode .kpi-red { --kpi-accent: #f87171; --kpi-tint: rgba(248, 113, 113, 0.16); }

    /* Tarjeta de host */
    .host-card { transition: transform 0.15s ease, box-shadow 0.15s ease; }
    .host-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
    .host-card.inactive { opacity: 0.6; }

    .meta-label { font-size: 10px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 4px; display: block; }
    .meta-value { font-size: 13px; font-weight: 500; color: var(--text); }

    .service-badge { font-size: 11px; }

    /* Métricas (CPU / RAM / Disco) */
    .metric-block { border-top: 1px solid var(--border); padding-top: 12px; margin-top: 12px; }
    .metric-row + .metric-row { margin-top: 10px; }
    .metric-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 4px; }
    .metric-name { font-size: 11px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.04em; }
    .metric-value { font-size: 13px; font-weight: 700; color: var(--text); font-variant-numeric: tabular-nums; }
    .metric-sub { font-size: 11px; color: var(--muted); margin-top: 3px; display: block; }
    .metric-row .progress { height: 6px; background: var(--surface-strong); border-radius: 3px; }

    /* Pie de paginación */
    .pager-footer { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-top: 16px; }
    .pager-footer .pagination { margin: 0; }

    @media (max-width: 576px) { .kpi-value { font-size: 22px; } .pager-footer { justify-content: center; } }
    @media (prefers-reduced-motion: reduce) { .kpi, .host-card { transition: none; } .kpi:hover, .host-card:hover { transform: none; } }

    /* Spinner del botón de sincronización */
    .btn-loading { position: relative; pointer-events: none; opacity: 0.7; }
    .btn-loading::after {
        content: ''; position: absolute; width: 16px; height: 16px;
        top: 50%; left: 50%; margin-left: -8px; margin-top: -8px;
        border: 2px solid #ffffff; border-top-color: transparent;
        border-radius: 50%; animation: monitoreo-spin 0.8s linear infinite;
    }
    @keyframes monitoreo-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-heart-pulse me-2"></i>Monitoreo de Sistemas</h4>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevo"><i class="fas fa-plus me-1"></i>Nuevo objetivo</a>
        <form method="POST" action="{{ route('monitoreo.sincronizar-nagios') }}" class="d-inline" onsubmit="return confirm('¿Sincronizar el estado de Nagios con el sistema de monitoreo?')">
            @csrf
            <button type="submit" class="btn btn-outline-primary btn-sincronizar-nagios"><i class="fas fa-sync me-1"></i>Sincronizar con Nagios</button>
        </form>
        <span class="badge bg-secondary" id="badgeUltimaSync" title="Última sincronización automática">
            <i class="fas fa-clock me-1"></i>--:--
        </span>
    </div>
</div>

{{-- Tarjetas de resumen (KPI) --}}
<div class="kpi-grid mb-4" aria-label="Resumen de monitoreo">
    <div class="kpi kpi-blue">
        <div class="kpi-icon"><i class="fas fa-server" aria-hidden="true"></i></div>
        <div><div class="kpi-value">{{ $resumen['total'] }}</div><div class="kpi-label">Total Equipos</div></div>
    </div>
    <div class="kpi kpi-green">
        <div class="kpi-icon"><i class="fas fa-check-circle" aria-hidden="true"></i></div>
        <div><div class="kpi-value">{{ $resumen['online'] }}</div><div class="kpi-label">Online</div></div>
    </div>
    <div class="kpi kpi-red">
        <div class="kpi-icon"><i class="fas fa-times-circle" aria-hidden="true"></i></div>
        <div><div class="kpi-value">{{ $resumen['offline'] }}</div><div class="kpi-label">Fuera de línea</div></div>
    </div>
    <div class="kpi kpi-amber">
        <div class="kpi-icon"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i></div>
        <div><div class="kpi-value">{{ $resumen['degradado'] }}</div><div class="kpi-label">Degradado</div></div>
    </div>
</div>

{{-- Filtros y búsqueda --}}
<div class="card mb-4">
    <div class="card-body">
        <div class="table-toolbar">
            <div class="table-toolbar-left">
                <div class="table-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="buscarMonitoreo" placeholder="Buscar por nombre, host o servicio...">
                </div>
                <select id="filtroTipo" class="table-filter">
                    <option value="">Todos los tipos</option>
                    @foreach($tipos as $tipo)
                        <option value="{{ $tipo }}">{{ ucfirst($tipo) }}</option>
                    @endforeach
                </select>
                <select id="filtroEstatus" class="table-filter">
                    <option value="">Todos los estatus</option>
                    <option value="online">Online</option>
                    <option value="offline">Fuera de línea</option>
                    <option value="degradado">Degradado</option>
                    <option value="desconocido">Desconocido</option>
                </select>
                <button class="btn btn-outline-secondary btn-sm" id="btnLimpiarFiltros" type="button">
                    <i class="fas fa-times me-1"></i>Limpiar
                </button>
            </div>
            <div class="table-toolbar-right">
                <span class="table-info">Mostrando {{ $hostsPaginados->count() }} de {{ $hostsPaginados->total() }} equipos</span>
            </div>
        </div>
    </div>
</div>

{{-- Grid de cards por host --}}
<div class="row g-3" id="gridMonitoreo">
    @foreach($hostsPaginados as $nombreHost => $serviciosHost)
        @php
            $primerServicio = $serviciosHost->first();
            $hostActivo = $primerServicio->activo;
            $estadoMasGrave = 'online';
            $ordenGravedad = ['desconocido' => 0, 'online' => 1, 'degradado' => 2, 'offline' => 3];
            foreach ($serviciosHost as $s) {
                if (($ordenGravedad[$s->estatus] ?? 0) > ($ordenGravedad[$estadoMasGrave] ?? 0)) {
                    $estadoMasGrave = $s->estatus;
                }
            }
            $colorBarra = fn ($valor) => $valor > 80 ? 'bg-danger' : ($valor > 60 ? 'bg-warning' : 'bg-success');

            $svcCpu = $serviciosHost->first(function ($s) {
                return stripos($s->nagios_service_description ?? '', 'cpu') !== false;
            });
            $svcMemoria = $serviciosHost->first(function ($s) {
                return stripos($s->nagios_service_description ?? '', 'mem') !== false;
            });
            $svcDisco = $serviciosHost->first(function ($s) {
                $desc = $s->nagios_service_description ?? '';
                return stripos($desc, 'disk') !== false || stripos($desc, 'disco') !== false;
            });
        @endphp

        <div class="col-md-6 col-lg-4 col-xl-3">
            <div class="card host-card h-100 {{ $hostActivo ? '' : 'inactive' }}"
                 data-host="{{ $nombreHost }}"
                 data-estatus="{{ $estadoMasGrave }}"
                 data-tipo="{{ $primerServicio->tipo }}"
                 data-id="{{ $primerServicio->id_Monitoreo }}">
                <div class="card-header d-flex justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2" style="min-width:0;">
                        <i class="fas {{ str_contains(strtolower($nombreHost), 'pc') || str_contains(strtolower($nombreHost), '192') ? 'fa-desktop' : 'fa-server' }}" aria-hidden="true"></i>
                        <strong class="text-truncate" title="{{ $nombreHost }}">{{ $nombreHost }}</strong>
                    </div>
                    <span class="badge {{ $primerServicio->badgeClass() }} service-badge">{{ $primerServicio->etiquetaEstatus() }}</span>
                </div>
                <div class="card-body p-3">
                    <div class="mb-3">
                        <span class="meta-label">Servicios</span>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($serviciosHost as $s)
                                <span class="badge {{ $s->badgeClass() }} service-badge" data-estatus="{{ $s->estatus }}"
                                      title="{{ $s->mensaje_ultimo_check }}">
                                    {{ $s->nagios_service_description ?? $s->nombre }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    {{-- Métricas del sistema (CPU, RAM, Disco) --}}
                    @if($svcCpu || $svcMemoria || $svcDisco)
                        <div class="metric-block">
                            <span class="meta-label">Recursos del sistema</span>

                            @if($svcCpu && $svcCpu->cpu_porcentaje)
                                <div class="metric-row">
                                    <div class="metric-head">
                                        <span class="metric-name">CPU</span>
                                        <span class="metric-value">{{ number_format($svcCpu->cpu_porcentaje, 1) }}%</span>
                                    </div>
                                    <div class="progress" role="progressbar"
                                         aria-valuenow="{{ $svcCpu->cpu_porcentaje }}" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar {{ $colorBarra((float) $svcCpu->cpu_porcentaje) }}" style="width: {{ $svcCpu->cpu_porcentaje }}%;"></div>
                                    </div>
                                </div>
                            @endif

                            @if($svcMemoria && $svcMemoria->memoria_porcentaje)
                                <div class="metric-row">
                                    <div class="metric-head">
                                        <span class="metric-name">Memoria RAM</span>
                                        <span class="metric-value">{{ number_format($svcMemoria->memoria_porcentaje, 1) }}%</span>
                                    </div>
                                    <div class="progress" role="progressbar"
                                         aria-valuenow="{{ $svcMemoria->memoria_porcentaje }}" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar {{ $colorBarra((float) $svcMemoria->memoria_porcentaje) }}" style="width: {{ $svcMemoria->memoria_porcentaje }}%;"></div>
                                    </div>
                                    <span class="metric-sub">
                                        {{ number_format($svcMemoria->memoria_usada_gb ?? 0, 2) }} GB de {{ number_format($svcMemoria->memoria_total_gb ?? 0, 2) }} GB
                                    </span>
                                </div>
                            @endif

                            @if($svcDisco && $svcDisco->disco_porcentaje)
                                <div class="metric-row">
                                    <div class="metric-head">
                                        <span class="metric-name">Disco</span>
                                        <span class="metric-value">{{ number_format($svcDisco->disco_porcentaje, 1) }}%</span>
                                    </div>
                                    <div class="progress" role="progressbar"
                                         aria-valuenow="{{ $svcDisco->disco_porcentaje }}" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar {{ $colorBarra((float) $svcDisco->disco_porcentaje) }}" style="width: {{ $svcDisco->disco_porcentaje }}%;"></div>
                                    </div>
                                    <span class="metric-sub">
                                        {{ number_format($svcDisco->disco_usado_gb ?? 0, 2) }} GB de {{ number_format($svcDisco->disco_total_gb ?? 0, 2) }} GB
                                    </span>
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="mt-3">
                        <div class="d-flex justify-content-between align-items-baseline mb-1">
                            <span class="meta-label mb-0">Último check</span>
                            <span class="meta-value">{{ $primerServicio->ultimo_check ? $primerServicio->ultimo_check->format('d/m/Y H:i') : '—' }}</span>
                        </div>
                        @if($primerServicio->ultimo_sincronizado_nagios)
                            <div class="d-flex justify-content-between align-items-baseline">
                                <span class="meta-label mb-0">Últ. sinc. Nagios</span>
                                <span class="meta-value text-primary">{{ $primerServicio->ultimo_sincronizado_nagios->format('d/m/Y H:i') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <div class="btn-group btn-group-sm w-100" role="group" aria-label="Acciones">
                        <button class="btn btn-outline-primary btn-editar"
                                data-id="{{ $primerServicio->id_Monitoreo }}"
                                data-nombre="{{ $primerServicio->nombre }}"
                                data-tipo="{{ $primerServicio->tipo }}"
                                data-host="{{ $primerServicio->host_o_url }}"
                                data-puerto="{{ $primerServicio->puerto ?? '' }}"
                                data-intervalo="{{ $primerServicio->intervalo_minutos }}"
                                title="Editar">
                            <i class="fas fa-edit" aria-hidden="true"></i>
                        </button>
                        <button class="btn btn-outline-{{ $hostActivo ? 'danger' : 'success' }} btn-toggle"
                                data-id="{{ $primerServicio->id_Monitoreo }}"
                                data-url="{{ route('monitoreo.toggle', $primerServicio->id_Monitoreo) }}"
                                title="{{ $hostActivo ? 'Desactivar' : 'Activar' }}">
                            <i class="fas fa-{{ $hostActivo ? 'ban' : 'check' }}" aria-hidden="true"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-eliminar"
                                data-id="{{ $primerServicio->id_Monitoreo }}"
                                data-nombre="{{ $primerServicio->nombre }}"
                                title="Eliminar">
                            <i class="fas fa-trash" aria-hidden="true"></i>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('monitoreo.destroy', $primerServicio->id_Monitoreo) }}" class="d-none btn-eliminar-form" data-id="{{ $primerServicio->id_Monitoreo }}">
                        @csrf @method('DELETE')
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    @if($hostsPaginados->isEmpty())
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="fas fa-heart-pulse fa-3x mb-3 opacity-25"></i>
                    <p class="mb-0">{{ $monitoreos->isEmpty() ? 'No hay objetivos de monitoreo registrados' : 'No hay equipos en esta página' }}</p>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Paginación: 9 tarjetas por página --}}
@if($hostsPaginados->hasPages())
    <div class="pager-footer">
        <span class="table-info">
            Mostrando {{ $hostsPaginados->firstItem() }}–{{ $hostsPaginados->lastItem() }} de {{ $hostsPaginados->total() }} equipos
        </span>
        {{ $hostsPaginados->links('pagination::bootstrap-5') }}
    </div>
@endif

<!-- Modal: Nuevo objetivo -->
<div class="modal fade" id="modalNuevo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('monitoreo.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Nuevo objetivo de monitoreo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" class="form-control" required placeholder="Ej. Servidor de correos">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo *</label>
                        <select name="tipo" class="form-select" required>
                            <option value="servidor">Servidor</option>
                            <option value="servicio">Servicio</option>
                            <option value="sitio_web">Sitio web</option>
                            <option value="equipo">Equipo de cómputo</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Host o URL *</label>
                        <input type="url" name="host_o_url" class="form-control" required placeholder="Ej. 192.168.1.10 o https://ejemplo.com">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Puerto</label>
                            <input type="number" name="puerto" class="form-control" placeholder="80">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Intervalo (min) *</label>
                            <input type="number" name="intervalo_minutos" class="form-control" value="5" min="1" max="1440" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Editar objetivo -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="formEditar" method="POST" action="">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar objetivo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit-id">
                    <div class="mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" id="edit-nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo *</label>
                        <select name="tipo" id="edit-tipo" class="form-select" required>
                            <option value="servidor">Servidor</option>
                            <option value="servicio">Servicio</option>
                            <option value="sitio_web">Sitio web</option>
                            <option value="equipo">Equipo de cómputo</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Host o URL *</label>
                        <input type="url" name="host_o_url" id="edit-host" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Puerto</label>
                            <input type="number" name="puerto" id="edit-puerto" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Intervalo (min) *</label>
                            <input type="number" name="intervalo_minutos" id="edit-intervalo" class="form-control" min="1" max="1440" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar cambios</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const formEditar = document.getElementById('formEditar');
    const modalEditar = new bootstrap.Modal(document.getElementById('modalEditar'));
    const cards = document.querySelectorAll('.host-card');
    const buscador = document.getElementById('buscarMonitoreo');
    const filtroTipo = document.getElementById('filtroTipo');
    const filtroEstatus = document.getElementById('filtroEstatus');

    // Abrir modal de editar
    document.querySelectorAll('.btn-editar').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit-id').value = this.dataset.id;
            document.getElementById('edit-nombre').value = this.dataset.nombre;
            document.getElementById('edit-tipo').value = this.dataset.tipo;
            document.getElementById('edit-host').value = this.dataset.host;
            document.getElementById('edit-puerto').value = this.dataset.puerto || '';
            document.getElementById('edit-intervalo').value = this.dataset.intervalo;
            formEditar.action = "{{ route('monitoreo.update', 'ID') }}".replace('ID', this.dataset.id);
            modalEditar.show();
        });
    });

    // Toggle activo/inactivo
    document.querySelectorAll('.btn-toggle').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('¿Cambiar el estado de este objetivo?')) return;
            fetch(this.dataset.url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(() => location.reload())
            .catch(() => alert('Error al cambiar el estado'));
        });
    });

    // Eliminar
    document.querySelectorAll('.btn-eliminar').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('¿Eliminar el objetivo "' + btn.dataset.nombre + '"?')) return;
            const form = document.querySelector('.btn-eliminar-form[data-id="' + btn.dataset.id + '"]');
            form.requestSubmit();
        });
    });

    // Filtros en tiempo real
    function aplicarFiltros() {
        const texto = buscador.value.toLowerCase();
        const tipo = filtroTipo.value;
        const estatus = filtroEstatus.value;

        cards.forEach(card => {
            const host = card.dataset.host.toLowerCase();
            const tipoCard = card.dataset.tipo;
            const estatusCard = card.dataset.estatus;

            const coincideTexto = host.includes(texto);
            const coincideTipo = !tipo || tipoCard === tipo;
            const coincideEstatus = !estatus || estatusCard === estatus;

            card.closest('.col-md-6').style.display = (coincideTexto && coincideTipo && coincideEstatus) ? '' : 'none';
        });
    }

    buscador.addEventListener('input', aplicarFiltros);
    filtroTipo.addEventListener('change', aplicarFiltros);
    filtroEstatus.addEventListener('change', aplicarFiltros);

    document.getElementById('btnLimpiarFiltros').addEventListener('click', function() {
        buscador.value = '';
        filtroTipo.value = '';
        filtroEstatus.value = '';
        aplicarFiltros();
    });

    // Auto-sincronización con Nagios cada 5 minutos (300.000 ms)
    const INTERVALO_SYNC_NAGIOS = 5 * 60 * 1000;
    let syncEnProgreso = false;

    async function sincronizarNagiosAutomatico() {
        if (syncEnProgreso) return;

        const btn = document.querySelector('.btn-sincronizar-nagios');
        const badge = document.getElementById('badgeUltimaSync');

        syncEnProgreso = true;
        btn?.classList.add('btn-loading');
        btn?.setAttribute('disabled', 'disabled');

        try {
            const response = await fetch("{{ route('monitoreo.sincronizar-nagios') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const ahora = new Date();
                badge.textContent = ahora.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
                badge.className = 'badge bg-success';
                badge.title = 'Última sincronización automática: ' + ahora.toLocaleString('es-MX');

                mostrarToastAutoSync('Sincronización automática con Nagios completada', 'success');

                // Recargar la página después de 1.5s para mostrar los datos actualizados
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                mostrarToastAutoSync('Error al sincronizar con Nagios: HTTP ' + response.status, 'danger');
            }
        } catch (error) {
            mostrarToastAutoSync('Error de conexión con Nagios: ' + error.message, 'danger');
        } finally {
            syncEnProgreso = false;
            btn?.classList.remove('btn-loading');
            btn?.removeAttribute('disabled');
        }
    }

    function mostrarToastAutoSync(mensaje, tipo) {
        // Reutilizar SweetAlert2 que ya está cargado en layouts/app.blade.php
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: tipo === 'success' ? 'success' : 'error',
            title: mensaje,
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    }

    // Iniciar auto-sincronización cada 5 minutos
    setInterval(sincronizarNagiosAutomatico, INTERVALO_SYNC_NAGIOS);

    // Sincronizar inmediatamente después de 10 segundos de cargar la página
    setTimeout(sincronizarNagiosAutomatico, 10000);
});
</script>
@endsection
