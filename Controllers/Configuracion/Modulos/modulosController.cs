using HELPDESK.Models.Configuracion.Modulos;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;

namespace HELPDESK.Controllers.Configuracion.Modulos {
    public class modulosController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;

        public modulosController(DBConector dbConector, PermisosService permisosService) {
            _dbConector = dbConector;
            _permisosService = permisosService;
        }

        [HttpGet]
        public IActionResult Index() {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            modulosModel model = new modulosModel {
                Modulos = new modulosModel().ObtenerModulos(_dbConector),
                SuccessMessage = TempData["SuccessMessage"]?.ToString(),
                ErrorMessage = TempData["ErrorMessage"]?.ToString()
            };

            return View("~/Views/Configuracion/Modulos/modulos.cshtml", model);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Create(modulosModel model) {
            IActionResult? acceso = ValidarAcceso("Crear");
            if (acceso != null)
                return acceso;

            if (string.IsNullOrWhiteSpace(model.Nombre)) {
                TempData["ErrorMessage"] = "El nombre del modulo es obligatorio.";
                return RedirectToAction("Index");
            }

            if (model.Orden < 0) {
                TempData["ErrorMessage"] = "El orden no puede ser negativo.";
                return RedirectToAction("Index");
            }

            model.Nombre = model.Nombre.Trim();

            if (model.ExisteNombre(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe un modulo con ese nombre.";
                return RedirectToAction("Index");
            }

            if (model.ExisteOrden(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe un modulo con ese orden.";
                return RedirectToAction("Index");
            }

            bool insertado = model.InsertarModulo(_dbConector);
            TempData[insertado ? "SuccessMessage" : "ErrorMessage"] =
                insertado ? "Modulo agregado correctamente." : "No fue posible agregar el modulo.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Edit(modulosModel model) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (model.IdModulos <= 0 || string.IsNullOrWhiteSpace(model.Nombre)) {
                TempData["ErrorMessage"] = "Datos incompletos para actualizar el modulo.";
                return RedirectToAction("Index");
            }

            if (model.Orden < 0) {
                TempData["ErrorMessage"] = "El orden no puede ser negativo.";
                return RedirectToAction("Index");
            }

            model.Nombre = model.Nombre.Trim();

            if (model.ExisteNombre(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe otro modulo con ese nombre.";
                return RedirectToAction("Index");
            }

            if (model.ExisteOrden(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe otro modulo con ese orden.";
                return RedirectToAction("Index");
            }

            bool actualizado = model.ActualizarModulo(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Modulo actualizado correctamente." : "No fue posible actualizar el modulo.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult ToggleActivo(int idModulos) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (idModulos <= 0) {
                TempData["ErrorMessage"] = "No se recibio el modulo a actualizar.";
                return RedirectToAction("Index");
            }

            modulosModel model = new modulosModel {
                IdModulos = idModulos
            };

            bool actualizado = model.CambiarEstatusModulo(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Estatus actualizado correctamente." : "No fue posible actualizar el estatus.";

            return RedirectToAction("Index");
        }

        private IActionResult? ValidarAcceso(string accion) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            if (!_permisosService.TienePermisoPorRuta(idUsuario, "modulos", accion)) {
                TempData["ErrorMessage"] = "No tienes permiso para realizar esta accion.";
                return RedirectToAction("Index", "Dashboard");
            }

            return null;
        }
    }
}
