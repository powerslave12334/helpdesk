var HelpDeskNotifications = (function () {
    var connection = null;
    var userId = null;

    function init(uid) {
        userId = uid || detectUserId();
        if (!userId) return;
        injectBell();
        startSignalR();
        loadNotifications();
        setupBellToggle();
        setupClickOutside();
    }

    function detectUserId() {
        var body = document.body;
        if (body && body.getAttribute("data-user-id")) {
            return parseInt(body.getAttribute("data-user-id"));
        }
        return null;
    }

    function injectBell() {
        var header = document.querySelector(".workspace-header");
        if (!header || document.getElementById("notifBell")) return;

        var wrapper = document.createElement("div");
        wrapper.className = "notif-wrapper";
        wrapper.id = "notifBell";
        wrapper.innerHTML = '<div class="notif-bell"><i class="fa-solid fa-bell"></i><span class="notif-badge" id="notifBadge">0</span></div>' +
            '<div class="notif-panel" id="notifPanel">' +
                '<div class="notif-panel-header"><h3>Notificaciones</h3><button class="notif-mark-all" id="notifMarkAll">Marcar todo leido</button></div>' +
                '<div class="notif-list" id="notifList"><div class="notif-empty"><i class="fa-solid fa-bell-slash"></i>Sin notificaciones</div></div>' +
            '</div>';

        header.appendChild(wrapper);
    }

    function startSignalR() {
        connection = new signalR.HubConnectionBuilder()
            .withUrl("/notificationHub")
            .withAutomaticReconnect()
            .build();

        connection.on("ReceiveNotification", function (data) {
            showToast(data);
            updateBadge(1);
        });

        connection.start().then(function () {
            if (userId) {
                connection.invoke("JoinGroup", userId.toString());
            }
        }).catch(function () {});
    }

    function loadNotifications() {
        fetch("/notificaciones/ObtenerNotificaciones")
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    renderList(data.notificaciones);
                    setBadge(data.noLeidas);
                }
            }).catch(function () {});
    }

    function renderList(notificaciones) {
        var list = document.getElementById("notifList");
        if (!list) return;

        if (notificaciones.length === 0) {
            list.innerHTML = '<div class="notif-empty"><i class="fa-solid fa-bell-slash"></i>Sin notificaciones</div>';
            return;
        }

        var html = "";
        for (var i = 0; i < notificaciones.length; i++) {
            var n = notificaciones[i];
            var iconClass = getIconClass(n.tipo);
            var unread = n.leido ? "" : " unread";
            var time = timeAgo(n.fechaCreacion);

            html += '<div class="notif-item' + unread + '" data-id="' + n.idNotificacion + '" data-url="' + (n.urlAccion || "") + '">' +
                '<div class="notif-icon ' + iconClass + '"><i class="fa-solid ' + getIcon(n.tipo) + '"></i></div>' +
                '<div class="notif-body">' +
                    '<p>' + escapeHtml(n.mensaje) + '</p>' +
                    '<div class="notif-time">' + time + '</div>' +
                '</div>' +
            '</div>';
        }
        list.innerHTML = html;

        list.querySelectorAll(".notif-item").forEach(function (item) {
            item.addEventListener("click", function () {
                var id = parseInt(item.getAttribute("data-id"));
                var url = item.getAttribute("data-url");
                markRead(id);
                if (url) window.location.href = url;
            });
        });
    }

    function setupBellToggle() {
        var bell = document.getElementById("notifBell");
        var panel = document.getElementById("notifPanel");
        if (!bell || !panel) return;

        bell.addEventListener("click", function (e) {
            e.stopPropagation();
            panel.classList.toggle("open");
            if (panel.classList.contains("open")) {
                loadNotifications();
            }
        });
    }

    function setupClickOutside() {
        document.addEventListener("click", function (e) {
            var panel = document.getElementById("notifPanel");
            var bell = document.getElementById("notifBell");
            if (panel && !panel.contains(e.target) && bell && !bell.contains(e.target)) {
                panel.classList.remove("open");
            }
        });

        var markAll = document.getElementById("notifMarkAll");
        if (markAll) {
            markAll.addEventListener("click", function () {
                fetch("/notificaciones/MarcarTodasLeido", { method: "POST" })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.success) {
                            setBadge(0);
                            loadNotifications();
                        }
                    });
            });
        }
    }

    function markRead(id) {
        fetch("/notificaciones/MarcarLeido", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(id)
        }).then(function (r) { return r.json(); })
          .then(function (data) {
              if (data.success) setBadge(data.noLeidas);
          });
    }

    function updateBadge(increment) {
        var badge = document.getElementById("notifBadge");
        if (!badge) return;
        var current = parseInt(badge.textContent) || 0;
        setBadge(current + increment);
    }

    function setBadge(count) {
        var badge = document.getElementById("notifBadge");
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count > 99 ? "99+" : count;
            badge.classList.add("visible");
        } else {
            badge.textContent = "0";
            badge.classList.remove("visible");
        }
    }

    function showToast(data) {
        var existing = document.querySelector(".notif-toast");
        if (existing) existing.remove();

        var toast = document.createElement("div");
        toast.className = "notif-toast";
        var iconClass = getIconClass(data.tipo);
        toast.innerHTML = '<div class="notif-icon ' + iconClass + '"><i class="fa-solid ' + getIcon(data.tipo) + '"></i></div>' +
            '<div class="notif-toast-body"><p>' + escapeHtml(data.mensaje) + '</p><span>Ahora</span></div>';

        document.body.appendChild(toast);
        requestAnimationFrame(function () { toast.classList.add("show"); });

        toast.addEventListener("click", function () {
            if (data.urlAccion) window.location.href = data.urlAccion;
        });

        setTimeout(function () {
            toast.classList.remove("show");
            setTimeout(function () { toast.remove(); }, 300);
        }, 5000);
    }

    function getIconClass(tipo) {
        var map = {
            ticket_asignado: "ticket_asignado",
            comentario: "comentario",
            resuelto: "resuelto",
            cerrado: "cerrado",
            equipo_asignado: "equipo_asignado",
            equipo_desvinculado: "equipo_desvinculado"
        };
        return map[tipo] || "default";
    }

    function getIcon(tipo) {
        var map = {
            ticket_asignado: "ticket",
            comentario: "comment",
            resuelto: "circle-check",
            cerrado: "lock",
            equipo_asignado: "laptop",
            equipo_desvinculado: "laptop"
        };
        return map[tipo] || "bell";
    }

    function timeAgo(dateStr) {
        var now = new Date();
        var date = new Date(dateStr);
        var diff = Math.floor((now - date) / 1000);
        if (diff < 60) return "Ahora";
        if (diff < 3600) return Math.floor(diff / 60) + " min";
        if (diff < 86400) return Math.floor(diff / 3600) + " h";
        return Math.floor(diff / 86400) + " d";
    }

    function escapeHtml(text) {
        var div = document.createElement("div");
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    return { init: init };
})();

document.addEventListener("DOMContentLoaded", function () {
    HelpDeskNotifications.init();
});
