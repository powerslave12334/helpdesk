@extends('layouts.app')
@section('title', 'Módulos por Rol - HelpDesk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-toggle-on me-2"></i>Módulos y Submódulos por Rol</h4>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle me-1"></i>Active los módulos y submódulos que cada rol podrá ver y utilizar. Al activar un módulo se marcan todos sus submódulos. Los roles administradores siempre tienen todo.
</div>

<div class="row">
    @forelse($roles as $r)
    @php
        $esAdmin = $esAdminPorRol[$r->id_Rol];
        $modsRol = $modulosPorRol->get($r->id_Rol, []);
        $subsRol = $submodulosPorRol->get($r->id_Rol, []);
    @endphp
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-user-shield me-2"></i>{{ $r->nombre }}</span>
                <span class="badge {{ $esAdmin ? 'bg-primary' : 'bg-secondary' }}">{{ $esAdmin ? 'Administrador' : 'Acceso por módulos' }}</span>
            </div>
            <div class="card-body">
                @if($esAdmin)
                    <div class="alert alert-success mb-0">
                        <i class="fas fa-check-circle me-1"></i>Tiene acceso a todos los módulos del sistema.
                    </div>
                @else
                    <form method="POST" action="{{ route('configuracion.rolModulo.guardar') }}">
                        @csrf
                        <input type="hidden" name="id_Rol" value="{{ $r->id_Rol }}">

                        @foreach($modulos as $m)
                        @php
                            $subs = $submodulosPorModulo->get($m['id'], collect());
                            $tieneSubs = $subs->count() > 0;
                            $modChecked = $tieneSubs
                                ? $subs->contains(fn($s) => in_array($s['clave'], $subsRol))
                                : in_array($m['id'], $modsRol);
                        @endphp
                        <div class="border rounded p-2 mb-2" data-modulo-card>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                    id="rol{{ $r->id_Rol }}-mod{{ $m['id'] }}"
                                    data-modulo-switch
                                    name="ModulosSeleccionados[]" value="{{ $m['id'] }}"
                                    {{ $modChecked ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="rol{{ $r->id_Rol }}-mod{{ $m['id'] }}">
                                    {{ $m['nombre'] }}
                                </label>
                            </div>

                            @if($tieneSubs)
                            <div class="ms-4 mt-1 d-flex flex-column gap-1">
                                @foreach($subs as $sm)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                        id="rol{{ $r->id_Rol }}-sub{{ $sm['clave'] }}"
                                        data-submodulo
                                        name="SubModulosSeleccionados[]" value="{{ $sm['clave'] }}"
                                        {{ in_array($sm['clave'], $subsRol) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="rol{{ $r->id_Rol }}-sub{{ $sm['clave'] }}">
                                        {{ $sm['nombre'] }}
                                    </label>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @endforeach

                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Guardar</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle me-1"></i>No hay roles registrados.</div>
    </div>
    @endforelse
</div>
@endsection

@section('scripts')
<script>
document.querySelectorAll('[data-modulo-card]').forEach(function(card) {
    const moduloSwitch = card.querySelector('[data-modulo-switch]');
    const subSwitches = card.querySelectorAll('[data-submodulo]');

    if (moduloSwitch) {
        moduloSwitch.addEventListener('change', function() {
            subSwitches.forEach(function(sub) { sub.checked = moduloSwitch.checked; });
        });
    }

    subSwitches.forEach(function(sub) {
        sub.addEventListener('change', function() {
            if (!moduloSwitch) return;
            const alguno = Array.from(subSwitches).some(s => s.checked);
            moduloSwitch.checked = alguno;
        });
    });
});
</script>
@endsection
