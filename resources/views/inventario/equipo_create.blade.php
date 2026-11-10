@extends('layouts.app')
@section('title', 'Nuevo Equipo - HelpDesk')

@section('content')
@php
$tipoActual = $tipoSeleccionado ? (int) $tipoSeleccionado->id_TipoEquipo : null;
$marcaActual = old('marca');
$modeloActual = old('modelo');
$ubicacionActual = old('ubicacion');
$procesadorActual = old('procesador');
$almacenamientoActual = old('almacenamiento');
$soActual = old('sistemaOperativo');
$tipoCamaraActual = old('tipoCamara');
$resolucionActual = old('resolucion');
$conexionActual = old('conexion');
$tipoGpsActual = old('tipoGps');
$imeiActual = old('imei');
$operadorActual = old('operador');
$lineaSimActual = old('lineaSim');
$tipoImpresoraActual = old('tipoImpresora');
$colorImpresionActual = old('colorImpresion');
$multifuncionActual = old('multifuncion');
$funcionesMarcadas = old('funciones', []);
@endphp
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">
        <i class="fas fa-plus me-2"></i>Nuevo Equipo
        @if($tipoSeleccionado)
        <span class="badge bg-primary ms-2">{{ $tipoSeleccionado->descripcion }}</span>
        @endif
    </h4>
    <a href="{{ $tipoSeleccionado ? route('inventario.equipo.tipos') : route('inventario.equipo.index') }}"
        class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Volver</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('inventario.equipo.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-3 mb-3" data-campo="marca">
                    <label class="form-label">Marca *</label>
                    <input type="text" id="inpMarcaLibre" name="marca" class="form-control" value="{{ $marcaActual }}"
                        required>
                </div>
                <div class="col-md-3 mb-3" data-campo="marca_dropdown">
                    <label class="form-label">Marca *</label>
                    <select name="marca_dropdown" id="selMarcaPc" class="form-select">
                        <option value="">Seleccionar...</option>
                        @foreach($catalogos['marcas'] as $m)
                        <option value="{{ $m }}"
                            {{ $marcaActual !== null && strcasecmp($marcaActual, $m) === 0 ? 'selected' : '' }}>{{ $m }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3" data-campo="modelo">
                    <label class="form-label">Modelo *</label>
                    <input type="text" id="inpModeloLibre" name="modelo" class="form-control"
                        value="{{ $modeloActual }}" required>
                </div>
                <div class="col-md-3 mb-3" data-campo="modelo_dropdown">
                    <label class="form-label">Modelo *</label>
                    <select name="modelo_dropdown" id="selModeloPc" class="form-select">
                        <option value="">Seleccionar...</option>
                        @foreach($catalogos['modelos'] as $mod)
                        <option value="{{ $mod->nombre }}" data-marca="{{ $mod->marca->nombre ?? '' }}"
                            {{ $modeloActual !== null && strcasecmp($modeloActual, $mod->nombre) === 0 ? 'selected' : '' }}>
                            {{ $mod->nombre }}</option>
                        @endforeach
                        @if($modeloActual && !$catalogos['modelos']->contains(fn ($m) => strcasecmp($m->nombre,
                        $modeloActual) === 0))
                        <option value="{{ $modeloActual }}" data-marca="{{ $marcaActual }}" selected>{{ $modeloActual }}
                        </option>
                        @endif
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Nombre Equipo</label>
                    <input type="text" name="nombreEquipo" class="form-control" value="{{ old('nombreEquipo') }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Tipo *</label>
                    <select name="id_TipoEquipo" id="selTipoEquipo" class="form-select" required
                        {{ $tipoSeleccionado ? 'disabled' : '' }}>
                        @foreach($tiposEquipo as $t)
                        <option value="{{ $t->id_TipoEquipo }}"
                            {{ ($tipoSeleccionado && $tipoSeleccionado->id_TipoEquipo == $t->id_TipoEquipo) ? 'selected' : '' }}>
                            {{ $t->descripcion }}</option>
                        @endforeach
                    </select>
                    @if($tipoSeleccionado)
                    <input type="hidden" name="id_TipoEquipo" value="{{ $tipoSeleccionado->id_TipoEquipo }}">
                    @endif
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Estatus *</label>
                    <select name="id_Estatus" class="form-select" required>
                        @foreach($estatus as $e)
                        <option value="{{ $e->id_Estatus }}">{{ $e->descripcion }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3" data-campo="numeroSerie">
                    <label class="form-label">Número de Serie</label>
                    <input type="text" name="numeroSerie" class="form-control" value="{{ old('numeroSerie') }}">
                </div>
                <div class="col-md-3 mb-3" data-campo="ubicacion">
                    <label class="form-label">Ubicación</label>
                    <input type="text" id="inpUbicacionLibre" name="ubicacion" class="form-control"
                        value="{{ $ubicacionActual }}">
                </div>
                <div class="col-md-3 mb-3" data-campo="ubicacion_dropdown">
                    <label class="form-label">Ubicación</label>
                    <select name="ubicacion_dropdown" id="selUbicacionPc" class="form-select">
                        <option value="">Seleccionar...</option>
                        @foreach($catalogos['departamentos'] as $d)
                        <option value="{{ $d }}"
                            {{ $ubicacionActual !== null && strcasecmp($ubicacionActual, $d) === 0 ? 'selected' : '' }}>
                            {{ $d }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3" data-campo="id_Oficinas">
                    <label class="form-label">Oficina</label>
                    <select name="id_Oficinas" class="form-select">
                        <option value="">Sin oficina</option>
                        @foreach($oficinas as $o)
                        <option value="{{ $o->id_Oficinas }}">{{ $o->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3" data-campo="observaciones">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2">{{ old('observaciones') }}</textarea>
                </div>
                <div class="col-md-4 mb-3" data-campo="id_PeriodoMantenimiento">
                    <label class="form-label">Período Mantenimiento</label>
                    <select name="id_PeriodoMantenimiento" class="form-select">
                        <option value="0">Sin período</option>
                        @foreach(\App\Models\PeriodoMantenimiento::where('activo', true)->get() as $p)
                        <option value="{{ $p->id_PeriodoMantenimiento }}"
                            {{ old('id_PeriodoMantenimiento') == $p->id_PeriodoMantenimiento ? 'selected' : '' }}>
                            {{ $p->descripcion }} ({{ $p->meses }} meses)</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3" data-campo="fechaUltimoMantenimiento">
                    <label class="form-label">Último Mantenimiento</label>
                    <input type="date" name="fechaUltimoMantenimiento" class="form-control"
                        value="{{ old('fechaUltimoMantenimiento') }}">
                </div>
                <div class="col-md-4 mb-3" data-campo="fechaAdquisicion">
                    <label class="form-label">Fecha de Adquisición</label>
                    <input type="date" name="fechaAdquisicion" class="form-control"
                        value="{{ old('fechaAdquisicion') }}">
                </div>
                <div class="col-md-4 mb-3" data-campo="costo">
                    <label class="form-label">Costo</label>
                    <input type="number" name="costo" class="form-control" min="0" step="0.01" placeholder="0.00"
                        value="{{ old('costo') }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 mb-3 d-flex align-items-end">
                    <div class="form-check" data-campo="esCompartido">
                        <input class="form-check-input" type="checkbox" name="esCompartido" id="chkCompartido"
                            value="1">
                        <label class="form-check-label" for="chkCompartido">Equipo Compartido</label>
                    </div>
                </div>
            </div>
            <h6 class="mt-3"><i class="fas fa-microchip me-1"></i>Especificaciones</h6>
            <div class="row">
                <div class="col-md-3 mb-3" data-campo="serieMotherboard"><label class="form-label">Serie
                        Motherboard</label><input type="text" name="serieMotherboard" class="form-control"
                        value="{{ old('serieMotherboard') }}"></div>
                <div class="col-md-3 mb-3" data-campo="procesador"><label class="form-label">Procesador</label><input
                        type="text" id="inpProcesadorLibre" name="procesador" class="form-control"
                        value="{{ $procesadorActual }}"></div>
                <div class="col-md-3 mb-3" data-campo="procesador_dropdown">
                    <label class="form-label">Procesador</label>
                    <select name="procesador_dropdown" id="selProcesadorPc" class="form-select">
                        <option value="">Seleccionar...</option>
                        @foreach($catalogos['procesadores'] as $p)
                        <option value="{{ $p }}"
                            {{ $procesadorActual !== null && strcasecmp($procesadorActual, $p) === 0 ? 'selected' : '' }}>
                            {{ $p }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3" data-campo="memoriaRam"><label class="form-label">Memoria RAM</label><input
                        type="text" name="memoriaRam" class="form-control" value="{{ old('memoriaRam') }}"></div>
                <div class="col-md-3 mb-3" data-campo="almacenamiento"><label
                        class="form-label">Almacenamiento</label><input type="text" id="inpAlmacenamientoLibre"
                        name="almacenamiento" class="form-control" value="{{ $almacenamientoActual }}"></div>
                <div class="col-md-3 mb-3" data-campo="almacenamiento_dropdown">
                    <label class="form-label">Tipo de Almacenamiento</label>
                    <select name="almacenamiento_dropdown" id="selAlmacenamientoPc" class="form-select">
                        <option value="">Seleccionar...</option>
                        @foreach($catalogos['tiposAlmacenamiento'] as $ta)
                        <option value="{{ $ta }}"
                            {{ $almacenamientoActual !== null && strcasecmp($almacenamientoActual, $ta) === 0 ? 'selected' : '' }}>
                            {{ $ta }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3" data-campo="tamanoAlmacenamiento"><label class="form-label">Tamaño de
                        Almacenamiento</label><input type="text" name="tamanoAlmacenamiento" class="form-control"
                        value="{{ old('tamanoAlmacenamiento') }}" placeholder="Ej. 512 GB, 1 TB"></div>
                <div class="col-md-3 mb-3" data-campo="sistemaOperativo"><label class="form-label">Sistema
                        Operativo</label><input type="text" id="inpSistemaOperativoLibre" name="sistemaOperativo"
                        class="form-control" value="{{ $soActual }}"></div>
                <div class="col-md-3 mb-3" data-campo="sistemaOperativo_dropdown">
                    <label class="form-label">Sistema Operativo</label>
                    <select name="sistemaOperativo_dropdown" id="selSistemaOperativoPc" class="form-select">
                        <option value="">Seleccionar...</option>
                        @foreach($catalogos['sistemasOperativos'] as $so)
                        <option value="{{ $so }}"
                            {{ $soActual !== null && strcasecmp($so, $soActual) === 0 ? 'selected' : '' }}>{{ $so }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3" data-campo="tipoTeclado"><label class="form-label">Tipo de
                        Teclado</label><select name="tipoTeclado" class="form-select">
                        <option value="">Seleccionar...</option>
                        @foreach($catalogos['tiposTeclado'] as $tt)
                        <option value="{{ $tt }}"
                            {{ old('tipoTeclado') !== null && strcasecmp(old('tipoTeclado'), $tt) === 0 ? 'selected' : '' }}>
                            {{ $tt }}</option>
                        @endforeach
                    </select></div>
                <div class="col-md-3 mb-3" data-campo="conectividad"><label
                        class="form-label">Conectividad</label><select name="conectividad" class="form-select">
                        <option value="">Seleccionar...</option>
                        @foreach($catalogos['conectividades'] as $c)
                        <option value="{{ $c }}"
                            {{ old('conectividad') !== null && strcasecmp(old('conectividad'), $c) === 0 ? 'selected' : '' }}>
                            {{ $c }}</option>
                        @endforeach
                    </select></div>
                <div class="col-md-3 mb-3" data-campo="capacidad"><label class="form-label">Capacidad</label><input
                        type="text" name="capacidad" class="form-control" placeholder="Ej. 1500 VA"
                        value="{{ old('capacidad') }}"></div>
                <div class="col-md-3 mb-3" data-campo="voltajeEntrada"><label class="form-label">Voltaje
                        Entrada</label><input type="text" name="voltajeEntrada" class="form-control"
                        placeholder="Ej. 110V" value="{{ old('voltajeEntrada') }}"></div>
                <div class="col-md-3 mb-3" data-campo="voltajeSalida"><label class="form-label">Voltaje
                        Salida</label><input type="text" name="voltajeSalida" class="form-control"
                        placeholder="Ej. 110V" value="{{ old('voltajeSalida') }}"></div>
                <div class="col-md-3 mb-3" data-campo="contactos"><label class="form-label">Número de
                        Contactos</label><input type="number" name="contactos" class="form-control" min="0" max="999"
                        value="{{ old('contactos') }}"></div>
                <div class="col-md-3 mb-3" data-campo="tamano"><label class="form-label">Tamaño</label><input
                        type="text" name="tamano" class="form-control" placeholder="Ej. 24 pulgadas"
                        value="{{ old('tamano') }}"></div>
                <div class="col-md-6 mb-3" data-campo="puertos">
                    <label class="form-label">Puertos</label>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach($puertosFijos as $i => $puerto)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="puertos[]" id="chkPuerto{{ $i }}"
                                value="{{ $puerto }}"
                                {{ in_array($puerto, old('puertos', []), true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="chkPuerto{{ $i }}">{{ $puerto }}</label>
                        </div>
                        @endforeach
                    </div>
                    <input type="text" name="puertosOtros" class="form-control mt-2"
                        placeholder="Otros puertos (separados por coma)" value="{{ old('puertosOtros') }}">
                </div>
                <div class="col-md-3 mb-3" data-campo="tipoImpresora"><label class="form-label">Tipo de
                        impresora</label><select name="tipoImpresora" class="form-select">
                        <option value="">Seleccionar...</option>
                        @foreach($catalogos['tiposImpresora'] as $ti)
                        <option value="{{ $ti }}"
                            {{ $tipoImpresoraActual !== null && strcasecmp($tipoImpresoraActual, $ti) === 0 ? 'selected' : '' }}>
                            {{ $ti }}</option>
                        @endforeach
                        @if($tipoImpresoraActual && !$catalogos['tiposImpresora']->contains(fn ($t) => strcasecmp($t,
                        $tipoImpresoraActual) === 0))
                        <option value="{{ $tipoImpresoraActual }}" selected>{{ $tipoImpresoraActual }}</option>
                        @endif
                    </select></div>
                <div class="col-md-3 mb-3" data-campo="tipoImpresora_color"><label
                        class="form-label">Impresión</label><select name="colorImpresion" class="form-select">
                        <option value="">Seleccionar...</option>
                        @foreach(['Color', 'Monocromática'] as $op)
                        <option value="{{ $op }}"
                            {{ $colorImpresionActual !== null && $colorImpresionActual === $op ? 'selected' : '' }}>
                            {{ $op }}</option>
                        @endforeach
                    </select></div>
                <div class="col-md-3 mb-3" data-campo="multifuncion"><label
                        class="form-label">Multifunción</label><select name="multifuncion" class="form-select">
                        <option value="">Seleccionar...</option>
                        <option value="1" {{ $multifuncionActual === '1' ? 'selected' : '' }}>Sí</option>
                        <option value="0" {{ $multifuncionActual === '0' ? 'selected' : '' }}>No</option>
                    </select></div>
                <div class="col-md-6 mb-3" data-campo="funciones">
                    <label class="form-label">Funciones</label>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach($funcionesFijas as $i => $funcion)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="funciones[]" id="chkFuncion{{ $i }}"
                                value="{{ $funcion }}"
                                {{ in_array($funcion, $funcionesMarcadas, true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="chkFuncion{{ $i }}">{{ $funcion }}</label>
                        </div>
                        @endforeach
                    </div>
                    <input type="text" name="funcionesOtros" class="form-control mt-2"
                        placeholder="Otras funciones (separadas por coma)" value="{{ old('funcionesOtros') }}">
                </div>
                <div class="col-md-3 mb-3" data-campo="tipoCamara"><label class="form-label">Tipo de
                        cámara</label><select name="tipoCamara" class="form-select">
                        <option value="">Seleccionar...</option>
                        @foreach($catalogos['tiposCamara'] as $tc)
                        <option value="{{ $tc }}"
                            {{ $tipoCamaraActual !== null && strcasecmp($tipoCamaraActual, $tc) === 0 ? 'selected' : '' }}>
                            {{ $tc }}</option>
                        @endforeach
                        @if($tipoCamaraActual && !$catalogos['tiposCamara']->contains(fn ($t) => strcasecmp($t,
                        $tipoCamaraActual) === 0))
                        <option value="{{ $tipoCamaraActual }}" selected>{{ $tipoCamaraActual }}</option>
                        @endif
                    </select></div>
                <div class="col-md-3 mb-3" data-campo="resolucion"><label class="form-label">Resolución</label><select
                        name="resolucion" class="form-select">
                        <option value="">Seleccionar...</option>
                        @foreach($resoluciones as $valor => $etiqueta)
                        <option value="{{ $valor }}" {{ $resolucionActual === $valor ? 'selected' : '' }}>
                            {{ $etiqueta }}</option>
                        @endforeach
                    </select></div>
                <div class="col-md-3 mb-3" data-campo="conexion"><label class="form-label">Conexión</label><select
                        name="conexion" class="form-select">
                        <option value="">Seleccionar...</option>
                        @foreach($conexiones as $op)
                        <option value="{{ $op }}"
                            {{ $conexionActual !== null && strcasecmp($conexionActual, $op) === 0 ? 'selected' : '' }}>
                            {{ $op }}</option>
                        @endforeach
                    </select></div>

                {{-- Campos personalizados del catálogo (definidos en inventario/campos) --}}
                @foreach($camposCustom as $custom)
                @php $valorCustom = old('attributes.' . $custom->name); @endphp
                <div class="col-md-3 mb-3" data-campo="{{ $custom->name }}">
                    <label class="form-label">
                        {{ $custom->label }}
                        @if(in_array($custom->name, $tipoSeleccionadoConfig ?? [], true))*@endif
                    </label>

                    @switch($custom->type)
                    @case('textarea')
                    <textarea name="attributes[{{ $custom->name }}]" class="form-control" rows="2"
                        placeholder="{{ $custom->help }}">{{ $valorCustom }}</textarea>
                    @break

                    @case('number')
                    @case('decimal')
                    <input type="number" name="attributes[{{ $custom->name }}]" class="form-control"
                        step="{{ $custom->type === 'decimal' ? '0.01' : '1' }}" value="{{ $valorCustom }}"
                        placeholder="{{ $custom->help }}">
                    @break

                    @case('date')
                    <input type="date" name="attributes[{{ $custom->name }}]" class="form-control"
                        value="{{ $valorCustom }}">
                    @break

                    @case('datetime')
                    <input type="datetime-local" name="attributes[{{ $custom->name }}]" class="form-control"
                        value="{{ $valorCustom }}">
                    @break

                    @case('boolean')
                    <select name="attributes[{{ $custom->name }}]" class="form-select">
                        <option value="">Seleccionar...</option>
                        <option value="1" {{ $valorCustom == '1' ? 'selected' : '' }}>Sí</option>
                        <option value="0" {{ $valorCustom == '0' ? 'selected' : '' }}>No</option>
                    </select>
                    @break

                    @case('select')
                    <select name="attributes[{{ $custom->name }}]" class="form-select">
                        <option value="">Seleccionar...</option>
                        @foreach(($custom->validation_rules['opciones'] ?? []) as $op)
                        <option value="{{ $op }}" {{ $valorCustom === $op ? 'selected' : '' }}>{{ $op }}</option>
                        @endforeach
                    </select>
                    @break

                    @case('tags')
                    @php
                    $seleccionados = is_array($valorCustom) ? $valorCustom : old('attributes.' . $custom->name, []);
                    if (is_string($seleccionados)) $seleccionados = array_filter(array_map('trim', explode(',', $seleccionados)));
                    @endphp
                    <div class="d-flex flex-wrap gap-2">
                        @foreach(($custom->validation_rules['opciones'] ?? []) as $i => $op)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                name="attributes[{{ $custom->name }}][]" value="{{ $op }}"
                                id="chk_{{ $custom->name }}_{{ $i }}"
                                {{ in_array($op, $seleccionados, true) ? 'checked' : '' }}>
                            <label class="form-check-label small" for="chk_{{ $custom->name }}_{{ $i }}">{{ $op }}</label>
                        </div>
                        @endforeach
                    </div>
                    @break

                    @default
                    <input type="text" name="attributes[{{ $custom->name }}]" class="form-control"
                        value="{{ $valorCustom }}" placeholder="{{ $custom->help }}">
                    @endswitch

                    @if($custom->help)
                    <div class="form-text">{{ $custom->help }}</div>
                    @endif
                </div>
                @endforeach
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar Equipo</button>
                <a href="{{ $tipoSeleccionado ? route('inventario.equipo.tipos') : route('inventario.equipo.index') }}"
                    class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const TIPOS_CATALOGO = @json(array_map('strval', config('equipo_perfiles.con_dropdown')));
    const MAPA_CAMPOS_BD = @json($camposPorTipo);
    const selTipo = document.getElementById('selTipoEquipo');
    const pares = [
        ['inpMarcaLibre', 'selMarcaPc'],
        ['inpModeloLibre', 'selModeloPc'],
        ['inpUbicacionLibre', 'selUbicacionPc'],
        ['inpProcesadorLibre', 'selProcesadorPc'],
        ['inpAlmacenamientoLibre', 'selAlmacenamientoPc'],
        ['inpSistemaOperativoLibre', 'selSistemaOperativoPc'],
    ];

    function forzarValorEnSelect(sel, valor) {
        if (!valor) return;
        if (![...sel.options].some(o => o.value === valor)) {
            sel.add(new Option(valor, valor, true, true));
        }
        sel.value = valor;
    }

    function filtrarModelos() {
        const selMarca = document.getElementById('selMarcaPc');
        const selModelo = document.getElementById('selModeloPc');
        if (!selMarca || !selModelo) return;
        const marca = selMarca.value;
        const marcador = selModelo.querySelector('option[value=""]');
        if (marcador) marcador.textContent = marca ? 'Seleccionar...' : 'Selecciona primero la marca';
        let primerVisible = '';
        [...selModelo.options].forEach(o => {
            if (o.value === '') return;
            const dm = o.dataset.marca || '';
            const visible = dm === '' || (marca && dm === marca);
            o.hidden = !visible;
            o.style.display = visible ? '' : 'none';
            if (visible && !primerVisible) primerVisible = o.value;
        });
        const actual = selModelo.options[selModelo.selectedIndex];
        if (marca && actual && actual.value !== '' && actual.hidden) {
            selModelo.value = primerVisible;
        }
    }

    function aplicarTipo() {
        const tipo = selTipo.value;
        if (!tipo) return;

        document.querySelectorAll('[data-campo]').forEach(container => {
            container.classList.add('d-none');
            container.querySelectorAll('input, select, textarea').forEach(i => i.disabled = true);
        });

        const permitidos = MAPA_CAMPOS_BD[tipo] || [];
        permitidos.forEach(campo => {
            const container = document.querySelector(`[data-campo="${campo}"]`);
            if (container) {
                container.classList.remove('d-none');
                container.querySelectorAll('input, select, textarea').forEach(i => i.disabled = false);
            }
        });

        const usaCatalogo = TIPOS_CATALOGO.includes(tipo);
        pares.forEach(([libreId, pcId]) => {
            const libre = document.getElementById(libreId);
            const pc = document.getElementById(pcId);
            if (!libre || !pc) return;
            const librePermitido = permitidos.includes(libre.name);
            const pcPermitido = permitidos.includes(pc.name);

            if (usaCatalogo) {
                if (pcPermitido) {
                    document.querySelector(`[data-campo="${pc.name}"]`).classList.remove('d-none');
                    document.querySelector(`[data-campo="${libre.name}"]`).classList.add('d-none');
                    pc.disabled = false;
                    libre.disabled = true;
                }
            } else {
                if (librePermitido) {
                    document.querySelector(`[data-campo="${libre.name}"]`).classList.remove('d-none');
                    document.querySelector(`[data-campo="${pc.name}"]`).classList.add('d-none');
                    libre.disabled = false;
                    pc.disabled = true;
                }
            }
        });

        if (usaCatalogo) {
            pares.forEach(([libreId, pcId]) => {
                const libre = document.getElementById(libreId),
                    pc = document.getElementById(pcId);
                if (!libre || !pc) return;
                if (!libre.value && pc.value) libre.value = pc.value;
            });
            filtrarModelos();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (selTipo) selTipo.addEventListener('change', aplicarTipo);
        aplicarTipo();
        filtrarModelos();
    });

</script>
@endsection
