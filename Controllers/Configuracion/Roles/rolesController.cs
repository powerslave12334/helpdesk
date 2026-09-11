using HELPDESK.Models.Configuracion.Roles;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;

namespace HELPDESK.Controllers.Configuracion.Roles {
    public class rolesController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;

        public rolesController(DBConector dbConector, PermisosService permisosService) {
            _dbConector = dbConector;
            _permisosService = permisosService;
        }

        [HttpGet]
        public IActionResult Index() {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            rolesModel model = new rolesModel {
                Roles = new rolesModel().ObtenerRoles(_dbConector),
                SuccessMessage = TempData["SuccessMessage"]?.ToString(),
                ErrorMessage = TempData["ErrorMessage"]?.ToString()
            };

            return View("~/Views/Configuracion/Roles/roles.cshtml", model);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Create(rolesModel model) {
            IActionResult? acceso = ValidarAcceso("Crear");
            if (acceso != null)
                return acceso;

            if (string.IsNullOrWhiteSpace(model.Nombre) || string.IsNullOrWhiteSpace(model.Descripcion)) {
                TempData["ErrorMessage"] = "El nombre y la descripcion del rol son obligatorios.";
                return RedirectToAction("Index");
            }

            model.Nombre = model.Nombre.Trim();
            model.Descripcion = model.Descripcion.Trim();

            if (model.ExisteNombre(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe un rol con ese nombre.";
                return RedirectToAction("Index");
            }

            bool insertado = model.InsertarRol(_dbConector);
            TempData[insertado ? "SuccessMessage" : "ErrorMessage"] =
                insertado ? "Rol agregado correctamente." : "No fue posible agregar el rol.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Edit(rolesModel model) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (model.IdRol <= 0 || string.IsNullOrWhiteSpace(model.Nombre) || string.IsNullOrWhiteSpace(model.Descripcion)) {
                TempData["ErrorMessage"] = "Datos incompletos para actualizar el rol.";
                return RedirectToAction("Index");
            }

            model.Nombre = model.Nombre.Trim();
            model.Descripcion = model.Descripcion.Trim();

            if (model.ExisteNombre(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe otro rol con ese nombre.";
                return RedirectToAction("Index");
            }

            bool actualizado = model.ActualizarRol(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Rol actualizado correctamente." : "No fue posible actualizar el rol.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult ToggleActivo(int idRol) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (idRol <= 0) {
                TempData["ErrorMessage"] = "No se recibio el rol a actualizar.";
                return RedirectToAction("Index");
            }

            rolesModel model = new rolesModel {
                IdRol = idRol
            };

            bool actualizado = model.CambiarEstatusRol(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Estatus actualizado correctamente." : "No fue posible actualizar el estatus.";

            return RedirectToAction("Index");
        }

        private IActionResult? ValidarAcceso(string accion) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            if (!_permisosService.TienePermisoPorRuta(idUsuario, "roles", accion)) {
                TempData["ErrorMessage"] = "No tienes permiso para realizar esta accion.";
                return RedirectToAction("Index", "Dashboard");
            }

            return null;
        }
    }
}
