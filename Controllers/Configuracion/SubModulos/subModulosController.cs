using HELPDESK.Models.Configuracion.SubModulos;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;

namespace HELPDESK.Controllers.Configuracion.SubModulos {
    public class subModulosController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;

        public subModulosController(DBConector dbConector, PermisosService permisosService) {
            _dbConector = dbConector;
            _permisosService = permisosService;
        }

        [HttpGet]
        public IActionResult Index() {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            subModulosModel model = new subModulosModel {
                SubModulos = new subModulosModel().ObtenerSubModulos(_dbConector),
                Modulos = new subModulosModel().ObtenerModulosActivos(_dbConector),
                SuccessMessage = TempData["SuccessMessage"]?.ToString(),
                ErrorMessage = TempData["ErrorMessage"]?.ToString()
            };

            return View("~/Views/Configuracion/SubModulos/subModulos.cshtml", model);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Create(subModulosModel model) {
            IActionResult? acceso = ValidarAcceso("Crear");
            if (acceso != null)
                return acceso;

            if (model.IdModulo <= 0 || string.IsNullOrWhiteSpace(model.Nombre) || string.IsNullOrWhiteSpace(model.Controller) || string.IsNullOrWhiteSpace(model.Action)) {
                TempData["ErrorMessage"] = "El modulo, nombre, controller y action son obligatorios.";
                return RedirectToAction("Index");
            }

            if (model.Orden < 0) {
                TempData["ErrorMessage"] = "El orden no puede ser negativo.";
                return RedirectToAction("Index");
            }

            model.Nombre = model.Nombre.Trim();
            model.Controller = model.Controller.Trim();
            model.Action = model.Action.Trim();

            if (model.ExisteNombre(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe un submodulo con ese nombre dentro del modulo seleccionado.";
                return RedirectToAction("Index");
            }

            if (model.ExisteOrden(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe un submodulo con ese orden dentro del modulo seleccionado.";
                return RedirectToAction("Index");
            }

            bool insertado = model.InsertarSubModulo(_dbConector);
            TempData[insertado ? "SuccessMessage" : "ErrorMessage"] =
                insertado ? "Submodulo agregado correctamente." : "No fue posible agregar el submodulo.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Edit(subModulosModel model) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (model.IdSubModulos <= 0 || model.IdModulo <= 0 || string.IsNullOrWhiteSpace(model.Nombre) || string.IsNullOrWhiteSpace(model.Controller) || string.IsNullOrWhiteSpace(model.Action)) {
                TempData["ErrorMessage"] = "Datos incompletos para actualizar el submodulo.";
                return RedirectToAction("Index");
            }

            if (model.Orden < 0) {
                TempData["ErrorMessage"] = "El orden no puede ser negativo.";
                return RedirectToAction("Index");
            }

            model.Nombre = model.Nombre.Trim();
            model.Controller = model.Controller.Trim();
            model.Action = model.Action.Trim();

            if (model.ExisteNombre(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe otro submodulo con ese nombre dentro del modulo seleccionado.";
                return RedirectToAction("Index");
            }

            if (model.ExisteOrden(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe otro submodulo con ese orden dentro del modulo seleccionado.";
                return RedirectToAction("Index");
            }

            bool actualizado = model.ActualizarSubModulo(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Submodulo actualizado correctamente." : "No fue posible actualizar el submodulo.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult ToggleActivo(int idSubModulos) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (idSubModulos <= 0) {
                TempData["ErrorMessage"] = "No se recibio el submodulo a actualizar.";
                return RedirectToAction("Index");
            }

            subModulosModel model = new subModulosModel {
                IdSubModulos = idSubModulos
            };

            bool actualizado = model.CambiarEstatusSubModulo(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Estatus actualizado correctamente." : "No fue posible actualizar el estatus.";

            return RedirectToAction("Index");
        }

        private IActionResult? ValidarAcceso(string accion) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            if (!_permisosService.TienePermisoPorRuta(idUsuario, "subModulos", accion)) {
                TempData["ErrorMessage"] = "No tienes permiso para realizar esta accion.";
                return RedirectToAction("Index", "Dashboard");
            }

            return null;
        }
    }
}
