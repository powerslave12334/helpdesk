(function () {
    var ROWS_PER_PAGE = 10;
    var currentPage = 1;
    var filteredRows = [];

    var searchInput = document.getElementById("searchInput");
    var tableBody = document.getElementById("tableBody");
    var rowCountEl = document.getElementById("rowCount");
    var paginationEl = document.getElementById("pagination");
    var allRows = tableBody ? Array.from(tableBody.querySelectorAll("tr")) : [];

    function normalize(t) { return (t || "").toLowerCase(); }

    function getRowText(row) {
        var cells = row.querySelectorAll("td");
        var parts = [];
        for (var i = 0; i < cells.length - 1; i++) {
            parts.push(normalize(cells[i].textContent));
        }
        return parts.join(" ");
    }

    function filterRows() {
        var q = normalize(searchInput ? searchInput.value : "");
        filteredRows = allRows.filter(function (row) {
            if (!q) return true;
            return getRowText(row).indexOf(q) !== -1;
        });
    }

    function renderTable() {
        if (!tableBody) return;
        tableBody.innerHTML = "";
        var colSpan = allRows.length > 0 ? allRows[0].querySelectorAll("td").length : 5;
        if (filteredRows.length === 0) {
            var tr = document.createElement("tr");
            var td = document.createElement("td");
            td.colSpan = colSpan;
            td.className = "empty-cell";
            td.textContent = "No hay resultados.";
            tr.appendChild(td);
            tableBody.appendChild(tr);
        } else {
            var start = (currentPage - 1) * ROWS_PER_PAGE;
            var end = Math.min(start + ROWS_PER_PAGE, filteredRows.length);
            for (var i = start; i < end; i++) {
                tableBody.appendChild(filteredRows[i]);
            }
        }
        renderInfo();
        renderPagination();
    }

    function renderInfo() {
        if (!rowCountEl) return;
        var total = filteredRows.length;
        if (total === 0) { rowCountEl.textContent = "Mostrando 0 registros"; return; }
        var start = (currentPage - 1) * ROWS_PER_PAGE + 1;
        var end = Math.min(currentPage * ROWS_PER_PAGE, total);
        rowCountEl.textContent = "Mostrando " + start + " a " + end + " de " + total + " registros";
    }

    function renderPagination() {
        if (!paginationEl) return;
        paginationEl.innerHTML = "";
        var total = filteredRows.length;
        var totalPages = Math.max(1, Math.ceil(total / ROWS_PER_PAGE));
        if (currentPage > totalPages) currentPage = totalPages;

        var prev = document.createElement("button");
        prev.type = "button"; prev.className = "page-btn";
        prev.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
        prev.disabled = currentPage <= 1;
        prev.addEventListener("click", function () { currentPage--; renderTable(); });
        paginationEl.appendChild(prev);

        var startP = Math.max(1, currentPage - 2);
        var endP = Math.min(totalPages, currentPage + 2);
        if (startP > 1) {
            var b = document.createElement("button");
            b.type = "button"; b.className = "page-btn"; b.textContent = "1";
            b.addEventListener("click", function () { currentPage = 1; renderTable(); });
            paginationEl.appendChild(b);
            if (startP > 2) {
                var d = document.createElement("span"); d.className = "page-dots"; d.textContent = "...";
                paginationEl.appendChild(d);
            }
        }
        for (var p = startP; p <= endP; p++) {
            var btn = document.createElement("button");
            btn.type = "button"; btn.className = "page-btn" + (p === currentPage ? " active" : "");
            btn.textContent = p; btn.setAttribute("data-page", p);
            btn.addEventListener("click", function () {
                currentPage = parseInt(this.getAttribute("data-page"), 10); renderTable();
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
            bLast.addEventListener("click", function () { currentPage = totalPages; renderTable(); });
            paginationEl.appendChild(bLast);
        }

        var next = document.createElement("button");
        next.type = "button"; next.className = "page-btn";
        next.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
        next.disabled = currentPage >= totalPages;
        next.addEventListener("click", function () { currentPage++; renderTable(); });
        paginationEl.appendChild(next);
    }

    if (searchInput) {
        searchInput.addEventListener("input", function () {
            currentPage = 1; filterRows(); renderTable();
        });
    }

    filterRows();
    renderTable();

    // === EXISTING MODAL LOGIC ===
    var modal = document.getElementById("editModal");
    var btnCloseModal = document.getElementById("btnCloseModal");
    var editId = document.getElementById("editIdDepartamento");
    var editName = document.getElementById("editNombre");

    document.querySelectorAll("[data-edit-id]").forEach(function (button) {
        button.addEventListener("click", function () {
            if (!modal || !editId || !editName) {
                return;
            }

            editId.value = button.getAttribute("data-edit-id") || "";
            editName.value = button.getAttribute("data-edit-name") || "";
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
})();
