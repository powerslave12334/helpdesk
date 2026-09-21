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
        :root {
            --bg: #f7fafc;
            --surface: #ffffff;
            --surface-strong: #edf2f7;
            --text: #1a202c;
            --muted: #4a5568;
            --primary: #1a73e8;
            --primary-soft: rgba(26,115,232,0.1);
            --border: #e2e8f0;
            --shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        body.dark-mode {
            --bg: #101419;
            --surface: #181d24;
            --surface-strong: #222936;
            --text: #eef3fb;
            --muted: #a4afbf;
            --primary: #9d4edd;
            --primary-soft: rgba(157, 78, 221, 0.16);
            --border: #2b3340;
            --shadow: 0 16px 40px rgba(0, 0, 0, 0.32);
        }
        .sidebar { width: 250px; min-height: 100vh; background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%); position: fixed; top: 0; left: 0; z-index: 100; transition: all 0.3s; }
        .sidebar .nav-link { color: #a0aec0; padding: 12px 20px; display: flex; align-items: center; gap: 10px; font-size: 0.9rem; border-left: 3px solid transparent; transition: all 0.2s; text-decoration: none; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: #fff; background: rgba(255,255,255,0.08); border-left-color: #e94560; }
        .sidebar .nav-link i { width: 20px; text-align: center; }
        .sidebar .nav-link .fa-chevron-down { transition: transform 0.2s ease; }
        .sidebar .nav-link[aria-expanded="true"] .fa-chevron-down { transform: rotate(180deg); }
        .sidebar .collapse .nav-link { padding-left: 50px; font-size: 0.83rem; }
        .sidebar .nav-section { color: #4a5568; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; padding: 15px 20px 5px; font-weight: 600; }
        .sidebar .brand { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar .brand h5 { color: #fff; margin: 0; font-weight: 700; }
        .sidebar .brand small { color: #a0aec0; }
        .main-content { margin-left: 250px; background: var(--bg); min-height: 100vh; transition: background 0.3s; }
        .top-bar { background: var(--surface); padding: 12px 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; transition: background 0.3s, border-color 0.3s; }
        .content-area { padding: 24px; }
        .card { border: none; box-shadow: var(--shadow); border-radius: 10px; background: var(--surface); transition: background 0.3s; }
        .card-header { background: var(--surface); border-bottom: 1px solid var(--border); font-weight: 600; }
        .badge-status { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; }
        .table { color: var(--text); }
        .table th { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--muted); border-bottom: 2px solid var(--border); }
        .table td { vertical-align: middle; font-size: 0.9rem; border-color: var(--border); }
        .btn-action { padding: 4px 8px; font-size: 0.75rem; }
        .form-control, .form-select { background: var(--surface); color: var(--text); border-color: var(--border); }
        .form-control:focus, .form-select:focus { background: var(--surface); color: var(--text); border-color: var(--primary); box-shadow: 0 0 0 0.2rem var(--primary-soft); }
        .form-label { color: var(--text); }
        body.dark-mode .sidebar .nav-section { color: #4a5568; }
        body.dark-mode .alert-success { background: rgba(37,180,107,0.15); color: #68d391; border-color: rgba(37,180,107,0.3); }
        body.dark-mode .alert-danger { background: rgba(231,76,60,0.15); color: #fc8181; border-color: rgba(231,76,60,0.3); }
        body.dark-mode .modal-content { background: var(--surface); color: var(--text); }
        body.dark-mode .modal-header { border-color: var(--border); }
        body.dark-mode .modal-footer { border-color: var(--border); }
        body.dark-mode .dropdown-menu { background: var(--surface); border-color: var(--border); }
        body.dark-mode .dropdown-item { color: var(--text); }
        body.dark-mode .dropdown-item:hover { background: var(--surface-strong); color: var(--text); }
        .btn-theme-toggle { background: transparent; border: 1px solid var(--border); color: var(--muted); border-radius: 8px; padding: 6px 10px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 6px; font-size: 0.85rem; }
        .btn-theme-toggle:hover { color: var(--primary); border-color: var(--primary); background: var(--primary-soft); }
        .text-muted { color: var(--muted) !important; }
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

            @php
                $isTickets = request()->routeIs('tickets.*') || request()->routeIs('ticketconfig.tipossoporte.*') || request()->routeIs('ticketconfig.tiposequipoticket.*') || request()->routeIs('ticketconfig.tiposdesarrollo.*') || request()->routeIs('ticketconfig.sistemas.*');
                $isInventario = request()->routeIs('inventario.*');
                $isEmpleados = request()->routeIs('empleados.*') || request()->routeIs('configuracion.deptos.*') || request()->routeIs('configuracion.areas.*') || request()->routeIs('configuracion.oficinas.*');
                $isConfig = request()->routeIs('configuracion.*') || request()->routeIs('ticketconfig.modulossistemas.*') || request()->routeIs('ticketconfig.submodulossistemas.*');
            @endphp

            <div class="nav-section">Tickets</div>
            <a href="#menuTickets" class="nav-link {{ $isTickets ? 'active' : '' }}" data-bs-toggle="collapse" role="button" aria-expanded="{{ $isTickets ? 'true' : 'false' }}">
                <i class="fas fa-ticket"></i> Tickets
                <span class="ms-auto"><i class="fas fa-chevron-down fa-xs"></i></span>
            </a>
            <div class="collapse {{ $isTickets ? 'show' : '' }}" id="menuTickets">
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
            </div>

            <div class="nav-section">GPS</div>
            <a href="#" class="nav-link">
                <i class="fas fa-map-marker-alt"></i> GPS
            </a>

            <div class="nav-section">Inventario</div>
            <a href="#menuInventario" class="nav-link {{ $isInventario ? 'active' : '' }}" data-bs-toggle="collapse" role="button" aria-expanded="{{ $isInventario ? 'true' : 'false' }}">
                <i class="fas fa-boxes-stacked"></i> Inventario
                <span class="ms-auto"><i class="fas fa-chevron-down fa-xs"></i></span>
            </a>
            <div class="collapse {{ $isInventario ? 'show' : '' }}" id="menuInventario">
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
            </div>

            <div class="nav-section">Empleados</div>
            <a href="#menuEmpleados" class="nav-link {{ $isEmpleados ? 'active' : '' }}" data-bs-toggle="collapse" role="button" aria-expanded="{{ $isEmpleados ? 'true' : 'false' }}">
                <i class="fas fa-users"></i> Empleados
                <span class="ms-auto"><i class="fas fa-chevron-down fa-xs"></i></span>
            </a>
            <div class="collapse {{ $isEmpleados ? 'show' : '' }}" id="menuEmpleados">
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
            </div>

            <div class="nav-section">Configuración</div>
            <a href="#menuConfig" class="nav-link {{ $isConfig ? 'active' : '' }}" data-bs-toggle="collapse" role="button" aria-expanded="{{ $isConfig ? 'true' : 'false' }}">
                <i class="fas fa-cog"></i> Configuración
                <span class="ms-auto"><i class="fas fa-chevron-down fa-xs"></i></span>
            </a>
            <div class="collapse {{ $isConfig ? 'show' : '' }}" id="menuConfig">
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
            </div>
        </nav>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div>
                <span class="text-muted">Bienvenido,</span>
                <strong>{{ session('NombreUsuario', auth()->user()->nombreCompleto()) }}</strong>
            </div>
            <div class="d-flex align-items-center gap-3">
                <button class="btn-theme-toggle" id="btnThemeToggle" title="Cambiar tema">
                    <i class="fas fa-moon" id="themeIcon"></i>
                    <span id="themeText">Modo oscuro</span>
                </button>
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
        const body = document.body;
        const btnTheme = document.getElementById('btnThemeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const themeText = document.getElementById('themeText');

        const saved = localStorage.getItem('helpdesk-theme');
        if (saved === 'dark') body.classList.add('dark-mode');
        updateThemeBtn();

        if (btnTheme) {
            btnTheme.addEventListener('click', function() {
                body.classList.toggle('dark-mode');
                localStorage.setItem('helpdesk-theme', body.classList.contains('dark-mode') ? 'dark' : 'light');
                updateThemeBtn();
            });
        }

        function updateThemeBtn() {
            const isDark = body.classList.contains('dark-mode');
            if (themeIcon) themeIcon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
            if (themeText) themeText.textContent = isDark ? 'Modo claro' : 'Modo oscuro';
        }

        window.addEventListener('storage', function(e) {
            if (e.key === 'helpdesk-theme') {
                if (e.newValue === 'dark') body.classList.add('dark-mode');
                else body.classList.remove('dark-mode');
                updateThemeBtn();
            }
        });
    })();
    </script>
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
