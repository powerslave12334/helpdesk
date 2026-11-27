<div wire:poll.10s>
    {{-- Estilos locales para KPIs y paginación --}}
    <style>
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; }
        .kpi { display: flex; align-items: center; gap: 12px; padding: 16px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); min-height: 82px; }
        .kpi-icon { width: 42px; height: 42px; flex-shrink: 0; border-radius: var(--radius-sm); display: grid; place-items: center; background: var(--surface-strong); color: var(--muted); font-size: 16px; }
        .kpi-value { font-size: 26px; font-weight: 700; line-height: 1.05; letter-spacing: -0.02em; color: var(--text); font-variant-numeric: tabular-nums; }
        .kpi-label { font-size: 11px; font-weight: 600; letter-spacing: 0.05em; color: var(--muted); margin-top: 2px; }
        .kpi-sub { font-size: 11px; color: var(--muted); margin-top: 2px; }

        /* Jerarquía de KPIs */
        .kpi--primary { border-color: var(--primary); background: var(--primary-soft); }
        .kpi--primary .kpi-icon { background: var(--primary); color: #ffffff; }
        .kpi--primary .kpi-value { color: var(--primary-hover); }

        .kpi--secondary { border-color: var(--border); }
        .kpi--secondary .kpi-icon { color: var(--muted); }

        .kpi--alert { border-color: #c2410c; }
        .kpi--alert .kpi-icon { background: #c2410c; color: #ffffff; }
        .kpi--alert .kpi-value { color: #c2410c; }

        /* Paginación */
        .pager-footer { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-top: 16px; }
        .pager-footer .pagination { margin: 0; }
        .pagination a { display: inline-block; padding: 6px 12px; margin: 0 2px; border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text); text-decoration: none; font-size: 13px; }
        .pagination a.active { background: var(--primary); border-color: var(--primary); color: #ffffff; }
        .pagination a:hover { background: var(--primary-soft); border-color: var(--primary); }

        @media (max-width: 576px) { .kpi-value { font-size: 22px; } .pager-footer { justify-content: center; } }
    </style>

    {{-- Cabecera con botones y estado --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="fas fa-heart-pulse me-2"></i>Monitoreo de Sistemas</h4>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <button
                wire:click="sincronizarNagios"
                wire:loading.attr="disabled"
                class="btn btn-outline-primary btn-sincronizar-nagios"
            >
                <i class="fas fa-sync me-1"></i>
                <span wire:loading.remove>Sincronizar con Nagios</span>
                <span wire:loading>Sincronizando...</span>
            </button>
            <span class="badge bg-secondary" id="badgeUltimaSync" title="Última sincronización automática">
                <i class="fas fa-clock me-1"></i>{{ $ultimaSync }}
            </span>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="kpi-grid mb-4" aria-label="Resumen de monitoreo">
        <div class="kpi kpi--primary">
            <div class="kpi-icon"><i class="fas fa-server" aria-hidden="true"></i></div>
            <div>
                <div class="kpi-value">{{ $resumen['total'] }}</div>
                <div class="kpi-label">Total Equipos</div>
            </div>
        </div>
        <div class="kpi kpi--secondary">
            <div class="kpi-icon"><i class="fas fa-check-circle" aria-hidden="true"></i></div>
            <div>
                <div class="kpi-value">{{ $resumen['online'] }}</div>
                <div class="kpi-label">Online</div>
            </div>
        </div>
        <div class="kpi kpi--secondary">
            <div class="kpi-icon"><i class="fas fa-times-circle" aria-hidden="true"></i></div>
            <div>
                <div class="kpi-value">{{ $resumen['offline'] }}</div>
                <div class="kpi-label">Fuera de línea</div>
            </div>
        </div>
        <div class="kpi kpi--alert">
            <div class="kpi-icon"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i></div>
            <div>
                <div class="kpi-value">{{ $resumen['degradado'] }}</div>
                <div class="kpi-label">Degradado</div>
            </div>
        </div>
    </div>

    {{-- Filtros y búsqueda --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="table-toolbar">
                <div class="table-toolbar-left">
                    <div class="table-search">
                        <i class="fas fa-search"></i>
                        <input
                            type="text"
                            wire:model.debounce.500ms="buscar"
                            placeholder="Buscar por nombre, host, IP o servicio..."
                            class="form-control"
                        >
                    </div>
                    <select wire:model="tipo" class="table-filter">
                        <option value="">Todos los tipos</option>
                        @foreach($tipos as $tipo)
                            <option value="{{ $tipo }}">{{ ucfirst($tipo) }}</option>
                        @endforeach
                    </select>
                    <select wire:model="estatus" class="table-filter">
                        <option value="">Todos los estatus</option>
                        <option value="online">Online</option>
                        <option value="offline">Fuera de línea</option>
                        <option value="degradado">Degradado</option>
                        <option value="desconocido">Desconocido</option>
                    </select>
                    <button wire:click="$refresh" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-times me-1"></i>Limpiar
                    </button>
                </div>
                <div class="table-toolbar-right">
                    <span class="table-info">
                        Mostrando {{ count($hosts) }} de {{ $totalHosts }} equipos
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Grid de cards por host --}}
    <div class="row g-2" id="gridMonitoreo">
        @foreach($hosts as $c)
            @php
                $colorBarra = fn ($valor) => $valor > 80 ? 'bg-danger' : ($valor > 60 ? 'bg-warning' : 'bg-success');
            @endphp

            <div class="col-md-4 col-lg-4 col-xl-4">
                <div class="card host-card h-100 {{ $c['activo'] ? '' : 'inactive' }}"
                     wire:key="host-{{ $c['id_Monitoreo'] }}"
                     data-host="{{ $c['nombreHost'] }}"
                     data-ip="{{ $c['host_o_url'] }}"
                     data-estatus="{{ $c['estadoMasGrave'] }}"
                     data-tipo="{{ $c['tipo'] }}"
                     data-id="{{ $c['id_Monitoreo'] }}">
                    <div class="card-header d-flex justify-content-between align-items-center gap-2">
                        <div class="d-flex align-items-center gap-2" style="min-width:0;">
                            <i class="fas {{ str_contains(strtolower($c['nombreHost']), 'pc') || str_contains(strtolower($c['nombreHost']), '192') ? 'fa-desktop' : 'fa-server' }}" aria-hidden="true"></i>
                            <div style="min-width:0;">
                                <strong class="text-truncate" title="{{ $c['nombreHost'] }}">{{ $c['nombreHost'] }}</strong>
                                <div class="text-truncate" style="font-size: 11px; color: var(--muted);">{{ $c['host_o_url'] }}</div>
                            </div>
                        </div>
                        <span class="badge {{ $c['servicios'][0]['badgeClass'] ?? 'bg-secondary' }} service-badge">{{ $c['servicios'][0]['etiquetaEstatus'] ?? $c['estadoMasGrave'] }}</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="mb-3">
                            <span class="meta-label">Servicios</span>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($c['servicios'] as $s)
                                    <span class="badge {{ $s['badgeClass'] }} service-badge" data-estatus="{{ $s['estatus'] }}"
                                          title="{{ $s['mensaje_ultimo_check'] }}">
                                        {{ $s['nagios_service_description'] ?? $s['nombre'] }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        {{-- Métricas del sistema (CPU, RAM, Disco) --}}
                        @if($c['hayMetricas'])
                            <div class="metric-block">
                                <span class="meta-label">Recursos del sistema</span>

                                @if($c['svcCpu'])
                                    <div class="metric-row">
                                        <div class="metric-head">
                                            <span class="metric-name">CPU</span>
                                            <span class="metric-value">{{ number_format($c['svcCpu']['cpu_porcentaje'], 1) }}%</span>
                                        </div>
                                        <div class="progress" role="progressbar"
                                             aria-valuenow="{{ $c['svcCpu']['cpu_porcentaje'] }}" aria-valuemin="0" aria-valuemax="100">
                                            <div class="progress-bar {{ $colorBarra($c['svcCpu']['cpu_porcentaje']) }}" style="width: {{ $c['svcCpu']['cpu_porcentaje'] }}%;"></div>
                                        </div>
                                    </div>
                                @endif

                                @if($c['svcMemoria'])
                                    <div class="metric-row">
                                        <div class="metric-head">
                                            <span class="metric-name">Memoria RAM</span>
                                            <span class="metric-value">{{ number_format($c['svcMemoria']['memoria_porcentaje'], 1) }}%</span>
                                        </div>
                                        <div class="progress" role="progressbar"
                                             aria-valuenow="{{ $c['svcMemoria']['memoria_porcentaje'] }}" aria-valuemin="0" aria-valuemax="100">
                                            <div class="progress-bar {{ $colorBarra($c['svcMemoria']['memoria_porcentaje']) }}" style="width: {{ $c['svcMemoria']['memoria_porcentaje'] }}%;"></div>
                                        </div>
                                        <span class="metric-sub">
                                            {{ number_format($c['svcMemoria']['memoria_usada_gb'] ?? 0, 2) }} GB de {{ number_format($c['svcMemoria']['memoria_total_gb'] ?? 0, 2) }} GB
                                        </span>
                                    </div>
                                @endif

                                @if($c['svcDisco'])
                                    <div class="metric-row">
                                        <div class="metric-head">
                                            <span class="metric-name">Disco</span>
                                            <span class="metric-value">{{ number_format($c['svcDisco']['disco_porcentaje'], 1) }}%</span>
                                        </div>
                                        <div class="progress" role="progressbar"
                                             aria-valuenow="{{ $c['svcDisco']['disco_porcentaje'] }}" aria-valuemin="0" aria-valuemax="100">
                                            <div class="progress-bar {{ $colorBarra($c['svcDisco']['disco_porcentaje']) }}" style="width: {{ $c['svcDisco']['disco_porcentaje'] }}%;"></div>
                                        </div>
                                        <span class="metric-sub">
                                            {{ number_format($c['svcDisco']['disco_usado_gb'] ?? 0, 2) }} GB de {{ number_format($c['svcDisco']['disco_total_gb'] ?? 0, 2) }} GB
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div class="mt-3">
                            <div class="d-flex justify-content-between align-items-baseline mb-1">
                                <span class="meta-label mb-0">Último check</span>
                                <span class="meta-value">{{ $c['ultimo_check'] }}</span>
                            </div>
                            @if($c['ultimo_sincronizado_nagios'])
                                <div class="d-flex justify-content-between align-items-baseline">
                                    <span class="meta-label mb-0">Últ. sinc. Nagios</span>
                                    <span class="meta-value text-primary">{{ $c['ultimo_sincronizado_nagios'] }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="btn-group btn-group-sm w-100" role="group" aria-label="Acciones">
                            <button
                                class="btn btn-outline-primary btn-editar"
                                data-id="{{ $c['id_Monitoreo'] }}"
                                data-nombre="{{ $c['nombreHost'] }}"
                                data-tipo="{{ $c['tipo'] }}"
                                data-host="{{ $c['host_o_url'] }}"
                                data-puerto=""
                                data-intervalo="5"
                                title="Editar"
                                data-bs-toggle="modal"
                                data-bs-target="#modalEditar"
                            >
                                <i class="fas fa-edit" aria-hidden="true"></i>
                            </button>
                            <button
                                wire:click="toggle({{ $c['id_Monitoreo'] }})"
                                class="btn btn-outline-{{ $c['activo'] ? 'danger' : 'success' }} btn-toggle"
                                title="{{ $c['activo'] ? 'Desactivar' : 'Activar' }}"
                            >
                                <i class="fas fa-{{ $c['activo'] ? 'ban' : 'check' }}" aria-hidden="true"></i>
                            </button>
                            <button
                                wire:click="eliminar({{ $c['id_Monitoreo'] }})"
                                wire:confirm="¿Eliminar este objetivo?"
                                class="btn btn-outline-danger btn-eliminar"
                                title="Eliminar"
                            >
                                <i class="fas fa-trash" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        @if(count($hosts) === 0)
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center text-muted py-5">
                        <i class="fas fa-heart-pulse fa-3x mb-3 opacity-25"></i>
                        <p class="mb-0">No hay objetivos de monitoreo registrados</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Paginación: 6 tarjetas por página --}}
    @if($totalHosts > 6)
        <div class="pager-footer">
            <span class="table-info">
                Mostrando {{ count($hosts) }} de {{ $totalHosts }} equipos
            </span>
            <div class="pagination">
                @for($i = 1; $i <= ceil($totalHosts / 6); $i++)
                    <a
                        href="?page={{ $i }}&buscar={{ $buscar }}&tipo={{ $tipo }}&estatus={{ $estatus }}"
                        wire:navigate
                        class="{{ $i == $page ? 'active' : '' }}"
                    >
                        {{ $i }}
                    </a>
                @endfor
            </div>
        </div>
    @endif
</div>
