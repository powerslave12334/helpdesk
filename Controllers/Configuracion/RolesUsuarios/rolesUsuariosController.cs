using HELPDESK.Models.Configuracion.RolesUsuarios;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;

namespace HELPDESK.Controllers.Configuracion.RolesUsuarios {
    public class rolesUsuariosController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;

        public rolesUsuariosController(DBConector dbConector, PermisosService permisosService) {
            _dbConector = dbConector;
            _permisosService = permisosService;
        }

        [HttpGet]
        public IActionResult Index() {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            rolesUsuariosModel model = new rolesUsuariosModel {
                RolesUsuarios = new rolesUsuariosModel().ObtenerRolesUsuarios(_dbConector),
                Roles = new rolesUsuariosModel().ObtenerRolesActivos(_dbConector),
                Usuarios = new rolesUsuariosModel().ObtenerUsuariosActivos(_dbConector),
                SuccessMessage = TempData["SuccessMessage"]?.ToString(),
                ErrorMessage = TempData["ErrorMessage"]?.ToString()
            };

            return View("~/Views/Configuracion/RolesUsuarios/rolesUsuarios.cshtml", model);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Create(rolesUsuariosModel model) {
            IActionResult? acceso = ValidarAcceso("Asignar");
            if (acceso != null)
                return acceso;

            if (model.IdRol <= 0 || model.IdUsuario <= 0) {
                TempData["ErrorMessage"] = "El usuario y el rol son obligatorios.";
                return RedirectToAction("Index");
            }

            if (model.UsuarioTieneRolActivo(_dbConector)) {
                TempData["ErrorMessage"] = "El usuario ya tiene un rol activo asignado.";
                return RedirectToAction("Index");
            }

            if (model.ExisteRelacion(_dbConector, true)) {
                TempData["ErrorMessage"] = "El usuario ya tiene asignado ese rol.";
                return RedirectToAction("Index");
            }

            bool guardado = model.ExisteRelacion(_dbConector, false)
                ? model.ReactivarRolUsuario(_dbConector)
                : model.InsertarRolUsuario(_dbConector);

            TempData[guardado ? "SuccessMessage" : "ErrorMessage"] =
                guardado ? "Rol asignado correctamente." : "No fue posible asignar el rol.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult ToggleActivo(int idRolesUsuario) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (idRolesUsuario <= 0) {
                TempData["ErrorMessage"] = "No se recibio la relacion a actualizar.";
                return RedirectToAction("Index");
            }

            rolesUsuariosModel model = new rolesUsuariosModel {
                IdRolesUsuario = idRolesUsuario
            };

            bool actualizado = model.CambiarEstatusRolUsuario(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Estatus actualizado correctamente." : "No fue posible actualizar el estatus.";

            return RedirectToAction("Index");
        }

        private IActionResult? ValidarAcceso(string accion) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            if (!_permisosService.TienePermisoPorRuta(idUsuario, "rolesUsuarios", accion)) {
                TempData["ErrorMessage"] = "No tienes permiso para realizar esta accion.";
                return RedirectToAction("Index", "Dashboard");
            }

            return null;
        }
    }
}
