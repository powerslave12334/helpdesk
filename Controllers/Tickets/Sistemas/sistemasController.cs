using HELPDESK.Models.Tickets.Sistemas;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;

namespace HELPDESK.Controllers.Tickets.Sistemas {
    public class sistemasController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;

        public sistemasController(DBConector dbConector, PermisosService permisosService) {
            _dbConector = dbConector;
            _permisosService = permisosService;
        }

        [HttpGet]
        public IActionResult Index() {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            sistemasModel model = new sistemasModel {
                Sistemas = new sistemasModel().ObtenerSistemas(_dbConector),
                SuccessMessage = TempData["SuccessMessage"]?.ToString(),
                ErrorMessage = TempData["ErrorMessage"]?.ToString()
            };

            return View("~/Views/Tickets/Sistemas/sistemas.cshtml", model);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Create(sistemasModel model) {
            IActionResult? acceso = ValidarAcceso("Crear");
            if (acceso != null)
                return acceso;

            if (string.IsNullOrWhiteSpace(model.Nombre)) {
                TempData["ErrorMessage"] = "El nombre del sistema es obligatorio.";
                return RedirectToAction("Index");
            }

            model.Nombre = model.Nombre.Trim();
            if (model.ExisteNombre(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe un sistema con ese nombre.";
                return RedirectToAction("Index");
            }

            bool insertado = model.InsertarSistema(_dbConector);
            TempData[insertado ? "SuccessMessage" : "ErrorMessage"] =
                insertado ? "Sistema agregado correctamente." : "No fue posible agregar el sistema.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Edit(sistemasModel model) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (model.IdSistemas <= 0 || string.IsNullOrWhiteSpace(model.Nombre)) {
                TempData["ErrorMessage"] = "Datos incompletos para actualizar el sistema.";
                return RedirectToAction("Index");
            }

            model.Nombre = model.Nombre.Trim();
            if (model.ExisteNombre(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe otro sistema con ese nombre.";
                return RedirectToAction("Index");
            }

            bool actualizado = model.ActualizarSistema(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Sistema actualizado correctamente." : "No fue posible actualizar el sistema.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult ToggleEnabled(int idSistemas) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (idSistemas <= 0) {
                TempData["ErrorMessage"] = "No se recibio el sistema a actualizar.";
                return RedirectToAction("Index");
            }

            bool actualizado = new sistemasModel { IdSistemas = idSistemas }.CambiarEstatus(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Sistema actualizado correctamente." : "No fue posible actualizar el sistema.";

            return RedirectToAction("Index");
        }

        private IActionResult? ValidarAcceso(string accion) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            if (!_permisosService.TienePermisoPorRuta(idUsuario, "sistemas", accion)) {
                TempData["ErrorMessage"] = "No tienes permiso para realizar esta accion.";
                return RedirectToAction("Index", "Dashboard");
            }

            return null;
        }
    }
}
