document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("editModal");
    const btnCloseModal = document.getElementById("btnCloseModal");
    const fields = {
        id: document.getElementById("editIdEquipoRelacion"),
        parent: document.getElementById("editIdEquipoPadre"),
        child: document.getElementById("editIdEquipoHijo"),
        type: document.getElementById("editIdTipoRelacionEquipo"),
        assignment: document.getElementById("editFechaAsignacion"),
        end: document.getElementById("editFechaBaja"),
        observations: document.getElementById("editObservaciones")
    };

    document.querySelectorAll("[data-edit-id]").forEach(function (button) {
        button.addEventListener("click", function () {
            if (!modal || !fields.id || !fields.parent) {
                return;
            }

            fields.id.value = button.getAttribute("data-edit-id") || "";
            fields.parent.value = button.getAttribute("data-edit-parent") || "";
            fields.child.value = button.getAttribute("data-edit-child") || "";
            fields.type.value = button.getAttribute("data-edit-type") || "";
            fields.assignment.value = button.getAttribute("data-edit-assignment") || "";
            fields.end.value = button.getAttribute("data-edit-end") || "";
            fields.observations.value = button.getAttribute("data-edit-observations") || "";
            modal.hidden = false;
            fields.parent.focus();
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

    function closeModal() {
        if (modal) {
            modal.hidden = true;
        }
    }
});
