document.addEventListener("DOMContentLoaded", function () {
    const createTicketModal = document.getElementById("createTicketModal");
    const btnOpenCreateTicketModal = document.getElementById("btnOpenCreateTicketModal");
    const btnCloseCreateTicketModal = document.getElementById("btnCloseCreateTicketModal");
    const developmentTicketModal = document.getElementById("developmentTicketModal");
    const btnOpenDevelopmentTicketModal = document.getElementById("btnOpenDevelopmentTicketModal");
    const btnCloseDevelopmentTicketModal = document.getElementById("btnCloseDevelopmentTicketModal");
    const btnCancelDevelopmentTicketModal = document.getElementById("btnCancelDevelopmentTicketModal");
    const btnSaveDevelopmentTicket = document.getElementById("btnSaveDevelopmentTicket");
    const developmentTicketForm = document.getElementById("developmentTicketForm");
    const developmentSystem = document.getElementById("developmentIdTipoSistema");
    const developmentModule = document.getElementById("developmentIdModulo");
    const developmentSubModule = document.getElementById("developmentIdSubModulo");
    const createType = document.getElementById("IdTipoSoporte");
    const createEquipmentField = document.getElementById("createEquipmentField");
    const createEquipment = document.getElementById("IdEquipo");
    const viewModal = document.getElementById("viewModal");
    const btnCloseViewModal = document.getElementById("btnCloseViewModal");
    const tabButtons = document.querySelectorAll("[data-ticket-tab]");
    const tabPanels = document.querySelectorAll("[data-ticket-panel]");
    const fields = {
        id: document.getElementById("viewId"),
        subject: document.getElementById("viewSubject"),
        type: document.getElementById("viewType"),
        area: document.getElementById("viewArea"),
        status: document.getElementById("viewStatus"),
        equipment: document.getElementById("viewEquipment"),
        technician: document.getElementById("viewTechnician"),
        image: document.getElementById("viewImage"),
        date: document.getElementById("viewDate"),
        problem: document.getElementById("viewProblem"),
        timeline: document.getElementById("viewTimeline"),
        evidences: document.getElementById("viewEvidences"),
        evidenceForm: document.getElementById("evidenceTicketForm"),
        evidenceIdTicket: document.getElementById("evidenceIdTicket"),
        evidenceIdTipoSoporte: document.getElementById("evidenceIdTipoSoporte"),
        evidenceFile: document.getElementById("modalEvidenciaTicket"),
        closeSection: document.getElementById("closeTicketSection"),
        closeIdTicket: document.getElementById("closeIdTicket"),
        commentIdTicket: document.getElementById("commentIdTicket"),
        commentIdTipoSoporte: document.getElementById("commentIdTipoSoporte"),
        commentSection: document.getElementById("commentTicketSection"),
        comment: document.getElementById("comentario"),
        managementSection: document.getElementById("managementTicketSection"),
        managementIdTicket: document.getElementById("managementIdTicket"),
        managementTypeText: document.getElementById("managementTipoSoporteText"),
        managementStatus: document.getElementById("managementIdEstatus"),
        managementTechnician: document.getElementById("managementIdTecnico")
    };

    document.querySelectorAll("[data-view-id]").forEach(function (button) {
        button.addEventListener("click", function () {
            if (!viewModal || !fields.id) {
                return;
            }

            setText(fields.id, button.getAttribute("data-view-id"));
            const idTipoSoporte = button.getAttribute("data-view-ticket-type") || "2";
            const idTicketOriginal = button.getAttribute("data-view-original-id") || button.getAttribute("data-view-id") || "";
            setText(fields.subject, button.getAttribute("data-view-subject"));
            setText(fields.type, button.getAttribute("data-view-type"));
            setText(fields.area, button.getAttribute("data-view-area"));
            setText(fields.status, button.getAttribute("data-view-status"));
            setText(fields.equipment, button.getAttribute("data-view-equipment"));
            setText(fields.technician, button.getAttribute("data-view-technician"));
            setText(fields.image, button.getAttribute("data-view-image"));
            setText(fields.date, button.getAttribute("data-view-date"));
            setText(fields.problem, button.getAttribute("data-view-problem"));
            if (fields.commentIdTicket) {
                fields.commentIdTicket.value = idTicketOriginal;
            }
            if (fields.commentIdTipoSoporte) {
                fields.commentIdTipoSoporte.value = idTipoSoporte;
            }
            if (fields.evidenceIdTicket) {
                fields.evidenceIdTicket.value = idTicketOriginal;
            }
            if (fields.evidenceIdTipoSoporte) {
                fields.evidenceIdTipoSoporte.value = idTipoSoporte;
            }
            if (fields.closeIdTicket) {
                fields.closeIdTicket.value = button.getAttribute("data-view-id") || "";
            }
            const isClosed = button.getAttribute("data-is-closed") === "true";
            if (fields.closeSection) {
                toggleHidden(fields.closeSection, isClosed || button.getAttribute("data-can-close") !== "true");
            }
            if (fields.evidenceForm) {
                toggleHidden(fields.evidenceForm, isClosed);
            }
            if (fields.commentSection) {
                toggleHidden(fields.commentSection, isClosed);
            }
            if (fields.managementSection) {
                toggleHidden(fields.managementSection, isClosed);
            }
            if (fields.evidenceFile) {
                fields.evidenceFile.value = "";
            }
            if (fields.comment) {
                fields.comment.value = "";
            }
            if (fields.managementIdTicket) {
                fields.managementIdTicket.value = button.getAttribute("data-view-id") || "";
            }
            if (fields.managementTypeText) {
                fields.managementTypeText.textContent = button.getAttribute("data-view-type") || "-";
            }
            filterTechniciansByTicketArea(button.getAttribute("data-view-area") || "");
            if (fields.managementStatus) {
                fields.managementStatus.value = button.getAttribute("data-view-status-id") || "";
            }
            if (fields.managementTechnician) {
                const technicianId = button.getAttribute("data-view-technician-id") || "";
                fields.managementTechnician.value = technicianId;
                const selectedTechnician = fields.managementTechnician.options[fields.managementTechnician.selectedIndex];
                if (!selectedTechnician || selectedTechnician.disabled || fields.managementTechnician.value !== technicianId) {
                    fields.managementTechnician.value = "";
                }
            }
            renderTimeline(idTicketOriginal, idTipoSoporte);
            renderEvidences(idTicketOriginal, idTipoSoporte);
            viewModal.hidden = false;
        });
    });

    tabButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            const tabId = button.getAttribute("data-ticket-tab") || "";

            tabButtons.forEach(function (item) {
                const isActive = item === button;
                item.classList.toggle("active", isActive);
                item.setAttribute("aria-selected", isActive ? "true" : "false");
            });

            tabPanels.forEach(function (panel) {
                panel.classList.toggle("active", panel.getAttribute("data-ticket-panel") === tabId);
            });
        });
    });

    if (createType) {
        createType.addEventListener("change", function () {
            toggleCreateEquipment();
        });
    }

    if (btnOpenCreateTicketModal) {
        btnOpenCreateTicketModal.addEventListener("click", function () {
            if (createTicketModal) {
                toggleCreateEquipment();
                createTicketModal.hidden = false;
                const firstField = createTicketModal.querySelector("select, input, textarea");
                if (firstField) {
                    firstField.focus();
                }
            }
        });
    }

    if (btnCloseCreateTicketModal) {
        btnCloseCreateTicketModal.addEventListener("click", closeCreateTicketModal);
    }

    if (btnOpenDevelopmentTicketModal) {
        btnOpenDevelopmentTicketModal.addEventListener("click", function () {
            if (developmentTicketModal) {
                resetSelect(developmentModule, "Seleccione un tipo de sistema");
                resetSelect(developmentSubModule, "Seleccione un modulo");
                developmentTicketModal.hidden = false;
                const firstField = developmentTicketModal.querySelector("select, input, textarea");
                if (firstField) {
                    firstField.focus();
                }
            }
        });
    }

    if (btnCloseDevelopmentTicketModal) {
        btnCloseDevelopmentTicketModal.addEventListener("click", closeDevelopmentTicketModal);
    }

    if (btnCancelDevelopmentTicketModal) {
        btnCancelDevelopmentTicketModal.addEventListener("click", closeDevelopmentTicketModal);
    }

    if (developmentSystem) {
        developmentSystem.addEventListener("change", function () {
            loadDevelopmentModules();
        });
    }

    if (developmentModule) {
        developmentModule.addEventListener("change", function () {
            loadDevelopmentSubModules();
        });
    }

    if (btnSaveDevelopmentTicket) {
        btnSaveDevelopmentTicket.addEventListener("click", function () {
            if (developmentTicketForm) {
                if (developmentTicketForm.reportValidity()) {
                    developmentTicketForm.submit();
                }
            }
        });
    }

    if (btnCloseViewModal) {
        btnCloseViewModal.addEventListener("click", closeViewModal);
    }

    if (createTicketModal) {
        createTicketModal.addEventListener("click", function (event) {
            if (event.target === createTicketModal) {
                closeCreateTicketModal();
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

    if (developmentTicketModal) {
        developmentTicketModal.addEventListener("click", function (event) {
            if (event.target === developmentTicketModal) {
                closeDevelopmentTicketModal();
            }
        });
    }

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            closeCreateTicketModal();
            closeDevelopmentTicketModal();
            closeViewModal();
        }
    });

    function closeCreateTicketModal() {
        if (createTicketModal) {
            createTicketModal.hidden = true;
        }
    }

    function closeDevelopmentTicketModal() {
        if (developmentTicketModal) {
            developmentTicketModal.hidden = true;
        }
    }

    async function loadDevelopmentModules() {
        if (!developmentSystem || !developmentModule || !developmentSubModule) {
            return;
        }

        const idSistemas = developmentSystem.value || "";
        resetSelect(developmentModule, idSistemas ? "Cargando..." : "Seleccione un tipo de sistema");
        resetSelect(developmentSubModule, "Seleccione un modulo");

        if (!idSistemas) {
            return;
        }

        try {
            const response = await fetch("/tickets/ObtenerModulosSistema?idSistemas=" + encodeURIComponent(idSistemas), {
                headers: { "Accept": "application/json" }
            });

            if (!response.ok) {
                resetSelect(developmentModule, "No fue posible cargar modulos");
                return;
            }

            const modules = await response.json();
            fillSelect(developmentModule, modules, "idModulo", "nombre", "Seleccione");
        } catch {
            resetSelect(developmentModule, "No fue posible cargar modulos");
        }
    }

    async function loadDevelopmentSubModules() {
        if (!developmentModule || !developmentSubModule) {
            return;
        }

        const idModulo = developmentModule.value || "";
        resetSelect(developmentSubModule, idModulo ? "Cargando..." : "Seleccione un modulo");

        if (!idModulo) {
            return;
        }

        try {
            const response = await fetch("/tickets/ObtenerSubModulosSistema?idModulo=" + encodeURIComponent(idModulo), {
                headers: { "Accept": "application/json" }
            });

            if (!response.ok) {
                resetSelect(developmentSubModule, "No fue posible cargar submodulos");
                return;
            }

            const subModules = await response.json();
            fillSelect(developmentSubModule, subModules, "idSubModulo", "nombre", "Seleccione");
        } catch {
            resetSelect(developmentSubModule, "No fue posible cargar submodulos");
        }
    }

    function resetSelect(select, text) {
        if (!select) {
            return;
        }

        select.innerHTML = "";
        const option = document.createElement("option");
        option.value = "";
        option.textContent = text;
        select.appendChild(option);
        select.disabled = true;
    }

    function fillSelect(select, items, valueField, textField, placeholder) {
        if (!select) {
            return;
        }

        select.innerHTML = "";
        const emptyOption = document.createElement("option");
        emptyOption.value = "";
        emptyOption.textContent = placeholder;
        select.appendChild(emptyOption);

        items.forEach(function (item) {
            const option = document.createElement("option");
            option.value = item[valueField];
            option.textContent = item[textField];
            select.appendChild(option);
        });

        select.disabled = items.length === 0;
    }

    function toggleCreateEquipment() {
        if (!createEquipmentField) {
            return;
        }

        createEquipmentField.hidden = false;
        createEquipmentField.classList.remove("is-hidden");

        if (createEquipment) {
            Array.from(createEquipment.options).forEach(function (option) {
                if (!option.value) {
                    option.hidden = false;
                    option.disabled = false;
                    option.textContent = "Sin equipo";
                    return;
                }

                option.hidden = false;
                option.disabled = false;
            });
        }
    }

    function closeViewModal() {
        if (viewModal) {
            viewModal.hidden = true;
        }
    }

    function setText(element, value) {
        if (element) {
            element.textContent = value || "-";
        }
    }

    function toggleHidden(element, isHidden) {
        element.hidden = isHidden;
        element.classList.toggle("is-hidden", isHidden);
    }

    function renderTimeline(idTicket, idTipoSoporte) {
        if (!fields.timeline) {
            return;
        }

        fields.timeline.innerHTML = "";
        const source = document.getElementById("ticket-comments-" + (idTipoSoporte || "2") + "-" + idTicket);

        if (!source) {
            fields.timeline.innerHTML = '<div class="ticket-timeline-empty">Sin comentarios registrados.</div>';
            return;
        }

        fields.timeline.innerHTML = source.innerHTML;
    }

    function renderEvidences(idTicket, idTipoSoporte) {
        if (!fields.evidences) {
            return;
        }

        fields.evidences.innerHTML = "";
        const evidences = getJsonData("ticket-evidences-" + (idTipoSoporte || "2") + "-" + idTicket);

        if (evidences.length === 0) {
            const empty = document.createElement("div");
            empty.className = "ticket-evidences-empty";
            empty.textContent = "Sin evidencias registradas.";
            fields.evidences.appendChild(empty);
            return;
        }

        evidences.forEach(function (evidence) {
            const item = document.createElement("article");
            item.className = "ticket-evidence-item";

            const icon = document.createElement("i");
            icon.className = "fa-solid fa-image";

            const content = document.createElement("div");
            content.className = "ticket-evidence-content";

            const link = document.createElement("a");
            link.href = evidence.url || "#";
            link.target = "_blank";
            link.rel = "noopener";
            link.textContent = evidence.nombreArchivo || "Evidencia";

            const meta = document.createElement("span");
            meta.textContent = `${evidence.usuarioAlta || "Usuario"} - ${evidence.fechaAlta || ""} - ${formatBytes(evidence.tamanioBytes)}`;

            content.appendChild(link);
            content.appendChild(meta);
            item.appendChild(icon);
            item.appendChild(content);
            fields.evidences.appendChild(item);
        });
    }

    function formatBytes(bytes) {
        if (!bytes || bytes <= 0) {
            return "0 KB";
        }

        if (bytes < 1024 * 1024) {
            return `${Math.ceil(bytes / 1024)} KB`;
        }

        return `${(bytes / 1024 / 1024).toFixed(2)} MB`;
    }

    function filterTechniciansByTicketArea(areaName) {
        if (!fields.managementTechnician) {
            return;
        }

        const normalizedAreaName = normalizeText(areaName);

        Array.from(fields.managementTechnician.options).forEach(function (option) {
            if (!option.value) {
                option.hidden = false;
                option.disabled = false;
                return;
            }

            const optionAreaName = normalizeText(option.getAttribute("data-area-name") || "");
            const visible = normalizedAreaName !== "" && optionAreaName === normalizedAreaName;
            option.hidden = !visible;
            option.disabled = !visible;
        });
    }

    function normalizeText(value) {
        return (value || "").trim().toLowerCase();
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

    function cssEscape(value) {
        if (window.CSS && typeof window.CSS.escape === "function") {
            return window.CSS.escape(value || "");
        }

        return (value || "").replace(/["\\]/g, "\\$&");
    }
});
