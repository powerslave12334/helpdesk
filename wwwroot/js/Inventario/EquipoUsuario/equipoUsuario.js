document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("editModal");
    const btnCloseModal = document.getElementById("btnCloseModal");
    const fields = {
        id: document.getElementById("editIdEquipoUsuario"),
        equipment: document.getElementById("editIdEquipo"),
        user: document.getElementById("editIdUsuario"),
        area: document.getElementById("editIdArea"),
        assignment: document.getElementById("editFechaAsignacion"),
        end: document.getElementById("editFechaBaja"),
        observations: document.getElementById("editObservaciones")
    };

    document.querySelectorAll("[data-edit-id]").forEach(function (button) {
        button.addEventListener("click", function () {
            if (!modal || !fields.id || !fields.equipment) {
                return;
            }

            fields.id.value = button.getAttribute("data-edit-id") || "";
            fields.equipment.value = button.getAttribute("data-edit-equipment") || "";
            fields.user.value = button.getAttribute("data-edit-user") || "";
            fields.area.value = button.getAttribute("data-edit-area") || "";
            fields.assignment.value = button.getAttribute("data-edit-assignment") || "";
            fields.end.value = button.getAttribute("data-edit-end") || "";
            fields.observations.value = button.getAttribute("data-edit-observations") || "";
            modal.hidden = false;
            fields.equipment.focus();
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
