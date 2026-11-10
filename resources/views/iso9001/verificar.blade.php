@extends('layouts.app')
@section('title', 'Verificar firma - ISO 9001 - HelpDesk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0"><i class="fas fa-fingerprint me-2"></i>Verificación de firma</h4>
        <p class="text-muted mb-0">Comprueba la integridad de un documento ISO 9001 mediante su huella SHA-256</p>
    </div>
    <a href="{{ route('iso9001.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('iso9001.verificar') }}" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label">Folio del documento</label>
                <input type="text" name="folio" class="form-control"
                       placeholder="Ej. FO-IT-01-0001"
                       value="{{ $folio !== '' ? $folio : old('folio') }}">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-shield-alt me-1"></i>Verificar
                </button>
            </div>
        </form>
    </div>
</div>

@if($resultado === 'vigente')
<div class="alert alert-success">
    <h5 class="alert-heading mb-1"><i class="fas fa-check-circle me-2"></i>Huella vigente</h5>
    El documento <strong>{{ $documento->folio }}</strong> no ha sido alterado: la información
    registrada coincide con la huella guardada al momento de crear/firmar.
</div>
@elseif($resultado === 'alterado')
<div class="alert alert-danger">
    <h5 class="alert-heading mb-1"><i class="fas fa-exclamation-triangle me-2"></i>Huella alterada</h5>
    La información del documento <strong>{{ $documento->folio }}</strong> <strong>no coincide</strong>
    con la huella registrada: el contenido fue modificado después de firmarse.
</div>
@elseif($resultado === 'no_encontrado')
<div class="alert alert-warning">
    <i class="fas fa-info-circle me-2"></i>
    No se encontró un documento activo con el folio <strong>{{ $folio }}</strong>.
    Verifica el folio (formato FO-IT-01-0001) o revisa la lista de documentos.
</div>
@endif

@if($documento)
<div class="card">
    <div class="card-header bg-white py-3">
        <strong><i class="fas fa-file-signature me-2"></i>{{ $documento->folio }}</strong>
        @if($documento->estaFirmado())
        <span class="badge bg-success ms-2">Firmado</span>
        @else
        <span class="badge bg-secondary ms-2">Pendiente</span>
        @endif
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <small class="text-muted d-block">Fecha del documento</small>
                {{ $documento->fecha->format('d/m/Y') }}
            </div>
            <div class="col-md-4">
                <small class="text-muted d-block">Empleado responsable</small>
                {{ $documento->nombreEmpleado }}
            </div>
            <div class="col-md-4">
                <small class="text-muted d-block">Correo</small>
                {{ $documento->correo }}
            </div>
            <div class="col-md-4">
                <small class="text-muted d-block">Firma empleado</small>
                @if($documento->fechaFirmaEmpleado)
                <i class="fas fa-check text-success me-1"></i>{{ $documento->fechaFirmaEmpleado->format('d/m/Y H:i') }}
                @else
                <span class="text-muted">Sin firmar</span>
                @endif
            </div>
            <div class="col-md-4">
                <small class="text-muted d-block">Firma de TI</small>
                @if($documento->fechaFirmaTi)
                <i class="fas fa-check text-success me-1"></i>{{ $documento->fechaFirmaTi->format('d/m/Y H:i') }}
                @else
                <span class="text-muted">Sin firmar</span>
                @endif
            </div>
            <div class="col-md-4">
                <small class="text-muted d-block">Creado por</small>
                {{ $documento->creador?->nombreCompleto() ?? '—' }}
            </div>
        </div>

        <div class="alert alert-secondary mb-0" style="font-size: 13px;">
            <small class="text-muted d-block mb-1">Huella SHA-256 registrada</small>
            <code style="word-break: break-all;">{{ $documento->huella ?? '—' }}</code>
        </div>
    </div>
</div>
@endif
@endsection
