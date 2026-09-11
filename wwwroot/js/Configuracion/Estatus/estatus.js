document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("editModal");
    const btnCloseModal = document.getElementById("btnCloseModal");
    const editId = document.getElementById("editIdEstatus");
    const editModule = document.getElementById("editIdModulos");
    const editDescription = document.getElementById("editDescripcion");

    document.querySelectorAll("[data-edit-id]").forEach(function (button) {
        button.addEventListener("click", function () {
            if (!modal || !editId || !editModule || !editDescription) {
                return;
            }

            editId.value = button.getAttribute("data-edit-id") || "";
            editModule.value = button.getAttribute("data-edit-module") || "";
            editDescription.value = button.getAttribute("data-edit-description") || "";
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
