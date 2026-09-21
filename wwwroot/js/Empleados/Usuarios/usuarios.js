(function () {
    var ROWS_PER_PAGE = 10;
    var currentPage = 1;
    var filteredRows = [];

    var searchInput = document.getElementById("searchInput");
    var tableBody = document.getElementById("tableBody");
    var rowCountEl = document.getElementById("rowCount");
    var paginationEl = document.getElementById("pagination");
    var allRows = tableBody ? Array.from(tableBody.querySelectorAll("tr")) : [];

    function normalize(t) { return (t || "").toLowerCase(); }

    function getRowText(row) {
        var cells = row.querySelectorAll("td");
        var parts = [];
        for (var i = 0; i < cells.length - 1; i++) {
            parts.push(normalize(cells[i].textContent));
        }
        return parts.join(" ");
    }

    function filterRows() {
        var q = normalize(searchInput ? searchInput.value : "");
        filteredRows = allRows.filter(function (row) {
            if (!q) return true;
            return getRowText(row).indexOf(q) !== -1;
        });
    }

    function renderTable() {
        if (!tableBody) return;
        tableBody.innerHTML = "";
        var colSpan = allRows.length > 0 ? allRows[0].querySelectorAll("td").length : 11;
        if (filteredRows.length === 0) {
            var tr = document.createElement("tr");
            var td = document.createElement("td");
            td.colSpan = colSpan;
            td.className = "empty-cell";
            td.textContent = "No hay resultados.";
            tr.appendChild(td);
            tableBody.appendChild(tr);
        } else {
            var start = (currentPage - 1) * ROWS_PER_PAGE;
            var end = Math.min(start + ROWS_PER_PAGE, filteredRows.length);
            for (var i = start; i < end; i++) {
                tableBody.appendChild(filteredRows[i]);
            }
        }
        renderInfo();
        renderPagination();
    }

    function renderInfo() {
        if (!rowCountEl) return;
        var total = filteredRows.length;
        if (total === 0) { rowCountEl.textContent = "Mostrando 0 registros"; return; }
        var start = (currentPage - 1) * ROWS_PER_PAGE + 1;
        var end = Math.min(currentPage * ROWS_PER_PAGE, total);
        rowCountEl.textContent = "Mostrando " + start + " a " + end + " de " + total + " registros";
    }

    function renderPagination() {
        if (!paginationEl) return;
        paginationEl.innerHTML = "";
        var total = filteredRows.length;
        var totalPages = Math.max(1, Math.ceil(total / ROWS_PER_PAGE));
        if (currentPage > totalPages) currentPage = totalPages;

        var prev = document.createElement("button");
        prev.type = "button"; prev.className = "page-btn";
        prev.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
        prev.disabled = currentPage <= 1;
        prev.addEventListener("click", function () { currentPage--; renderTable(); });
        paginationEl.appendChild(prev);

        var startP = Math.max(1, currentPage - 2);
        var endP = Math.min(totalPages, currentPage + 2);
        if (startP > 1) {
            var b = document.createElement("button");
            b.type = "button"; b.className = "page-btn"; b.textContent = "1";
            b.addEventListener("click", function () { currentPage = 1; renderTable(); });
            paginationEl.appendChild(b);
            if (startP > 2) {
                var d = document.createElement("span"); d.className = "page-dots"; d.textContent = "...";
                paginationEl.appendChild(d);
            }
        }
        for (var p = startP; p <= endP; p++) {
            var btn = document.createElement("button");
            btn.type = "button"; btn.className = "page-btn" + (p === currentPage ? " active" : "");
            btn.textContent = p; btn.setAttribute("data-page", p);
            btn.addEventListener("click", function () {
                currentPage = parseInt(this.getAttribute("data-page"), 10); renderTable();
            });
            paginationEl.appendChild(btn);
        }
        if (endP < totalPages) {
            if (endP < totalPages - 1) {
                var d2 = document.createElement("span"); d2.className = "page-dots"; d2.textContent = "...";
                paginationEl.appendChild(d2);
            }
            var bLast = document.createElement("button");
            bLast.type = "button"; bLast.className = "page-btn"; bLast.textContent = totalPages;
            bLast.addEventListener("click", function () { currentPage = totalPages; renderTable(); });
            paginationEl.appendChild(bLast);
        }

        var next = document.createElement("button");
        next.type = "button"; next.className = "page-btn";
        next.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
        next.disabled = currentPage >= totalPages;
        next.addEventListener("click", function () { currentPage++; renderTable(); });
        paginationEl.appendChild(next);
    }

    if (searchInput) {
        searchInput.addEventListener("input", function () {
            currentPage = 1; filterRows(); renderTable();
        });
    }

    filterRows();
    renderTable();

    // === EXISTING MODAL LOGIC ===
    var createModal = document.getElementById("createModal");
    var modal = document.getElementById("editModal");
    var accessModal = document.getElementById("accessModal");
    var btnOpenCreateModal = document.getElementById("btnOpenCreateModal");
    var btnCloseCreateModal = document.getElementById("btnCloseCreateModal");
    var btnCloseModal = document.getElementById("btnCloseModal");
    var btnCloseAccessModal = document.getElementById("btnCloseAccessModal");
    var createName = document.getElementById("Nombre");
    var fields = {
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
    var accessFields = {
        id: document.getElementById("accessIdUsuario"),
        employee: document.getElementById("accessEmployeeName"),
        user: document.getElementById("accessUsuario"),
        active: document.getElementById("accessActivo"),
        currentPasswordField: document.getElementById("currentPasswordField"),
        currentPassword: document.getElementById("accessCurrentPassword"),
        password: document.getElementById("accessPassword"),
        confirm: document.getElementById("accessConfirmPassword")
    };
    var restorePasswordForm = document.getElementById("restorePasswordForm");
    var restorePasswordId = document.getElementById("restorePasswordIdUsuario");
    var passwordToggles = document.querySelectorAll("[data-password-toggle]");

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

            var hasAccess = (button.getAttribute("data-access-has") || "") === "true";
            var isSelfAccess = (button.getAttribute("data-access-self") || "") === "true";
            var canEditActive = (button.getAttribute("data-access-can-edit-active") || "") === "true";
            var canRestorePassword = (button.getAttribute("data-access-can-restore") || "") === "true";
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
            var input = document.getElementById(button.getAttribute("data-password-toggle") || "");
            if (!input) {
                return;
            }

            var showPassword = input.getAttribute("type") === "password";
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
        var icon = button.querySelector("i");
        button.setAttribute("aria-label", isVisible ? "Ocultar contrasena" : "Mostrar contrasena");

        if (icon) {
            icon.classList.toggle("fa-eye", !isVisible);
            icon.classList.toggle("fa-eye-slash", isVisible);
        }
    }
})();
