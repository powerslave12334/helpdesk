document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("editModal");
    const btnCloseModal = document.getElementById("btnCloseModal");
    const editId = document.getElementById("editIdTipoEquipoCampo");
    const editType = document.getElementById("editIdTipoEquipo");
    const editField = document.getElementById("editIdEquipoCampo");
    const editRequired = document.getElementById("editRequerido");

    document.querySelectorAll("[data-edit-id]").forEach(function (button) {
        button.addEventListener("click", function () {
            if (!modal || !editId || !editType || !editField || !editRequired) {
                return;
            }

            editId.value = button.getAttribute("data-edit-id") || "";
            editType.value = button.getAttribute("data-edit-type") || "";
            editField.value = button.getAttribute("data-edit-field") || "";
            editRequired.checked = (button.getAttribute("data-edit-required") || "") === "true";
            modal.hidden = false;
            editType.focus();
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
