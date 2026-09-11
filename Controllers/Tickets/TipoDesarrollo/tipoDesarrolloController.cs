using HELPDESK.Models.Tickets.TipoDesarrollo;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;

namespace HELPDESK.Controllers.Tickets.TipoDesarrollo {
    public class tipoDesarrolloController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;

        public tipoDesarrolloController(DBConector dbConector, PermisosService permisosService) {
            _dbConector = dbConector;
            _permisosService = permisosService;
        }

        [HttpGet]
        public IActionResult Index() {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            tipoDesarrolloModel model = new tipoDesarrolloModel {
                TiposDesarrollo = new tipoDesarrolloModel().ObtenerTiposDesarrollo(_dbConector),
                SuccessMessage = TempData["SuccessMessage"]?.ToString(),
                ErrorMessage = TempData["ErrorMessage"]?.ToString()
            };

            return View("~/Views/Tickets/TipoDesarrollo/tipoDesarrollo.cshtml", model);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Create(tipoDesarrolloModel model) {
            IActionResult? acceso = ValidarAcceso("Crear");
            if (acceso != null)
                return acceso;

            if (string.IsNullOrWhiteSpace(model.Nombre)) {
                TempData["ErrorMessage"] = "El nombre del tipo de desarrollo es obligatorio.";
                return RedirectToAction("Index");
            }

            model.Nombre = model.Nombre.Trim();
            model.Descripcion = model.Descripcion?.Trim() ?? string.Empty;
            if (model.ExisteNombre(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe un tipo de desarrollo con ese nombre.";
                return RedirectToAction("Index");
            }

            bool insertado = model.InsertarTipoDesarrollo(_dbConector);
            TempData[insertado ? "SuccessMessage" : "ErrorMessage"] =
                insertado ? "Tipo de desarrollo agregado correctamente." : "No fue posible agregar el tipo de desarrollo.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Edit(tipoDesarrolloModel model) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (model.IdTipoDesarrollo <= 0 || string.IsNullOrWhiteSpace(model.Nombre)) {
                TempData["ErrorMessage"] = "Datos incompletos para actualizar el tipo de desarrollo.";
                return RedirectToAction("Index");
            }

            model.Nombre = model.Nombre.Trim();
            model.Descripcion = model.Descripcion?.Trim() ?? string.Empty;
            if (model.ExisteNombre(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe otro tipo de desarrollo con ese nombre.";
                return RedirectToAction("Index");
            }

            bool actualizado = model.ActualizarTipoDesarrollo(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Tipo de desarrollo actualizado correctamente." : "No fue posible actualizar el tipo de desarrollo.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult ToggleEnabled(int idTipoDesarrollo) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (idTipoDesarrollo <= 0) {
                TempData["ErrorMessage"] = "No se recibio el tipo de desarrollo a actualizar.";
                return RedirectToAction("Index");
            }

            bool actualizado = new tipoDesarrolloModel { IdTipoDesarrollo = idTipoDesarrollo }.CambiarEstatus(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Tipo de desarrollo actualizado correctamente." : "No fue posible actualizar el tipo de desarrollo.";

            return RedirectToAction("Index");
        }

        private IActionResult? ValidarAcceso(string accion) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            if (!_permisosService.TienePermisoPorRuta(idUsuario, "tipoDesarrollo", accion)) {
                TempData["ErrorMessage"] = "No tienes permiso para realizar esta accion.";
                return RedirectToAction("Index", "Dashboard");
            }

            return null;
        }
    }
}
