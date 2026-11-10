@extends('layouts.app')
@section('title', 'Personalizar Campos - ' . $tipo->descripcion)

@section('styles')
<style>
    .campo-card {
        border: 1px solid var(--border);
        border-radius: var(--radius-md, 8px);
        background: var(--surface);
        padding: 14px 16px;
        transition: all 0.2s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .campo-card:hover {
        border-color: var(--primary);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    .campo-card.is-active {
        border-color: rgba(37, 99, 235, 0.4);
        background: rgba(37, 99, 235, 0.02);
    }

    .seccion-header {
        background: var(--surface-strong, #f8fafc);
        border: 1px solid var(--border);
        border-radius: var(--radius-md, 8px);
        padding: 12px 18px;
        margin-bottom: 16px;
    }

    .template-btn {
        font-size: 13px;
        padding: 6px 12px;
        border-radius: 20px;
    }

</style>
@endsection

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <a href="{{ route('inventario.tipoEquipo.index') }}" class="text-decoration-none text-muted small">
            <i class="fas fa-arrow-left me-1"></i>Volver a Tipos de Equipo
        </a>
        <h4 class="mb-0 mt-1">
            <i class="fas fa-sliders-h text-primary me-2"></i>Personalizar Campos:
            <strong>{{ $tipo->descripcion }}</strong>
        </h4>
        <span class="text-muted small">Selecciona los campos que se solicitarán y mostrarán en el formulario para este
            tipo de equipo.</span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('inventario.campos.index') }}" class="btn btn-outline-primary"
            title="Definir nuevos campos en el catálogo">
            <i class="fas fa-list-check me-1"></i>Gestionar Catálogo
        </a>
        <a href="{{ route('inventario.tipoEquipo.index') }}" class="btn btn-secondary">Cancelar</a>
        <button type="button" class="btn btn-primary" onclick="document.getElementById('formCampos').submit()">
            <i class="fas fa-save me-1"></i>Guardar Cambios
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-1"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Plantillas rápidas y acciones -->
<div class="card mb-4">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="fw-bold small text-muted text-uppercase me-1"><i class="fas fa-magic me-1"></i>Plantillas
                    Rápidas:</span>
                <button type="button" class="btn btn-outline-primary btn-sm template-btn"
                    onclick="aplicarPlantilla('computo')">
                    <i class="fas fa-laptop me-1"></i>Cómputo (PC/Laptop)
                </button>
                <button type="button" class="btn btn-outline-info btn-sm template-btn"
                    onclick="aplicarPlantilla('monitor')">
                    <i class="fas fa-tv me-1"></i>Pantalla / Monitor
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm template-btn"
                    onclick="aplicarPlantilla('periferico')">
                    <i class="fas fa-keyboard me-1"></i>Periférico (Teclado/Mouse)
                </button>
                <button type="button" class="btn btn-outline-warning text-dark btn-sm template-btn"
                    onclick="aplicarPlantilla('energia')">
                    <i class="fas fa-plug me-1"></i>Energía (Regulador/UPS)
                </button>
                <button type="button" class="btn btn-outline-success btn-sm template-btn"
                    onclick="aplicarPlantilla('impresion')">
                    <i class="fas fa-print me-1"></i>Impresora
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm template-btn"
                    onclick="aplicarPlantilla('gps')">
                    <i class="fas fa-location-dot me-1"></i>GPS
                </button>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-light btn-sm" onclick="marcarTodos(true)">
                    <i class="fas fa-check-double me-1"></i>Marcar todos
                </button>
                <button type="button" class="btn btn-light btn-sm" onclick="marcarTodos(false)">
                    <i class="fas fa-times me-1"></i>Desmarcar todos
                </button>
            </div>
        </div>
    </div>
</div>

<form id="formCampos" method="POST" action="{{ route('inventario.tipoEquipo.guardarCampos', $tipo->id_TipoEquipo) }}">
    @csrf

    @foreach($seccionesCatalogo as $claveSec => $seccion)
    <div class="mb-4">
        <div class="seccion-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="{{ $seccion['icono'] }} text-primary fa-lg"></i>
                <div>
                    <h6 class="mb-0 fw-bold">{{ $seccion['titulo'] }}</h6>
                    <small class="text-muted">{{ $seccion['descripcion'] }}</small>
                </div>
            </div>
            <div>
                <button type="button" class="btn btn-sm btn-link text-decoration-none"
                    onclick="toggleSeccion('sec_{{ $claveSec }}', true)">Marcar grupo</button>
                <span class="text-muted">|</span>
                <button type="button" class="btn btn-sm btn-link text-decoration-none text-muted"
                    onclick="toggleSeccion('sec_{{ $claveSec }}', false)">Desmarcar grupo</button>
            </div>
        </div>

        <div class="row g-3" id="sec_{{ $claveSec }}">
            @foreach($seccion['campos'] as $claveCampo => $info)
            @php
            $estaAsignado = $asignados->has($claveCampo);
            $esRequerido = $estaAsignado ? (bool) $asignados->get($claveCampo)->es_requerido : false;
            @endphp
            <div class="col-md-4 col-sm-6">
                <div class="campo-card {{ $estaAsignado ? 'is-active' : '' }}" id="card_{{ $claveCampo }}">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <label class="form-check-label fw-bold mb-0 cursor-pointer"
                                for="chk_campo_{{ $claveCampo }}">
                                {{ $info['etiqueta'] }}
                                @if(!empty($info['es_custom']))
                                <span class="badge bg-info text-dark ms-1" title="Campo personalizado">Nuevo</span>
                                @endif
                            </label>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input switch-campo" type="checkbox" name="campos[]"
                                    value="{{ $claveCampo }}" id="chk_campo_{{ $claveCampo }}"
                                    data-campo="{{ $claveCampo }}" {{ $estaAsignado ? 'checked' : '' }}
                                    onchange="actualizarCard('{{ $claveCampo }}')">
                            </div>
                        </div>
                        <p class="text-muted small mb-3">{{ $info['ayuda'] }}</p>
                    </div>

                    <div class="pt-2 border-top d-flex justify-content-between align-items-center">
                        @if(!empty($info['es_custom']))
                        <small class="text-muted font-monospace"><i class="fas fa-wand-magic-sparkles me-1"></i>atributos.{{ $claveCampo }}</small>
                        @else
                        <small class="text-muted font-monospace">tblEquipo.{{ $claveCampo }}</small>
                        @endif
                        <div class="form-check m-0">
                            <input class="form-check-input check-req" type="checkbox" name="requeridos[]"
                                value="{{ $claveCampo }}" id="chk_req_{{ $claveCampo }}"
                                {{ $esRequerido ? 'checked' : '' }} {{ !$estaAsignado ? 'disabled' : '' }}>
                            <label class="form-check-label small text-muted cursor-pointer"
                                for="chk_req_{{ $claveCampo }}" title="¿Es un campo de captura obligatoria?">
                                Obligatorio
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach

    <div class="card my-4 sticky-bottom shadow-lg border-primary">
        <div class="card-body d-flex justify-content-between align-items-center py-3">
            <div>
                <span class="fw-bold" id="contadorCampos">0</span> campos seleccionados para
                <strong>{{ $tipo->descripcion }}</strong>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('inventario.tipoEquipo.index') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fas fa-save me-1"></i>Guardar Configuración
                </button>
            </div>
        </div>
    </div>
</form>

@endsection

@section('scripts')
<script>
    const PLANTILLAS = {
        computo: [
            'marca', 'modelo', 'numeroSerie', 'nombreEquipo', 'ubicacion', 'id_Oficinas',
            'esCompartido', 'observaciones', 'fechaAdquisicion', 'costo', 'estadoGarantia', 'fechaFinGarantia',
            'id_PeriodoMantenimiento', 'fechaUltimoMantenimiento',
            'serieMotherboard', 'procesador', 'memoriaRam', 'almacenamiento', 'tamanoAlmacenamiento',
            'sistemaOperativo', 'especificaciones', 'ip', 'mac'
        ],
        monitor: [
            'marca', 'modelo', 'numeroSerie', 'nombreEquipo', 'ubicacion', 'id_Oficinas',
            'observaciones', 'fechaAdquisicion', 'costo', 'estadoGarantia', 'fechaFinGarantia',
            'tamano', 'puertos'
        ],
        periferico: [
            'marca', 'numeroSerie', 'ubicacion', 'id_Oficinas', 'observaciones',
            'fechaAdquisicion', 'costo', 'tipoTeclado', 'conectividad'
        ],
        energia: [
            'marca', 'modelo', 'numeroSerie', 'nombreEquipo', 'ubicacion', 'id_Oficinas',
            'observaciones', 'fechaAdquisicion', 'costo', 'capacidad', 'voltajeEntrada', 'voltajeSalida',
            'contactos'
        ],
        impresion: [
            'marca', 'modelo', 'numeroSerie', 'nombreEquipo', 'ubicacion', 'id_Oficinas',
            'observaciones', 'fechaAdquisicion', 'costo', 'estadoGarantia', 'fechaFinGarantia',
            'id_PeriodoMantenimiento', 'fechaUltimoMantenimiento',
            'tipoImpresora', 'colorImpresion', 'multifuncion', 'funciones', 'ip', 'mac'
        ],
        gps: [
            'marca', 'modelo', 'numeroSerie', 'nombreEquipo', 'ubicacion', 'id_Oficinas',
            'observaciones', 'fechaAdquisicion', 'costo', 'tipoGps', 'imei', 'operador', 'lineaSim'
        ]
    };

    function actualizarCard(campo) {
        const chk = document.getElementById('chk_campo_' + campo);
        const card = document.getElementById('card_' + campo);
        const req = document.getElementById('chk_req_' + campo);

        if (chk.checked) {
            card.classList.add('is-active');
            req.disabled = false;
        } else {
            card.classList.remove('is-active');
            req.checked = false;
            req.disabled = true;
        }
        actualizarContador();
    }

    function actualizarContador() {
        const seleccionados = document.querySelectorAll('.switch-campo:checked').length;
        document.getElementById('contadorCampos').textContent = seleccionados;
    }

    function marcarTodos(marcar) {
        document.querySelectorAll('.switch-campo').forEach(chk => {
            chk.checked = marcar;
            actualizarCard(chk.dataset.campo);
        });
    }

    function toggleSeccion(secId, marcar) {
        const sec = document.getElementById(secId);
        if (!sec) return;
        sec.querySelectorAll('.switch-campo').forEach(chk => {
            chk.checked = marcar;
            actualizarCard(chk.dataset.campo);
        });
    }

    function aplicarPlantilla(nombre) {
        const campos = PLANTILLAS[nombre] || [];
        marcarTodos(false);
        campos.forEach(c => {
            const chk = document.getElementById('chk_campo_' + c);
            if (chk) {
                chk.checked = true;
                actualizarCard(c);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', actualizarContador);

</script>
@endsection
