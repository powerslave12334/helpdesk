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

            <h6 class="mt-3"><i class="fas fa-sliders me-1"></i>Campos Personalizados</h6>
            <div id="camposDinamicos" class="row">
                @foreach($tipoCampos as $tc)
                    @if(!$tc->campo) @continue @endif
                    @php
                        $valor = $equipo->equipoDetalle->where('id_EquipoCampo', $tc->id_EquipoCampo)->first()->valor ?? '';
                        $nombre = $tc->campo->nombre;
                    @endphp
                    @if($nombre === 'Estado Garantía')
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ $nombre }} {{ $tc->requerido ? '*' : '' }}</label>
                            <select name="campos_dinamicos[{{ $tc->id_EquipoCampo }}]" class="form-select" {{ $tc->requerido ? 'required' : '' }}>
                                <option value="">Seleccionar...</option>
                                <option value="Vigente" {{ $valor === 'Vigente' ? 'selected' : '' }}>Vigente</option>
                                <option value="Vencida" {{ $valor === 'Vencida' ? 'selected' : '' }}>Vencida</option>
                            </select>
                        </div>
                    @elseif($nombre === 'Fecha Fin Garantía')
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ $nombre }} {{ $tc->requerido ? '*' : '' }}</label>
                            <div class="input-group">
                                <input type="{{ $valor === 'N/A' ? 'text' : 'date' }}" name="campos_dinamicos[{{ $tc->id_EquipoCampo }}]" class="form-control" id="fechaGarantia_{{ $tc->id_EquipoCampo }}" value="{{ $valor }}">
                                <button type="button" class="btn btn-outline-secondary btn-na {{ $valor === 'N/A' ? 'active' : '' }}" data-target="fechaGarantia_{{ $tc->id_EquipoCampo }}" title="No aplica">{{ $valor === 'N/A' ? 'Fecha' : 'N/A' }}</button>
                            </div>
                        </div>
                    @elseif($nombre === 'Sistema Operativo')
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ $nombre }} {{ $tc->requerido ? '*' : '' }}</label>
                            <select name="campos_dinamicos[{{ $tc->id_EquipoCampo }}]" class="form-select" {{ $tc->requerido ? 'required' : '' }}>
                                <option value="">Seleccionar...</option>
                                @foreach(['Windows 11 Pro','Windows 11 Home','Windows 10 Pro','Windows 10 Home','Windows Server 2022','Windows Server 2019','Ubuntu','Linux Mint','macOS','ChromeOS','Sin SO'] as $op)
                                <option value="{{ $op }}" {{ $valor === $op ? 'selected' : '' }}>{{ $op }}</option>
                                @endforeach
                            </select>
                        </div>
                    @elseif($nombre === 'Tipo Almacenamiento')
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ $nombre }} {{ $tc->requerido ? '*' : '' }}</label>
                            <select name="campos_dinamicos[{{ $tc->id_EquipoCampo }}]" class="form-select" {{ $tc->requerido ? 'required' : '' }}>
                                <option value="">Seleccionar...</option>
                                @foreach(['Mecánico (HDD)','SSD','NVMe','Híbrido (HDD + SSD)'] as $op)
                                <option value="{{ $op }}" {{ $valor === $op ? 'selected' : '' }}>{{ $op }}</option>
                                @endforeach
                            </select>
                        </div>
                    @elseif($nombre === 'Tipo Conexión')
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ $nombre }} {{ $tc->requerido ? '*' : '' }}</label>
                            <select name="campos_dinamicos[{{ $tc->id_EquipoCampo }}]" class="form-select" {{ $tc->requerido ? 'required' : '' }}>
                                <option value="">Seleccionar...</option>
                                @foreach(['USB','Inalámbrico','PS/2','Bluetooth','USB + Inalámbrico'] as $op)
                                <option value="{{ $op }}" {{ $valor === $op ? 'selected' : '' }}>{{ $op }}</option>
                                @endforeach
                            </select>
                        </div>
                    @elseif($nombre === 'Tipo Teclado')
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ $nombre }} {{ $tc->requerido ? '*' : '' }}</label>
                            <select name="campos_dinamicos[{{ $tc->id_EquipoCampo }}]" class="form-select" {{ $tc->requerido ? 'required' : '' }}>
                                <option value="">Seleccionar...</option>
                                @foreach(['Mecánico','Membrana','Scissor','Fantasma (Topre)'] as $op)
                                <option value="{{ $op }}" {{ $valor === $op ? 'selected' : '' }}>{{ $op }}</option>
                                @endforeach
                            </select>
                        </div>
                    @elseif($nombre === 'Retroiluminado')
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ $nombre }} {{ $tc->requerido ? '*' : '' }}</label>
                            <select name="campos_dinamicos[{{ $tc->id_EquipoCampo }}]" class="form-select" {{ $tc->requerido ? 'required' : '' }}>
                                <option value="">Seleccionar...</option>
                                @foreach(['Sí','No'] as $op)
                                <option value="{{ $op }}" {{ $valor === $op ? 'selected' : '' }}>{{ $op }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ $nombre }} {{ $tc->requerido ? '*' : '' }}</label>
                            <input type="text" name="campos_dinamicos[{{ $tc->id_EquipoCampo }}]" class="form-control"
                                value="{{ $valor }}" {{ $tc->requerido ? 'required' : '' }}>
                        </div>
                    @endif
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
document.getElementById('selTipoEquipo')?.addEventListener('change', function() {
    const container = document.getElementById('camposDinamicos');
    container.innerHTML = '<p class="text-muted">Cargando campos...</p>';
    fetch(`/inventario/equipo/campos/${this.value}`)
        .then(r => r.json())
        .then(data => {
            container.innerHTML = '';
            data.forEach(c => {
                const nombre = c.campo?.nombre;
                if (!nombre) return;
                const req = c.requerido ? 'required' : '';
                const reqStar = c.requerido ? '*' : '';
                let html = '';

                if (nombre === 'Estado Garantía') {
                    html = `
                        <div class="col-md-4 mb-3">
                            <label class="form-label">${nombre} ${reqStar}</label>
                            <select name="campos_dinamicos[${c.id_EquipoCampo}]" class="form-select" ${req}>
                                <option value="">Seleccionar...</option>
                                <option value="Vigente">Vigente</option>
                                <option value="Vencida">Vencida</option>
                            </select>
                        </div>`;
                } else if (nombre === 'Fecha Fin Garantía') {
                    html = `
                        <div class="col-md-4 mb-3">
                            <label class="form-label">${nombre} ${reqStar}</label>
                            <div class="input-group">
                                <input type="date" name="campos_dinamicos[${c.id_EquipoCampo}]" class="form-control" id="fechaGarantia_${c.id_EquipoCampo}">
                                <button type="button" class="btn btn-outline-secondary btn-na" data-target="fechaGarantia_${c.id_EquipoCampo}" title="No aplica">N/A</button>
                            </div>
                        </div>`;
                } else if (nombre === 'Sistema Operativo') {
                    html = `
                        <div class="col-md-4 mb-3">
                            <label class="form-label">${nombre} ${reqStar}</label>
                            <select name="campos_dinamicos[${c.id_EquipoCampo}]" class="form-select" ${req}>
                                <option value="">Seleccionar...</option>
                                <option value="Windows 11 Pro">Windows 11 Pro</option>
                                <option value="Windows 11 Home">Windows 11 Home</option>
                                <option value="Windows 10 Pro">Windows 10 Pro</option>
                                <option value="Windows 10 Home">Windows 10 Home</option>
                                <option value="Windows Server 2022">Windows Server 2022</option>
                                <option value="Windows Server 2019">Windows Server 2019</option>
                                <option value="Ubuntu">Ubuntu</option>
                                <option value="Linux Mint">Linux Mint</option>
                                <option value="macOS">macOS</option>
                                <option value="ChromeOS">ChromeOS</option>
                                <option value="Sin SO">Sin SO</option>
                            </select>
                        </div>`;
                } else if (nombre === 'Tipo Almacenamiento') {
                    html = `
                        <div class="col-md-4 mb-3">
                            <label class="form-label">${nombre} ${reqStar}</label>
                            <select name="campos_dinamicos[${c.id_EquipoCampo}]" class="form-select" ${req}>
                                <option value="">Seleccionar...</option>
                                <option value="Mecánico (HDD)">Mecánico (HDD)</option>
                                <option value="SSD">SSD</option>
                                <option value="NVMe">NVMe</option>
                                <option value="Híbrido (HDD + SSD)">Híbrido (HDD + SSD)</option>
                            </select>
                        </div>`;
                } else if (nombre === 'Tipo Conexión') {
                    html = `
                        <div class="col-md-4 mb-3">
                            <label class="form-label">${nombre} ${reqStar}</label>
                            <select name="campos_dinamicos[${c.id_EquipoCampo}]" class="form-select" ${req}>
                                <option value="">Seleccionar...</option>
                                <option value="USB">USB</option>
                                <option value="Inalámbrico">Inalámbrico</option>
                                <option value="PS/2">PS/2</option>
                                <option value="Bluetooth">Bluetooth</option>
                                <option value="USB + Inalámbrico">USB + Inalámbrico</option>
                            </select>
                        </div>`;
                } else if (nombre === 'Tipo Teclado') {
                    html = `
                        <div class="col-md-4 mb-3">
                            <label class="form-label">${nombre} ${reqStar}</label>
                            <select name="campos_dinamicos[${c.id_EquipoCampo}]" class="form-select" ${req}>
                                <option value="">Seleccionar...</option>
                                <option value="Mecánico">Mecánico</option>
                                <option value="Membrana">Membrana</option>
                                <option value="Scissor">Scissor</option>
                                <option value="Fantasma (Topre)">Fantasma (Topre)</option>
                            </select>
                        </div>`;
                } else if (nombre === 'Retroiluminado') {
                    html = `
                        <div class="col-md-4 mb-3">
                            <label class="form-label">${nombre} ${reqStar}</label>
                            <select name="campos_dinamicos[${c.id_EquipoCampo}]" class="form-select" ${req}>
                                <option value="">Seleccionar...</option>
                                <option value="Sí">Sí</option>
                                <option value="No">No</option>
                            </select>
                        </div>`;
                } else {
                    html = `
                        <div class="col-md-4 mb-3">
                            <label class="form-label">${nombre} ${reqStar}</label>
                            <input type="text" name="campos_dinamicos[${c.id_EquipoCampo}]" class="form-control" ${req}>
                        </div>`;
                }

                container.innerHTML += html;
            });

            // Eventos para botones N/A
            container.querySelectorAll('.btn-na').forEach(btn => {
                btn.addEventListener('click', function() {
                    const input = document.getElementById(this.dataset.target);
                    if (input.value === 'N/A') {
                        input.value = '';
                        input.type = 'date';
                        this.textContent = 'N/A';
                        this.classList.remove('active');
                    } else {
                        input.value = 'N/A';
                        input.type = 'text';
                        this.textContent = 'Fecha';
                        this.classList.add('active');
                    }
                });
            });
        });
});
</script>
@endsection
