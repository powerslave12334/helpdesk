/**
 * Tabla con filtros y paginación client-side.
 * Uso: data-table en el contenedor, data-table-body en tbody, data-table-search en input de búsqueda.
 * Opcional: data-table-filter="campo" en <select> para filtros por columna.
 */
(function () {
    'use strict';

    const ROWS_PER_PAGE = 10;

    function initTable(container) {
        const searchInput = container.querySelector('[data-table-search]');
        const tbody = container.querySelector('[data-table-body]');
        const infoEl = container.querySelector('[data-table-info]');
        const paginationEl = container.querySelector('[data-table-pagination]');
        const filters = container.querySelectorAll('[data-table-filter]');

        if (!tbody) return;

        let currentPage = 1;
        let filteredRows = [];

        function getAllRows() {
            return Array.from(tbody.querySelectorAll('tr[data-row]'));
        }

        function getFilterValue(selectEl) {
            return selectEl ? selectEl.value.toLowerCase() : '';
        }

        function filterRows() {
            const term = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const rows = getAllRows();

            filteredRows = rows.filter(function (row) {
                var text = row.textContent.toLowerCase();
                var matchesSearch = !term || text.includes(term);

                var matchesFilters = true;
                filters.forEach(function (f) {
                    var field = f.getAttribute('data-table-filter');
                    var val = getFilterValue(f);
                    if (val) {
                        var cell = row.querySelector('[data-field="' + field + '"]');
                        if (cell) {
                            matchesFilters = matchesFilters && cell.textContent.toLowerCase().includes(val);
                        }
                    }
                });

                return matchesSearch && matchesFilters;
            });

            currentPage = 1;
            render();
        }

        function render() {
            var total = filteredRows.length;
            var totalPages = Math.max(1, Math.ceil(total / ROWS_PER_PAGE));
            if (currentPage > totalPages) currentPage = totalPages;

            var start = (currentPage - 1) * ROWS_PER_PAGE;
            var end = start + ROWS_PER_PAGE;

            getAllRows().forEach(function (row) {
                row.style.display = 'none';
            });

            filteredRows.forEach(function (row, i) {
                row.style.display = (i >= start && i < end) ? '' : 'none';
            });

            if (infoEl) {
                if (total === 0) {
                    infoEl.textContent = 'Sin resultados';
                } else {
                    infoEl.textContent = (start + 1) + '–' + Math.min(end, total) + ' de ' + total;
                }
            }

            if (paginationEl) {
                renderPagination(totalPages);
            }
        }

        function renderPagination(totalPages) {
            paginationEl.innerHTML = '';

            function btn(label, page, disabled, active) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'page-btn' + (active ? ' active' : '');
                b.innerHTML = label;
                b.disabled = disabled;
                if (!disabled && !active) {
                    b.addEventListener('click', function () {
                        currentPage = page;
                        render();
                    });
                }
                return b;
            }

            var prev = btn('<i class="fas fa-chevron-left"></i>', currentPage - 1, currentPage <= 1);
            paginationEl.appendChild(prev);

            var pages = [];
            if (totalPages <= 7) {
                for (var i = 1; i <= totalPages; i++) pages.push(i);
            } else {
                pages.push(1);
                if (currentPage > 3) {
                    var dots = document.createElement('span');
                    dots.className = 'page-dots';
                    dots.textContent = '...';
                    paginationEl.appendChild(dots);
                }
                for (var j = Math.max(2, currentPage - 1); j <= Math.min(totalPages - 1, currentPage + 1); j++) {
                    pages.push(j);
                }
                if (currentPage < totalPages - 2) {
                    var dots2 = document.createElement('span');
                    dots2.className = 'page-dots';
                    dots2.textContent = '...';
                    paginationEl.appendChild(dots2);
                }
                pages.push(totalPages);
            }

            pages.forEach(function (p) {
                paginationEl.appendChild(btn(p, p, false, p === currentPage));
            });

            var next = btn('<i class="fas fa-chevron-right"></i>', currentPage + 1, currentPage >= totalPages);
            paginationEl.appendChild(next);
        }

        if (searchInput) {
            searchInput.addEventListener('input', filterRows);
        }

        filters.forEach(function (f) {
            f.addEventListener('change', filterRows);
        });

        filterRows();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-table]').forEach(initTable);
    });
})();
