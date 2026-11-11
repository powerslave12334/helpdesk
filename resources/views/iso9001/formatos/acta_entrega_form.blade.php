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
                    <label class="form-label">Entregado por <span class="text-danger">*</span></label>
                    <input type="text" name="entregadoPor" class="form-control @error('entregadoPor') is-invalid @enderror"
                           value="{{ old('entregadoPor') }}" placeholder="Nombre de quien entrega el equipo" maxlength="150">
                    @error('entregadoPor')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Recibido por <span class="text-danger">*</span></label>
                    <input type="text" name="recibidoPor" class="form-control @error('recibidoPor') is-invalid @enderror"
                           value="{{ old('recibidoPor') }}" placeholder="Nombre de quien recibe el equipo" maxlength="150">
                    @error('recibidoPor')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
                <div>
                    <h6 class="mb-0"><i class="fas fa-laptop me-2"></i>Equipos entregados <span class="text-danger">*</span></h6>
                    <small class="text-muted">Agrega un renglón por equipo; las condiciones se eligen del catálogo del formato.</small>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" id="btnAgregarEquipo">
                    <i class="fas fa-plus me-1"></i>Agregar equipo
                </button>
            </div>

            @error('equipos')
            <div class="alert alert-danger py-2" style="font-size: 13px;">
                <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
            </div>
            @enderror

            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0" id="tablaEquipos">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 9%;">Cantidad</th>
                            <th style="width: 24%;">Descripción</th>
                            <th style="width: 14%;">Marca</th>
                            <th style="width: 15%;">Modelo</th>
                            <th style="width: 16%;">No. de serie</th>
                            <th style="width: 17%;">Condiciones</th>
                            <th style="width: 5%;"></th>
                        </tr>
                    </thead>
                    <tbody id="cuerpoEquipos">
                        @foreach(old('equipos') ?: $filasDefault as $indice => $fila)
                        @include('iso9001.formatos.renglon_equipo', [
                            'indice' => $indice,
                            'fila' => $fila,
                            'restaurar' => true,
                        ])
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('iso9001.documentos', ['clave' => $formato['clave']]) }}" class="btn btn-outline-secondary">
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Guardar documento
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Plantilla del renglón que agrega el JavaScript (__I__ = índice de fila). --}}
<template id="plantillaRenglon">
    @include('iso9001.formatos.renglon_equipo', [
        'indice' => '__I__',
        'fila' => $filasDefault[0],
        'restaurar' => false,
    ])
</template>
@endsection

@section('scripts')
<script>
    (function () {
        const cuerpo = document.getElementById('cuerpoEquipos');
        const plantilla = document.getElementById('plantillaRenglon');

        // El siguiente índice nunca reutiliza los existentes (aunque se
        // quiten renglones), para que los name[] no colisionen.
        let siguienteIndice = 0;
        cuerpo.querySelectorAll('[name^="equipos["]').forEach(function (campo) {
            const coincidencia = campo.name.match(/^equipos\[(\d+)\]/);
            if (coincidencia) {
                siguienteIndice = Math.max(siguienteIndice, parseInt(coincidencia[1], 10) + 1);
            }
        });

        document.getElementById('btnAgregarEquipo').addEventListener('click', function () {
            const html = plantilla.innerHTML.split('__I__').join(String(siguienteIndice++));
            cuerpo.insertAdjacentHTML('beforeend', html);
        });

        // Quitar un renglón (delegado: el botón vive en filas clonadas).
        cuerpo.addEventListener('click', function (evento) {
            const boton = evento.target.closest('[data-quitar]');
            if (boton) {
                boton.closest('tr').remove();
            }
        });
    })();
</script>
@endsection
