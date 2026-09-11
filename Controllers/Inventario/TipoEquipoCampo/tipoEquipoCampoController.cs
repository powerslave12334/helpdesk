using HELPDESK.Models.Inventario.TipoEquipoCampo;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;

namespace HELPDESK.Controllers.Inventario.TipoEquipoCampo {
    public class tipoEquipoCampoController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;

        public tipoEquipoCampoController(DBConector dbConector, PermisosService permisosService) {
            _dbConector = dbConector;
            _permisosService = permisosService;
        }

        [HttpGet]
        public IActionResult Index() {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            tipoEquipoCampoModel model = CrearModeloPantalla();
            model.SuccessMessage = TempData["SuccessMessage"]?.ToString();
            model.ErrorMessage = TempData["ErrorMessage"]?.ToString();

            return View("~/Views/Inventario/TipoEquipoCampo/tipoEquipoCampo.cshtml", model);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Create(tipoEquipoCampoModel model) {
            IActionResult? acceso = ValidarAcceso("Crear");
            if (acceso != null)
                return acceso;

            if (model.IdTipoEquipo <= 0 || model.IdEquipoCampo <= 0) {
                TempData["ErrorMessage"] = "Seleccione el tipo de equipo y el campo.";
                return RedirectToAction("Index");
            }

            if (model.ExisteRelacion(_dbConector)) {
                TempData["ErrorMessage"] = "Ese campo ya esta relacionado con el tipo de equipo seleccionado.";
                return RedirectToAction("Index");
            }

            bool insertado = model.InsertarRelacion(_dbConector);
            TempData[insertado ? "SuccessMessage" : "ErrorMessage"] =
                insertado ? "Relacion agregada correctamente." : "No fue posible agregar la relacion.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Edit(tipoEquipoCampoModel model) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (model.IdTipoEquipoCampo <= 0 || model.IdTipoEquipo <= 0 || model.IdEquipoCampo <= 0) {
                TempData["ErrorMessage"] = "Datos incompletos para actualizar la relacion.";
                return RedirectToAction("Index");
            }

            if (model.ExisteRelacion(_dbConector)) {
                TempData["ErrorMessage"] = "Ese campo ya esta relacionado con el tipo de equipo seleccionado.";
                return RedirectToAction("Index");
            }

            bool actualizado = model.ActualizarRelacion(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Relacion actualizada correctamente." : "No fue posible actualizar la relacion.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult ToggleActivo(int idTipoEquipoCampo) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (idTipoEquipoCampo <= 0) {
                TempData["ErrorMessage"] = "No se recibio la relacion a actualizar.";
                return RedirectToAction("Index");
            }

            tipoEquipoCampoModel model = new tipoEquipoCampoModel {
                IdTipoEquipoCampo = idTipoEquipoCampo
            };

            bool actualizado = model.CambiarEstatus(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Relacion actualizada correctamente." : "No fue posible actualizar la relacion.";

            return RedirectToAction("Index");
        }

        private tipoEquipoCampoModel CrearModeloPantalla() {
            tipoEquipoCampoModel data = new tipoEquipoCampoModel();

            return new tipoEquipoCampoModel {
                Relaciones = data.ObtenerRelaciones(_dbConector),
                TiposEquipo = data.ObtenerTiposEquipoActivos(_dbConector),
                Campos = data.ObtenerCamposActivos(_dbConector)
            };
        }

        private IActionResult? ValidarAcceso(string accion) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            if (!_permisosService.TienePermisoPorRuta(idUsuario, "tipoEquipoCampo", accion)) {
                TempData["ErrorMessage"] = "No tienes permiso para realizar esta accion.";
                return RedirectToAction("Index", "Dashboard");
            }

            return null;
        }
    }
}
