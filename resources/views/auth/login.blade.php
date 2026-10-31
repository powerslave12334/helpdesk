<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login | HELPDESK</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
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
