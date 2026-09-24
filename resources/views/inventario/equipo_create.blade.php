@extends('layouts.app')
@section('title', 'Nuevo Equipo - HelpDesk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">
        <i class="fas fa-plus me-2"></i>Nuevo Equipo
        @if($tipoSeleccionado)
            <span class="badge bg-primary ms-2">{{ $tipoSeleccionado->descripcion }}</span>
        @endif
    </h4>
    <a href="{{ $tipoSeleccionado ? route('inventario.equipo.tipos') : route('inventario.equipo.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Volver</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('inventario.equipo.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Marca *</label>
                    <input type="text" name="marca" class="form-control" value="{{ old('marca') }}" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Modelo *</label>
                    <input type="text" name="modelo" class="form-control" value="{{ old('modelo') }}" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Nombre Equipo</label>
                    <input type="text" name="nombreEquipo" class="form-control" value="{{ old('nombreEquipo') }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Tipo *</label>
                    <select name="id_TipoEquipo" id="selTipoEquipo" class="form-select" required {{ $tipoSeleccionado ? 'disabled' : '' }}>
                        @foreach($tiposEquipo as $t)
                        <option value="{{ $t->id_TipoEquipo }}" {{ ($tipoSeleccionado && $tipoSeleccionado->id_TipoEquipo == $t->id_TipoEquipo) ? 'selected' : '' }}>{{ $t->descripcion }}</option>
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
                <div class="col-md-3 mb-3">
                    <label class="form-label">Número de Serie</label>
                    <input type="text" name="numeroSerie" class="form-control" value="{{ old('numeroSerie') }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Ubicación</label>
                    <input type="text" name="ubicacion" class="form-control" value="{{ old('ubicacion') }}">
                </div>
                <div class="col-md-3 mb-3">
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
                <div class="col-md-4 mb-3">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2">{{ old('observaciones') }}</textarea>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Período Mantenimiento</label>
                    <select name="id_PeriodoMantenimiento" class="form-select">
                        <option value="0">Sin período</option>
                        @foreach(\App\Models\PeriodoMantenimiento::where('activo', true)->get() as $p)
                        <option value="{{ $p->id_PeriodoMantenimiento }}" {{ old('id_PeriodoMantenimiento') == $p->id_PeriodoMantenimiento ? 'selected' : '' }}>{{ $p->descripcion }} ({{ $p->meses }} meses)</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Último Mantenimiento</label>
                    <input type="date" name="fechaUltimoMantenimiento" class="form-control" value="{{ old('fechaUltimoMantenimiento') }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 mb-3 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="esCompartido" id="chkCompartido" value="1">
                        <label class="form-check-label" for="chkCompartido">Equipo Compartido</label>
                    </div>
                </div>
            </div>

            <h6 class="mt-3"><i class="fas fa-sliders me-1"></i>Campos Personalizados</h6>
            <div id="camposDinamicos" class="row"></div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar Equipo</button>
                <a href="{{ $tipoSeleccionado ? route('inventario.equipo.tipos') : route('inventario.equipo.index') }}" class="btn btn-secondary">Cancelar</a>
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
document.getElementById('selTipoEquipo')?.dispatchEvent(new Event('change'));
</script>
@endsection
