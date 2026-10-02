@extends('layouts.app')
@section('title', 'Editar Equipo - HelpDesk')

@section('content')
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
                <div class="col-md-3 mb-3">
                    <label class="form-label">Marca *</label>
                    <input type="text" name="marca" class="form-control" value="{{ $equipo->marca }}" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Modelo *</label>
                    <input type="text" name="modelo" class="form-control" value="{{ $equipo->modelo }}" required>
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
                <div class="col-md-3 mb-3">
                    <label class="form-label">Ubicación</label>
                    <input type="text" name="ubicacion" class="form-control" value="{{ $equipo->ubicacion }}">
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
                <div class="col-md-4 mb-3">
                    <label class="form-label">Período Mantenimiento</label>
                    <select name="id_PeriodoMantenimiento" class="form-select">
                        <option value="0">Sin período</option>
                        @foreach(\App\Models\PeriodoMantenimiento::where('activo', true)->get() as $p)
                        <option value="{{ $p->id_PeriodoMantenimiento }}" {{ $equipo->id_PeriodoMantenimiento == $p->id_PeriodoMantenimiento ? 'selected' : '' }}>{{ $p->descripcion }} ({{ $p->meses }} meses)</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Último Mantenimiento</label>
                    <input type="date" name="fechaUltimoMantenimiento" class="form-control" value="{{ $equipo->fechaUltimoMantenimiento ? $equipo->fechaUltimoMantenimiento->format('Y-m-d') : '' }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 mb-3 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="esCompartido" id="chkCompartido" value="1" {{ $equipo->esCompartido ? 'checked' : '' }}>
                        <label class="form-check-label" for="chkCompartido">Equipo Compartido</label>
                    </div>
                </div>
            </div>

            <h6 class="mt-3"><i class="fas fa-microchip me-1"></i>Especificaciones</h6>
            <div class="row">
                <div class="col-md-3 mb-3"><label class="form-label">Serie Motherboard</label><input type="text" name="serieMotherboard" class="form-control" value="{{ $equipo->serieMotherboard }}"></div>
                <div class="col-md-3 mb-3"><label class="form-label">Procesador</label><input type="text" name="procesador" class="form-control" value="{{ $equipo->procesador }}"></div>
                <div class="col-md-3 mb-3"><label class="form-label">Memoria RAM</label><input type="text" name="memoriaRam" class="form-control" value="{{ $equipo->memoriaRam }}"></div>
                <div class="col-md-3 mb-3"><label class="form-label">Almacenamiento</label><input type="text" name="almacenamiento" class="form-control" value="{{ $equipo->almacenamiento }}"></div>
                <div class="col-md-3 mb-3"><label class="form-label">Sistema Operativo</label><input type="text" name="sistemaOperativo" class="form-control" value="{{ $equipo->sistemaOperativo }}"></div>
                <div class="col-md-3 mb-3"><label class="form-label">IP</label><input type="text" name="ip" class="form-control" value="{{ $equipo->ip }}"></div>
                <div class="col-md-3 mb-3"><label class="form-label">MAC</label><input type="text" name="mac" class="form-control" value="{{ $equipo->mac }}"></div>
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
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Actualizar Equipo</button>
                <a href="{{ route('inventario.equipo.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection


