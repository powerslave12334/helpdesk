window.HelpDeskDashboard = (function () {
    function renderWorkspace(title, view) {
        const workspaceTitle = document.getElementById("workspaceTitle");
        const workspaceContent = document.getElementById("workspaceContent");

        if (workspaceTitle) {
            workspaceTitle.textContent = title;
        }

        if (!workspaceContent) {
            return;
        }

        const descriptions = {
            "dashboard": "Resumen general del sistema HelpDesk.",
            "crear-ticket": "Aqui construiremos el formulario para levantar una nueva solicitud de soporte.",
            "mis-tickets": "Aqui se mostrara el listado de tickets creados o asignados al usuario actual.",
            "todos-tickets": "Aqui se consultaran todos los tickets del sistema segun permisos.",
            "equipos": "Aqui se administrara el inventario de equipos de computo.",
            "configuraciones": "Aqui se registraran las configuraciones de los equipos.",
            "mantenimientos": "Aqui se dara seguimiento a mantenimientos preventivos y correctivos.",
            "empleado-registro": "Aqui construiremos el formulario para registrar empleados.",
            "empleados": "Aqui se consultara y administrara el catalogo de empleados.",
            "departamentos-areas": "Aqui se administraran departamentos y areas.",
            "gps-instalacion": "Aqui se registraran instalaciones y datos de GPS.",
            "vehiculos": "Aqui se administrara el catalogo de vehiculos.",
            "operadores": "Aqui se administrara la relacion de operadores.",
            "roles-permisos": "Aqui se administraran roles, modulos, acciones y permisos de usuarios.",
            "tipos-soporte": "Aqui se administrara el catalogo de tipos de soporte.",
            "estatus": "Aqui se administraran los estatus usados por tickets, equipos y procesos.",
            "accesos": "Aqui se administraran accesos y credenciales registradas para los sistemas."
        };

        workspaceContent.innerHTML = `
            <div class="work-panel">
                <h2>${title}</h2>
                <p>${descriptions[view] || "Contenido en preparacion."}</p>
            </div>
        `;
    }

    return {
        renderWorkspace: renderWorkspace
    };
})();
