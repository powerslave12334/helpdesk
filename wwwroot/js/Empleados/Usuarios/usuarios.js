document.addEventListener("DOMContentLoaded", function () {
    const createModal = document.getElementById("createModal");
    const modal = document.getElementById("editModal");
    const accessModal = document.getElementById("accessModal");
    const btnOpenCreateModal = document.getElementById("btnOpenCreateModal");
    const btnCloseCreateModal = document.getElementById("btnCloseCreateModal");
    const btnCloseModal = document.getElementById("btnCloseModal");
    const btnCloseAccessModal = document.getElementById("btnCloseAccessModal");
    const createName = document.getElementById("Nombre");
    const fields = {
        id: document.getElementById("editIdUsuario"),
        name: document.getElementById("editNombre"),
        app: document.getElementById("editApp"),
        apm: document.getElementById("editApm"),
        email: document.getElementById("editCorreo"),
        phone: document.getElementById("editTelefono"),
        depto: document.getElementById("editIdDepto"),
        area: document.getElementById("editIdArea"),
        office: document.getElementById("editIdOficinas"),
        type: document.getElementById("editIdTipoEmpleado"),
        tech: document.getElementById("editEsTecnico")
    };
    const accessFields = {
        id: document.getElementById("accessIdUsuario"),
        employee: document.getElementById("accessEmployeeName"),
        user: document.getElementById("accessUsuario"),
        active: document.getElementById("accessActivo"),
        currentPasswordField: document.getElementById("currentPasswordField"),
        currentPassword: document.getElementById("accessCurrentPassword"),
        password: document.getElementById("accessPassword"),
        confirm: document.getElementById("accessConfirmPassword")
    };
    const restorePasswordForm = document.getElementById("restorePasswordForm");
    const restorePasswordId = document.getElementById("restorePasswordIdUsuario");
    const passwordToggles = document.querySelectorAll("[data-password-toggle]");

    document.querySelectorAll("[data-edit-id]").forEach(function (button) {
        button.addEventListener("click", function () {
            if (!modal || !fields.id || !fields.name) {
                return;
            }

            fields.id.value = button.getAttribute("data-edit-id") || "";
            fields.name.value = button.getAttribute("data-edit-name") || "";
            fields.app.value = button.getAttribute("data-edit-app") || "";
            fields.apm.value = button.getAttribute("data-edit-apm") || "";
            fields.email.value = button.getAttribute("data-edit-email") || "";
            fields.phone.value = button.getAttribute("data-edit-phone") || "";
            fields.depto.value = button.getAttribute("data-edit-depto") || "";
            fields.area.value = button.getAttribute("data-edit-area") || "";
            fields.office.value = button.getAttribute("data-edit-office") || "";
            fields.type.value = button.getAttribute("data-edit-type") || "";
            if (fields.tech) {
                fields.tech.checked = (button.getAttribute("data-edit-tech") || "") === "true";
            }
            modal.hidden = false;
            fields.name.focus();
        });
    });

    document.querySelectorAll("[data-access-id]").forEach(function (button) {
        button.addEventListener("click", function () {
            if (!accessModal || !accessFields.id || !accessFields.user) {
                return;
            }

            const hasAccess = (button.getAttribute("data-access-has") || "") === "true";
            const isSelfAccess = (button.getAttribute("data-access-self") || "") === "true";
            const canEditActive = (button.getAttribute("data-access-can-edit-active") || "") === "true";
            const canRestorePassword = (button.getAttribute("data-access-can-restore") || "") === "true";
            accessFields.id.value = button.getAttribute("data-access-id") || "";
            if (restorePasswordId) {
                restorePasswordId.value = accessFields.id.value;
            }
            accessFields.employee.textContent = button.getAttribute("data-access-employee") || "";
            accessFields.user.value = button.getAttribute("data-access-login") || "";
            accessFields.active.checked = hasAccess
                ? (button.getAttribute("data-access-active") || "") === "true"
                : true;
            accessFields.currentPassword.value = "";
            accessFields.password.value = "";
            accessFields.confirm.value = "";
            resetPasswordVisibility();
            accessFields.user.readOnly = isSelfAccess;
            accessFields.active.disabled = !canEditActive;
            if (accessFields.currentPasswordField) {
                accessFields.currentPasswordField.classList.toggle("is-hidden", !hasAccess);
            }
            accessFields.currentPassword.required = false;
            accessFields.password.required = !hasAccess;
            accessFields.confirm.required = !hasAccess;
            if (restorePasswordForm) {
                restorePasswordForm.classList.toggle("is-hidden", !hasAccess || !canRestorePassword);
            }
            accessModal.hidden = false;
            (isSelfAccess ? accessFields.currentPassword : accessFields.user).focus();
        });
    });

    passwordToggles.forEach(function (button) {
        button.addEventListener("click", function () {
            const input = document.getElementById(button.getAttribute("data-password-toggle") || "");
            if (!input) {
                return;
            }

            const showPassword = input.getAttribute("type") === "password";
            input.setAttribute("type", showPassword ? "text" : "password");
            updatePasswordToggle(button, showPassword);
        });
    });

    document.querySelectorAll('input[name="Telefono"]').forEach(function (input) {
        input.addEventListener("input", function () {
            input.value = input.value.replace(/\D/g, "").slice(0, 10);
        });
    });

    if (btnOpenCreateModal) {
        btnOpenCreateModal.addEventListener("click", function () {
            if (createModal) {
                createModal.hidden = false;
                if (createName) {
                    createName.focus();
                }
            }
        });
    }

    if (btnCloseCreateModal) {
        btnCloseCreateModal.addEventListener("click", closeCreateModal);
    }

    if (btnCloseModal) {
        btnCloseModal.addEventListener("click", closeModal);
    }

    if (btnCloseAccessModal) {
        btnCloseAccessModal.addEventListener("click", closeAccessModal);
    }

    if (createModal) {
        createModal.addEventListener("click", function (event) {
            if (event.target === createModal) {
                closeCreateModal();
            }
        });
    }

    if (modal) {
        modal.addEventListener("click", function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    if (accessModal) {
        accessModal.addEventListener("click", function (event) {
            if (event.target === accessModal) {
                closeAccessModal();
            }
        });
    }

    if (restorePasswordForm) {
        restorePasswordForm.addEventListener("submit", function (event) {
            if (!confirm("Se generara una contraseña temporal y se enviara al correo del empleado. ¿Deseas continuar?")) {
                event.preventDefault();
            }
        });
    }

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            closeCreateModal();
            closeModal();
            closeAccessModal();
        }
    });

    function closeCreateModal() {
        if (createModal) {
            createModal.hidden = true;
        }
    }

    function closeModal() {
        if (modal) {
            modal.hidden = true;
        }
    }

    function closeAccessModal() {
        if (accessModal) {
            accessModal.hidden = true;
            resetPasswordVisibility();
        }
    }

    function resetPasswordVisibility() {
        [accessFields.currentPassword, accessFields.password, accessFields.confirm].forEach(function (input) {
            if (input) {
                input.setAttribute("type", "password");
            }
        });

        passwordToggles.forEach(function (button) {
            updatePasswordToggle(button, false);
        });
    }

    function updatePasswordToggle(button, isVisible) {
        const icon = button.querySelector("i");
        button.setAttribute("aria-label", isVisible ? "Ocultar contrasena" : "Mostrar contrasena");

        if (icon) {
            icon.classList.toggle("fa-eye", !isVisible);
            icon.classList.toggle("fa-eye-slash", isVisible);
        }
    }
});
