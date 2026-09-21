@extends('layouts.app')
@section('title', 'Permisos por Rol - HelpDesk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-user-shield me-2"></i>Matriz de Permisos por Rol</h4>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('configuracion.rolPermiso.index') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Seleccionar Rol</label>
                <select name="idRol" class="form-select" id="selRol">
                    <option value="0">-- Todos los roles --</option>
                    @foreach($roles as $r)
                    <option value="{{ $r->id_Rol }}" {{ $idRol == $r->id_Rol ? 'selected' : '' }}>{{ $r->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i>Filtrar</button>
            </div>
        </form>
    </div>
</div>

@if($idRol)
<form method="POST" action="{{ route('configuracion.rolPermiso.guardarMatriz') }}">
    @csrf
    <input type="hidden" name="id_Rol" value="{{ $idRol }}">

    @foreach($modulos as $m)
    <div class="card mb-3">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0"><i class="fas fa-puzzle-piece me-1"></i>{{ 'Módulo ' . $m->id_Modulos }}</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>SubMódulo</th>
                            @foreach($acciones as $a)
                            <th class="text-center" style="width: 100px;">{{ $a->nombre }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($submodulos->where('id_Modulos', $m->id_Modulos) as $sm)
                        <tr>
                            <td>{{ $sm->nombre }}</td>
                            @foreach($acciones as $a)
                            @php
                                $checked = $permisos->where('id_Modulos', $m->id_Modulos)
                                    ->where('id_SubModulos', $sm->id_SubModulos)
                                    ->where('id_Accion', $a->id_Accion)
                                    ->where('permitido', true)->first();
                            @endphp
                            <td class="text-center">
                                <input class="form-check-input" type="checkbox"
                                    name="PermisosSeleccionados[]"
                                    value="{{ $m->id_Modulos }}|{{ $sm->id_SubModulos }}|{{ $a->id_Accion }}"
                                    {{ $checked ? 'checked' : '' }}>
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endforeach

    <div class="text-end mb-4">
        <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-save me-1"></i>Guardar Permisos</button>
    </div>
</form>
@else
<div class="alert alert-info">
    <i class="fas fa-info-circle me-1"></i>Seleccione un rol para ver y editar su matriz de permisos.
</div>
@endif
@endsection
