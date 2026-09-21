(function () {
    var tableBody = document.getElementById("tableBody");
    var searchInput = document.getElementById("searchInput");
    var paginationEl = document.getElementById("pagination");
    var rowCountEl = document.getElementById("rowCount");

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
            td.colSpan = 5;
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

    // --- Modal edit ---
    var modal = document.getElementById("editModal");
    var btnCloseModal = document.getElementById("btnCloseModal");
    var editId = document.getElementById("editIdTipoEquipo");
    var editDescription = document.getElementById("editDescripcion");

    document.querySelectorAll("[data-edit-id]").forEach(function (button) {
        button.addEventListener("click", function () {
            if (!modal || !editId || !editDescription) return;
            editId.value = button.getAttribute("data-edit-id") || "";
            editDescription.value = button.getAttribute("data-edit-description") || "";
            modal.hidden = false;
            editDescription.focus();
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
        if (e.key === "Escape" && modal) modal.hidden = true;
    });
})();
