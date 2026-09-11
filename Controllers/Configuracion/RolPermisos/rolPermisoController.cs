using HELPDESK.Models.Configuracion.RolPermisos;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;

namespace HELPDESK.Controllers.Configuracion.RolPermisos {
    public class rolPermisoController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;

        public rolPermisoController(DBConector dbConector, PermisosService permisosService) {
            _dbConector = dbConector;
            _permisosService = permisosService;
        }

        [HttpGet]
        public IActionResult Index(int idRol = 0) {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            rolPermisoModel model = CrearModeloPantalla(idRol);
            model.IdRol = idRol;
            model.SuccessMessage = TempData["SuccessMessage"]?.ToString();
            model.ErrorMessage = TempData["ErrorMessage"]?.ToString();

            return View("~/Views/Configuracion/RolPermisos/rolPermiso.cshtml", model);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Create(rolPermisoModel model) {
            IActionResult? acceso = ValidarAcceso("Asignar");
            if (acceso != null)
                return acceso;

            if (model.IdRol <= 0 || model.IdModulo <= 0 || model.IdSubModulo <= 0 || model.IdAccion <= 0) {
                TempData["ErrorMessage"] = "Rol, modulo, submodulo y accion son obligatorios.";
                return RedirectToAction("Index");
            }

            bool guardado = model.ExistePermiso(_dbConector)
                ? model.ActualizarPermisoExistente(_dbConector)
                : model.InsertarPermiso(_dbConector);

            TempData[guardado ? "SuccessMessage" : "ErrorMessage"] =
                guardado ? "Permiso guardado correctamente." : "No fue posible guardar el permiso.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult GuardarMatriz(rolPermisoModel model) {
            IActionResult? acceso = ValidarAcceso("Asignar");
            if (acceso != null)
                return acceso;

            if (model.IdRol <= 0) {
                TempData["ErrorMessage"] = "Selecciona un rol para guardar permisos.";
                return RedirectToAction("Index");
            }

            bool guardado = model.GuardarMatrizPermisos(_dbConector);
            TempData[guardado ? "SuccessMessage" : "ErrorMessage"] =
                guardado ? "Permisos del rol guardados correctamente." : "No fue posible guardar los permisos del rol.";

            return RedirectToAction("Index", new { idRol = model.IdRol });
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult ToggleActivo(int idRolPermiso) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (idRolPermiso <= 0) {
                TempData["ErrorMessage"] = "No se recibio el permiso a actualizar.";
                return RedirectToAction("Index");
            }

            rolPermisoModel model = new rolPermisoModel {
                IdRolPermiso = idRolPermiso
            };

            bool actualizado = model.CambiarEstatusPermiso(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Estatus actualizado correctamente." : "No fue posible actualizar el estatus.";

            return RedirectToAction("Index");
        }

        private rolPermisoModel CrearModeloPantalla(int idRol = 0) {
            rolPermisoModel data = new rolPermisoModel();

            return new rolPermisoModel {
                Permisos = data.ObtenerPermisos(_dbConector, idRol),
                Roles = data.ObtenerRolesActivos(_dbConector),
                Modulos = data.ObtenerModulosActivos(_dbConector),
                SubModulos = data.ObtenerSubModulosActivos(_dbConector),
                Acciones = data.ObtenerAccionesActivas(_dbConector)
            };
        }

        private IActionResult? ValidarAcceso(string accion) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            if (!_permisosService.TienePermisoPorRuta(idUsuario, "rolPermiso", accion)) {
                TempData["ErrorMessage"] = "No tienes permiso para realizar esta accion.";
                return RedirectToAction("Index", "Dashboard");
            }

            return null;
        }
    }
}
