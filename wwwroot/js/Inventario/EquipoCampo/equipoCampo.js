document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("editModal");
    const btnCloseModal = document.getElementById("btnCloseModal");
    const editId = document.getElementById("editIdEquipoCampo");
    const editName = document.getElementById("editNombre");
    const editDescription = document.getElementById("editDescripcion");

    document.querySelectorAll("[data-edit-id]").forEach(function (button) {
        button.addEventListener("click", function () {
            if (!modal || !editId || !editName || !editDescription) {
                return;
            }

            editId.value = button.getAttribute("data-edit-id") || "";
            editName.value = button.getAttribute("data-edit-name") || "";
            editDescription.value = button.getAttribute("data-edit-description") || "";
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

    function closeModal() {
        if (modal) {
            modal.hidden = true;
        }
    }
});
