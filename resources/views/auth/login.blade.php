<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HelpDesk - Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); min-height: 100vh; }
        .login-card { background: rgba(255,255,255,0.95); border-radius: 15px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .login-header { background: linear-gradient(135deg, #e94560, #0f3460); border-radius: 15px 15px 0 0; padding: 30px; color: white; text-align: center; }
        .login-header img { max-height: 60px; }
        .form-control { border-radius: 10px; padding: 12px 15px; border: 2px solid #e0e0e0; }
        .form-control:focus { border-color: #e94560; box-shadow: 0 0 0 0.2rem rgba(233,69,96,0.25); }
        .btn-login { background: linear-gradient(135deg, #e94560, #c23152); border: none; border-radius: 10px; padding: 12px; font-weight: 600; letter-spacing: 1px; }
        .btn-login:hover { background: linear-gradient(135deg, #c23152, #a02040); transform: translateY(-2px); box-shadow: 0 5px 20px rgba(233,69,96,0.4); }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="login-card">
                    <div class="login-header">
                        <h3><i class="fas fa-headset me-2"></i>HelpDesk</h3>
                        <p class="mb-0 mt-2" style="font-size: 0.9rem; opacity: 0.9;">Sistema de Soporte Técnico</p>
                    </div>
                    <div class="p-4">
                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                {{ $errors->first() }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login.post') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-user me-1"></i>Usuario
                                </label>
                                <input type="text" name="usuario" class="form-control"
                                       value="{{ old('usuario') }}" required autofocus
                                       placeholder="Ingrese su usuario">
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-lock me-1"></i>Contraseña
                                </label>
                                <input type="password" name="password" class="form-control"
                                       required placeholder="Ingrese su contraseña">
                            </div>

                            <button type="submit" class="btn btn-login btn-primary w-100 text-white">
                                <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                            </button>
                        </form>

                        <div class="text-center mt-3">
                            <a href="#" data-bs-toggle="modal" data-bs-target="#forgotModal" class="text-decoration-none" style="color: #e94560; font-size: 0.85rem;">
                                <i class="fas fa-key me-1"></i>¿Olvidaste tu contraseña?
                            </a>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <small class="text-white-50">HelpDesk &copy; {{ date('Y') }} - Alcomex</small>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <div class="modal fade" id="forgotModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #e94560, #0f3460); color: white;">
                    <h5 class="modal-title"><i class="fas fa-key me-2"></i>Recuperar Contraseña</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('login.recuperarPassword') }}">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted mb-3">Ingresa tu usuario o correo registrado. Se enviará una contraseña temporal.</p>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Usuario o correo</label>
                            <input type="text" name="usuarioRecuperacion" class="form-control" required maxlength="100" placeholder="usuario o correo@ejemplo.com">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i>Enviar Contraseña Temporal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
