
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("editModal");
    const btnCloseModal = document.getElementById("btnCloseModal");
    const editId = document.getElementById("editIdConfiguracionSistema");
    const editKey = document.getElementById("editClave");
    const editValue = document.getElementById("editValor");
    const editDescription = document.getElementById("editDescripcion");

    document.querySelectorAll("[data-edit-id]").forEach(function (button) {
        button.addEventListener("click", function () {
            if (!modal || !editId || !editKey || !editValue || !editDescription) {
                return;
            }

            editId.value = button.getAttribute("data-edit-id") || "";
            editKey.value = button.getAttribute("data-edit-key") || "";
            editValue.value = button.getAttribute("data-edit-value") || "";
            editDescription.value = button.getAttribute("data-edit-description") || "";
            modal.hidden = false;
            editKey.focus();
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
