using HELPDESK.Models.Configuracion.PermisoUsuarios;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;

namespace HELPDESK.Controllers.Configuracion.PermisoUsuarios {
    public class permisoUsuariosController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;

        public permisoUsuariosController(DBConector dbConector, PermisosService permisosService) {
            _dbConector = dbConector;
            _permisosService = permisosService;
        }

        [HttpGet]
        public IActionResult Index(int idUsuario = 0) {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            permisoUsuariosModel model = CrearModeloPantalla(idUsuario);
            model.IdUsuario = idUsuario;
            model.SuccessMessage = TempData["SuccessMessage"]?.ToString();
            model.ErrorMessage = TempData["ErrorMessage"]?.ToString();

            return View("~/Views/Configuracion/PermisoUsuarios/permisoUsuarios.cshtml", model);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Create(permisoUsuariosModel model) {
            IActionResult? acceso = ValidarAcceso("Asignar");
            if (acceso != null)
                return acceso;

            if (model.IdUsuario <= 0 || model.IdModulo <= 0 || model.IdSubModulo <= 0 || model.IdAccion <= 0) {
                TempData["ErrorMessage"] = "Usuario, modulo, submodulo y accion son obligatorios.";
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
        public IActionResult GuardarMatriz(permisoUsuariosModel model) {
            IActionResult? acceso = ValidarAcceso("Asignar");
            if (acceso != null)
                return acceso;

            if (model.IdUsuario <= 0) {
                TempData["ErrorMessage"] = "Selecciona un usuario para guardar permisos.";
                return RedirectToAction("Index");
            }

            bool guardado = model.GuardarMatrizPermisos(_dbConector);
            TempData[guardado ? "SuccessMessage" : "ErrorMessage"] =
                guardado ? "Permisos del usuario guardados correctamente." : "No fue posible guardar los permisos del usuario.";

            return RedirectToAction("Index", new { idUsuario = model.IdUsuario });
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult ToggleActivo(int idPermisoUsuarios) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (idPermisoUsuarios <= 0) {
                TempData["ErrorMessage"] = "No se recibio el permiso a actualizar.";
                return RedirectToAction("Index");
            }

            permisoUsuariosModel model = new permisoUsuariosModel {
                IdPermisoUsuarios = idPermisoUsuarios
            };

            bool actualizado = model.CambiarEstatusPermiso(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Estatus actualizado correctamente." : "No fue posible actualizar el estatus.";

            return RedirectToAction("Index");
        }

        private permisoUsuariosModel CrearModeloPantalla(int idUsuario = 0) {
            permisoUsuariosModel data = new permisoUsuariosModel();

            return new permisoUsuariosModel {
                Permisos = data.ObtenerPermisos(_dbConector, idUsuario),
                Usuarios = data.ObtenerUsuariosActivos(_dbConector),
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
            if (!_permisosService.TienePermisoPorRuta(idUsuario, "permisoUsuarios", accion)) {
                TempData["ErrorMessage"] = "No tienes permiso para realizar esta accion.";
                return RedirectToAction("Index", "Dashboard");
            }

            return null;
        }
    }
}
