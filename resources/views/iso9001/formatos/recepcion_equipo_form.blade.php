@extends('layouts.app')
@section('title', 'Nuevo ' . $formato['clave'] . ' - ISO 9001')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-2">
    <div>
        <h4 class="mb-0">
            <i class="{{ $formato['icono'] ?? 'fas fa-file' }} me-2"></i>Nuevo {{ $formato['nombre'] }}
            <span class="badge bg-primary ms-2">{{ $formato['clave'] }}</span>
        </h4>
        <p class="text-muted mb-0">Captura los datos que aparecerán en el documento</p>
    </div>
    <a href="{{ route('iso9001.documentos', ['clave' => $formato['clave']]) }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        @if($errors->any())
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>Revisa los campos marcados:
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('iso9001.store', ['clave' => $formato['clave']]) }}" method="POST">
            @csrf

            {{-- Fila 1: fecha + motivo + tipo de activo --}}
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Fecha <span class="text-danger">*</span></label>
                    <input type="date" name="fecha" class="form-control @error('fecha') is-invalid @enderror"
                        value="{{ old('fecha', $fechaDefault) }}">
                    @error('fecha')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label d-block">Motivo de la Entrega <span class="text-danger">*</span></label>
                    @foreach($motivos as $opcion)
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="motivoEntrega" id="motivo{{ $loop->index }}"
                            value="{{ $opcion }}" @checked(old('motivoEntrega')===$opcion)>
                        <label class="form-check-label" for="motivo{{ $loop->index }}">{{ $opcion }}</label>
                    </div>
                    @endforeach
                    @error('motivoEntrega')
                    <div class="text-danger" style="font-size: 13px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-5">
                    <label class="form-label d-block">Tipo de activo entregado <span
                            class="text-danger">*</span></label>
                    <div class="row">
                        @foreach($tiposActivo as $opcion)
                        <div class="col-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="tipoActivo[]"
                                    id="tipo{{ $loop->index }}" value="{{ $opcion }}" @checked(in_array($opcion,
                                    old('tipoActivo', [])))>
                                <label class="form-check-label" for="tipo{{ $loop->index }}">{{ $opcion }}</label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @error('tipoActivo')
                    <div class="text-danger" style="font-size: 13px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <hr class="my-4">

            {{-- Fila 2: selección de equipo asignado e identificación del activo --}}
            <div class="row g-3">
                <h6 class="mb-3 mt-4 d-block"><i class="fas fa-barcode me-2"></i>Identificación del Activo <span
                        class="text-danger">*</span></h6>
                <div class="col-md-6">
                    <label class="form-label">Equipo asignado <span class="text-muted">(selecciona para
                            autocompletar)</span></label>
                    <select name="id_equipo" id="selectEquipo"
                        class="form-select @error('id_equipo') is-invalid @enderror">
                        <option value="">-- Seleccionar equipo asignado --</option>
                        @foreach($equipos as $equipo)
                        <option value="{{ $equipo['id_Equipo'] }}" data-nombre="{{ $equipo['nombreEquipo'] }}"
                            data-marca="{{ $equipo['marca'] }}" data-modelo="{{ $equipo['modelo'] }}"
                            data-serie="{{ $equipo['numeroSerie'] }}" data-imei="{{ $equipo['imei'] ?? '' }}"
                            data-telefono="{{ $equipo['lineaSim'] ?? '' }}"
                            @checked(old('id_equipo')==$equipo['id_Equipo'])] {{ $equipo['nombreEquipo'] }} ·
                            {{ $equipo['marca'] }} {{ $equipo['modelo'] }} · {{ $equipo['numeroSerie'] }} <span
                            class="text-muted small d-block">{{ $equipo['usuario'] }}</span>
                        </option>
                        @endforeach
                    </select>
                    @error('id_equipo')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Si el equipo no aparece, deja este campo vacío y escribe los datos
                        manualmente.</div>
                </div>

                <div class="col-md-6">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Marca</label>
                            <input type="text" name="marca" id="campoMarca" maxlength="100"
                                class="form-control @error('marca') is-invalid @enderror" value="{{ old('marca') }}"
                                placeholder="Ej. Dell">
                            @error('marca')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Modelo</label>
                            <input type="text" name="modelo" id="campoModelo" maxlength="100"
                                class="form-control @error('modelo') is-invalid @enderror" value="{{ old('modelo') }}"
                                placeholder="Ej. Latitude 5420">
                            @error('modelo')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Número de serie</label>
                            <input type="text" name="noSerie" id="campoNoSerie" maxlength="100"
                                class="form-control @error('noSerie') is-invalid @enderror" value="{{ old('noSerie') }}"
                                placeholder="Ej. DL123456">
                            @error('noSerie')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">IMEI <small class="text-muted">(solo teléfonos)</small></label>
                            <input type="text" name="imei" id="campoImei" maxlength="100"
                                class="form-control @error('imei') is-invalid @enderror" value="{{ old('imei') }}"
                                placeholder="Opcional">
                            @error('imei')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Número telefónico <small class="text-muted">(solo
                                    teléfonos)</small></label>
                            <input type="text" name="numeroTelefonico" id="campoNumeroTelefonico" maxlength="50"
                                class="form-control @error('numeroTelefonico') is-invalid @enderror"
                                value="{{ old('numeroTelefonico') }}" placeholder="Opcional">
                            @error('numeroTelefonico')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <h6 class="mb-3"><i class="fas fa-plug me-2"></i>Accesorios del Activo <span
                            class="text-danger">*</span></h6>
                    <div class="row">
                        @foreach($accesorios as $opcion)
                        <div class="col-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="accesorios[]"
                                    id="accesorio{{ $loop->index }}" value="{{ $opcion }}" @checked(in_array($opcion,
                                    old('accesorios', [])))>
                                <label class="form-check-label" for="accesorio{{ $loop->index }}">{{ $opcion }}</label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @error('accesorios')
                    <div class="text-danger" style="font-size: 13px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <hr class="my-4">

            {{-- Fila 3: descripción de la entrega --}}
            <div class="mb-3">
                <label class="form-label">Descripción de la Entrega <span class="text-danger">*</span></label>
                <textarea name="descripcionEntrega" rows="3" maxlength="500"
                    class="form-control @error('descripcionEntrega') is-invalid @enderror"
                    placeholder="Describe cómo se recibió el equipo">{{ old('descripcionEntrega', $descripcionDefault) }}</textarea>
                @error('descripcionEntrega')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Fila 4: gastos de reparación --}}
            <div class="row g-3 mb-2">
                <div class="col-md-7">
                    <label class="form-label d-block">Aplicación de Gastos de Reparación <span
                            class="text-danger">*</span></label>
                    @foreach($aplicacionesGastos as $opcion)
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="aplicacionGastos"
                            id="gasto{{ $loop->index }}" value="{{ $opcion }}"
                            @checked(old('aplicacionGastos')===$opcion)>
                        <label class="form-check-label" for="gasto{{ $loop->index }}">{{ $opcion }}</label>
                    </div>
                    @endforeach
                    @error('aplicacionGastos')
                    <div class="text-danger" style="font-size: 13px;">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Gastos de Reparación ($) <span class="text-danger">*</span></label>
                    <input type="number" name="gastosReparacion" min="0" max="9999999999.99" step="0.01"
                        class="form-control @error('gastosReparacion') is-invalid @enderror"
                        value="{{ old('gastosReparacion', '0') }}">
                    @error('gastosReparacion')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <hr class="my-4">

            {{-- Fila 5: firmantes --}}
            <div class="row g-3 mb-2">
                <div class="col-md-6">
                    <label class="form-label">Realiza Revisión <small class="text-muted">(se imprime: Jefe de
                            Sistemas)</small> <span class="text-danger">*</span></label>
                    <input type="text" name="revisadoPor" maxlength="150"
                        class="form-control @error('revisadoPor') is-invalid @enderror" value="{{ old('revisadoPor') }}"
                        placeholder="Nombre de quien realiza la revisión">
                    @error('revisadoPor')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Entrega Activo <small class="text-muted">(se imprime: Persona)</small>
                        <span class="text-danger">*</span></label>
                    <input type="text" name="entregadoPor" maxlength="150"
                        class="form-control @error('entregadoPor') is-invalid @enderror"
                        value="{{ old('entregadoPor') }}" placeholder="Nombre de quien entrega el activo">
                    @error('entregadoPor')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('iso9001.documentos', ['clave' => $formato['clave']]) }}"
                    class="btn btn-outline-secondary">
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Guardar documento
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    (function () {
        const select = document.getElementById('selectEquipo');
        const campos = {
            marca: document.getElementById('campoMarca'),
            modelo: document.getElementById('campoModelo'),
            noSerie: document.getElementById('campoNoSerie'),
            imei: document.getElementById('campoImei'),
            numeroTelefonico: document.getElementById('campoNumeroTelefonico'),
        };

        function autocompletar() {
            const opcion = select.options[select.selectedIndex];

            // Limpia campos cuando no hay selección.
            if (!opcion.value) {
                Object.values(campos).forEach(function (campo) {
                    campo.value = '';
                });
                return;
            }

            campos.marca.value = opcion.dataset.marca || '';
            campos.modelo.value = opcion.dataset.modelo || '';
            campos.noSerie.value = opcion.dataset.serie || '';
            campos.imei.value = opcion.dataset.imei || '';
            campos.numeroTelefonico.value = opcion.dataset.telefono || '';
        }

        select.addEventListener('change', autocompletar);

        // Autocompletar al cargar si ya hay una selección (errores de validación).
        document.addEventListener('DOMContentLoaded', autocompletar);
    })();

</script>
@endsection
