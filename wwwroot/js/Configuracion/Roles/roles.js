document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("editModal");
    const btnCloseModal = document.getElementById("btnCloseModal");
    const editId = document.getElementById("editIdRol");
    const editNombre = document.getElementById("editNombre");
    const editDescripcion = document.getElementById("editDescripcion");

    document.querySelectorAll("[data-edit-id]").forEach(function (button) {
        button.addEventListener("click", function () {
            if (!modal || !editId || !editNombre || !editDescripcion) {
                return;
            }

            editId.value = button.getAttribute("data-edit-id") || "";
            editNombre.value = button.getAttribute("data-edit-name") || "";
            editDescripcion.value = button.getAttribute("data-edit-description") || "";
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
