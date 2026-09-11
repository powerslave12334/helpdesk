using HELPDESK.Models.Inventario.TipoEquipo;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;

namespace HELPDESK.Controllers.Inventario.TipoEquipo {
    public class tipoEquipoController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;

        public tipoEquipoController(DBConector dbConector, PermisosService permisosService) {
            _dbConector = dbConector;
            _permisosService = permisosService;
        }

        [HttpGet]
        public IActionResult Index() {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            tipoEquipoModel model = CrearModeloPantalla();
            model.SuccessMessage = TempData["SuccessMessage"]?.ToString();
            model.ErrorMessage = TempData["ErrorMessage"]?.ToString();

            return View("~/Views/Inventario/TipoEquipo/tipoEquipo.cshtml", model);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Create(tipoEquipoModel model) {
            IActionResult? acceso = ValidarAcceso("Crear");
            if (acceso != null)
                return acceso;

            if (string.IsNullOrWhiteSpace(model.Descripcion)) {
                TempData["ErrorMessage"] = "La descripcion del tipo de equipo es obligatoria.";
                return RedirectToAction("Index");
            }

            model.Descripcion = model.Descripcion.Trim();

            if (model.ExisteDescripcion(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe un tipo de equipo con esa descripcion.";
                return RedirectToAction("Index");
            }

            bool insertado = model.InsertarTipoEquipo(_dbConector);
            TempData[insertado ? "SuccessMessage" : "ErrorMessage"] =
                insertado ? "Tipo de equipo agregado correctamente." : "No fue posible agregar el tipo de equipo.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Edit(tipoEquipoModel model) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (model.IdTipoEquipo <= 0 || string.IsNullOrWhiteSpace(model.Descripcion)) {
                TempData["ErrorMessage"] = "Datos incompletos para actualizar el tipo de equipo.";
                return RedirectToAction("Index");
            }

            model.Descripcion = model.Descripcion.Trim();

            if (model.ExisteDescripcion(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe otro tipo de equipo con esa descripcion.";
                return RedirectToAction("Index");
            }

            bool actualizado = model.ActualizarTipoEquipo(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Tipo de equipo actualizado correctamente." : "No fue posible actualizar el tipo de equipo.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult ToggleActivo(int idTipoEquipo) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (idTipoEquipo <= 0) {
                TempData["ErrorMessage"] = "No se recibio el tipo de equipo a actualizar.";
                return RedirectToAction("Index");
            }

            tipoEquipoModel model = new tipoEquipoModel {
                IdTipoEquipo = idTipoEquipo
            };

            bool actualizado = model.CambiarEstatus(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Tipo de equipo actualizado correctamente." : "No fue posible actualizar el tipo de equipo.";

            return RedirectToAction("Index");
        }

        private tipoEquipoModel CrearModeloPantalla() {
            tipoEquipoModel data = new tipoEquipoModel();

            return new tipoEquipoModel {
                TiposEquipo = data.ObtenerTiposEquipo(_dbConector)
            };
        }

        private IActionResult? ValidarAcceso(string accion) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            if (!_permisosService.TienePermisoPorRuta(idUsuario, "tipoEquipo", accion)) {
                TempData["ErrorMessage"] = "No tienes permiso para realizar esta accion.";
                return RedirectToAction("Index", "Dashboard");
            }

            return null;
        }
    }
}
