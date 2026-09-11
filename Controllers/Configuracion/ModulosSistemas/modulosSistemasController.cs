using HELPDESK.Models.Configuracion.ModulosSistemas;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;

namespace HELPDESK.Controllers.Configuracion.ModulosSistemas {
    public class modulosSistemasController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;

        public modulosSistemasController(DBConector dbConector, PermisosService permisosService) {
            _dbConector = dbConector;
            _permisosService = permisosService;
        }

        [HttpGet]
        public IActionResult Index() {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            modulosSistemasModel data = new modulosSistemasModel();
            modulosSistemasModel model = new modulosSistemasModel {
                ModulosSistemas = data.ObtenerModulosSistemas(_dbConector),
                Sistemas = data.ObtenerSistemasActivos(_dbConector),
                SuccessMessage = TempData["SuccessMessage"]?.ToString(),
                ErrorMessage = TempData["ErrorMessage"]?.ToString()
            };

            return View("~/Views/Configuracion/ModulosSistemas/modulosSistemas.cshtml", model);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Create(modulosSistemasModel model) {
            IActionResult? acceso = ValidarAcceso("Crear");
            if (acceso != null)
                return acceso;

            if (string.IsNullOrWhiteSpace(model.Nombre) || model.IdSistemas <= 0) {
                TempData["ErrorMessage"] = "Nombre del modulo y sistema son obligatorios.";
                return RedirectToAction("Index");
            }

            model.Nombre = model.Nombre.Trim();
            if (model.ExisteNombre(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe un modulo con ese nombre para el sistema seleccionado.";
                return RedirectToAction("Index");
            }

            bool insertado = model.InsertarModuloSistema(_dbConector);
            TempData[insertado ? "SuccessMessage" : "ErrorMessage"] =
                insertado ? "Modulo de sistema agregado correctamente." : "No fue posible agregar el modulo de sistema.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Edit(modulosSistemasModel model) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (model.IdModulosSistemas <= 0 || string.IsNullOrWhiteSpace(model.Nombre) || model.IdSistemas <= 0) {
                TempData["ErrorMessage"] = "Datos incompletos para actualizar el modulo de sistema.";
                return RedirectToAction("Index");
            }

            model.Nombre = model.Nombre.Trim();
            if (model.ExisteNombre(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe otro modulo con ese nombre para el sistema seleccionado.";
                return RedirectToAction("Index");
            }

            bool actualizado = model.ActualizarModuloSistema(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Modulo de sistema actualizado correctamente." : "No fue posible actualizar el modulo de sistema.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult ToggleEnabled(int idModulosSistemas) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (idModulosSistemas <= 0) {
                TempData["ErrorMessage"] = "No se recibio el modulo de sistema a actualizar.";
                return RedirectToAction("Index");
            }

            bool actualizado = new modulosSistemasModel { IdModulosSistemas = idModulosSistemas }.CambiarEstatus(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Modulo de sistema actualizado correctamente." : "No fue posible actualizar el modulo de sistema.";

            return RedirectToAction("Index");
        }

        private IActionResult? ValidarAcceso(string accion) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            if (!_permisosService.TienePermisoPorRuta(idUsuario, "modulosSistemas", accion)) {
                TempData["ErrorMessage"] = "No tienes permiso para realizar esta accion.";
                return RedirectToAction("Index", "Dashboard");
            }

            return null;
        }
    }
}
