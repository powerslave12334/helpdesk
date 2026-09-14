(function () {
    var ROWS_PER_PAGE = 10;
    var tabState = {};
    var activeTabId = null;

    var searchInput = document.getElementById("searchInput");
    var rowCountEl = document.getElementById("rowCount");
    var paginationEl = document.getElementById("pagination");
    var tabButtons = document.querySelectorAll("[data-ticket-tab]");
    var tabPanels = document.querySelectorAll("[data-ticket-panel]");

    function normalize(t) { return (t || "").toLowerCase(); }

    function getRowText(row) {
        var cells = row.querySelectorAll("td");
        var parts = [];
        for (var i = 0; i < cells.length - 1; i++) {
            parts.push(normalize(cells[i].textContent));
        }
        return parts.join(" ");
    }

    function initTab(tabId) {
        var tbody = document.getElementById("tableBody" + tabId.charAt(0).toUpperCase() + tabId.slice(1));
        var allRows = tbody ? Array.from(tbody.querySelectorAll("tr")) : [];
        tabState[tabId] = {
            tbody: tbody,
            allRows: allRows,
            filteredRows: allRows.slice(),
            currentPage: 1
        };
    }

    function filterTabRows(tabId) {
        var state = tabState[tabId];
        if (!state) return;
        var q = normalize(searchInput ? searchInput.value : "");
        state.filteredRows = state.allRows.filter(function (row) {
            if (!q) return true;
            return getRowText(row).indexOf(q) !== -1;
        });
        state.currentPage = 1;
    }

    function renderTabTable(tabId) {
        var state = tabState[tabId];
        if (!state || !state.tbody) return;
        state.tbody.innerHTML = "";
        var colSpan = state.allRows.length > 0 ? state.allRows[0].querySelectorAll("td").length : 11;
        if (state.filteredRows.length === 0) {
            var tr = document.createElement("tr");
            var td = document.createElement("td");
            td.colSpan = colSpan;
            td.className = "empty-cell";
            td.textContent = "No hay resultados.";
            tr.appendChild(td);
            state.tbody.appendChild(tr);
        } else {
            var start = (state.currentPage - 1) * ROWS_PER_PAGE;
            var end = Math.min(start + ROWS_PER_PAGE, state.filteredRows.length);
            for (var i = start; i < end; i++) {
                state.tbody.appendChild(state.filteredRows[i]);
            }
        }
        renderInfo(state.filteredRows.length, state.currentPage);
        renderPagination(state);
    }

    function renderInfo(total, currentPage) {
        if (!rowCountEl) return;
        if (total === 0) { rowCountEl.textContent = "Mostrando 0 registros"; return; }
        var start = (currentPage - 1) * ROWS_PER_PAGE + 1;
        var end = Math.min(currentPage * ROWS_PER_PAGE, total);
        rowCountEl.textContent = "Mostrando " + start + " a " + end + " de " + total + " registros";
    }

    function renderPagination(state) {
        if (!paginationEl) return;
        paginationEl.innerHTML = "";
        var total = state.filteredRows.length;
        var totalPages = Math.max(1, Math.ceil(total / ROWS_PER_PAGE));
        if (state.currentPage > totalPages) state.currentPage = totalPages;
        var prev = document.createElement("button");
        prev.type = "button"; prev.className = "page-btn";
        prev.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
        prev.disabled = state.currentPage <= 1;
        prev.addEventListener("click", function () { state.currentPage--; renderTabTable(activeTabId); });
        paginationEl.appendChild(prev);
        var startP = Math.max(1, state.currentPage - 2);
        var endP = Math.min(totalPages, state.currentPage + 2);
        if (startP > 1) {
            var b = document.createElement("button");
            b.type = "button"; b.className = "page-btn"; b.textContent = "1";
            b.addEventListener("click", function () { state.currentPage = 1; renderTabTable(activeTabId); });
            paginationEl.appendChild(b);
            if (startP > 2) {
                var d = document.createElement("span"); d.className = "page-dots"; d.textContent = "...";
                paginationEl.appendChild(d);
            }
        }
        for (var p = startP; p <= endP; p++) {
            var btn = document.createElement("button");
            btn.type = "button"; btn.className = "page-btn" + (p === state.currentPage ? " active" : "");
            btn.textContent = p; btn.setAttribute("data-page", p);
            btn.addEventListener("click", function () {
                state.currentPage = parseInt(this.getAttribute("data-page"), 10); renderTabTable(activeTabId);
            });
            paginationEl.appendChild(btn);
        }
        if (endP < totalPages) {
            if (endP < totalPages - 1) {
                var d2 = document.createElement("span"); d2.className = "page-dots"; d2.textContent = "...";
                paginationEl.appendChild(d2);
            }
            var bLast = document.createElement("button");
            bLast.type = "button"; bLast.className = "page-btn"; bLast.textContent = totalPages;
            bLast.addEventListener("click", function () { state.currentPage = totalPages; renderTabTable(activeTabId); });
            paginationEl.appendChild(bLast);
        }
        var next = document.createElement("button");
        next.type = "button"; next.className = "page-btn";
        next.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
        next.disabled = state.currentPage >= totalPages;
        next.addEventListener("click", function () { state.currentPage++; renderTabTable(activeTabId); });
        paginationEl.appendChild(next);
    }

    function switchToTab(tabId) {
        activeTabId = tabId;
        tabButtons.forEach(function (item) {
            var isActive = item.getAttribute("data-ticket-tab") === tabId;
            item.classList.toggle("active", isActive);
            item.setAttribute("aria-selected", isActive ? "true" : "false");
        });
        tabPanels.forEach(function (panel) {
            panel.classList.toggle("active", panel.getAttribute("data-ticket-panel") === tabId);
        });
        if (tabState[tabId]) {
            filterTabRows(tabId);
            renderTabTable(tabId);
        }
    }

    tabButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            var tabId = button.getAttribute("data-ticket-tab") || "";
            switchToTab(tabId);
        });
    });

    if (searchInput) {
        searchInput.addEventListener("input", function () {
            if (activeTabId) {
                filterTabRows(activeTabId);
                renderTabTable(activeTabId);
            }
        });
    }

    // Initialize all tabs
    tabButtons.forEach(function (button) {
        var tabId = button.getAttribute("data-ticket-tab") || "";
        initTab(tabId);
    });

    // Activate first tab
    if (tabButtons.length > 0) {
        var firstTabId = tabButtons[0].getAttribute("data-ticket-tab") || "";
        switchToTab(firstTabId);
    }

    // === EXISTING MODAL LOGIC ===
    var createTicketModal = document.getElementById("createTicketModal");
    var btnOpenCreateTicketModal = document.getElementById("btnOpenCreateTicketModal");
    var btnCloseCreateTicketModal = document.getElementById("btnCloseCreateTicketModal");
    var developmentTicketModal = document.getElementById("developmentTicketModal");
    var btnOpenDevelopmentTicketModal = document.getElementById("btnOpenDevelopmentTicketModal");
    var btnCloseDevelopmentTicketModal = document.getElementById("btnCloseDevelopmentTicketModal");
    var btnCancelDevelopmentTicketModal = document.getElementById("btnCancelDevelopmentTicketModal");
    var btnSaveDevelopmentTicket = document.getElementById("btnSaveDevelopmentTicket");
    var developmentTicketForm = document.getElementById("developmentTicketForm");
    var developmentSystem = document.getElementById("developmentIdTipoSistema");
    var developmentModule = document.getElementById("developmentIdModulo");
    var developmentSubModule = document.getElementById("developmentIdSubModulo");
    var createType = document.getElementById("IdTipoSoporte");
    var createEquipmentField = document.getElementById("createEquipmentField");
    var createEquipment = document.getElementById("IdEquipo");
    var viewModal = document.getElementById("viewModal");
    var btnCloseViewModal = document.getElementById("btnCloseViewModal");
    var fields = {
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
            var idTipoSoporte = button.getAttribute("data-view-ticket-type") || "2";
            var idTicketOriginal = button.getAttribute("data-view-original-id") || button.getAttribute("data-view-id") || "";
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
            var isClosed = button.getAttribute("data-is-closed") === "true";
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
                var technicianId = button.getAttribute("data-view-technician-id") || "";
                fields.managementTechnician.value = technicianId;
                var selectedTechnician = fields.managementTechnician.options[fields.managementTechnician.selectedIndex];
                if (!selectedTechnician || selectedTechnician.disabled || fields.managementTechnician.value !== technicianId) {
                    fields.managementTechnician.value = "";
                }
            }
            renderTimeline(idTicketOriginal, idTipoSoporte);
            renderEvidences(idTicketOriginal, idTipoSoporte);
            viewModal.hidden = false;
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
                var firstField = createTicketModal.querySelector("select, input, textarea");
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
                var firstField = developmentTicketModal.querySelector("select, input, textarea");
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

        var idSistemas = developmentSystem.value || "";
        resetSelect(developmentModule, idSistemas ? "Cargando..." : "Seleccione un tipo de sistema");
        resetSelect(developmentSubModule, "Seleccione un modulo");

        if (!idSistemas) {
            return;
        }

        try {
            var response = await fetch("/tickets/ObtenerModulosSistema?idSistemas=" + encodeURIComponent(idSistemas), {
                headers: { "Accept": "application/json" }
            });

            if (!response.ok) {
                resetSelect(developmentModule, "No fue posible cargar modulos");
                return;
            }

            var modules = await response.json();
            fillSelect(developmentModule, modules, "idModulo", "nombre", "Seleccione");
        } catch (e) {
            resetSelect(developmentModule, "No fue posible cargar modulos");
        }
    }

    async function loadDevelopmentSubModules() {
        if (!developmentModule || !developmentSubModule) {
            return;
        }

        var idModulo = developmentModule.value || "";
        resetSelect(developmentSubModule, idModulo ? "Cargando..." : "Seleccione un modulo");

        if (!idModulo) {
            return;
        }

        try {
            var response = await fetch("/tickets/ObtenerSubModulosSistema?idModulo=" + encodeURIComponent(idModulo), {
                headers: { "Accept": "application/json" }
            });

            if (!response.ok) {
                resetSelect(developmentSubModule, "No fue posible cargar submodulos");
                return;
            }

            var subModules = await response.json();
            fillSelect(developmentSubModule, subModules, "idSubModulo", "nombre", "Seleccione");
        } catch (e) {
            resetSelect(developmentSubModule, "No fue posible cargar submodulos");
        }
    }

    function resetSelect(select, text) {
        if (!select) {
            return;
        }

        select.innerHTML = "";
        var option = document.createElement("option");
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
        var emptyOption = document.createElement("option");
        emptyOption.value = "";
        emptyOption.textContent = placeholder;
        select.appendChild(emptyOption);

        items.forEach(function (item) {
            var option = document.createElement("option");
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
        var source = document.getElementById("ticket-comments-" + (idTipoSoporte || "2") + "-" + idTicket);

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
        var evidences = getJsonData("ticket-evidences-" + (idTipoSoporte || "2") + "-" + idTicket);

        if (evidences.length === 0) {
            var empty = document.createElement("div");
            empty.className = "ticket-evidences-empty";
            empty.textContent = "Sin evidencias registradas.";
            fields.evidences.appendChild(empty);
            return;
        }

        evidences.forEach(function (evidence) {
            var item = document.createElement("article");
            item.className = "ticket-evidence-item";

            var icon = document.createElement("i");
            icon.className = "fa-solid fa-image";

            var content = document.createElement("div");
            content.className = "ticket-evidence-content";

            var link = document.createElement("a");
            link.href = evidence.url || "#";
            link.target = "_blank";
            link.rel = "noopener";
            link.textContent = evidence.nombreArchivo || "Evidencia";

            var meta = document.createElement("span");
            meta.textContent = evidence.usuarioAlta + " - " + evidence.fechaAlta + " - " + formatBytes(evidence.tamanioBytes);

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
            return Math.ceil(bytes / 1024) + " KB";
        }

        return (bytes / 1024 / 1024).toFixed(2) + " MB";
    }

    function filterTechniciansByTicketArea(areaName) {
        if (!fields.managementTechnician) {
            return;
        }

        var normalizedAreaName = normalizeText(areaName);

        Array.from(fields.managementTechnician.options).forEach(function (option) {
            if (!option.value) {
                option.hidden = false;
                option.disabled = false;
                return;
            }

            var optionAreaName = normalizeText(option.getAttribute("data-area-name") || "");
            var visible = normalizedAreaName !== "" && optionAreaName === normalizedAreaName;
            option.hidden = !visible;
            option.disabled = !visible;
        });
    }

    function normalizeText(value) {
        return (value || "").trim().toLowerCase();
    }

    function getJsonData(id) {
        var source = document.getElementById(id);
        if (!source) {
            return [];
        }

        try {
            return JSON.parse(source.textContent || "[]");
        } catch (e) {
            return [];
        }
    }
})();
