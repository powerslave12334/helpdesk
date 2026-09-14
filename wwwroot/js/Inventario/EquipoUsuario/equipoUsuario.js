(function () {
    var tableBody = document.getElementById("tableBody");
    var searchInput = document.getElementById("searchInput");
    var paginationEl = document.getElementById("pagination");
    var rowCountEl = document.getElementById("rowCount");
    var selectAll = document.getElementById("selectAll");
    var btnBulk = document.getElementById("btnDesvincularSeleccionados");

    if (!tableBody || !searchInput || !paginationEl) return;

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
            for (var i = 0; i < allRows.length; i++) {
                var text = allRows[i].textContent.toLowerCase();
                if (text.indexOf(term) !== -1) {
                    filteredRows.push(allRows[i]);
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

        for (var i = 0; i < allRows.length; i++) {
            allRows[i].style.display = "none";
        }

        for (var j = start; j < end && j < filteredRows.length; j++) {
            filteredRows[j].style.display = "";
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
        updateBulkButton();
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

    // --- Checkboxes ---
    function getVisibleChecks() {
        var checks = [];
        for (var i = 0; i < filteredRows.length; i++) {
            var c = filteredRows[i].querySelector(".row-check");
            if (c) checks.push(c);
        }
        return checks;
    }

    function updateBulkButton() {
        if (!btnBulk) return;
        var checks = getVisibleChecks();
        var any = false;
        for (var i = 0; i < checks.length; i++) {
            if (checks[i].checked) { any = true; break; }
        }
        btnBulk.disabled = !any;
    }

    if (selectAll) {
        selectAll.addEventListener("change", function () {
            var checks = getVisibleChecks();
            for (var i = 0; i < checks.length; i++) {
                checks[i].checked = selectAll.checked;
            }
            updateBulkButton();
        });
    }

    tableBody.addEventListener("change", function (e) {
        if (e.target && e.target.classList.contains("row-check")) {
            var checks = getVisibleChecks();
            var allChecked = checks.length > 0;
            for (var i = 0; i < checks.length; i++) {
                if (!checks[i].checked) { allChecked = false; break; }
            }
            if (selectAll) selectAll.checked = allChecked;
            updateBulkButton();
        }
    });

    // --- Motivo modal (individual) ---
    var motivoModal = document.getElementById("motivoModal");
    var motivoForm = document.getElementById("motivoForm");
    var motivoIdInput = document.getElementById("motivoIdEquipoUsuario");
    var motivoTextarea = document.getElementById("motivoTexto");
    var btnCloseMotivoModal = document.getElementById("btnCloseMotivoModal");

    tableBody.addEventListener("click", function (e) {
        var btn = e.target.closest("[data-desvincular-id]");
        if (!btn) return;
        var id = btn.getAttribute("data-desvincular-id");
        if (!id) return;
        motivoIdInput.value = id;
        motivoTextarea.value = "";
        motivoModal.hidden = false;
        motivoTextarea.focus();
    });

    if (btnCloseMotivoModal) {
        btnCloseMotivoModal.addEventListener("click", function () {
            motivoModal.hidden = true;
        });
    }

    if (motivoModal) {
        motivoModal.addEventListener("click", function (e) {
            if (e.target === motivoModal) motivoModal.hidden = true;
        });
    }

    if (motivoForm) {
        motivoForm.addEventListener("submit", function () {
            motivoForm.action = "/equipoUsuario/Desvincular";
        });
    }

    // --- Motivo modal (bulk) ---
    var motivoBulkModal = document.getElementById("motivoBulkModal");
    var motivoBulkForm = document.getElementById("motivoBulkForm");
    var motivoBulkIds = document.getElementById("motivoBulkIds");
    var motivoBulkTextarea = document.getElementById("motivoBulkTexto");
    var btnCloseMotivoBulkModal = document.getElementById("btnCloseMotivoBulkModal");

    if (btnBulk) {
        btnBulk.addEventListener("click", function () {
            var checks = getVisibleChecks();
            var ids = [];
            for (var i = 0; i < checks.length; i++) {
                if (checks[i].checked) {
                    ids.push(checks[i].getAttribute("data-id"));
                }
            }

            if (ids.length === 0) return;

            motivoBulkIds.innerHTML = "";
            for (var j = 0; j < ids.length; j++) {
                var input = document.createElement("input");
                input.type = "hidden";
                input.name = "ids";
                input.value = ids[j];
                motivoBulkIds.appendChild(input);
            }

            motivoBulkTextarea.value = "";
            motivoBulkModal.hidden = false;
            motivoBulkTextarea.focus();
        });
    }

    if (btnCloseMotivoBulkModal) {
        btnCloseMotivoBulkModal.addEventListener("click", function () {
            motivoBulkModal.hidden = true;
        });
    }

    if (motivoBulkModal) {
        motivoBulkModal.addEventListener("click", function (e) {
            if (e.target === motivoBulkModal) motivoBulkModal.hidden = true;
        });
    }

    if (motivoBulkForm) {
        motivoBulkForm.addEventListener("submit", function () {
            motivoBulkForm.action = "/equipoUsuario/DesvincularSeleccionados";
        });
    }

    // --- Modal edit ---
    var modal = document.getElementById("editModal");
    var btnCloseModal = document.getElementById("btnCloseModal");

    document.querySelectorAll("[data-edit-id]").forEach(function (button) {
        button.addEventListener("click", function () {
            if (!modal) return;
            var f = {
                id: document.getElementById("editIdEquipoUsuario"),
                eq: document.getElementById("editIdEquipo"),
                us: document.getElementById("editIdUsuario"),
                ar: document.getElementById("editIdArea"),
                fa: document.getElementById("editFechaAsignacion"),
                fb: document.getElementById("editFechaBaja"),
                ob: document.getElementById("editObservaciones")
            };
            if (!f.id || !f.eq) return;
            f.id.value = button.getAttribute("data-edit-id") || "";
            f.eq.value = button.getAttribute("data-edit-equipment") || "";
            f.us.value = button.getAttribute("data-edit-user") || "";
            f.ar.value = button.getAttribute("data-edit-area") || "";
            f.fa.value = button.getAttribute("data-edit-assignment") || "";
            f.fb.value = button.getAttribute("data-edit-end") || "";
            f.ob.value = button.getAttribute("data-edit-observations") || "";
            modal.hidden = false;
            f.eq.focus();
        });
    });

    if (btnCloseModal) {
        btnCloseModal.addEventListener("click", function () {
            if (modal) modal.hidden = true;
        });
    }

    if (modal) {
        modal.addEventListener("click", function (e) {
            if (e.target === modal) modal.hidden = true;
        });
    }

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            if (modal) modal.hidden = true;
            if (motivoModal) motivoModal.hidden = true;
            if (motivoBulkModal) motivoBulkModal.hidden = true;
        }
    });
})();
