@extends('layouts.app')
@section('title', $formato['clave'] . ' - ISO 9001 - HelpDesk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">
        <i class="{{ $formato['icono'] ?? 'fas fa-file' }} me-2"></i>{{ $formato['nombre'] }}
        <span class="badge bg-primary ms-2">{{ $formato['clave'] }}</span>
    </h4>
    <a href="{{ route('iso9001.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Volver</a>
</div>

<div class="card">
    <div class="card-body text-center py-5">
        <i class="fas fa-tools fa-3x text-muted mb-3"></i>
        <h5>Formato en construcción</h5>
        <p class="text-muted mb-4">
            El formato <strong>{{ $formato['clave'] }} - {{ $formato['nombre'] }}</strong> se está habilitando.
            Pronto podrás ver sus documentos y generarlos desde aquí.
        </p>
        <a href="{{ route('iso9001.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Volver al panel
        </a>
    </div>
</div>
@endsection
