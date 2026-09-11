document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("editModal");
    const btnCloseModal = document.getElementById("btnCloseModal");
    const editId = document.getElementById("editIdSubModulos");
    const editModulo = document.getElementById("editIdModulo");
    const editNombre = document.getElementById("editNombre");
    const editOrden = document.getElementById("editOrden");
    const editController = document.getElementById("editController");
    const editAction = document.getElementById("editAction");

    document.querySelectorAll("[data-edit-id]").forEach(function (button) {
        button.addEventListener("click", function () {
            if (!modal || !editId || !editModulo || !editNombre || !editOrden || !editController || !editAction) {
                return;
            }

            editId.value = button.getAttribute("data-edit-id") || "";
            editModulo.value = button.getAttribute("data-edit-module") || "";
            editNombre.value = button.getAttribute("data-edit-name") || "";
            editOrden.value = button.getAttribute("data-edit-order") || "0";
            editController.value = button.getAttribute("data-edit-controller") || "";
            editAction.value = button.getAttribute("data-edit-action") || "";
            modal.hidden = false;
            editNombre.focus();
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
