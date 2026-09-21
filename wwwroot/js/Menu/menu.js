document.addEventListener("DOMContentLoaded", function () {
    var body = document.body;
    var btnSidebarToggle = document.getElementById("btnSidebarToggle");
    var btnThemeToggle = document.getElementById("btnThemeToggle");
    var themeText = document.getElementById("themeText");
    var themeIcon = document.getElementById("themeIcon");
    var sidebar = document.getElementById("appSidebar");
    var overlay = document.getElementById("sidebarOverlay");

    var savedTheme = localStorage.getItem("helpdesk-theme");
    if (savedTheme === "dark") {
        body.classList.add("dark-mode");
    }
    updateThemeButton();

    var savedSidebar = localStorage.getItem("helpdesk-sidebar");
    if (savedSidebar === "collapsed") {
        body.classList.add("sidebar-collapsed");
    }

    if (btnSidebarToggle) {
        btnSidebarToggle.addEventListener("click", function () {
            body.classList.toggle("sidebar-collapsed");
            localStorage.setItem("helpdesk-sidebar", body.classList.contains("sidebar-collapsed") ? "collapsed" : "expanded");

            document.querySelectorAll(".nav-group.open").forEach(function (group) {
                group.classList.remove("open");
            });

            closeAllPortaledSubmenus();

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
            var group = button.closest(".nav-group");
            var wasOpen = group && group.classList.contains("open");

            document.querySelectorAll(".nav-group.open").forEach(function (item) {
                if (item !== group) {
                    item.classList.remove("open");
                    var siblingBtn = item.querySelector(".nav-parent");
                    if (siblingBtn) siblingBtn.setAttribute("aria-expanded", "false");
                    restoreSubmenu(item);
                }
            });

            if (group) {
                group.classList.toggle("open");
                button.setAttribute("aria-expanded", group.classList.contains("open"));

                if (!wasOpen && body.classList.contains("sidebar-collapsed")) {
                    portalSubmenu(group);
                } else if (wasOpen) {
                    restoreSubmenu(group);
                }
            }
        });
    });

    document.querySelectorAll("[data-view]").forEach(function (button) {
        button.addEventListener("click", function () {
            var title = button.getAttribute("data-title") || "Dashboard principal";
            var view = button.getAttribute("data-view") || "dashboard";

            closeAllPortaledSubmenus();

            document.querySelectorAll("[data-view]").forEach(function (item) {
                item.classList.remove("active");
            });

            button.classList.add("active");

            if (window.HelpDeskDashboard && typeof window.HelpDeskDashboard.renderWorkspace === "function") {
                window.HelpDeskDashboard.renderWorkspace(title, view);
            }

            closeMobileSidebar();
        });
    });

    function updateThemeButton() {
        var darkMode = body.classList.contains("dark-mode");

        if (themeText) {
            themeText.textContent = darkMode ? "Modo claro" : "Modo oscuro";
        }

        if (themeIcon) {
            themeIcon.innerHTML = darkMode ? '<i class="fa-solid fa-sun"></i>' : '<i class="fa-solid fa-moon"></i>';
        }
    }

    function portalSubmenu(group) {
        var submenu = group.querySelector(".submenu");
        if (!submenu || submenu.parentElement === body) return;

        var sidebarRect = sidebar.getBoundingClientRect();
        var groupRect = group.getBoundingClientRect();

        submenu._originalParent = submenu.parentNode;
        submenu._originalNextSibling = submenu.nextSibling;
        submenu._navGroup = group;
        group._portaledSubmenu = submenu;
        body.appendChild(submenu);

        var top = groupRect.top - 4;
        var submenuHeight = submenu.scrollHeight || 200;
        var viewportHeight = window.innerHeight;

        if (top + submenuHeight > viewportHeight - 10) {
            top = viewportHeight - submenuHeight - 10;
        }
        if (top < 10) top = 10;

        submenu.style.left = (sidebarRect.right + 8) + "px";
        submenu.style.top = top + "px";
        submenu.classList.add("portaled");
    }

    function restoreSubmenu(group) {
        var submenu = group._portaledSubmenu || group.querySelector(".submenu");
        if (!submenu || !submenu.classList.contains("portaled")) return;

        submenu.style.left = "";
        submenu.style.top = "";
        submenu.classList.remove("portaled");
        submenu._navGroup = null;
        group._portaledSubmenu = null;

        if (submenu._originalParent) {
            if (submenu._originalNextSibling) {
                submenu._originalParent.insertBefore(submenu, submenu._originalNextSibling);
            } else {
                submenu._originalParent.appendChild(submenu);
            }
        }

        submenu._originalParent = null;
        submenu._originalNextSibling = null;
    }

    function closeAllPortaledSubmenus() {
        document.querySelectorAll(".submenu.portaled").forEach(function (submenu) {
            var group = submenu._navGroup;
            if (group) {
                group.classList.remove("open");
                var btn = group.querySelector(".nav-parent");
                if (btn) btn.setAttribute("aria-expanded", "false");
                restoreSubmenu(group);
            }
        });
    }

    document.addEventListener("click", function (e) {
        if (!body.classList.contains("sidebar-collapsed")) return;
        if (sidebar.contains(e.target)) return;

        var portaled = document.querySelector(".submenu.portaled");
        if (portaled && !portaled.contains(e.target)) {
            var group = portaled._navGroup;
            if (group) {
                group.classList.remove("open");
                var btn = group.querySelector(".nav-parent");
                if (btn) btn.setAttribute("aria-expanded", "false");
                restoreSubmenu(group);
            }
        }
    });

    function injectMobileMenuBtn() {
        if (document.getElementById("mobileMenuBtn")) return;
        var btn = document.createElement("button");
        btn.type = "button";
        btn.id = "mobileMenuBtn";
        btn.className = "mobile-menu-btn";
        btn.setAttribute("aria-label", "Abrir menu");
        btn.innerHTML = '<span></span>';
        var header = document.querySelector(".workspace-header");
        if (header) {
            header.prepend(btn);
        }
        btn.addEventListener("click", function () {
            sidebar.classList.add("mobile-open");
            overlay.classList.add("visible");
        });
    }

    function closeMobileSidebar() {
        if (sidebar) sidebar.classList.remove("mobile-open");
        if (overlay) overlay.classList.remove("visible");
    }

    if (overlay) {
        overlay.addEventListener("click", closeMobileSidebar);
    }

    injectMobileMenuBtn();

    window.addEventListener("resize", function () {
        document.querySelectorAll(".submenu.portaled").forEach(function (submenu) {
            var group = submenu._navGroup;
            if (group) {
                var sidebarRect = sidebar.getBoundingClientRect();
                var groupRect = group.getBoundingClientRect();

                var top = groupRect.top - 4;
                var submenuHeight = submenu.scrollHeight || 200;
                var viewportHeight = window.innerHeight;

                if (top + submenuHeight > viewportHeight - 10) {
                    top = viewportHeight - submenuHeight - 10;
                }
                if (top < 10) top = 10;

                submenu.style.left = (sidebarRect.right + 8) + "px";
                submenu.style.top = top + "px";
            }
        });
    });

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            closeAllPortaledSubmenus();
            closeMobileSidebar();
        }
    });

    window.addEventListener("storage", function (e) {
        if (e.key === "helpdesk-theme") {
            if (e.newValue === "dark") {
                body.classList.add("dark-mode");
            } else {
                body.classList.remove("dark-mode");
            }
            updateThemeButton();
        }
    });
});
