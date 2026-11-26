@extends('layouts.app')

@section('title', 'Nuevo ' . $formato['clave'] . ' - ISO 9001')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-2">
    <div>
        <h4 class="mb-0">
            <i class="{{ $formato['icono'] ?? 'fas fa-file' }} me-2"></i>Nueva {{ $formato['nombre'] }}
            <span class="badge bg-primary ms-2">{{ $formato['clave'] }}</span>
        </h4>
        <p class="text-muted mb-0">Registra un activo de TI en el inventario de equipos</p>
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
                <div class="col-md-4">
                    <label class="form-label">Usuario <span class="text-danger">*</span></label>
                    <select name="usuario_id" id="selectUsuario" class="form-select @error('usuario_id') is-invalid @enderror">
                        <option value="">-- Seleccionar usuario --</option>
                        @foreach($usuarios as $u)
                        <option value="{{ $u->id_Usuario }}"
                            data-departamento="{{ $u->depto?->nombre ?? '' }}"
                            {{ old('usuario_id') == $u->id_Usuario ? 'selected' : '' }}>
                            {{ $u->nombreCompleto() }} ({{ $u->correo }})
                        </option>
                        @endforeach
                    </select>
                    @error('usuario_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Departamento</label>
                    <input type="text" name="departamento" id="campoDepartamento" maxlength="100" class="form-control @error('departamento') is-invalid @enderror"
                           value="{{ old('departamento') }}" placeholder="Departamento del usuario" readonly>
                    @error('departamento')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Ubicación <span class="text-danger">*</span></label>
                    <input type="text" name="ubicacion" maxlength="150" class="form-control @error('ubicacion') is-invalid @enderror"
                           value="{{ old('ubicacion') }}" placeholder="Ej. Oficina 101">
                    @error('ubicacion')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Tipo de Equipo <span class="text-danger">*</span></label>
                    <select name="tipoEquipo" class="form-select @error('tipoEquipo') is-invalid @enderror">
                        @foreach($tiposEquipo as $te)
                        <option value="{{ $te->id_TipoEquipo }}" {{ old('tipoEquipo') == $te->id_TipoEquipo ? 'selected' : '' }}>
                            {{ $te->descripcion }}
                        </option>
                        @endforeach
                    </select>
                    @error('tipoEquipo')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">No. Serie <span class="text-danger">*</span></label>
                    <input type="text" name="noSerie" maxlength="100" class="form-control @error('noSerie') is-invalid @enderror"
                           value="{{ old('noSerie') }}" placeholder="Ej. ABC123456">
                    @error('noSerie')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Marca <span class="text-danger">*</span></label>
                    <input type="text" name="marca" maxlength="100" class="form-control @error('marca') is-invalid @enderror"
                           value="{{ old('marca') }}" placeholder="Ej. Dell">
                    @error('marca')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Modelo <span class="text-danger">*</span></label>
                    <input type="text" name="modelo" maxlength="100" class="form-control @error('modelo') is-invalid @enderror"
                           value="{{ old('modelo') }}" placeholder="Ej. Latitude 5420">
                    @error('modelo')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Condición <span class="text-danger">*</span></label>
                    <select name="condicion" class="form-select @error('condicion') is-invalid @enderror">
                        @foreach($estatus as $e)
                        <option value="{{ $e->id_Estatus }}" {{ old('condicion') == $e->id_Estatus ? 'selected' : '' }}>
                            {{ $e->descripcion }}
                        </option>
                        @endforeach
                    </select>
                    @error('condicion')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('iso9001.documentos', ['clave' => $formato['clave']]) }}" class="btn btn-outline-secondary">
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Registrar activo
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    (function () {
        const select = document.getElementById('selectUsuario');
        const campoDepartamento = document.getElementById('campoDepartamento');

        function autocompletar() {
            const opcion = select.options[select.selectedIndex];
            if (campoDepartamento && opcion) {
                campoDepartamento.value = opcion.dataset.departamento || '';
            }
        }

        select.addEventListener('change', autocompletar);
        document.addEventListener('DOMContentLoaded', autocompletar);
    })();
</script>
@endsection
