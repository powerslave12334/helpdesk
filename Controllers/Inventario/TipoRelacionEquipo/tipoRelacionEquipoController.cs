using HELPDESK.Models.Inventario.TipoRelacionEquipo;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;

namespace HELPDESK.Controllers.Inventario.TipoRelacionEquipo {
    public class tipoRelacionEquipoController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;

        public tipoRelacionEquipoController(DBConector dbConector, PermisosService permisosService) {
            _dbConector = dbConector;
            _permisosService = permisosService;
        }

        [HttpGet]
        public IActionResult Index() {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            tipoRelacionEquipoModel model = CrearModeloPantalla();
            model.SuccessMessage = TempData["SuccessMessage"]?.ToString();
            model.ErrorMessage = TempData["ErrorMessage"]?.ToString();

            return View("~/Views/Inventario/TipoRelacionEquipo/tipoRelacionEquipo.cshtml", model);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Create(tipoRelacionEquipoModel model) {
            IActionResult? acceso = ValidarAcceso("Crear");
            if (acceso != null)
                return acceso;

            if (string.IsNullOrWhiteSpace(model.Descripcion)) {
                TempData["ErrorMessage"] = "La descripcion del tipo de relacion es obligatoria.";
                return RedirectToAction("Index");
            }

            model.Descripcion = model.Descripcion.Trim();

            if (model.ExisteDescripcion(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe un tipo de relacion con esa descripcion.";
                return RedirectToAction("Index");
            }

            bool insertado = model.InsertarTipoRelacion(_dbConector);
            TempData[insertado ? "SuccessMessage" : "ErrorMessage"] =
                insertado ? "Tipo de relacion agregado correctamente." : "No fue posible agregar el tipo de relacion.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Edit(tipoRelacionEquipoModel model) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (model.IdTipoRelacionEquipo <= 0 || string.IsNullOrWhiteSpace(model.Descripcion)) {
                TempData["ErrorMessage"] = "Datos incompletos para actualizar el tipo de relacion.";
                return RedirectToAction("Index");
            }

            model.Descripcion = model.Descripcion.Trim();

            if (model.ExisteDescripcion(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe otro tipo de relacion con esa descripcion.";
                return RedirectToAction("Index");
            }

            bool actualizado = model.ActualizarTipoRelacion(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Tipo de relacion actualizado correctamente." : "No fue posible actualizar el tipo de relacion.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult ToggleActivo(int idTipoRelacionEquipo) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (idTipoRelacionEquipo <= 0) {
                TempData["ErrorMessage"] = "No se recibio el tipo de relacion a actualizar.";
                return RedirectToAction("Index");
            }

            tipoRelacionEquipoModel model = new tipoRelacionEquipoModel {
                IdTipoRelacionEquipo = idTipoRelacionEquipo
            };

            bool actualizado = model.CambiarEstatus(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Tipo de relacion actualizado correctamente." : "No fue posible actualizar el tipo de relacion.";

            return RedirectToAction("Index");
        }

        private tipoRelacionEquipoModel CrearModeloPantalla() {
            tipoRelacionEquipoModel data = new tipoRelacionEquipoModel();

            return new tipoRelacionEquipoModel {
                TiposRelacion = data.ObtenerTiposRelacion(_dbConector)
            };
        }

        private IActionResult? ValidarAcceso(string accion) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            if (!_permisosService.TienePermisoPorRuta(idUsuario, "tipoRelacionEquipo", accion)) {
                TempData["ErrorMessage"] = "No tienes permiso para realizar esta accion.";
                return RedirectToAction("Index", "Dashboard");
            }

            return null;
        }
    }
}
