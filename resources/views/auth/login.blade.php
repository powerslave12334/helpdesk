<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login | HELPDESK</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #eef3f9;
            color: #1f2937;
        }

        .login-page {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
        }

        .login-hero {
            position: relative;
            display: flex;
            align-items: center;
            padding: 48px 64px;
            background: linear-gradient(135deg, rgba(10, 60, 130, 0.94), rgba(26, 115, 232, 0.86)), url('https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat;
            color: #ffffff;
        }

        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(3, 25, 52, 0.12), rgba(3, 25, 52, 0.28));
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 620px;
        }

        .hero-tag {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 16px;
            border: 1px solid rgba(255, 255, 255, 0.28);
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.16em;
        }

        .hero-content h1 {
            margin: 0 0 18px;
            font-size: 46px;
            line-height: 1.1;
        }

        .hero-content p {
            margin: 0;
            max-width: 520px;
            font-size: 17px;
            line-height: 1.7;
            color: rgba(255, 255, 255, 0.92);
        }

        .hero-points {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 28px;
        }

        .hero-point {
            padding: 10px 14px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(4px);
            font-size: 14px;
        }

        .login-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px;
            background: #f8fbff;
        }

        .login-card {
            width: 100%;
            max-width: 430px;
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.10);
            padding: 36px;
        }

        .login-header h2 {
            margin: 0 0 10px;
            font-size: 30px;
            color: #0f172a;
        }

        .login-header p {
            margin: 0 0 24px;
            color: #64748b;
            line-height: 1.5;
        }

        .alert-error {
            margin-bottom: 18px;
            padding: 14px 16px;
            border-radius: 12px;
            background: #fdecec;
            color: #b42318;
            font-size: 14px;
            border: 1px solid #f7c2c0;
        }

        .alert-success {
            margin-bottom: 18px;
            padding: 14px 16px;
            border-radius: 12px;
            background: #ecfdf3;
            color: #027a48;
            font-size: 14px;
            border: 1px solid #abefc6;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 700;
            color: #334155;
        }

        .form-control {
            width: 100%;
            height: 48px;
            border: 1px solid #dbe3ee;
            border-radius: 14px;
            padding: 0 14px;
            font-size: 15px;
            outline: none;
            transition: border-color .2s ease, box-shadow .2s ease;
            background: #ffffff;
        }

        .form-control:focus {
            border-color: #1a73e8;
            box-shadow: 0 0 0 4px rgba(26, 115, 232, 0.12);
        }

        .password-wrap {
            position: relative;
        }

        .password-wrap .form-control {
            padding-right: 52px;
        }

        .password-wrap .form-control::-ms-reveal,
        .password-wrap .form-control::-ms-clear {
            display: none;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 34px;
            height: 34px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 17px;
            line-height: 1;
        }

        .toggle-password.is-visible::after {
            content: "";
            position: absolute;
            left: 8px;
            top: 16px;
            width: 18px;
            height: 2px;
            border-radius: 999px;
            background: #334155;
            transform: rotate(-38deg);
            transform-origin: center;
            pointer-events: none;
        }

        .toggle-password:focus-visible {
            outline: none;
        }

        .login-links {
            margin: 4px 0 22px;
            text-align: right;
        }

        .login-links a {
            color: #1a73e8;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .link-button {
            border: 0;
            padding: 0;
            background: transparent;
            color: #1a73e8;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .link-button:hover {
            text-decoration: underline;
        }

        .link-button:focus-visible {
            outline: none;
        }

        .btn-login {
            width: 100%;
            height: 50px;
            border: none;
            border-radius: 14px;
            background: linear-gradient(135deg, #0b57d0, #1a73e8);
            color: #ffffff;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: .04em;
            cursor: pointer;
            transition: transform .15s ease, box-shadow .15s ease;
            box-shadow: 0 14px 30px rgba(26, 115, 232, 0.28);
        }

        .btn-login:hover {
            transform: translateY(-1px);
        }

        .field-validation {
            display: block;
            margin-top: 6px;
            color: #b42318;
            font-size: 13px;
        }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 20;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(15, 23, 42, 0.48);
        }

        .modal-backdrop[hidden] {
            display: none;
        }

        .modal-card {
            width: min(430px, 100%);
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
            padding: 26px;
        }

        .modal-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            margin: 0 0 8px;
            font-size: 22px;
            color: #0f172a;
        }

        .modal-header p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
            line-height: 1.5;
        }

        .modal-close {
            width: 34px;
            height: 34px;
            border: 0;
            border-radius: 10px;
            background: #eef3f9;
            color: #334155;
            cursor: pointer;
            font-size: 24px;
            line-height: 1;
        }

        .modal-close:hover {
            background: #dbe3ee;
        }

        .modal-close:focus-visible {
            outline: none;
        }

        @media (max-width: 991px) {
            .login-page {
                grid-template-columns: 1fr;
            }

            .login-hero {
                min-height: 320px;
                padding: 36px 28px;
            }

            .hero-content h1 {
                font-size: 34px;
            }
        }

        @media (max-width: 768px) {
            .login-hero {
                display: none;
            }

            .login-panel {
                min-height: 100vh;
                padding: 20px;
            }

            .login-card {
                max-width: 100%;
                padding: 28px 22px;
                border-radius: 20px;
            }
        }
    </style>
</head>
<body data-user-id="{{ session('IdUsuario', 0) }}">
    <div class="login-page">
        <section class="login-hero">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <span class="hero-tag">HELPDESK</span>
                <h1>Soporte TI centralizado para atender incidencias de forma rápida y ordenada.</h1>
                <p>
                    Administra solicitudes, da seguimiento a tickets y mejora la atención interna con una plataforma
                    pensada para el equipo de tecnología.
                </p>
                <div class="hero-points">
                    <div class="hero-point">Gestión de incidencias</div>
                    <div class="hero-point">Seguimiento de usuarios</div>
                    <div class="hero-point">Control de atención TI</div>
                </div>
            </div>
        </section>

        <section class="login-panel">
            <div class="login-card">
                <div class="login-header">
                    <h2>Iniciar sesión</h2>
                    <p>Ingresa tus credenciales para acceder al sistema.</p>
                </div>

                @if ($errors->any())
                    <div class="alert-error">{{ $errors->first() }}</div>
                @endif

                @if (session('success'))
                    <div class="alert-success">{{ session('success') }}</div>
                @endif

                <form action="{{ route('login.post') }}" method="post" class="login-form">
                    @csrf

                    <div class="form-group">
                        <label for="Usuario">Usuario</label>
                        <input id="Usuario" name="usuario" value="{{ old('usuario') }}" class="form-control" autocomplete="username" />
                    </div>

                    <div class="form-group">
                        <label for="txtPassword">Password</label>
                        <div class="password-wrap">
                            <input id="txtPassword" name="password" type="password" class="form-control" autocomplete="current-password" />
                            <button type="button" class="toggle-password" id="btnTogglePassword" aria-label="Mostrar contraseña">👁</button>
                        </div>
                    </div>

                    <div class="login-links">
                        <button type="button" class="link-button" id="btnOpenForgotPassword">¿Olvidó su contraseña?</button>
                    </div>

                    <button type="submit" class="btn-login">INGRESAR</button>
                </form>
            </div>
        </section>
    </div>

    <div class="modal-backdrop" id="forgotPasswordModal" hidden>
        <div class="modal-card forgot-card" role="dialog" aria-modal="true" aria-labelledby="forgotPasswordTitle">
            <div class="modal-header">
                <div>
                    <h3 id="forgotPasswordTitle">Recuperar contraseña</h3>
                    <p>Captura tu usuario o correo registrado.</p>
                </div>
                <button type="button" class="modal-close" id="btnCloseForgotPassword" aria-label="Cerrar">×</button>
            </div>

            <form action="{{ route('login.recuperarPassword') }}" method="post" class="forgot-form">
                @csrf

                <div class="form-group">
                    <label for="usuarioRecuperacion">Usuario o correo</label>
                    <input id="usuarioRecuperacion" name="usuarioRecuperacion" class="form-control" maxlength="100" autocomplete="username" required />
                </div>

                <button type="submit" class="btn-login">ENVIAR CONTRASEÑA TEMPORAL</button>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const passwordInput = document.getElementById('txtPassword');
        const toggleButton = document.getElementById('btnTogglePassword');
        const forgotPasswordModal = document.getElementById('forgotPasswordModal');
        const btnOpenForgotPassword = document.getElementById('btnOpenForgotPassword');
        const btnCloseForgotPassword = document.getElementById('btnCloseForgotPassword');
        const recoveryInput = document.getElementById('usuarioRecuperacion');

        if (passwordInput && toggleButton) {
            passwordInput.setAttribute('type', 'password');
            toggleButton.classList.remove('is-visible');
            toggleButton.setAttribute('aria-label', 'Mostrar contraseña');

            toggleButton.addEventListener('click', function () {
                const isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                toggleButton.setAttribute('aria-label', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
                toggleButton.classList.toggle('is-visible', isPassword);
            });
        }

        if (btnOpenForgotPassword && forgotPasswordModal) {
            btnOpenForgotPassword.addEventListener('click', function () {
                forgotPasswordModal.hidden = false;

                if (recoveryInput) {
                    recoveryInput.value = '';
                    recoveryInput.focus();
                }
            });
        }

        if (btnCloseForgotPassword) {
            btnCloseForgotPassword.addEventListener('click', closeForgotPasswordModal);
        }

        if (forgotPasswordModal) {
            forgotPasswordModal.addEventListener('click', function (event) {
                if (event.target === forgotPasswordModal) {
                    closeForgotPasswordModal();
                }
            });
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeForgotPasswordModal();
            }
        });

        function closeForgotPasswordModal() {
            if (forgotPasswordModal) {
                forgotPasswordModal.hidden = true;
            }
        }
    });
    </script>
</body>
</html>
