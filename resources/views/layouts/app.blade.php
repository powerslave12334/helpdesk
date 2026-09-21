<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'HelpDesk')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar { width: 250px; min-height: 100vh; background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%); position: fixed; top: 0; left: 0; z-index: 100; transition: all 0.3s; }
        .sidebar .nav-link { color: #a0aec0; padding: 12px 20px; display: flex; align-items: center; gap: 10px; font-size: 0.9rem; border-left: 3px solid transparent; transition: all 0.2s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: #fff; background: rgba(255,255,255,0.08); border-left-color: #e94560; }
        .sidebar .nav-link i { width: 20px; text-align: center; }
        .sidebar .nav-section { color: #4a5568; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; padding: 15px 20px 5px; font-weight: 600; }
        .sidebar .brand { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar .brand h5 { color: #fff; margin: 0; font-weight: 700; }
        .sidebar .brand small { color: #a0aec0; }
        .main-content { margin-left: 250px; background: #f7fafc; min-height: 100vh; }
        .top-bar { background: #fff; padding: 12px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .content-area { padding: 24px; }
        .card { border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border-radius: 10px; }
        .card-header { background: #fff; border-bottom: 1px solid #e2e8f0; font-weight: 600; }
        .badge-status { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; }
        .table th { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; color: #4a5568; border-bottom: 2px solid #e2e8f0; }
        .table td { vertical-align: middle; font-size: 0.9rem; }
        .btn-action { padding: 4px 8px; font-size: 0.75rem; }
    </style>
    @yield('styles')
</head>
<body>
    <div class="sidebar">
        <div class="brand">
            <h5><i class="fas fa-headset me-2"></i>HelpDesk</h5>
            <small>Sistema de Soporte</small>
        </div>
        <nav class="mt-3">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>

            <div class="nav-section">Tickets</div>
            <a href="{{ route('tickets.index') }}" class="nav-link {{ request()->routeIs('tickets.index') ? 'active' : '' }}">
                <i class="fas fa-ticket"></i> Tickets
            </a>
            <a href="{{ route('ticketconfig.tipossoporte.index') }}" class="nav-link {{ request()->routeIs('ticketconfig.tipossoporte.*') ? 'active' : '' }}">
                <i class="fas fa-headset"></i> Tipo Soporte
            </a>
            <a href="{{ route('ticketconfig.tiposequipoticket.index') }}" class="nav-link {{ request()->routeIs('ticketconfig.tiposequipoticket.*') ? 'active' : '' }}">
                <i class="fas fa-laptop"></i> Tipo Equipo Soporte
            </a>
            <a href="{{ route('ticketconfig.tiposdesarrollo.index') }}" class="nav-link {{ request()->routeIs('ticketconfig.tiposdesarrollo.*') ? 'active' : '' }}">
                <i class="fas fa-code"></i> Tipo de Desarrollo
            </a>
            <a href="{{ route('ticketconfig.sistemas.index') }}" class="nav-link {{ request()->routeIs('ticketconfig.sistemas.*') ? 'active' : '' }}">
                <i class="fas fa-desktop"></i> Tipo de Sistema
            </a>

            <div class="nav-section">GPS</div>
            <a href="#" class="nav-link">
                <i class="fas fa-map-marker-alt"></i> GPS
            </a>

            <div class="nav-section">Inventario</div>
            <a href="{{ route('inventario.tipoEquipo.index') }}" class="nav-link {{ request()->routeIs('inventario.tipoEquipo.*') ? 'active' : '' }}">
                <i class="fas fa-tags"></i> Tipo Inventario
            </a>
            <a href="{{ route('inventario.equipo.index') }}" class="nav-link {{ request()->routeIs('inventario.equipo.*') ? 'active' : '' }}">
                <i class="fas fa-laptop"></i> Equipo
            </a>
            <a href="{{ route('inventario.asignacion.index') }}" class="nav-link {{ request()->routeIs('inventario.asignacion.*') ? 'active' : '' }}">
                <i class="fas fa-user-tag"></i> Asignación de Equipo
            </a>
            <a href="{{ route('inventario.campo.index') }}" class="nav-link {{ request()->routeIs('inventario.campo.*') ? 'active' : '' }}">
                <i class="fas fa-sliders"></i> Campos por Tipo
            </a>
            <a href="{{ route('inventario.tipoRelacion.index') }}" class="nav-link {{ request()->routeIs('inventario.tipoRelacion.*') ? 'active' : '' }}">
                <i class="fas fa-link"></i> Tipo Relación Equipo
            </a>
            <a href="{{ route('inventario.relacion.index') }}" class="nav-link {{ request()->routeIs('inventario.relacion.*') ? 'active' : '' }}">
                <i class="fas fa-project-diagram"></i> Relación Equipo
            </a>
            <a href="{{ route('inventario.historial.index') }}" class="nav-link {{ request()->routeIs('inventario.historial.*') ? 'active' : '' }}">
                <i class="fas fa-history"></i> Historial de Asignación
            </a>

            <div class="nav-section">Empleados</div>
            <a href="{{ route('empleados.index') }}" class="nav-link {{ request()->routeIs('empleados.index') ? 'active' : '' }}">
                <i class="fas fa-users"></i> Empleados
            </a>
            <a href="{{ route('empleados.index') }}" class="nav-link {{ request()->routeIs('empleados.index') ? 'active' : '' }}">
                <i class="fas fa-eye"></i> Ver Empleados
            </a>
            <a href="{{ route('configuracion.deptos.index') }}" class="nav-link {{ request()->routeIs('configuracion.deptos.*') ? 'active' : '' }}">
                <i class="fas fa-sitemap"></i> Departamentos
            </a>
            <a href="{{ route('configuracion.areas.index') }}" class="nav-link {{ request()->routeIs('configuracion.areas.*') ? 'active' : '' }}">
                <i class="fas fa-building"></i> Áreas
            </a>
            <a href="{{ route('configuracion.oficinas.index') }}" class="nav-link {{ request()->routeIs('configuracion.oficinas.*') ? 'active' : '' }}">
                <i class="fas fa-map-marker-alt"></i> Oficinas
            </a>
            <a href="{{ route('empleados.tipoEmpleado.index') }}" class="nav-link {{ request()->routeIs('empleados.tipoEmpleado.*') ? 'active' : '' }}">
                <i class="fas fa-id-card"></i> Tipo de Empleado
            </a>

            <div class="nav-section">Configuración</div>
            <a href="{{ route('configuracion.configGeneral.index') }}" class="nav-link {{ request()->routeIs('configuracion.configGeneral.*') ? 'active' : '' }}">
                <i class="fas fa-cog"></i> Configuración General
            </a>
            <a href="{{ route('configuracion.modulos.index') }}" class="nav-link {{ request()->routeIs('configuracion.modulos.*') ? 'active' : '' }}">
                <i class="fas fa-puzzle-piece"></i> Módulos
            </a>
            <a href="{{ route('configuracion.submodulos.index') }}" class="nav-link {{ request()->routeIs('configuracion.submodulos.*') ? 'active' : '' }}">
                <i class="fas fa-puzzle-piece"></i> Submódulos
            </a>
            <a href="{{ route('configuracion.acciones.index') }}" class="nav-link {{ request()->routeIs('configuracion.acciones.*') ? 'active' : '' }}">
                <i class="fas fa-bolt"></i> Acciones
            </a>
            <a href="{{ route('configuracion.roles.index') }}" class="nav-link {{ request()->routeIs('configuracion.roles.*') ? 'active' : '' }}">
                <i class="fas fa-user-shield"></i> Roles
            </a>
            <a href="{{ route('configuracion.rolesUsuarios.index') }}" class="nav-link {{ request()->routeIs('configuracion.rolesUsuarios.*') ? 'active' : '' }}">
                <i class="fas fa-user-tag"></i> Roles de Usuarios
            </a>
            <a href="{{ route('configuracion.rolPermiso.index') }}" class="nav-link {{ request()->routeIs('configuracion.rolPermiso.*') ? 'active' : '' }}">
                <i class="fas fa-lock"></i> Permisos por Rol
            </a>
            <a href="{{ route('configuracion.permisoUsuarios.index') }}" class="nav-link {{ request()->routeIs('configuracion.permisoUsuarios.*') ? 'active' : '' }}">
                <i class="fas fa-user-lock"></i> Permisos por Usuario
            </a>
            <a href="{{ route('configuracion.estatus.index') }}" class="nav-link {{ request()->routeIs('configuracion.estatus.*') ? 'active' : '' }}">
                <i class="fas fa-list-check"></i> Estatus
            </a>
            <a href="{{ route('ticketconfig.modulossistemas.index') }}" class="nav-link {{ request()->routeIs('ticketconfig.modulossistemas.*') ? 'active' : '' }}">
                <i class="fas fa-cubes"></i> Módulos Sistemas
            </a>
            <a href="{{ route('ticketconfig.submodulossistemas.index') }}" class="nav-link {{ request()->routeIs('ticketconfig.submodulossistemas.*') ? 'active' : '' }}">
                <i class="fas fa-cube"></i> Submódulos Sistemas
            </a>
        </nav>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div>
                <span class="text-muted">Bienvenido,</span>
                <strong>{{ session('NombreUsuario', auth()->user()->nombreCompleto()) }}</strong>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="position-relative" id="notif-bell">
                    <i class="fas fa-bell text-muted"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" id="notif-count">0</span>
                </span>
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i>Salir
                    </button>
                </form>
            </div>
        </div>
        <div class="content-area">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    (function() {
        const bell = document.getElementById('notif-bell');
        const countBadge = document.getElementById('notif-count');
        if (!bell) return;

        function fetchNotificaciones() {
            fetch('{{ route("api.notificaciones.obtener") }}', {
                headers: { 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.noLeidas > 0) {
                    countBadge.textContent = data.noLeidas;
                    countBadge.classList.remove('d-none');
                } else {
                    countBadge.classList.add('d-none');
                }
            })
            .catch(() => {});
        }

        bell.addEventListener('click', function() {
            fetchNotificaciones();
        });

        fetchNotificaciones();
        setInterval(fetchNotificaciones, 30000);
    })();
    </script>
    @yield('scripts')
</body>
</html>
