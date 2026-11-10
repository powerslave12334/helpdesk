@extends('layouts.app')
@section('title', 'Editar Equipo - HelpDesk')

@section('content')
@php
    $perfiles = config('equipo_perfiles');
    $tipoActual = (int) $equipo->id_TipoEquipo;
    $enDropdown = in_array($tipoActual, $perfiles['con_dropdown'], true);
    $enMarcaDropdown = in_array($tipoActual, $perfiles['con_marca_dropdown'], true);
    $conModeloDropdown = in_array($tipoActual, $perfiles['con_modelo_dropdown'], true);
    $sinModelo = in_array($tipoActual, $perfiles['sin_modelo'], true);
    $enCamposPc = in_array($tipoActual, $perfiles['campos']['pc'], true);
    $enLista = fn (array $tipos) => in_array($tipoActual, $tipos, true);

    $marcaActual = old('marca', $equipo->marca);
    $modeloActual = old('modelo', $equipo->modelo);
    $ubicacionActual = old('ubicacion', $equipo->ubicacion);
    $procesadorActual = old('procesador', $equipo->procesador);
    $almacenamientoActual = old('almacenamiento', $equipo->almacenamiento);
    $soActual = old('sistemaOperativo', $equipo->sistemaOperativo);
    $puertosEquipo = array_values(array_filter(array_map('trim', explode(',', (string) ($equipo->puertos ?? '')))));
    $puertosMarcados = array_values(array_intersect($puertosEquipo, $puertosFijos));
    $puertosOtrosActual = implode(', ', array_values(array_diff($puertosEquipo, $puertosFijos)));

    $claseLibre = fn () => 'col-md-3 mb-3 campo-libre' . ($enDropdown ? ' d-none' : '');
    $attrLibre = fn () => $enDropdown ? 'disabled' : '';
    $claseMarcaLibre = fn () => 'col-md-3 mb-3' . ($enMarcaDropdown ? ' d-none' : '');
    $attrMarcaLibre = fn () => $enMarcaDropdown ? 'disabled' : '';
    $claseMarcaPc = fn () => 'col-md-3 mb-3' . ($enMarcaDropdown ? '' : ' d-none');
    $attrMarcaPc = fn () => $enMarcaDropdown ? '' : 'disabled';
    $claseModeloLibre = fn () => 'col-md-3 mb-3' . (($conModeloDropdown || $sinModelo) ? ' d-none' : '');
    $attrModeloLibre = fn () => ($conModeloDropdown || $sinModelo) ? 'disabled' : '';
    $clasePcComun = fn () => 'col-md-3 mb-3 campo-pc' . ($enDropdown ? '' : ' d-none');
    $attrPcComun = fn () => $enDropdown ? '' : 'disabled';
    $claseModeloPc = fn () => 'col-md-3 mb-3' . ($conModeloDropdown ? '' : ' d-none');
    $attrModeloPc = fn () => $conModeloDropdown ? '' : 'disabled';
    $clasePcSolo = fn () => 'col-md-3 mb-3 campo-pc' . ($enCamposPc ? '' : ' d-none');
    $attrPcSolo = fn () => $enCamposPc ? '' : 'disabled';
    $claseEn = fn (array $tipos, string $col = 'col-md-3') => $col . ' mb-3' . ($enLista($tipos) ? '' : ' d-none');
    $attrEn = fn (array $tipos) => $enLista($tipos) ? '' : 'disabled';
    $claseSin = fn (array $tipos, string $col = 'col-md-4') => $col . ' mb-3' . ($enLista($tipos) ? ' d-none' : '');
    $attrSin = fn (array $tipos) => $enLista($tipos) ? 'disabled' : '';

    $listaSinTextos = implode(',', $perfiles['sin_textos']);
    $listaDropdown = implode(',', $perfiles['con_dropdown']);
    $listaMarcaDropdown = implode(',', $perfiles['con_marca_dropdown']);
    // Texto libre de Modelo oculto: tipos con dropdown de modelo + tipos sin modelo.
    $tiposModeloTexto = array_unique(array_merge($perfiles['con_modelo_dropdown'], $perfiles['sin_modelo']));
    sort($tiposModeloTexto);
    $listaModeloTexto = implode(',', $tiposModeloTexto);
    $listaModeloDropdown = implode(',', $perfiles['con_modelo_dropdown']);
    $listaCamposPc = implode(',', $perfiles['campos']['pc']);
    $listaPerifericos = implode(',', $perfiles['campos']['perifericos']);
    $listaMonitor = implode(',', $perfiles['campos']['monitor']);
    $listaTipoTeclado = implode(',', $perfiles['campos']['tipo_teclado']);
    $listaConectividad = implode(',', $perfiles['campos']['conectividad']);
    $listaRegulador = implode(',', $perfiles['campos']['regulador']);
    $listaImpresora = implode(',', $perfiles['campos']['impresora']);
    $listaCamara = implode(',', $perfiles['campos']['camara']);
    $resoluciones = ['1MP' => '1MP', '2MP' => '2MP (1080p)', '4MP' => '4MP', '5MP' => '5MP', '8MP' => '8MP (4K)'];
    $conexiones = ['PoE', 'Cableado', 'Inalámbrica'];
    $tipoCamaraActual = old('tipoCamara', $equipo->tipoCamara);
    $resolucionActual = old('resolucion', $equipo->resolucion);
    $conexionActual = old('conexion', $equipo->conexion);
    // Serie Motherboard / Memoria RAM ocultas también para impresora (sin_internos).
    $sinTextoInternos = array_values(array_unique(array_merge($perfiles['sin_textos'], $perfiles['sin_internos'])));
    $listaSinTextoInternos = implode(',', $sinTextoInternos);
    $funcionesFijas = ['Imprimir', 'Copiar', 'Escanear', 'Fax'];
    $funcionesEquipo = array_values(array_filter(array_map('trim', explode(',', (string) ($equipo->funciones ?? '')))));
    $funcionesMarcadas = old('funciones', $funcionesEquipo);
    $tipoImpresoraActual = old('tipoImpresora', $equipo->tipoImpresora);
    $colorImpresionActual = old('colorImpresion', $equipo->colorImpresion);
    $multifuncionActual = old('multifuncion', $equipo->multifuncion === null ? '' : ($equipo->multifuncion ? '1' : '0'));
    $listaGps = implode(',', $perfiles['campos']['gps']);
    $tipoGpsActual = old('tipoGps', $equipo->tipoGps);
    $imeiActual = old('imei', $equipo->imei);
    $operadorActual = old('operador', $equipo->operador);
    $operadoresGps = config('equipo_perfiles.operadores_gps', []);
    $lineaSimActual = old('lineaSim', $equipo->lineaSim);
    // IP y MAC también se ocultan en los tipos sin_textos_pc (GPS).
    $sinTextoIpMac = array_values(array_unique(array_merge($perfiles['sin_textos'], $perfiles['sin_textos_pc'])));
    $listaSinTextoIpMac = implode(',', $sinTextoIpMac);
    // Procesador / Almacenamiento / SO en texto libre se ocultan en
    // con_dropdown (contraparte de catálogo) y en sin_textos_pc (GPS).
    $listaTextoPcLibre = implode(',', array_unique(array_merge($perfiles['con_dropdown'], $perfiles['sin_textos_pc'])));
    $enTextoPcLibre = $enDropdown || in_array($tipoActual, $perfiles['sin_textos_pc'], true);
    $claseTextoPcLibre = fn () => 'col-md-3 mb-3 campo-libre' . ($enTextoPcLibre ? ' d-none' : '');
    $attrTextoPcLibre = fn () => $enTextoPcLibre ? 'disabled' : '';
@endphp
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Equipo #{{ $equipo->id_Equipo }}</h4>
    <a href="{{ route('inventario.equipo.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Volver</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('inventario.equipo.update', $equipo->id_Equipo) }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="{{ $claseMarcaLibre() }}" data-sin-tipos="{{ $listaMarcaDropdown }}">
                    <label class="form-label">Marca *</label>
                    <input type="text" id="inpMarcaLibre" name="marca" class="form-control" value="{{ $marcaActual }}" required {{ $attrMarcaLibre() }}>
                </div>
                <div class="{{ $claseMarcaPc() }}" data-tipos="{{ $listaMarcaDropdown }}">
                    <label class="form-label">Marca *</label>
                    <select name="marca" id="selMarcaPc" class="form-select" required {{ $attrMarcaPc() }}>
                        <option value="">Seleccionar...</option>
                        @foreach($catalogos['marcas'] as $m)
                        <option value="{{ $m }}" {{ $marcaActual !== null && strcasecmp($marcaActual, $m) === 0 ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="{{ $claseModeloLibre() }}" data-sin-tipos="{{ $listaModeloTexto }}">
                    <label class="form-label">Modelo *</label>
                    <input type="text" id="inpModeloLibre" name="modelo" class="form-control" value="{{ $modeloActual }}" required {{ $attrModeloLibre() }}>
                </div>
                <div class="{{ $claseModeloPc() }}" data-tipos="{{ $listaModeloDropdown }}">
                    <label class="form-label">Modelo *</label>
                    <select name="modelo" id="selModeloPc" class="form-select" required {{ $attrModeloPc() }}>
                        <option value="">Seleccionar...</option>
                        @foreach($catalogos['modelos'] as $mod)
                        <option value="{{ $mod->nombre }}" data-marca="{{ $mod->marca->nombre ?? '' }}" {{ $modeloActual !== null && strcasecmp($modeloActual, $mod->nombre) === 0 ? 'selected' : '' }}>{{ $mod->nombre }}</option>
                        @endforeach
                        @if($modeloActual && !$catalogos['modelos']->contains(fn ($m) => strcasecmp($m->nombre, $modeloActual) === 0))
                        <option value="{{ $modeloActual }}" data-marca="{{ $marcaActual }}" selected>{{ $modeloActual }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Nombre Equipo</label>
                    <input type="text" name="nombreEquipo" class="form-control" value="{{ $equipo->nombreEquipo }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Tipo *</label>
                    <select name="id_TipoEquipo" id="selTipoEquipo" class="form-select" required>
                        @foreach($tiposEquipo as $t)
                        <option value="{{ $t->id_TipoEquipo }}" {{ $equipo->id_TipoEquipo == $t->id_TipoEquipo ? 'selected' : '' }}>{{ $t->descripcion }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Estatus *</label>
                    <select name="id_Estatus" class="form-select" required>
                        @foreach($estatus as $e)
                        <option value="{{ $e->id_Estatus }}" {{ $equipo->id_Estatus == $e->id_Estatus ? 'selected' : '' }}>{{ $e->descripcion }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Número de Serie</label>
                    <input type="text" name="numeroSerie" class="form-control" value="{{ $equipo->numeroSerie }}">
                </div>
                <div class="{{ $claseLibre() }}" data-sin-tipos="{{ $listaDropdown }}">
                    <label class="form-label">Ubicación</label>
                    <input type="text" id="inpUbicacionLibre" name="ubicacion" class="form-control" value="{{ $ubicacionActual }}" {{ $attrLibre() }}>
                </div>
                <div class="{{ $clasePcComun() }}" data-tipos="{{ $listaDropdown }}">
                    <label class="form-label">Ubicación</label>
                    <select name="ubicacion" id="selUbicacionPc" class="form-select" {{ $attrPcComun() }}>
                        <option value="">Seleccionar...</option>
                        @foreach($catalogos['departamentos'] as $d)
                        <option value="{{ $d }}" {{ $ubicacionActual !== null && strcasecmp($ubicacionActual, $d) === 0 ? 'selected' : '' }}>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Oficina</label>
                    <select name="id_Oficinas" class="form-select">
                        <option value="">Sin oficina</option>
                        @foreach($oficinas as $o)
                        <option value="{{ $o->id_Oficinas }}" {{ $equipo->id_Oficinas == $o->id_Oficinas ? 'selected' : '' }}>{{ $o->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2">{{ $equipo->observaciones }}</textarea>
                </div>
                <div class="{{ $claseSin($perfiles['sin_textos']) }}" data-sin-tipos="{{ $listaSinTextos }}">
                    <label class="form-label">Período Mantenimiento</label>
                    <select name="id_PeriodoMantenimiento" class="form-select" {{ $attrSin($perfiles['sin_textos']) }}>
                        <option value="0">Sin período</option>
                        @foreach(\App\Models\PeriodoMantenimiento::where('activo', true)->get() as $p)
                        <option value="{{ $p->id_PeriodoMantenimiento }}" {{ $equipo->id_PeriodoMantenimiento == $p->id_PeriodoMantenimiento ? 'selected' : '' }}>{{ $p->descripcion }} ({{ $p->meses }} meses)</option>
                        @endforeach
                    </select>
                </div>
                <div class="{{ $claseSin($perfiles['sin_textos']) }}" data-sin-tipos="{{ $listaSinTextos }}">
                    <label class="form-label">Último Mantenimiento</label>
                    <input type="date" name="fechaUltimoMantenimiento" class="form-control" value="{{ old('fechaUltimoMantenimiento', $equipo->fechaUltimoMantenimiento ? $equipo->fechaUltimoMantenimiento->format('Y-m-d') : '') }}" {{ $attrSin($perfiles['sin_textos']) }}>
                </div>
                <div class="{{ $claseEn($perfiles['campos']['perifericos'], 'col-md-4') }}" data-tipos="{{ $listaPerifericos }}">
                    <label class="form-label">Fecha de Adquisición</label>
                    <input type="date" name="fechaAdquisicion" class="form-control" value="{{ old('fechaAdquisicion', $equipo->fechaAdquisicion ? $equipo->fechaAdquisicion->format('Y-m-d') : '') }}" {{ $attrEn($perfiles['campos']['perifericos']) }}>
                </div>
                <div class="{{ $claseEn($perfiles['campos']['perifericos'], 'col-md-4') }}" data-tipos="{{ $listaPerifericos }}">
                    <label class="form-label">Costo</label>
                    <input type="number" name="costo" class="form-control" min="0" step="0.01" placeholder="0.00" value="{{ old('costo', $equipo->costo !== null ? $equipo->costo : '') }}" {{ $attrEn($perfiles['campos']['perifericos']) }}>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 mb-3 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="esCompartido" id="chkCompartido" value="1" {{ old('esCompartido', $equipo->esCompartido) ? 'checked' : '' }}>
                        <label class="form-check-label" for="chkCompartido">Equipo Compartido</label>
                    </div>
                </div>
            </div>

            <h6 class="mt-3"><i class="fas fa-microchip me-1"></i>Especificaciones</h6>
            <div class="row">
                <div class="{{ $claseSin($sinTextoInternos, 'col-md-3') }}" data-sin-tipos="{{ $listaSinTextoInternos }}"><label class="form-label">Serie Motherboard</label><input type="text" name="serieMotherboard" class="form-control" value="{{ $equipo->serieMotherboard }}" {{ $attrSin($sinTextoInternos) }}></div>
                <div class="{{ $claseTextoPcLibre() }}" data-sin-tipos="{{ $listaTextoPcLibre }}"><label class="form-label">Procesador</label><input type="text" id="inpProcesadorLibre" name="procesador" class="form-control" value="{{ $procesadorActual }}" {{ $attrTextoPcLibre() }}></div>
                <div class="{{ $clasePcSolo() }}" data-tipos="{{ $listaCamposPc }}">
                    <label class="form-label">Procesador</label>
                    <select name="procesador" id="selProcesadorPc" class="form-select" {{ $attrPcSolo() }}>
                        <option value="">Seleccionar...</option>
                        @foreach($catalogos['procesadores'] as $p)
                        <option value="{{ $p }}" {{ $procesadorActual !== null && strcasecmp($procesadorActual, $p) === 0 ? 'selected' : '' }}>{{ $p }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="{{ $claseSin($sinTextoInternos, 'col-md-3') }}" data-sin-tipos="{{ $listaSinTextoInternos }}"><label class="form-label">Memoria RAM</label><input type="text" name="memoriaRam" class="form-control" value="{{ $equipo->memoriaRam }}" {{ $attrSin($sinTextoInternos) }}></div>
                <div class="{{ $claseTextoPcLibre() }}" data-sin-tipos="{{ $listaTextoPcLibre }}"><label class="form-label">Almacenamiento</label><input type="text" id="inpAlmacenamientoLibre" name="almacenamiento" class="form-control" value="{{ $almacenamientoActual }}" {{ $attrTextoPcLibre() }}></div>
                <div class="{{ $clasePcSolo() }}" data-tipos="{{ $listaCamposPc }}">
                    <label class="form-label">Tipo de Almacenamiento</label>
                    <select name="almacenamiento" id="selAlmacenamientoPc" class="form-select" {{ $attrPcSolo() }}>
                        <option value="">Seleccionar...</option>
                        @foreach($catalogos['tiposAlmacenamiento'] as $ta)
                        <option value="{{ $ta }}" {{ $almacenamientoActual !== null && strcasecmp($almacenamientoActual, $ta) === 0 ? 'selected' : '' }}>{{ $ta }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="{{ $clasePcSolo() }}" data-tipos="{{ $listaCamposPc }}"><label class="form-label">Tamaño de Almacenamiento</label><input type="text" name="tamanoAlmacenamiento" class="form-control" value="{{ old('tamanoAlmacenamiento', $equipo->tamanoAlmacenamiento) }}" placeholder="Ej. 512 GB, 1 TB" {{ $attrPcSolo() }}></div>
                <div class="{{ $claseTextoPcLibre() }}" data-sin-tipos="{{ $listaTextoPcLibre }}"><label class="form-label">Sistema Operativo</label><input type="text" id="inpSistemaOperativoLibre" name="sistemaOperativo" class="form-control" value="{{ $soActual }}" {{ $attrTextoPcLibre() }}></div>
                <div class="{{ $clasePcSolo() }}" data-tipos="{{ $listaCamposPc }}">
                    <label class="form-label">Sistema Operativo</label>
                    <select name="sistemaOperativo" id="selSistemaOperativoPc" class="form-select" {{ $attrPcSolo() }}>
                        <option value="">Seleccionar...</option>
                        @foreach($catalogos['sistemasOperativos'] as $so)
                        <option value="{{ $so }}" {{ $soActual !== null && strcasecmp($soActual, $so) === 0 ? 'selected' : '' }}>{{ $so }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="{{ $claseEn($perfiles['campos']['tipo_teclado']) }}" data-tipos="{{ $listaTipoTeclado }}">
                    <label class="form-label">Tipo de Teclado</label>
                    <select name="tipoTeclado" class="form-select" {{ $attrEn($perfiles['campos']['tipo_teclado']) }}>
                        <option value="">Seleccionar...</option>
                        @foreach($catalogos['tiposTeclado'] as $tt)
                        <option value="{{ $tt }}" {{ old('tipoTeclado', $equipo->tipoTeclado) !== null && strcasecmp((string) old('tipoTeclado', $equipo->tipoTeclado), $tt) === 0 ? 'selected' : '' }}>{{ $tt }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="{{ $claseEn($perfiles['campos']['conectividad']) }}" data-tipos="{{ $listaConectividad }}">
                    <label class="form-label">Conectividad</label>
                    <select name="conectividad" class="form-select" {{ $attrEn($perfiles['campos']['conectividad']) }}>
                        <option value="">Seleccionar...</option>
                        @foreach($catalogos['conectividades'] as $c)
                        <option value="{{ $c }}" {{ old('conectividad', $equipo->conectividad) !== null && strcasecmp((string) old('conectividad', $equipo->conectividad), $c) === 0 ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="{{ $claseEn($perfiles['campos']['regulador']) }}" data-tipos="{{ $listaRegulador }}">
                    <label class="form-label">Capacidad</label>
                    <input type="text" name="capacidad" class="form-control" placeholder="Ej. 1500 VA" value="{{ old('capacidad', $equipo->capacidad) }}" {{ $attrEn($perfiles['campos']['regulador']) }}>
                </div>
                <div class="{{ $claseEn($perfiles['campos']['regulador']) }}" data-tipos="{{ $listaRegulador }}">
                    <label class="form-label">Voltaje Entrada</label>
                    <input type="text" name="voltajeEntrada" class="form-control" placeholder="Ej. 110V" value="{{ old('voltajeEntrada', $equipo->voltajeEntrada) }}" {{ $attrEn($perfiles['campos']['regulador']) }}>
                </div>
                <div class="{{ $claseEn($perfiles['campos']['regulador']) }}" data-tipos="{{ $listaRegulador }}">
                    <label class="form-label">Voltaje Salida</label>
                    <input type="text" name="voltajeSalida" class="form-control" placeholder="Ej. 110V" value="{{ old('voltajeSalida', $equipo->voltajeSalida) }}" {{ $attrEn($perfiles['campos']['regulador']) }}>
                </div>
                <div class="{{ $claseEn($perfiles['campos']['regulador']) }}" data-tipos="{{ $listaRegulador }}">
                    <label class="form-label">Número de Contactos</label>
                    <input type="number" name="contactos" class="form-control" min="0" max="999" value="{{ old('contactos', $equipo->contactos ?? '') }}" {{ $attrEn($perfiles['campos']['regulador']) }}>
                </div>
                <div class="{{ $claseEn($perfiles['campos']['monitor']) }}" data-tipos="{{ $listaMonitor }}">
                    <label class="form-label">Tamaño</label>
                    <input type="text" name="tamano" class="form-control" placeholder="Ej. 24 pulgadas" value="{{ old('tamano', $equipo->tamano) }}" {{ $attrEn($perfiles['campos']['monitor']) }}>
                </div>
                <div class="{{ $claseEn($perfiles['campos']['monitor'], 'col-md-6') }}" data-tipos="{{ $listaMonitor }}">
                    <label class="form-label">Puertos</label>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach($puertosFijos as $i => $puerto)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="puertos[]" id="chkPuerto{{ $i }}" value="{{ $puerto }}" {{ in_array($puerto, old('puertos', $puertosMarcados), true) ? 'checked' : '' }} {{ $attrEn($perfiles['campos']['monitor']) }}>
                            <label class="form-check-label" for="chkPuerto{{ $i }}">{{ $puerto }}</label>
                        </div>
                        @endforeach
                    </div>
                    <input type="text" name="puertosOtros" class="form-control mt-2" placeholder="Otros puertos (separados por coma)" value="{{ old('puertosOtros', $puertosOtrosActual) }}" {{ $attrEn($perfiles['campos']['monitor']) }}>
                </div>
                <div class="{{ $claseEn($perfiles['campos']['impresora']) }}" data-tipos="{{ $listaImpresora }}">
                    <label class="form-label">Tipo de impresora</label>
                    <select name="tipoImpresora" class="form-select" {{ $attrEn($perfiles['campos']['impresora']) }}>
                        <option value="">Seleccionar...</option>
                        @foreach($catalogos['tiposImpresora'] as $ti)
                        <option value="{{ $ti }}" {{ $tipoImpresoraActual !== null && strcasecmp((string) $tipoImpresoraActual, $ti) === 0 ? 'selected' : '' }}>{{ $ti }}</option>
                        @endforeach
                        @if($tipoImpresoraActual && !$catalogos['tiposImpresora']->contains(fn ($t) => strcasecmp($t, (string) $tipoImpresoraActual) === 0))
                        <option value="{{ $tipoImpresoraActual }}" selected>{{ $tipoImpresoraActual }}</option>
                        @endif
                    </select>
                </div>
                <div class="{{ $claseEn($perfiles['campos']['impresora']) }}" data-tipos="{{ $listaImpresora }}">
                    <label class="form-label">Impresión</label>
                    <select name="colorImpresion" class="form-select" {{ $attrEn($perfiles['campos']['impresora']) }}>
                        <option value="">Seleccionar...</option>
                        @foreach(['Color', 'Monocromática'] as $op)
                        <option value="{{ $op }}" {{ $colorImpresionActual !== null && $colorImpresionActual === $op ? 'selected' : '' }}>{{ $op }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="{{ $claseEn($perfiles['campos']['impresora']) }}" data-tipos="{{ $listaImpresora }}">
                    <label class="form-label">Multifunción</label>
                    <select name="multifuncion" class="form-select" {{ $attrEn($perfiles['campos']['impresora']) }}>
                        <option value="">Seleccionar...</option>
                        <option value="1" {{ $multifuncionActual === '1' ? 'selected' : '' }}>Sí</option>
                        <option value="0" {{ $multifuncionActual === '0' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
                <div class="{{ $claseEn($perfiles['campos']['impresora'], 'col-md-6') }}" data-tipos="{{ $listaImpresora }}">
                    <label class="form-label">Funciones</label>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach($funcionesFijas as $i => $funcion)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="funciones[]" id="chkFuncion{{ $i }}" value="{{ $funcion }}" {{ in_array($funcion, $funcionesMarcadas, true) ? 'checked' : '' }} {{ $attrEn($perfiles['campos']['impresora']) }}>
                            <label class="form-check-label" for="chkFuncion{{ $i }}">{{ $funcion }}</label>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="{{ $claseEn($perfiles['campos']['camara']) }}" data-tipos="{{ $listaCamara }}">
                    <label class="form-label">Tipo de cámara</label>
                    <select name="tipoCamara" class="form-select" {{ $attrEn($perfiles['campos']['camara']) }}>
                        <option value="">Seleccionar...</option>
                        @foreach($catalogos['tiposCamara'] as $tc)
                        <option value="{{ $tc }}" {{ $tipoCamaraActual !== null && strcasecmp((string) $tipoCamaraActual, $tc) === 0 ? 'selected' : '' }}>{{ $tc }}</option>
                        @endforeach
                        @if($tipoCamaraActual && !$catalogos['tiposCamara']->contains(fn ($t) => strcasecmp($t, (string) $tipoCamaraActual) === 0))
                        <option value="{{ $tipoCamaraActual }}" selected>{{ $tipoCamaraActual }}</option>
                        @endif
                    </select>
                </div>
                <div class="{{ $claseEn($perfiles['campos']['camara']) }}" data-tipos="{{ $listaCamara }}">
                    <label class="form-label">Resolución</label>
                    <select name="resolucion" class="form-select" {{ $attrEn($perfiles['campos']['camara']) }}>
                        <option value="">Seleccionar...</option>
                        @foreach($resoluciones as $valor => $etiqueta)
                        <option value="{{ $valor }}" {{ $resolucionActual === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="{{ $claseEn($perfiles['campos']['camara']) }}" data-tipos="{{ $listaCamara }}">
                    <label class="form-label">Conexión</label>
                    <select name="conexion" class="form-select" {{ $attrEn($perfiles['campos']['camara']) }}>
                        <option value="">Seleccionar...</option>
                        @foreach($conexiones as $op)
                        <option value="{{ $op }}" {{ $conexionActual !== null && $conexionActual === $op ? 'selected' : '' }}>{{ $op }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="{{ $claseEn($perfiles['campos']['gps']) }}" data-tipos="{{ $listaGps }}">
                    <label class="form-label">Tipo de GPS</label>
                    <select name="tipoGps" class="form-select" {{ $attrEn($perfiles['campos']['gps']) }}>
                        <option value="">Seleccionar...</option>
                        @foreach($catalogos['tiposGps'] as $tg)
                        <option value="{{ $tg }}" {{ $tipoGpsActual !== null && strcasecmp((string) $tipoGpsActual, $tg) === 0 ? 'selected' : '' }}>{{ $tg }}</option>
                        @endforeach
                        @if($tipoGpsActual && !$catalogos['tiposGps']->contains(fn ($t) => strcasecmp($t, (string) $tipoGpsActual) === 0))
                        <option value="{{ $tipoGpsActual }}" selected>{{ $tipoGpsActual }}</option>
                        @endif
                    </select>
                </div>
                <div class="{{ $claseEn($perfiles['campos']['gps']) }}" data-tipos="{{ $listaGps }}">
                    <label class="form-label">IMEI</label>
                    <input type="text" name="imei" class="form-control" maxlength="20" pattern="[0-9]{15}" title="15 dígitos" placeholder="15 dígitos" value="{{ $imeiActual }}" {{ $attrEn($perfiles['campos']['gps']) }}>
                </div>
                <div class="{{ $claseEn($perfiles['campos']['gps']) }}" data-tipos="{{ $listaGps }}">
                    <label class="form-label">Operador</label>
                    <select name="operador" class="form-select" {{ $attrEn($perfiles['campos']['gps']) }}>
                        <option value="">Seleccionar...</option>
                        @foreach($operadoresGps as $opGps)
                        <option value="{{ $opGps }}" {{ $operadorActual !== null && $operadorActual === $opGps ? 'selected' : '' }}>{{ $opGps }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="{{ $claseEn($perfiles['campos']['gps']) }}" data-tipos="{{ $listaGps }}">
                    <label class="form-label">Línea / SIM</label>
                    <input type="text" name="lineaSim" class="form-control" maxlength="20" value="{{ $lineaSimActual }}" {{ $attrEn($perfiles['campos']['gps']) }}>
                </div>
                <div class="{{ $claseSin($sinTextoIpMac, 'col-md-3') }}" data-sin-tipos="{{ $listaSinTextoIpMac }}"><label class="form-label">IP</label><input type="text" name="ip" class="form-control" value="{{ $equipo->ip }}" {{ $attrSin($sinTextoIpMac) }}></div>
                <div class="{{ $claseSin($sinTextoIpMac, 'col-md-3') }}" data-sin-tipos="{{ $listaSinTextoIpMac }}"><label class="form-label">MAC</label><input type="text" name="mac" class="form-control" value="{{ $equipo->mac }}" {{ $attrSin($sinTextoIpMac) }}></div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Estado Garantía</label>
                    <select name="estadoGarantia" class="form-select">
                        <option value="">Seleccionar...</option>
                        @foreach(['Vigente', 'Vencida', 'Sin garantía'] as $op)
                        <option value="{{ $op }}" {{ $equipo->estadoGarantia == $op ? 'selected' : '' }}>{{ $op }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3"><label class="form-label">Fecha Fin Garantía</label><input type="date" name="fechaFinGarantia" class="form-control" value="{{ $equipo->fechaFinGarantia ? $equipo->fechaFinGarantia->format('Y-m-d') : '' }}"></div>
                <div class="col-md-12 mb-3"><label class="form-label">Especificaciones adicionales</label><textarea name="especificaciones" class="form-control" rows="2">{{ $equipo->especificaciones }}</textarea></div>

                {{-- Campos personalizados del catálogo (definidos en inventario/campos) --}}
                @foreach($camposCustom as $custom)
                @php $valorCustom = old('attributes.' . $custom->name, $equipo->attributes[$custom->name] ?? null); @endphp
                <div class="col-md-3 mb-3 d-none" data-campo="{{ $custom->name }}">
                    <label class="form-label">{{ $custom->label }}</label>

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
                    $seleccionados = is_array($valorCustom) ? $valorCustom : [];
                    if (is_string($seleccionados)) $seleccionados = array_filter(array_map('trim', explode(',', $seleccionados)));
                    @endphp
                    <div class="d-flex flex-wrap gap-2">
                        @foreach(($custom->validation_rules['opciones'] ?? []) as $i => $op)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                name="attributes[{{ $custom->name }}][]" value="{{ $op }}"
                                id="editchk_{{ $custom->name }}_{{ $i }}"
                                {{ in_array($op, $seleccionados, true) ? 'checked' : '' }}>
                            <label class="form-check-label small" for="editchk_{{ $custom->name }}_{{ $i }}">{{ $op }}</label>
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
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Actualizar Equipo</button>
                <a href="{{ route('inventario.equipo.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
const PC_ESCRITORIO = '1';
const TIPOS_CATALOGO = @json(array_map('strval', config('equipo_perfiles.con_dropdown')));
const MAPA_CAMPOS_BD = @json($camposPorTipo ?? []);
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
    const esPc = tipo === PC_ESCRITORIO;

    // Capa 1: grupos campo-pc / campo-libre (comportamiento de PC sin cambios)
    document.querySelectorAll('.campo-pc').forEach(el => el.classList.toggle('d-none', !esPc));
    document.querySelectorAll('.campo-libre').forEach(el => el.classList.toggle('d-none', esPc));
    document.querySelectorAll('.campo-pc select, .campo-pc input').forEach(i => i.disabled = !esPc);
    document.querySelectorAll('.campo-libre select, .campo-libre input').forEach(i => i.disabled = esPc);

    // Capa 2: reglas declaradas por tipo con data-tipos / data-sin-tipos
    const listaDe = valor => (valor || '').split(',').map(s => s.trim()).filter(Boolean);
    document.querySelectorAll('[data-tipos]').forEach(el => {
        const visible = listaDe(el.dataset.tipos).includes(tipo);
        el.classList.toggle('d-none', !visible);
        el.querySelectorAll('input, select, textarea').forEach(i => i.disabled = !visible);
    });
    document.querySelectorAll('[data-sin-tipos]').forEach(el => {
        const oculto = listaDe(el.dataset.sinTipos).includes(tipo);
        const visible = !oculto && (!el.classList.contains('campo-libre') || !esPc);
        el.classList.toggle('d-none', !visible);
        el.querySelectorAll('input, select, textarea').forEach(i => i.disabled = !visible);
    });

    // Capa 3: Si existe configuración dinámica en BD para este tipo, tiene prioridad
    if (MAPA_CAMPOS_BD && MAPA_CAMPOS_BD[tipo]) {
        const camposPermitidos = MAPA_CAMPOS_BD[tipo];
        document.querySelectorAll('form .col-md-3, form .col-md-4, form .col-md-6, form .col-md-12').forEach(container => {
            const inputs = container.querySelectorAll('input[name], select[name], textarea[name]');
            if (!inputs.length) return;

            let nombreCampo = null;
            inputs.forEach(inp => {
                const n = inp.name.replace('[]', '');
                if (n && n !== 'id_TipoEquipo' && n !== '_token' && n !== '_method') {
                    nombreCampo = n;
                }
            });

            if (!nombreCampo) return;

            if (nombreCampo === 'puertosOtros') nombreCampo = 'puertos';

            // Campos personalizados: attributes[nombre] -> nombre
            const attrMatch = typeof nombreCampo === 'string' ? nombreCampo.match(/^attributes\[(.+)\]$/) : null;
            if (attrMatch) nombreCampo = attrMatch[1];

            if (!camposPermitidos.includes(nombreCampo)) {
                container.classList.add('d-none');
                inputs.forEach(i => i.disabled = true);
            } else {
                if (container.classList.contains('campo-pc') && !esPc) {
                    // mantener oculto si no es pc
                } else if (container.classList.contains('campo-libre') && esPc) {
                    // mantener oculto si es pc
                } else {
                    container.classList.remove('d-none');
                    inputs.forEach(i => i.disabled = false);
                }
            }
        });
    }

    // Sincronización de pares: cada tipo copia el valor hacia el campo visible
    const usaCatalogo = TIPOS_CATALOGO.includes(tipo);
    if (usaCatalogo) {
        pares.forEach(([libreId, pcId]) => {
            const libre = document.getElementById(libreId), pc = document.getElementById(pcId);
            if (!libre || !pc) return;
            if (!libre.disabled && pc.disabled) {
                if (!libre.value && pc.value) libre.value = pc.value;
            } else if (!pc.value && libre.value) {
                forzarValorEnSelect(pc, libre.value);
            }
        });
        filtrarModelos();
    } else {
        pares.forEach(([libreId, pcId]) => {
            const libre = document.getElementById(libreId), pc = document.getElementById(pcId);
            if (libre && pc && !libre.value && pc.value) libre.value = pc.value;
        });
    }
}

const selMarcaPc = document.getElementById('selMarcaPc');
if (selMarcaPc) selMarcaPc.addEventListener('change', filtrarModelos);
if (selTipo) selTipo.addEventListener('change', aplicarTipo);

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => { aplicarTipo(); filtrarModelos(); });
} else {
    aplicarTipo();
    filtrarModelos();
}
</script>
@endsection
