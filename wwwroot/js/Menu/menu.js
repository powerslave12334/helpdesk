document.addEventListener("DOMContentLoaded", function () {
    const body = document.body;
    const btnSidebarToggle = document.getElementById("btnSidebarToggle");
    const btnThemeToggle = document.getElementById("btnThemeToggle");
    const themeText = document.getElementById("themeText");
    const themeIcon = document.getElementById("themeIcon");

    const savedTheme = localStorage.getItem("helpdesk-theme");
    if (savedTheme === "dark") {
        body.classList.add("dark-mode");
    }

    updateThemeButton();

    if (btnSidebarToggle) {
        btnSidebarToggle.addEventListener("click", function () {
            body.classList.toggle("sidebar-collapsed");

            if (body.classList.contains("sidebar-collapsed")) {
                document.querySelectorAll(".nav-group.open").forEach(function (group) {
                    group.classList.remove("open");
                });
            }

            btnSidebarToggle.setAttribute(
                "aria-label",
                body.classList.contains("sidebar-collapsed") ? "Expandir menu" : "Contraer menu"
            );
        });
    }

    if (btnThemeToggle) {
        btnThemeToggle.addEventListener("click", function () {
            body.classList.toggle("dark-mode");
            localStorage.setItem("helpdesk-theme", body.classList.contains("dark-mode") ? "dark" : "light");
            updateThemeButton();
        });
    }

    document.querySelectorAll(".nav-parent").forEach(function (button) {
        button.addEventListener("click", function () {
            const group = button.closest(".nav-group");

            document.querySelectorAll(".nav-group").forEach(function (item) {
                if (item !== group) {
                    item.classList.remove("open");
                }
            });

            if (group) {
                group.classList.toggle("open");
            }
        });
    });

    document.querySelectorAll("[data-view]").forEach(function (button) {
        button.addEventListener("click", function () {
            const title = button.getAttribute("data-title") || "Dashboard principal";
            const view = button.getAttribute("data-view") || "dashboard";

            document.querySelectorAll("[data-view]").forEach(function (item) {
                item.classList.remove("active");
            });

            button.classList.add("active");

            if (window.HelpDeskDashboard && typeof window.HelpDeskDashboard.renderWorkspace === "function") {
                window.HelpDeskDashboard.renderWorkspace(title, view);
            }
        });
    });

    function updateThemeButton() {
        const darkMode = body.classList.contains("dark-mode");

        if (themeText) {
            themeText.textContent = darkMode ? "Modo claro" : "Modo oscuro";
        }

        if (themeIcon) {
            themeIcon.innerHTML = darkMode ? '<i class="fa-solid fa-sun"></i>' : '<i class="fa-solid fa-moon"></i>';
        }
    }
});
