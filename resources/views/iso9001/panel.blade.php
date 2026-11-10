@extends('layouts.app')
@section('title', 'ISO 9001 - Generador de Documentos - HelpDesk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-certificate me-2"></i>ISO 9001 - Generador de Documentos</h4>
    <a href="{{ route('dashboard') }}" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Volver</a>
</div>

<div class="card">
    <div class="card-body">
        <p class="text-muted mb-4">Selecciona el formato FO-IT que deseas generar:</p>
        <div class="row g-3">
            @foreach($formatos as $formato)
            <div class="col-md-3 col-sm-6">
                <a href="{{ route('iso9001.documentos', ['clave' => $formato['clave']]) }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm tipo-card">
                        <div class="card-body text-center py-4">
                            <div class="tipo-icon mb-3">
                                <i class="{{ $formato['icono'] ?? 'fas fa-file' }} fa-2x text-primary"></i>
                            </div>
                            <span class="badge bg-primary mb-2">{{ $formato['clave'] }}</span>
                            <h6 class="card-title mb-0 text-dark">{{ $formato['nombre'] }}</h6>
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@section('scripts')
<style>
.tipo-card {
    transition: all 0.2s ease;
    cursor: pointer;
}
.tipo-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.15) !important;
    border-color: #0d6efd !important;
}
.tipo-icon {
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
@endsection
