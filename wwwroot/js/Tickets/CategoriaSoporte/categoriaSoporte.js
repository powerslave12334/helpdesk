
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("editModal");
    const btnCloseModal = document.getElementById("btnCloseModal");
    const editId = document.getElementById("editIdCategoriaSoporte");
    const editType = document.getElementById("editIdTipoSoporte");
    const editDescription = document.getElementById("editDescripcion");
    const editEquipmentType = document.getElementById("editTipoEquipoTicket");

    document.querySelectorAll("[data-edit-id]").forEach(function (button) {
        button.addEventListener("click", function () {
            if (!modal || !editId || !editType || !editDescription || !editEquipmentType) {
                return;
            }

            editId.value = button.getAttribute("data-edit-id") || "";
            editType.value = button.getAttribute("data-edit-type") || "";
            editDescription.value = button.getAttribute("data-edit-description") || "";
            editEquipmentType.value = button.getAttribute("data-edit-equipment-type") || "";
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
