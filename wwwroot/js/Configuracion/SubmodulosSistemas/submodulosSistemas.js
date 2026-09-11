document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("editModal");
    const btnCloseModal = document.getElementById("btnCloseModal");
    const editId = document.getElementById("editIdSubmodulosSistemas");
    const editName = document.getElementById("editNombre");
    const editSystem = document.getElementById("editIdSistemas");
    const editModule = document.getElementById("editIdModulosSistemas");

    document.querySelectorAll("[data-modules-target]").forEach(function (select) {
        select.addEventListener("change", function () {
            loadModules(select, "");
        });
    });

    document.querySelectorAll("[data-edit-id]").forEach(function (button) {
        button.addEventListener("click", async function () {
            if (!modal || !editId || !editName || !editSystem || !editModule) {
                return;
            }

            editId.value = button.getAttribute("data-edit-id") || "";
            editName.value = button.getAttribute("data-edit-name") || "";
            editSystem.value = button.getAttribute("data-edit-system") || "";
            await loadModules(editSystem, button.getAttribute("data-edit-module") || "");
            modal.hidden = false;
            editName.focus();
        });
    });

    if (btnCloseModal) {
        btnCloseModal.addEventListener("click", closeModal);
    }

    if (modal) {
        modal.addEventListener("click", function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            closeModal();
        }
    });

    async function loadModules(systemSelect, selectedValue) {
        const targetId = systemSelect.getAttribute("data-modules-target") || "";
        const moduleSelect = document.getElementById(targetId);
        const idSistemas = systemSelect.value || "";

        if (!moduleSelect) {
            return;
        }

        resetModules(moduleSelect, idSistemas ? "Cargando..." : "Seleccione un sistema");

        if (!idSistemas) {
            return;
        }

        try {
            const response = await fetch("/submodulosSistemas/ObtenerModulosPorSistema?idSistemas=" + encodeURIComponent(idSistemas), {
                headers: { "Accept": "application/json" }
            });

            if (!response.ok) {
                resetModules(moduleSelect, "No fue posible cargar modulos");
                return;
            }

            const modules = await response.json();
            moduleSelect.innerHTML = '<option value="">Seleccione</option>';

            modules.forEach(function (item) {
                const option = document.createElement("option");
                option.value = item.idModulosSistemas;
                option.textContent = item.nombre;
                moduleSelect.appendChild(option);
            });

            moduleSelect.disabled = modules.length === 0;
            if (selectedValue) {
                moduleSelect.value = selectedValue;
            }
        } catch {
            resetModules(moduleSelect, "No fue posible cargar modulos");
        }
    }

    function resetModules(moduleSelect, text) {
        moduleSelect.innerHTML = "";
        const option = document.createElement("option");
        option.value = "";
        option.textContent = text;
        moduleSelect.appendChild(option);
        moduleSelect.disabled = true;
    }

    function closeModal() {
        if (modal) {
            modal.hidden = true;
        }
    }
});
