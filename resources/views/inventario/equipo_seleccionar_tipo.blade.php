@extends('layouts.app')
@section('title', 'Seleccionar Tipo de Equipo - HelpDesk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-laptop me-2"></i>Nuevo Equipo - Seleccionar Tipo</h4>
    <a href="{{ route('inventario.equipo.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Volver</a>
</div>

<div class="card">
    <div class="card-body">
        <p class="text-muted mb-4">Selecciona el tipo de equipo que deseas registrar:</p>
        <div class="row g-3">
            @foreach($tiposEquipo as $tipo)
            <div class="col-md-3 col-sm-6">
                <a href="{{ route('inventario.equipo.create', ['tipo' => $tipo->id_TipoEquipo]) }}" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm tipo-card">
                        <div class="card-body text-center py-4">
                            <div class="tipo-icon mb-3">
                                @switch($tipo->id_TipoEquipo)
                                    @case(1)
                                        <i class="fas fa-desktop fa-2x text-primary"></i>
                                        @break
                                    @case(2)
                                        <i class="fas fa-tv fa-2x text-info"></i>
                                        @break
                                    @case(3)
                                        <i class="fas fa-keyboard fa-2x text-secondary"></i>
                                        @break
                                    @case(4)
                                        <i class="fas fa-computer-mouse fa-2x text-secondary"></i>
                                        @break
                                    @case(5)
                                        <i class="fas fa-plug fa-2x text-warning"></i>
                                        @break
                                    @case(6)
                                        <i class="fas fa-laptop fa-2x text-primary"></i>
                                        @break
                                    @case(7)
                                        <i class="fas fa-print fa-2x text-success"></i>
                                        @break
                                    @case(8)
                                        <i class="fas fa-video fa-2x text-danger"></i>
                                        @break
                                    @case(9)
                                        <i class="fas fa-location-dot fa-2x text-info"></i>
                                        @break
                                    @case(10)
                                        <i class="fas fa-mobile-screen fa-2x text-primary"></i>
                                        @break
                                    @case(11)
                                        <i class="fas fa-sim-card fa-2x text-success"></i>
                                        @break
                                    @case(12)
                                        <i class="fas fa-box fa-2x text-secondary"></i>
                                        @break
                                    @case(13)
                                        <i class="fas fa-battery-full fa-2x text-warning"></i>
                                        @break
                                    @case(14)
                                        <i class="fas fa-volume-high fa-2x text-info"></i>
                                        @break
                                    @case(15)
                                        <i class="fas fa-network-wired fa-2x text-success"></i>
                                        @break
                                    @case(16)
                                        <i class="fas fa-desktop fa-2x text-primary"></i>
                                        @break
                                    @case(17)
                                        <i class="fas fa-phone fa-2x text-secondary"></i>
                                        @break
                                    @default
                                        <i class="fas fa-cube fa-2x text-secondary"></i>
                                @endswitch
                            </div>
                            <h6 class="card-title mb-0">{{ $tipo->descripcion }}</h6>
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
