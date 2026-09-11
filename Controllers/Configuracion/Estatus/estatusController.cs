using HELPDESK.Models.Configuracion.Estatus;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;

namespace HELPDESK.Controllers.Configuracion.Estatus {
    public class estatusController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;

        public estatusController(DBConector dbConector, PermisosService permisosService) {
            _dbConector = dbConector;
            _permisosService = permisosService;
        }

        [HttpGet]
        public IActionResult Index() {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            estatusModel model = CrearModeloPantalla();
            model.SuccessMessage = TempData["SuccessMessage"]?.ToString();
            model.ErrorMessage = TempData["ErrorMessage"]?.ToString();

            return View("~/Views/Configuracion/Estatus/estatus.cshtml", model);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Create(estatusModel model) {
            IActionResult? acceso = ValidarAcceso("Crear");
            if (acceso != null)
                return acceso;

            if (string.IsNullOrWhiteSpace(model.Descripcion)) {
                TempData["ErrorMessage"] = "La descripcion del estatus es obligatoria.";
                return RedirectToAction("Index");
            }

            model.Descripcion = model.Descripcion.Trim();

            if (model.ExisteDescripcion(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe un estatus con esa descripcion para el modulo seleccionado.";
                return RedirectToAction("Index");
            }

            bool insertado = model.InsertarEstatus(_dbConector);
            TempData[insertado ? "SuccessMessage" : "ErrorMessage"] =
                insertado ? "Estatus agregado correctamente." : "No fue posible agregar el estatus.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Edit(estatusModel model) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (model.IdEstatus <= 0 || string.IsNullOrWhiteSpace(model.Descripcion)) {
                TempData["ErrorMessage"] = "Datos incompletos para actualizar el estatus.";
                return RedirectToAction("Index");
            }

            model.Descripcion = model.Descripcion.Trim();

            if (model.ExisteDescripcion(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe otro estatus con esa descripcion para el modulo seleccionado.";
                return RedirectToAction("Index");
            }

            bool actualizado = model.ActualizarEstatus(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Estatus actualizado correctamente." : "No fue posible actualizar el estatus.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult ToggleActivo(int idEstatus) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (idEstatus <= 0) {
                TempData["ErrorMessage"] = "No se recibio el estatus a actualizar.";
                return RedirectToAction("Index");
            }

            estatusModel model = new estatusModel {
                IdEstatus = idEstatus
            };

            bool actualizado = model.CambiarEstatus(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Estatus actualizado correctamente." : "No fue posible actualizar el estatus.";

            return RedirectToAction("Index");
        }

        private estatusModel CrearModeloPantalla() {
            estatusModel data = new estatusModel();

            return new estatusModel {
                Estatus = data.ObtenerEstatus(_dbConector),
                Modulos = data.ObtenerModulosActivos(_dbConector)
            };
        }

        private IActionResult? ValidarAcceso(string accion) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            if (!_permisosService.TienePermisoPorRuta(idUsuario, "estatus", accion)) {
                TempData["ErrorMessage"] = "No tienes permiso para realizar esta accion.";
                return RedirectToAction("Index", "Dashboard");
            }

            return null;
        }
    }
}
