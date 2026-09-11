using HELPDESK.Models.Tickets.TipoSoporte;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;

namespace HELPDESK.Controllers.Tickets.TipoSoporte {
    public class tipoSoporteController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;

        public tipoSoporteController(DBConector dbConector, PermisosService permisosService) {
            _dbConector = dbConector;
            _permisosService = permisosService;
        }

        [HttpGet]
        public IActionResult Index() {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            tipoSoporteModel model = CrearModeloPantalla();
            model.SuccessMessage = TempData["SuccessMessage"]?.ToString();
            model.ErrorMessage = TempData["ErrorMessage"]?.ToString();

            return View("~/Views/Tickets/TipoSoporte/tipoSoporte.cshtml", model);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Create(tipoSoporteModel model) {
            IActionResult? acceso = ValidarAcceso("Crear");
            if (acceso != null)
                return acceso;

            if (string.IsNullOrWhiteSpace(model.Descripcion)) {
                TempData["ErrorMessage"] = "La descripcion del tipo de soporte es obligatoria.";
                return RedirectToAction("Index");
            }

            if (!model.IdArea.HasValue || model.IdArea.Value <= 0) {
                TempData["ErrorMessage"] = "Selecciona el area del tipo de soporte.";
                return RedirectToAction("Index");
            }

            model.Descripcion = model.Descripcion.Trim();

            if (model.ExisteDescripcion(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe un tipo de soporte con esa descripcion.";
                return RedirectToAction("Index");
            }

            bool insertado = model.InsertarTipoSoporte(_dbConector);
            TempData[insertado ? "SuccessMessage" : "ErrorMessage"] =
                insertado ? "Tipo de soporte agregado correctamente." : "No fue posible agregar el tipo de soporte.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Edit(tipoSoporteModel model) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (model.IdTipoSoporte <= 0 || string.IsNullOrWhiteSpace(model.Descripcion)) {
                TempData["ErrorMessage"] = "Datos incompletos para actualizar el tipo de soporte.";
                return RedirectToAction("Index");
            }

            if (!model.IdArea.HasValue || model.IdArea.Value <= 0) {
                TempData["ErrorMessage"] = "Selecciona el area del tipo de soporte.";
                return RedirectToAction("Index");
            }

            model.Descripcion = model.Descripcion.Trim();

            if (model.ExisteDescripcion(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe otro tipo de soporte con esa descripcion.";
                return RedirectToAction("Index");
            }

            bool actualizado = model.ActualizarTipoSoporte(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Tipo de soporte actualizado correctamente." : "No fue posible actualizar el tipo de soporte.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult ToggleActivo(int idTipoSoporte) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (idTipoSoporte <= 0) {
                TempData["ErrorMessage"] = "No se recibio el tipo de soporte a actualizar.";
                return RedirectToAction("Index");
            }

            tipoSoporteModel model = new tipoSoporteModel {
                IdTipoSoporte = idTipoSoporte
            };

            bool actualizado = model.CambiarEstatus(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Tipo de soporte actualizado correctamente." : "No fue posible actualizar el tipo de soporte.";

            return RedirectToAction("Index");
        }

        private tipoSoporteModel CrearModeloPantalla() {
            tipoSoporteModel data = new tipoSoporteModel();

            return new tipoSoporteModel {
                TiposSoporte = data.ObtenerTiposSoporte(_dbConector),
                Areas = data.ObtenerAreasActivas(_dbConector)
            };
        }

        private IActionResult? ValidarAcceso(string accion) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            if (!_permisosService.TienePermisoPorRuta(idUsuario, "tipoSoporte", accion)) {
                TempData["ErrorMessage"] = "No tienes permiso para realizar esta accion.";
                return RedirectToAction("Index", "Dashboard");
            }

            return null;
        }
    }
}
