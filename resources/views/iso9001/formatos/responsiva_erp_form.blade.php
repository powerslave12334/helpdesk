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

                <div class="col-md-9">
                    <label class="form-label">Nombre del empleado responsable del manejo de clave <span class="text-danger">*</span></label>
                    <input type="text" name="nombreEmpleado" class="form-control @error('nombreEmpleado') is-invalid @enderror"
                           value="{{ old('nombreEmpleado') }}" placeholder="Nombre completo del empleado" maxlength="150">
                    @error('nombreEmpleado')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Puesto <span class="text-danger">*</span></label>
                    <input type="text" name="puesto" class="form-control @error('puesto') is-invalid @enderror"
                           value="{{ old('puesto') }}" placeholder="Puesto del empleado" maxlength="100">
                    @error('puesto')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Correo <span class="text-danger">*</span></label>
                    <input type="email" name="correo" class="form-control @error('correo') is-invalid @enderror"
                           value="{{ old('correo') }}" placeholder="correo@empresa.com" maxlength="150">
                    @error('correo')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Usuario ERP <span class="text-danger">*</span></label>
                    <input type="text" name="usuarioErp" class="form-control @error('usuarioErp') is-invalid @enderror"
                           value="{{ old('usuarioErp') }}" placeholder="Usuario de acceso al ERP" maxlength="100">
                    @error('usuarioErp')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Contraseña ERP <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" name="contrasenaErp" id="contrasenaErp"
                               class="form-control @error('contrasenaErp') is-invalid @enderror"
                               value="{{ old('contrasenaErp') }}" placeholder="Contraseña de acceso al ERP" maxlength="255">
                        <button class="btn btn-outline-secondary" type="button" id="toggleContrasena" title="Mostrar u ocultar contraseña">
                            <i class="fas fa-eye"></i>
                        </button>
                        @error('contrasenaErp')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
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
@endsection

@section('scripts')
<script>
    document.getElementById('toggleContrasena').addEventListener('click', function () {
        const campo = document.getElementById('contrasenaErp');
        const visible = campo.type === 'text';
        campo.type = visible ? 'password' : 'text';
        this.innerHTML = visible ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
    });
</script>
@endsection
