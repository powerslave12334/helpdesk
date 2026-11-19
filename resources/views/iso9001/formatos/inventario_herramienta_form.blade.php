@extends('layouts.app')

@section('title', 'Nuevo ' . $formato['clave'] . ' - ISO 9001')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-2">
    <div>
        <h4 class="mb-0">
            <i class="{{ $formato['icono'] ?? 'fas fa-file' }} me-2"></i>Nueva {{ $formato['nombre'] }}
            <span class="badge bg-primary ms-2">{{ $formato['clave'] }}</span>
        </h4>
        <p class="text-muted mb-0">Registra una herramienta en el inventario</p>
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
                    <label class="form-label">Código <span class="text-danger">*</span></label>
                    <input type="text" name="codigo" maxlength="50" class="form-control @error('codigo') is-invalid @enderror"
                           value="{{ old('codigo') }}" placeholder="Ej. HER-001">
                    @error('codigo')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" maxlength="150" class="form-control @error('nombre') is-invalid @enderror"
                           value="{{ old('nombre') }}" placeholder="Ej. Taladro eléctrico">
                    @error('nombre')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Marca <span class="text-danger">*</span></label>
                    <input type="text" name="marca" maxlength="100" class="form-control @error('marca') is-invalid @enderror"
                           value="{{ old('marca') }}" placeholder="Ej. Makita">
                    @error('marca')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Modelo <span class="text-danger">*</span></label>
                    <input type="text" name="modelo" maxlength="100" class="form-control @error('modelo') is-invalid @enderror"
                           value="{{ old('modelo') }}" placeholder="Ej. HP457DWE">
                    @error('modelo')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Número de serie</label>
                    <input type="text" name="numeroSerie" maxlength="100" class="form-control @error('numeroSerie') is-invalid @enderror"
                           value="{{ old('numeroSerie') }}" placeholder="Opcional">
                    @error('numeroSerie')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Número de inventario</label>
                    <input type="text" name="numeroInventario" maxlength="100" class="form-control @error('numeroInventario') is-invalid @enderror"
                           value="{{ old('numeroInventario') }}" placeholder="Opcional">
                    @error('numeroInventario')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Ubicación <span class="text-danger">*</span></label>
                    <input type="text" name="ubicacion" maxlength="150" class="form-control @error('ubicacion') is-invalid @enderror"
                           value="{{ old('ubicacion') }}" placeholder="Ej. Bodega A">
                    @error('ubicacion')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Estado <span class="text-danger">*</span></label>
                    <select name="estado" class="form-select @error('estado') is-invalid @enderror">
                        @foreach($estados as $clave => $etiqueta)
                            <option value="{{ $clave }}" {{ old('estado') === $clave ? 'selected' : '' }}>
                                {{ $etiqueta }}
                            </option>
                        @endforeach
                    </select>
                    @error('estado')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Fecha de adquisición</label>
                    <input type="date" name="fechaAdquisicion" class="form-control @error('fechaAdquisicion') is-invalid @enderror"
                           value="{{ old('fechaAdquisicion') }}">
                    @error('fechaAdquisicion')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('iso9001.documentos', ['clave' => $formato['clave']]) }}" class="btn btn-outline-secondary">
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Registrar herramienta
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
