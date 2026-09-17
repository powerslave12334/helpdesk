window.HelpDeskDashboard = (function () {
    var chartEstatus = null;
    var chartMes = null;

    var colors = {
        primary: "#1c6bb3",
        primarySoft: "rgba(28, 107, 179, 0.15)",
        success: "#25b46b",
        warning: "#f0a500",
        danger: "#e74c3c",
        info: "#3498db",
        palette: ["#1c6bb3", "#25b46b", "#f0a500", "#e74c3c", "#9d4edd", "#3498db", "#1abc9c", "#e67e22"]
    };

    function isDarkMode() {
        return document.body.classList.contains("dark-mode");
    }

    function getGridColor() {
        return isDarkMode() ? "rgba(255,255,255,0.08)" : "rgba(0,0,0,0.06)";
    }

    function getTextColor() {
        return isDarkMode() ? "#a4afbf" : "#6b778c";
    }

    function initCharts() {
        var data = window.__dashboardData;
        if (!data) return;

        initEstatusChart(data.ticketsPorEstatus || []);
        initMesChart(data.ticketsPorMes || []);
    }

    function initEstatusChart(items) {
        var canvas = document.getElementById("chartEstatus");
        if (!canvas) return;

        if (chartEstatus) {
            chartEstatus.destroy();
        }

        var labels = items.map(function (i) { return i.Label; });
        var values = items.map(function (i) { return i.Value; });
        var bgColors = items.map(function (_, idx) { return colors.palette[idx % colors.palette.length]; });

        chartEstatus = new Chart(canvas, {
            type: "doughnut",
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: bgColors,
                    borderWidth: 0,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "62%",
                plugins: {
                    legend: {
                        position: "bottom",
                        labels: {
                            padding: 14,
                            usePointStyle: true,
                            pointStyleWidth: 10,
                            color: getTextColor(),
                            font: { size: 12 }
                        }
                    }
                }
            }
        });
    }

    function initMesChart(items) {
        var canvas = document.getElementById("chartMes");
        if (!canvas) return;

        if (chartMes) {
            chartMes.destroy();
        }

        var labels = items.map(function (i) { return i.Label; });
        var values = items.map(function (i) { return i.Value; });

        chartMes = new Chart(canvas, {
            type: "bar",
            data: {
                labels: labels,
                datasets: [{
                    label: "Tickets",
                    data: values,
                    backgroundColor: colors.primarySoft,
                    borderColor: colors.primary,
                    borderWidth: 2,
                    borderRadius: 6,
                    barPercentage: 0.6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: getTextColor(), font: { size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: getGridColor() },
                        ticks: {
                            color: getTextColor(),
                            font: { size: 11 },
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }

    function refreshTheme() {
        if (chartEstatus) {
            chartEstatus.options.plugins.legend.labels.color = getTextColor();
            chartEstatus.update();
        }
        if (chartMes) {
            chartMes.options.scales.x.ticks.color = getTextColor();
            chartMes.options.scales.y.ticks.color = getTextColor();
            chartMes.options.scales.y.grid.color = getGridColor();
            chartMes.update();
        }
    }

    function renderWorkspace(title, view) {
        var workspaceTitle = document.getElementById("workspaceTitle");
        var workspaceContent = document.getElementById("workspaceContent");

        if (workspaceTitle) {
            workspaceTitle.textContent = title;
        }

        if (!workspaceContent) {
            return;
        }

        var descriptions = {
            "dashboard": "Resumen general del sistema HelpDesk.",
            "crear-ticket": "Formulario para levantar una nueva solicitud de soporte.",
            "mis-tickets": "Listado de tickets creados o asignados al usuario actual.",
            "todos-tickets": "Consulta de todos los tickets del sistema segun permisos.",
            "equipos": "Administracion del inventario de equipos de computo.",
            "configuraciones": "Configuraciones de los equipos.",
            "mantenimientos": "Seguimiento a mantenimientos preventivos y correctivos.",
            "empleado-registro": "Formulario para registrar empleados.",
            "empleados": "Consulta y administracion del catalogo de empleados.",
            "departamentos-areas": "Administracion de departamentos y areas.",
            "gps-instalacion": "Registro de instalaciones y datos de GPS.",
            "vehiculos": "Catalogo de vehiculos.",
            "operadores": "Relacion de operadores.",
            "roles-permisos": "Administracion de roles, modulos, acciones y permisos.",
            "tipos-soporte": "Catalogo de tipos de soporte.",
            "estatus": "Estatus usados por tickets, equipos y procesos.",
            "accesos": "Accesos y credenciales registradas para los sistemas."
        };

        workspaceContent.innerHTML =
            '<div class="work-panel">' +
                '<h2>' + title + '</h2>' +
                '<p>' + (descriptions[view] || "Contenido en preparacion.") + '</p>' +
            '</div>';
    }

    document.addEventListener("DOMContentLoaded", function () {
        initCharts();

        var observer = new MutationObserver(function () {
            refreshTheme();
        });
        observer.observe(document.body, { attributes: true, attributeFilter: ["class"] });
    });

    return {
        renderWorkspace: renderWorkspace,
        refreshTheme: refreshTheme
    };
})();
