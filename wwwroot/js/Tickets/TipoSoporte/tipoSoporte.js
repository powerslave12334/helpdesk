document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("editModal");
    const btnCloseModal = document.getElementById("btnCloseModal");
    const editId = document.getElementById("editIdTipoSoporte");
    const editDescription = document.getElementById("editDescripcion");
    const editArea = document.getElementById("editIdArea");

    document.querySelectorAll("[data-edit-id]").forEach(function (button) {
        button.addEventListener("click", function () {
            if (!modal || !editId || !editDescription || !editArea) {
                return;
            }

            editId.value = button.getAttribute("data-edit-id") || "";
            editDescription.value = button.getAttribute("data-edit-description") || "";
            editArea.value = button.getAttribute("data-edit-area") || "";
            modal.hidden = false;
            editDescription.focus();
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
