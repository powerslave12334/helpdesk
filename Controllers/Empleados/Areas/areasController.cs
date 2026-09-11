using HELPDESK.Models.Empleados.Areas;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;

namespace HELPDESK.Controllers.Empleados.Areas {
    public class areasController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;

        public areasController(DBConector dbConector, PermisosService permisosService) {
            _dbConector = dbConector;
            _permisosService = permisosService;
        }

        [HttpGet]
        public IActionResult Index() {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            areasModel model = CrearModeloPantalla();
            model.SuccessMessage = TempData["SuccessMessage"]?.ToString();
            model.ErrorMessage = TempData["ErrorMessage"]?.ToString();

            return View("~/Views/Empleados/Areas/areas.cshtml", model);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Create(areasModel model) {
            IActionResult? acceso = ValidarAcceso("Crear");
            if (acceso != null)
                return acceso;

            if (string.IsNullOrWhiteSpace(model.Nombre)) {
                TempData["ErrorMessage"] = "El nombre del area es obligatorio.";
                return RedirectToAction("Index");
            }

            model.Nombre = model.Nombre.Trim();

            if (model.ExisteNombre(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe un area con ese nombre.";
                return RedirectToAction("Index");
            }

            bool insertado = model.InsertarArea(_dbConector);
            TempData[insertado ? "SuccessMessage" : "ErrorMessage"] =
                insertado ? "Area agregada correctamente." : "No fue posible agregar el area.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Edit(areasModel model) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (model.IdArea <= 0 || string.IsNullOrWhiteSpace(model.Nombre)) {
                TempData["ErrorMessage"] = "Datos incompletos para actualizar el area.";
                return RedirectToAction("Index");
            }

            model.Nombre = model.Nombre.Trim();

            if (model.ExisteNombre(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe otra area con ese nombre.";
                return RedirectToAction("Index");
            }

            bool actualizado = model.ActualizarArea(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Area actualizada correctamente." : "No fue posible actualizar el area.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult ToggleActivo(int idArea) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (idArea <= 0) {
                TempData["ErrorMessage"] = "No se recibio el area a actualizar.";
                return RedirectToAction("Index");
            }

            areasModel model = new areasModel {
                IdArea = idArea
            };

            bool actualizado = model.CambiarEstatus(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Area actualizada correctamente." : "No fue posible actualizar el area.";

            return RedirectToAction("Index");
        }

        private areasModel CrearModeloPantalla() {
            areasModel data = new areasModel();

            return new areasModel {
                Areas = data.ObtenerAreas(_dbConector)
            };
        }

        private IActionResult? ValidarAcceso(string accion) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            if (!_permisosService.TienePermisoPorRuta(idUsuario, "areas", accion)) {
                TempData["ErrorMessage"] = "No tienes permiso para realizar esta accion.";
                return RedirectToAction("Index", "Dashboard");
            }

            return null;
        }
    }
}
