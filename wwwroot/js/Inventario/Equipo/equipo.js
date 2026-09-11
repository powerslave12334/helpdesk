document.addEventListener("DOMContentLoaded", function () {
    const createModal = document.getElementById("createModal");
    const modal = document.getElementById("editModal");
    const viewModal = document.getElementById("viewModal");
    const btnOpenCreateModal = document.getElementById("btnOpenCreateModal");
    const btnCloseCreateModal = document.getElementById("btnCloseCreateModal");
    const btnCloseModal = document.getElementById("btnCloseModal");
    const btnCloseViewModal = document.getElementById("btnCloseViewModal");
    const fields = {
        id: document.getElementById("editIdEquipo"),
        brand: document.getElementById("editMarca"),
        model: document.getElementById("editModelo"),
        type: document.getElementById("editIdTipoEquipo"),
        status: document.getElementById("editIdEstatus"),
        date: document.getElementById("editFechaAdquisicion"),
        serial: document.getElementById("editNumeroSerie"),
        name: document.getElementById("editNombreEquipo"),
        location: document.getElementById("editUbicacion"),
        office: document.getElementById("editIdOficinas"),
        period: document.getElementById("editIdPeriodoMantenimiento"),
        shared: document.getElementById("editEsCompartido"),
        observations: document.getElementById("editObservaciones")
    };
    const createType = document.getElementById("IdTipoEquipo");
    const createBrand = document.getElementById("Marca");
    const viewFields = {
        equipment: document.getElementById("viewEquipment"),
        name: document.getElementById("viewName"),
        type: document.getElementById("viewType"),
        status: document.getElementById("viewStatus"),
        serial: document.getElementById("viewSerial"),
        shared: document.getElementById("viewShared"),
        office: document.getElementById("viewOffice"),
        location: document.getElementById("viewLocation"),
        date: document.getElementById("viewDate"),
        observations: document.getElementById("viewObservations"),
        details: document.getElementById("viewDetails"),
        relations: document.getElementById("viewRelations")
    };

    document.querySelectorAll("[data-view-id]").forEach(function (button) {
        button.addEventListener("click", function () {
            if (!viewModal || !viewFields.equipment) {
                return;
            }

            const idEquipo = button.getAttribute("data-view-id") || "";
            setText(viewFields.equipment, button.getAttribute("data-view-equipment"));
            setText(viewFields.name, button.getAttribute("data-view-name"));
            setText(viewFields.type, button.getAttribute("data-view-type"));
            setText(viewFields.status, button.getAttribute("data-view-status"));
            setText(viewFields.serial, button.getAttribute("data-view-serial"));
            setText(viewFields.shared, button.getAttribute("data-view-shared"));
            setText(viewFields.office, button.getAttribute("data-view-office"));
            setText(viewFields.location, button.getAttribute("data-view-location"));
            setText(viewFields.date, button.getAttribute("data-view-date"));
            setText(viewFields.observations, button.getAttribute("data-view-observations"));
            renderDetails(idEquipo);
            renderRelations(idEquipo);
            viewModal.hidden = false;
        });
    });

    document.querySelectorAll("[data-edit-id]").forEach(function (button) {
        button.addEventListener("click", function () {
            if (!modal || !fields.id || !fields.brand) {
                return;
            }

            fields.id.value = button.getAttribute("data-edit-id") || "";
            fields.brand.value = button.getAttribute("data-edit-brand") || "";
            fields.model.value = button.getAttribute("data-edit-model") || "";
            fields.type.value = button.getAttribute("data-edit-type") || "";
            fields.status.value = button.getAttribute("data-edit-status") || "";
            fields.date.value = button.getAttribute("data-edit-date") || "";
            fields.serial.value = button.getAttribute("data-edit-serial") || "";
            fields.name.value = button.getAttribute("data-edit-name") || "";
            fields.location.value = button.getAttribute("data-edit-location") || "";
            fields.office.value = button.getAttribute("data-edit-office") || "";
            fields.period.value = button.getAttribute("data-edit-period") || "0";
            if (fields.shared) {
                fields.shared.checked = button.getAttribute("data-edit-shared") === "true";
            }
            fields.observations.value = button.getAttribute("data-edit-observations") || "";
            updateDetails("edit", fields.type.value, getDetailValues(fields.id.value));
            modal.hidden = false;
            fields.brand.focus();
        });
    });

    if (createType) {
        createType.addEventListener("change", function () {
            updateDetails("create", createType.value, null);
        });

        updateDetails("create", createType.value, null);
    }

    if (fields.type) {
        fields.type.addEventListener("change", function () {
            updateDetails("edit", fields.type.value, null);
        });
    }

    if (btnOpenCreateModal) {
        btnOpenCreateModal.addEventListener("click", function () {
            if (createModal) {
                createModal.hidden = false;
                if (createBrand) {
                    createBrand.focus();
                }
            }
        });
    }

    if (btnCloseCreateModal) {
        btnCloseCreateModal.addEventListener("click", closeCreateModal);
    }

    if (btnCloseModal) {
        btnCloseModal.addEventListener("click", closeModal);
    }

    if (btnCloseViewModal) {
        btnCloseViewModal.addEventListener("click", closeViewModal);
    }

    if (createModal) {
        createModal.addEventListener("click", function (event) {
            if (event.target === createModal) {
                closeCreateModal();
            }
        });
    }

    if (modal) {
        modal.addEventListener("click", function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    if (viewModal) {
        viewModal.addEventListener("click", function (event) {
            if (event.target === viewModal) {
                closeViewModal();
            }
        });
    }

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            closeCreateModal();
            closeModal();
            closeViewModal();
        }
    });

    function closeCreateModal() {
        if (createModal) {
            createModal.hidden = true;
        }
    }

    function closeModal() {
        if (modal) {
            modal.hidden = true;
        }
    }

    function closeViewModal() {
        if (viewModal) {
            viewModal.hidden = true;
        }
    }

    function getDetailValues(idEquipo) {
        const source = document.getElementById("equipment-details-" + idEquipo);
        if (!source) {
            return {};
        }

        try {
            const details = JSON.parse(source.textContent || "[]");
            return details.reduce(function (values, detail) {
                values[String(detail.idEquipoCampo)] = detail.valor || "";
                return values;
            }, {});
        } catch {
            return {};
        }
    }

    function getJsonData(id) {
        const source = document.getElementById(id);
        if (!source) {
            return [];
        }

        try {
            return JSON.parse(source.textContent || "[]");
        } catch {
            return [];
        }
    }

    function renderDetails(idEquipo) {
        if (!viewFields.details) {
            return;
        }

        const details = getJsonData("equipment-view-details-" + idEquipo);
        viewFields.details.innerHTML = "";

        if (details.length === 0) {
            viewFields.details.appendChild(createEmpty("Sin detalles registrados."));
            return;
        }

        details.forEach(function (detail) {
            viewFields.details.appendChild(createItem(detail.campo, detail.valor));
        });
    }

    function renderRelations(idEquipo) {
        if (!viewFields.relations) {
            return;
        }

        const relations = getJsonData("equipment-view-relations-" + idEquipo);
        viewFields.relations.innerHTML = "";

        if (relations.length === 0) {
            viewFields.relations.appendChild(createEmpty("Sin relaciones activas."));
            return;
        }

        relations.forEach(function (relation) {
            const label = relation.direccion === "Padre"
                ? relation.tipoRelacion
                : "Relacionado como " + relation.tipoRelacion;
            const dateText = relation.fechaAsignacion ? " desde " + relation.fechaAsignacion : "";
            viewFields.relations.appendChild(createItem(label, (relation.equipoRelacionado || "") + dateText));
        });
    }

    function createItem(label, value) {
        const item = document.createElement("div");
        item.className = "equipment-view-item";

        const labelNode = document.createElement("span");
        labelNode.className = "equipment-view-label";
        labelNode.textContent = label || "Dato";

        const valueNode = document.createElement("span");
        valueNode.className = "equipment-view-value";
        valueNode.textContent = value || "-";

        item.appendChild(labelNode);
        item.appendChild(valueNode);

        return item;
    }

    function createEmpty(text) {
        const item = document.createElement("div");
        item.className = "equipment-view-empty";
        item.textContent = text;

        return item;
    }

    function setText(element, value) {
        if (element) {
            element.textContent = value || "-";
        }
    }

    function updateDetails(formName, typeId, values) {
        document.querySelectorAll('[data-detail-form="' + formName + '"].equipment-detail-group').forEach(function (group) {
            const isVisible = group.getAttribute("data-detail-type") === String(typeId || "");
            const valueInput = group.querySelector('input[type="text"]');
            const hiddenInput = group.querySelector('input[type="hidden"]');

            group.hidden = !isVisible;

            if (!valueInput || !hiddenInput) {
                return;
            }

            valueInput.required = isVisible && group.getAttribute("data-required") === "true";

            if (!isVisible) {
                valueInput.value = "";
                return;
            }

            if (values) {
                valueInput.value = values[hiddenInput.value] || "";
            }
        });
    }
});
