(function () {
    // --- Search & Pagination ---
    var tableBody = document.getElementById("tableBody");
    var searchInput = document.getElementById("searchInput");
    var paginationEl = document.getElementById("pagination");
    var rowCountEl = document.getElementById("rowCount");

    if (tableBody && searchInput && paginationEl) {
        var allRows = [];
        var rows = tableBody.getElementsByTagName("tr");
        for (var i = 0; i < rows.length; i++) {
            if (rows[i].cells.length > 1) {
                allRows.push(rows[i]);
            }
        }

        var rowsPerPage = 10;
        var currentPage = 1;
        var filteredRows = allRows.slice();

        function filterRows() {
            var term = searchInput.value.toLowerCase().trim();
            if (!term) {
                filteredRows = allRows.slice();
            } else {
                filteredRows = [];
                for (var j = 0; j < allRows.length; j++) {
                    if (allRows[j].textContent.toLowerCase().indexOf(term) !== -1) {
                        filteredRows.push(allRows[j]);
                    }
                }
            }
            currentPage = 1;
            renderTable();
        }

        function renderTable() {
            var total = filteredRows.length;
            var totalPages = Math.max(1, Math.ceil(total / rowsPerPage));
            if (currentPage > totalPages) currentPage = totalPages;
            var start = (currentPage - 1) * rowsPerPage;
            var end = start + rowsPerPage;

            for (var k = 0; k < allRows.length; k++) {
                allRows[k].style.display = "none";
            }
            for (var l = start; l < end && l < filteredRows.length; l++) {
                filteredRows[l].style.display = "";
            }

            var existing = document.getElementById("emptyRowDynamic");
            if (existing) existing.remove();

            if (total === 0) {
                var tr = document.createElement("tr");
                tr.id = "emptyRowDynamic";
                var td = document.createElement("td");
                td.colSpan = 11;
                td.className = "empty-cell";
                td.textContent = "No se encontraron resultados.";
                tr.appendChild(td);
                tableBody.appendChild(tr);
            }

            if (rowCountEl) {
                rowCountEl.textContent = total + (total === 1 ? " resultado" : " resultados");
            }
            renderPagination(totalPages);
        }

        function renderPagination(totalPages) {
            paginationEl.innerHTML = "";
            if (totalPages <= 1) return;

            var prev = document.createElement("button");
            prev.type = "button";
            prev.className = "page-btn";
            prev.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
            prev.disabled = currentPage === 1;
            prev.addEventListener("click", function () {
                if (currentPage > 1) { currentPage--; renderTable(); }
            });
            paginationEl.appendChild(prev);

            var maxVisible = 5;
            var sp = Math.max(1, currentPage - Math.floor(maxVisible / 2));
            var ep = Math.min(totalPages, sp + maxVisible - 1);
            if (ep - sp < maxVisible - 1) sp = Math.max(1, ep - maxVisible + 1);

            if (sp > 1) {
                paginationEl.appendChild(makePageBtn(1));
                if (sp > 2) {
                    var d = document.createElement("span");
                    d.className = "page-dots";
                    d.textContent = "...";
                    paginationEl.appendChild(d);
                }
            }
            for (var p = sp; p <= ep; p++) {
                paginationEl.appendChild(makePageBtn(p));
            }
            if (ep < totalPages) {
                if (ep < totalPages - 1) {
                    var d2 = document.createElement("span");
                    d2.className = "page-dots";
                    d2.textContent = "...";
                    paginationEl.appendChild(d2);
                }
                paginationEl.appendChild(makePageBtn(totalPages));
            }

            var next = document.createElement("button");
            next.type = "button";
            next.className = "page-btn";
            next.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
            next.disabled = currentPage === totalPages;
            next.addEventListener("click", function () {
                if (currentPage < totalPages) { currentPage++; renderTable(); }
            });
            paginationEl.appendChild(next);
        }

        function makePageBtn(page) {
            var btn = document.createElement("button");
            btn.type = "button";
            btn.className = "page-btn" + (page === currentPage ? " active" : "");
            btn.textContent = page;
            btn.addEventListener("click", function () {
                currentPage = page;
                renderTable();
            });
            return btn;
        }

        var timer;
        searchInput.addEventListener("input", function () {
            clearTimeout(timer);
            timer = setTimeout(filterRows, 250);
        });

        renderTable();
    }

    // --- Modals ---
    var createModal = document.getElementById("createModal");
    var modal = document.getElementById("editModal");
    var viewModal = document.getElementById("viewModal");
    var btnOpenCreateModal = document.getElementById("btnOpenCreateModal");
    var btnCloseCreateModal = document.getElementById("btnCloseCreateModal");
    var btnCloseModal = document.getElementById("btnCloseModal");
    var btnCloseViewModal = document.getElementById("btnCloseViewModal");
    var fields = {
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
    var createType = document.getElementById("IdTipoEquipo");
    var createBrand = document.getElementById("Marca");
    var viewFields = {
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
            if (!viewModal || !viewFields.equipment) return;
            var idEquipo = button.getAttribute("data-view-id") || "";
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
            if (!modal || !fields.id || !fields.brand) return;
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
                if (createBrand) createBrand.focus();
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
        createModal.addEventListener("click", function (e) {
            if (e.target === createModal) closeCreateModal();
        });
    }
    if (modal) {
        modal.addEventListener("click", function (e) {
            if (e.target === modal) closeModal();
        });
    }
    if (viewModal) {
        viewModal.addEventListener("click", function (e) {
            if (e.target === viewModal) closeViewModal();
        });
    }

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            closeCreateModal();
            closeModal();
            closeViewModal();
        }
    });

    function closeCreateModal() { if (createModal) createModal.hidden = true; }
    function closeModal() { if (modal) modal.hidden = true; }
    function closeViewModal() { if (viewModal) viewModal.hidden = true; }

    function getDetailValues(idEquipo) {
        var source = document.getElementById("equipment-details-" + idEquipo);
        if (!source) return {};
        try {
            var details = JSON.parse(source.textContent || "[]");
            return details.reduce(function (values, detail) {
                values[String(detail.idEquipoCampo)] = detail.valor || "";
                return values;
            }, {});
        } catch { return {}; }
    }

    function getJsonData(id) {
        var source = document.getElementById(id);
        if (!source) return [];
        try { return JSON.parse(source.textContent || "[]"); } catch { return []; }
    }

    function renderDetails(idEquipo) {
        if (!viewFields.details) return;
        var details = getJsonData("equipment-view-details-" + idEquipo);
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
        if (!viewFields.relations) return;
        var relations = getJsonData("equipment-view-relations-" + idEquipo);
        viewFields.relations.innerHTML = "";
        if (relations.length === 0) {
            viewFields.relations.appendChild(createEmpty("Sin relaciones activas."));
            return;
        }
        relations.forEach(function (relation) {
            var label = relation.direccion === "Padre" ? relation.tipoRelacion : "Relacionado como " + relation.tipoRelacion;
            var dateText = relation.fechaAsignacion ? " desde " + relation.fechaAsignacion : "";
            viewFields.relations.appendChild(createItem(label, (relation.equipoRelacionado || "") + dateText));
        });
    }

    function createItem(label, value) {
        var item = document.createElement("div");
        item.className = "equipment-view-item";
        var labelNode = document.createElement("span");
        labelNode.className = "equipment-view-label";
        labelNode.textContent = label || "Dato";
        var valueNode = document.createElement("span");
        valueNode.className = "equipment-view-value";
        valueNode.textContent = value || "-";
        item.appendChild(labelNode);
        item.appendChild(valueNode);
        return item;
    }

    function createEmpty(text) {
        var item = document.createElement("div");
        item.className = "equipment-view-empty";
        item.textContent = text;
        return item;
    }

    function setText(element, value) {
        if (element) element.textContent = value || "-";
    }

    function updateDetails(formName, typeId, values) {
        document.querySelectorAll('[data-detail-form="' + formName + '"].equipment-detail-group').forEach(function (group) {
            var isVisible = group.getAttribute("data-detail-type") === String(typeId || "");
            var valueInput = group.querySelector('input[type="text"]');
            var hiddenInput = group.querySelector('input[type="hidden"]');
            group.hidden = !isVisible;
            if (!valueInput || !hiddenInput) return;
            valueInput.required = isVisible && group.getAttribute("data-required") === "true";
            if (!isVisible) { valueInput.value = ""; return; }
            if (values) valueInput.value = values[hiddenInput.value] || "";
        });
    }
})();
