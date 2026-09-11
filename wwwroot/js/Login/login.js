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
