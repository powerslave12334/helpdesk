<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'HelpDesk')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/table-controls.css') }}">
    <style>
        :root {
            --font: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            --bg: #f8fafc;
            --surface: #ffffff;
            --surface-strong: #f1f5f9;
            --text: #0f172a;
            --muted: #64748b;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --primary-soft: rgba(37, 99, 235, 0.08);
            --border: #e2e8f0;
            --border-focus: #2563eb;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.04);
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.06), 0 2px 4px rgba(0, 0, 0, 0.04);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.08), 0 4px 6px rgba(0, 0, 0, 0.04);
            --radius-sm: 6px;
            --radius: 10px;
            --radius-lg: 14px;
        }
        body.dark-mode {
            --bg: #0f172a;
            --surface: #1e293b;
            --surface-strong: #334155;
            --text: #f1f5f9;
            --muted: #94a3b8;
            --primary: #60a5fa;
            --primary-hover: #93c5fd;
            --primary-soft: rgba(96, 165, 250, 0.12);
            --border: #334155;
            --border-focus: #60a5fa;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.2);
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.3), 0 1px 2px rgba(0, 0, 0, 0.2);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.3), 0 2px 4px rgba(0, 0, 0, 0.2);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.4), 0 4px 6px rgba(0, 0, 0, 0.3);
        }
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: var(--font); font-size: 14px; line-height: 1.5; color: var(--text); background: var(--bg); -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
        h1, h2, h3, h4, h5, h6 { font-weight: 600; letter-spacing: -0.01em; }

        /* Sidebar */
        .sidebar { width: 260px; min-height: 100vh; background: #0f172a; position: fixed; top: 0; left: 0; z-index: 100; overflow-y: auto; overflow-x: hidden; transition: width 0.2s ease; }
        .sidebar .brand { padding: 20px 20px 16px; border-bottom: 1px solid rgba(255,255,255,0.08); display: flex; align-items: center; gap: 12px; }
        .sidebar .brand-icon { width: 36px; height: 36px; border-radius: var(--radius-sm); background: var(--primary); display: grid; place-items: center; color: #fff; font-size: 16px; flex-shrink: 0; }
        .sidebar .brand h5 { color: #f8fafc; margin: 0; font-weight: 700; font-size: 15px; letter-spacing: -0.01em; }
        .sidebar .brand small { color: #64748b; font-size: 11px; display: block; margin-top: 1px; }
        .sidebar .nav-section { color: #475569; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; padding: 20px 20px 6px; font-weight: 600; }
        .sidebar .nav-link { color: #94a3b8; padding: 9px 16px; margin: 1px 10px; display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 500; border-radius: var(--radius-sm); transition: all 0.15s ease; text-decoration: none; }
        .sidebar .nav-link:hover { color: #e2e8f0; background: rgba(255,255,255,0.06); }
        .sidebar .nav-link.active { color: #f8fafc; background: rgba(37, 99, 235, 0.15); }
        .sidebar .nav-link i { width: 20px; text-align: center; font-size: 14px; opacity: 0.7; }
        .sidebar .nav-link.active i { opacity: 1; color: var(--primary); }
        .sidebar .nav-link .fa-chevron-down { transition: transform 0.2s ease; margin-left: auto; font-size: 10px; opacity: 0.5; }
        .sidebar .nav-link[aria-expanded="true"] .fa-chevron-down { transform: rotate(180deg); }
        .sidebar .collapse .nav-link { padding-left: 46px; font-size: 13px; margin: 0 10px; }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }

        /* Main content */
        .main-content { margin-left: 260px; background: var(--bg); min-height: 100vh; transition: background 0.2s, margin-left 0.2s ease; }
        .top-bar { background: var(--surface); padding: 10px 28px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 50; }
        .content-area { padding: 28px; }

        /* Cards */
        .card { border: 1px solid var(--border); box-shadow: var(--shadow-sm); border-radius: var(--radius); background: var(--surface); transition: box-shadow 0.2s, border-color 0.2s; }
        .card:hover { box-shadow: var(--shadow); }
        .card-header { background: var(--surface); border-bottom: 1px solid var(--border); font-weight: 600; font-size: 14px; padding: 14px 20px; }

        /* Tables */
        .table { color: var(--text); }
        .table th { font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); font-weight: 600; border-bottom: 1px solid var(--border); padding: 10px 16px; }
        .table td { vertical-align: middle; font-size: 13px; padding: 12px 16px; border-color: var(--border); }
        .table > :not(caption) > * > * { color: var(--text); background-color: transparent; }
        .table-striped > tbody > tr:nth-of-type(odd) > * { color: var(--text); background-color: transparent; }
        .table-hover > tbody > tr:hover > * { color: var(--text); background-color: var(--primary-soft); }

        /* Buttons */
        .btn { font-weight: 500; border-radius: var(--radius-sm); font-size: 13px; }
        .btn-primary { background: var(--primary); border-color: var(--primary); box-shadow: 0 1px 2px rgba(37, 99, 235, 0.2); }
        .btn-primary:hover { background: var(--primary-hover); border-color: var(--primary-hover); box-shadow: 0 2px 4px rgba(37, 99, 235, 0.3); transform: translateY(-1px); }
        .btn-primary:active { transform: translateY(0); }

        /* Badges */
        .badge { font-weight: 500; font-size: 11px; padding: 4px 10px; border-radius: var(--radius-sm); }

        /* Forms */
        .form-control, .form-select { border-radius: var(--radius-sm); font-size: 13px; border-color: var(--border); }
        .form-control:focus, .form-select:focus { border-color: var(--border-focus); box-shadow: 0 0 0 3px var(--primary-soft); }
        .form-label { font-size: 13px; font-weight: 500; color: var(--text); margin-bottom: 6px; }

        /* Alerts */
        .alert { border-radius: var(--radius); font-size: 13px; border: none; }

        /* Theme toggle */
        .btn-theme-toggle { background: transparent; border: 1px solid var(--border); color: var(--muted); border-radius: var(--radius-sm); padding: 6px 12px; cursor: pointer; transition: all 0.15s; display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 500; }
        .btn-theme-toggle:hover { color: var(--primary); border-color: var(--primary); background: var(--primary-soft); }

        /* Dark mode overrides */
        body.dark-mode .card { border-color: var(--border); }
        body.dark-mode .card-header { background: var(--surface-strong); border-color: var(--border); }
        body.dark-mode .modal-content { background: var(--surface); color: var(--text); border-color: var(--border); }
        body.dark-mode .modal-header, body.dark-mode .modal-footer { border-color: var(--border); }
        body.dark-mode .modal-title { color: var(--text); }
        body.dark-mode .form-control, body.dark-mode .form-select, body.dark-mode textarea { background-color: var(--surface-strong); color: var(--text); border-color: var(--border); }
        body.dark-mode .form-control:focus, body.dark-mode .form-select:focus { background-color: var(--surface-strong); color: var(--text); border-color: var(--border-focus); }
        body.dark-mode .form-label, body.dark-mode .form-check-label { color: var(--text); }
        body.dark-mode .table { color: var(--text) !important; }
        body.dark-mode .table > :not(caption) > * > * { color: var(--text) !important; background-color: transparent !important; }
        body.dark-mode .table th { color: var(--muted) !important; border-color: var(--border) !important; }
        body.dark-mode .table td { color: var(--text) !important; border-color: var(--border) !important; }
        body.dark-mode .table-striped > tbody > tr:nth-of-type(odd) > * { background-color: rgba(255,255,255,0.02) !important; }
        body.dark-mode .table-hover > tbody > tr:hover > * { background-color: var(--primary-soft) !important; }
        body.dark-mode .badge { border: 1px solid rgba(255,255,255,0.1); }
        body.dark-mode .bg-info { background-color: #164e63 !important; color: #67e8f9 !important; }
        body.dark-mode .bg-success { background-color: #065f46 !important; color: #6ee7b7 !important; }
        body.dark-mode .bg-danger { background-color: #7f1d1d !important; color: #fca5a5 !important; }
        body.dark-mode .bg-warning { background-color: #78350f !important; color: #fcd34d !important; }
        body.dark-mode .bg-secondary { background-color: #334155 !important; color: #cbd5e1 !important; }
        body.dark-mode .bg-primary { background-color: rgba(96, 165, 250, 0.15) !important; color: var(--primary) !important; }
        body.dark-mode .btn-primary { background-color: var(--primary); border-color: var(--primary); color: #0f172a; }
        body.dark-mode .btn-primary:hover { background-color: var(--primary-hover); border-color: var(--primary-hover); }
        body.dark-mode .btn-danger { background-color: #dc2626; border-color: #dc2626; }
        body.dark-mode .btn-success { background-color: #059669; border-color: #059669; }
        body.dark-mode .page-link { background: var(--surface); border-color: var(--border); color: var(--text); }
        body.dark-mode .page-item.active .page-link { background: var(--primary); border-color: var(--primary); color: #0f172a; }
        body.dark-mode .page-item.disabled .page-link { background: var(--surface-strong); border-color: var(--border); color: var(--muted); }
        body.dark-mode .text-muted { color: var(--muted) !important; }
        body.dark-mode .text-dark { color: var(--text) !important; }
        body.dark-mode a { color: var(--primary); }
        body.dark-mode .btn-outline-secondary { color: var(--muted); border-color: var(--border); }
        body.dark-mode .btn-outline-secondary:hover { color: var(--text); background: var(--surface-strong); border-color: var(--border); }
        body.dark-mode .btn-outline-primary { color: var(--primary); border-color: var(--primary); }
        body.dark-mode .btn-outline-primary:hover { background: var(--primary); color: #0f172a; }
        body.dark-mode .dropdown-menu { background: var(--surface); border-color: var(--border); }
        body.dark-mode .dropdown-item { color: var(--text); }
        body.dark-mode .dropdown-item:hover, body.dark-mode .dropdown-item:focus { background: var(--surface-strong); color: var(--text); }
        body.dark-mode .list-group-item { background: var(--surface); color: var(--text); border-color: var(--border); }
        body.dark-mode .alert-success { background-color: #065f46; color: #6ee7b7; }
        body.dark-mode .alert-danger { background-color: #7f1d1d; color: #fca5a5; }

        /* Scrollbar global */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--muted); }

        /* Focus visible */
        :focus-visible { outline: 2px solid var(--primary); outline-offset: 2px; }
        .form-control:focus-visible, .form-select:focus-visible { outline: none; }

        /* Responsive sidebar */
        .btn-sidebar-toggle { display: none; background: transparent; border: 1px solid var(--border); color: var(--muted); border-radius: var(--radius-sm); padding: 6px 10px; cursor: pointer; }
        .btn-sidebar-toggle:hover { color: var(--primary); border-color: var(--primary); background: var(--primary-soft); }
        .sidebar-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); z-index: 99; opacity: 0; visibility: hidden; transition: opacity 0.2s ease, visibility 0.2s ease; }
        .sidebar-backdrop.show { opacity: 1; visibility: visible; }
        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.25s ease; }
            .sidebar.open { transform: translateX(0); box-shadow: var(--shadow-lg); }
            .main-content { margin-left: 0; }
            .btn-sidebar-toggle { display: inline-flex; align-items: center; }
        }
        @media (min-width: 992px) { .sidebar-backdrop { display: none; } }
        @media (max-width: 576px) { .content-area { padding: 16px; } .top-bar { padding: 10px 16px; } }

        /* Sidebar colapsable (solo escritorio) */
        .btn-sidebar-collapse { display: none; background: transparent; border: 1px solid var(--border); color: var(--muted); border-radius: var(--radius-sm); padding: 6px 10px; cursor: pointer; transition: all 0.15s ease; align-items: center; }
        .btn-sidebar-collapse:hover { color: var(--primary); border-color: var(--primary); background: var(--primary-soft); }
        .sidebar .brand-text { min-width: 0; }
        .sidebar .nav-label { white-space: nowrap; }

        @media (min-width: 992px) {
            .btn-sidebar-collapse { display: inline-flex; }

            body.sidebar-collapsed .sidebar { width: 72px; }
            body.sidebar-collapsed .main-content { margin-left: 72px; }

            body.sidebar-collapsed .sidebar .brand { justify-content: center; padding: 20px 12px 16px; }
            body.sidebar-collapsed .sidebar .brand-text { display: none; }
            body.sidebar-collapsed .sidebar .nav-section { display: none; }
            body.sidebar-collapsed .sidebar .nav-group { margin-top: 4px; }
            body.sidebar-collapsed .sidebar .nav-label,
            body.sidebar-collapsed .sidebar .nav-chevron { display: none; }
            body.sidebar-collapsed .sidebar .nav-link { justify-content: center; padding: 10px 0; margin: 2px 8px; gap: 0; }
            body.sidebar-collapsed .sidebar .nav-link i { width: auto; }

            /* Submenús: se despliegan como flyout al pasar el cursor */
            body.sidebar-collapsed .sidebar .collapse { display: none; }
            body.sidebar-collapsed .sidebar .collapse.show {
                display: block;
                position: fixed;
                left: 72px;
                min-width: 220px;
                width: max-content;
                background: #0f172a;
                border: 1px solid rgba(255,255,255,0.08);
                border-left: none;
                border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
                padding: 6px 0;
                margin: 0;
                box-shadow: 12px 0 32px rgba(0,0,0,0.35);
                z-index: 130;
            }
            body.sidebar-collapsed .sidebar .collapse.show .nav-link { justify-content: flex-start; padding: 9px 16px; margin: 0 8px; gap: 10px; }
            body.sidebar-collapsed .sidebar .collapse.show .nav-label { display: inline; }
            body.sidebar-collapsed .sidebar .collapse.show .nav-link i { width: 20px; text-align: center; }
        }
        @media (prefers-reduced-motion: reduce) { .sidebar, .sidebar-backdrop, .main-content { transition: none; } }
    </style>
    @yield('styles')
</head>
<body>
    <script>
        // Aplica el estado colapsado antes de pintar para evitar parpadeos.
        try {
            if (localStorage.getItem('helpdesk-sidebar') === 'collapsed') {
                document.body.classList.add('sidebar-collapsed');
            }
        } catch (e) {}
    </script>
    <div class="sidebar" id="appSidebar">
        <div class="brand">
            <div class="brand-icon"><i class="fas fa-headset"></i></div>
            <div class="brand-text">
                <h5>HelpDesk</h5>
                <small>Sistema de Soporte</small>
            </div>
        </div>
        <nav class="mt-3">
            @php
                $modulosCatalogo = \App\Support\Modulos::modulos();
                $submodulosCatalogo = \App\Support\Modulos::submodulos();
                $permisosService = app(\App\Services\PermisosService::class);
                $idUsuarioSesion = (int) session('IdUsuario', 0);
                $modulosActivosIds = $permisosService->modulosActivosUsuarioIds($idUsuarioSesion);
                $submodulosActivos = $permisosService->submodulosActivosUsuarioClaves($idUsuarioSesion);
                $tieneRolActivo = $idUsuarioSesion > 0 && $permisosService->tieneRolActivo($idUsuarioSesion);
            @endphp

            @foreach($modulosCatalogo as $m)
                @if(isset($m['enlace']))
                <a href="{{ route($m['enlace']) }}" class="nav-link {{ request()->routeIs($m['enlace']) ? 'active' : '' }}">
                    <i class="{{ $m['icono'] }}"></i> <span class="nav-label">{{ $m['nombre'] }}</span>
                </a>
                @elseif(in_array((int) $m['id'], $modulosActivosIds, true))
                @php
                    $seccionAbierta = collect($m['patrones'] ?? [])->contains(fn ($patron) => request()->routeIs($patron));
                @endphp
                <div class="nav-group">
                    <div class="nav-section"><span class="nav-label">{{ $m['nombre'] }}</span></div>
                    <a href="#menuMod{{ $m['id'] }}" class="nav-link {{ $seccionAbierta ? 'active' : '' }}" data-bs-toggle="collapse" role="button" aria-expanded="{{ $seccionAbierta ? 'true' : 'false' }}">
                        <i class="{{ $m['icono'] }}"></i> <span class="nav-label">{{ $m['nombre'] }}</span>
                        <span class="ms-auto nav-chevron"><i class="fas fa-chevron-down fa-xs"></i></span>
                    </a>
                    <div class="collapse {{ $seccionAbierta ? 'show' : '' }}" id="menuMod{{ $m['id'] }}">
                        @foreach($submodulosCatalogo as $s)
                            @if((int) $s['id_Modulos'] === (int) $m['id'] && in_array($s['clave'], $submodulosActivos, true))
                            <a href="{{ route($s['ruta']) }}" class="nav-link {{ request()->routeIs($s['activa'] ?? $s['ruta']) ? 'active' : '' }}">
                                <i class="{{ $s['icono'] }}"></i> <span class="nav-label">{{ $s['nombre'] }}</span>
                            </a>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif
            @endforeach
        </nav>
    </div>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="main-content">
        <div class="top-bar">
            <div class="d-flex align-items-center gap-2">
                <button class="btn-sidebar-toggle" id="btnSidebarToggle" title="Menú" aria-label="Abrir menú" aria-controls="appSidebar" aria-expanded="false">
                    <i class="fas fa-bars" aria-hidden="true"></i>
                </button>
                <button class="btn-sidebar-collapse" id="btnSidebarCollapse" title="Colapsar menú" aria-label="Colapsar menú" aria-controls="appSidebar" aria-expanded="true">
                    <i class="fas fa-angles-left" aria-hidden="true"></i>
                </button>
                <div>
                    <span class="text-muted" style="font-size:13px">Bienvenido,</span>
                    <strong style="font-size:14px">{{ session('NombreUsuario', auth()->user()->nombreCompleto()) }}</strong>
                </div>
                @if(\App\Support\ConexionActiva::esProductivo() && auth()->user()?->esAdministrador())
                    <span class="badge bg-danger" style="font-size:11px; letter-spacing:.3px;" title="La aplicación está operando sobre la base de datos productiva (192.168.11.20 - HELPDESK)">
                        <i class="fas fa-exclamation-triangle me-1"></i>BD PRODUCTIVO
                    </span>
                @endif
            </div>
            <div class="d-flex align-items-center gap-2">
                <button class="btn-theme-toggle" id="btnThemeToggle" title="Cambiar tema">
                    <i class="fas fa-moon" id="themeIcon"></i>
                </button>
                <div class="position-relative" id="notif-wrapper">
                    <span class="position-relative" id="notif-bell" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false" aria-controls="notif-panel" aria-label="Notificaciones" style="cursor:pointer; padding:6px 8px; border-radius:var(--radius-sm); transition: background 0.15s;" onmouseover="this.style.background='var(--primary-soft)'" onmouseout="this.style.background='transparent'">
                        <i class="fas fa-bell" style="color:var(--muted); font-size:15px"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" id="notif-count" style="font-size:9px; padding:3px 5px;">0</span>
                    </span>
                    <div id="notif-panel" class="d-none" role="region" aria-label="Notificaciones" style="position:absolute; top:calc(100% + 10px); right:0; width:350px; max-width:calc(100vw - 24px); max-height:420px; overflow-y:auto; background:var(--surface-strong); border:1px solid var(--border); border-radius:var(--radius-sm); box-shadow:0 8px 24px rgba(0,0,0,.15); z-index:1080;">
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 14px; border-bottom:1px solid var(--border); position:sticky; top:0; background:var(--surface-strong);">
                            <strong style="font-size:13px;">Notificaciones</strong>
                            <button type="button" id="notif-mark-all" style="background:none; border:none; color:var(--primary); font-size:12px; cursor:pointer; padding:0;">Marcar todas leídas</button>
                        </div>
                        <div id="notif-list" style="padding:6px;"></div>
                    </div>
                </div>
                <div style="width:1px; height:24px; background:var(--border); margin:0 4px;"></div>
                <div style="display:flex; align-items:center; gap:8px; padding:4px 12px 4px 4px; border-radius:var(--radius-sm); background:var(--surface-strong);">
                    <div style="width:30px; height:30px; border-radius:50%; background:var(--primary); color:#fff; display:grid; place-items:center; font-size:11px; font-weight:600;">
                        {{ substr(session('NombreUsuario', 'U'), 0, 2) }}
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" style="background:none; border:none; color:var(--muted); cursor:pointer; font-size:13px; font-weight:500; padding:0; display:flex; align-items:center; gap:4px;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--muted)'">
                            <i class="fas fa-sign-out-alt"></i> Salir
                        </button>
                    </form>
                </div>
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
            @if(!$tieneRolActivo)
                <div class="alert alert-warning" role="alert">
                    <i class="fas fa-user-slash me-2"></i>Su usuario no tiene un rol asignado. Contacte al administrador para habilitar sus módulos.
                </div>
            @endif
            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    (function() {
        const body = document.body;
        const btnTheme = document.getElementById('btnThemeToggle');
        const themeIcon = document.getElementById('themeIcon');

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
        const panel = document.getElementById('notif-panel');
        const list = document.getElementById('notif-list');
        const markAllBtn = document.getElementById('notif-mark-all');
        if (!bell || !panel) return;

        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const rObtener = '{{ route("api.notificaciones.obtener") }}';
        const rMarcar = '{{ route("api.notificaciones.marcarLeido") }}';
        const rMarcarTodas = '{{ route("api.notificaciones.marcarTodasLeido") }}';

        function escapeHtml(s) {
            return String(s ?? '').replace(/[&<>"']/g, c => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            }[c]));
        }

        function fmtFecha(f) {
            if (!f) return '';
            const d = new Date(f);
            if (isNaN(d)) return '';
            return d.toLocaleString('es-MX', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
        }

        function actualizarBadge(noLeidas) {
            if (noLeidas > 0) {
                countBadge.textContent = noLeidas > 99 ? '99+' : noLeidas;
                countBadge.classList.remove('d-none');
            } else {
                countBadge.classList.add('d-none');
            }
        }

        function renderLista(items) {
            if (!items || items.length === 0) {
                list.innerHTML = '<div style="padding:16px; text-align:center; color:var(--muted); font-size:13px;">No tienes notificaciones</div>';
                return;
            }
            list.innerHTML = '';
            items.forEach(n => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.style.cssText = 'display:block; width:100%; text-align:left; border:none; border-bottom:1px solid var(--border); padding:10px 12px; cursor:pointer; font-size:13px; color:inherit; background:' + (n.Leido ? 'transparent' : 'var(--primary-soft)') + ';';
                btn.innerHTML =
                    '<div style="display:flex; gap:8px; align-items:flex-start;">' +
                        '<i class="fas ' + (n.Leido ? 'fa-envelope-open' : 'fa-envelope') + '" style="color:var(--primary); margin-top:3px; font-size:12px;"></i>' +
                        '<div style="min-width:0;">' +
                            '<div style="white-space:pre-wrap; word-break:break-word;">' + escapeHtml(n.Mensaje) + '</div>' +
                            '<div style="color:var(--muted); font-size:11px; margin-top:3px;">' + escapeHtml(fmtFecha(n.FechaCreacion)) + '</div>' +
                        '</div>' +
                    '</div>';
                btn.addEventListener('click', () => abrirNotificacion(n));
                list.appendChild(btn);
            });
        }

        function fetchNotificaciones() {
            fetch(rObtener, { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    actualizarBadge(data.noLeidas || 0);
                    if (!panel.classList.contains('d-none')) {
                        renderLista(data.notificaciones || []);
                    }
                })
                .catch(() => {});
        }

        function abrirNotificacion(n) {
            const marcar = n.Leido
                ? Promise.resolve()
                : fetch(rMarcar, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ id_Notificacion: n.id_Notificacion })
                }).catch(() => {});

            marcar.then(() => {
                cerrarPanel();
                fetchNotificaciones();
            });
        }

        function abrirPanel() {
            panel.classList.remove('d-none');
            bell.setAttribute('aria-expanded', 'true');
            fetchNotificaciones();
        }

        function cerrarPanel() {
            panel.classList.add('d-none');
            bell.setAttribute('aria-expanded', 'false');
        }

        function alternarPanel() {
            panel.classList.contains('d-none') ? abrirPanel() : cerrarPanel();
        }

        bell.addEventListener('click', alternarPanel);
        bell.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                alternarPanel();
            }
        });

        markAllBtn?.addEventListener('click', function() {
            fetch(rMarcarTodas, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: '{}'
            })
            .then(r => r.json())
            .then(() => fetchNotificaciones())
            .catch(() => {});
        });

        document.addEventListener('click', function(e) {
            const wrapper = document.getElementById('notif-wrapper');
            if (!panel.classList.contains('d-none') && wrapper && !wrapper.contains(e.target)) {
                cerrarPanel();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !panel.classList.contains('d-none')) {
                cerrarPanel();
                bell.focus();
            }
        });

        fetchNotificaciones();
        setInterval(fetchNotificaciones, 30000);
    })();
    </script>
    <script src="{{ asset('js/table-controls.js') }}"></script>
    <script>
    (function() {
        const sidebar = document.getElementById('appSidebar');
        const btn = document.getElementById('btnSidebarToggle');
        const backdrop = document.getElementById('sidebarBackdrop');
        if (!sidebar || !btn) return;

        function close() {
            sidebar.classList.remove('open');
            if (backdrop) backdrop.classList.remove('show');
            btn.setAttribute('aria-expanded', 'false');
        }

        btn.addEventListener('click', function() {
            const open = sidebar.classList.toggle('open');
            if (backdrop) backdrop.classList.toggle('show', open);
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        if (backdrop) backdrop.addEventListener('click', close);

        sidebar.querySelectorAll('a.nav-link').forEach(function(a) {
            if (!a.hasAttribute('data-bs-toggle')) a.addEventListener('click', close);
        });
    })();
    </script>
    <script>
    (function() {
        const body = document.body;
        const sidebar = document.getElementById('appSidebar');
        const btn = document.getElementById('btnSidebarCollapse');
        if (!sidebar) return;

        const KEY = 'helpdesk-sidebar';
        const esEscritorio = () => window.matchMedia('(min-width: 992px)').matches;
        const colapsado = () => body.classList.contains('sidebar-collapsed');

        const grupos = Array.from(sidebar.querySelectorAll('.nav-group'))
            .map(g => ({
                grupo: g,
                gatillo: g.querySelector('a[data-bs-toggle="collapse"]'),
                menu: g.querySelector('.collapse')
            }))
            .filter(x => x.gatillo && x.menu);

        function ocultarFlyouts() {
            grupos.forEach(x => x.menu.classList.remove('show'));
        }

        function abrirFlyout(x) {
            x.menu.classList.add('show');
            const top = x.gatillo.getBoundingClientRect().top;
            const alto = x.menu.offsetHeight;
            const min = 8;
            const max = Math.max(min, window.innerHeight - alto - 8);
            x.menu.style.top = Math.min(Math.max(top, min), max) + 'px';
        }

        function aplicar(colapsar, guardar) {
            body.classList.toggle('sidebar-collapsed', colapsar);

            if (btn) {
                const icono = btn.querySelector('i');
                btn.title = colapsar ? 'Expandir menú' : 'Colapsar menú';
                btn.setAttribute('aria-label', btn.title);
                btn.setAttribute('aria-expanded', colapsar ? 'false' : 'true');
                if (icono) icono.className = colapsar ? 'fas fa-angles-right' : 'fas fa-angles-left';
            }

            if (colapsar) {
                ocultarFlyouts();
            } else {
                // Al expandir, reabre la sección activa y limpia posiciones.
                grupos.forEach(x => {
                    const activa = x.gatillo.classList.contains('active');
                    x.menu.classList.toggle('show', activa);
                    x.menu.style.top = '';
                    x.gatillo.setAttribute('aria-expanded', activa ? 'true' : 'false');
                });
            }

            if (guardar) {
                try { localStorage.setItem(KEY, colapsar ? 'collapsed' : 'expanded'); } catch (e) {}
            }
        }

        if (btn) {
            btn.addEventListener('click', () => aplicar(!colapsado(), true));
        }

        grupos.forEach(x => {
            x.grupo.addEventListener('mouseenter', () => {
                if (colapsado() && esEscritorio()) abrirFlyout(x);
            });
            x.grupo.addEventListener('mouseleave', () => {
                if (colapsado()) x.menu.classList.remove('show');
            });
            x.gatillo.addEventListener('click', e => {
                if (colapsado() && esEscritorio()) {
                    // En modo riel el clic no altera el acordeón: abre el flyout.
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    abrirFlyout(x);
                }
            });
        });

        window.addEventListener('resize', () => {
            if (!esEscritorio()) ocultarFlyouts();
        });

        // Sincroniza con el estado aplicado antes de pintar.
        aplicar(colapsado(), false);
    })();
    </script>
    @yield('scripts')
</body>
</html>
