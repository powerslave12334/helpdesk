using HELPDESK.Models.Inventario.EquipoCampo;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;

namespace HELPDESK.Controllers.Inventario.EquipoCampo {
    public class equipoCampoController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;

        public equipoCampoController(DBConector dbConector, PermisosService permisosService) {
            _dbConector = dbConector;
            _permisosService = permisosService;
        }

        [HttpGet]
        public IActionResult Index() {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            equipoCampoModel model = CrearModeloPantalla();
            model.SuccessMessage = TempData["SuccessMessage"]?.ToString();
            model.ErrorMessage = TempData["ErrorMessage"]?.ToString();

            return View("~/Views/Inventario/EquipoCampo/equipoCampo.cshtml", model);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Create(equipoCampoModel model) {
            IActionResult? acceso = ValidarAcceso("Crear");
            if (acceso != null)
                return acceso;

            if (string.IsNullOrWhiteSpace(model.Nombre)) {
                TempData["ErrorMessage"] = "El nombre del campo es obligatorio.";
                return RedirectToAction("Index");
            }

            model.Nombre = model.Nombre.Trim();
            model.Descripcion = model.Descripcion?.Trim() ?? string.Empty;

            if (model.ExisteNombre(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe un campo de equipo con ese nombre.";
                return RedirectToAction("Index");
            }

            bool insertado = model.InsertarCampo(_dbConector);
            TempData[insertado ? "SuccessMessage" : "ErrorMessage"] =
                insertado ? "Campo de equipo agregado correctamente." : "No fue posible agregar el campo de equipo.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Edit(equipoCampoModel model) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (model.IdEquipoCampo <= 0 || string.IsNullOrWhiteSpace(model.Nombre)) {
                TempData["ErrorMessage"] = "Datos incompletos para actualizar el campo de equipo.";
                return RedirectToAction("Index");
            }

            model.Nombre = model.Nombre.Trim();
            model.Descripcion = model.Descripcion?.Trim() ?? string.Empty;

            if (model.ExisteNombre(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe otro campo de equipo con ese nombre.";
                return RedirectToAction("Index");
            }

            bool actualizado = model.ActualizarCampo(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Campo de equipo actualizado correctamente." : "No fue posible actualizar el campo de equipo.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult ToggleActivo(int idEquipoCampo) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (idEquipoCampo <= 0) {
                TempData["ErrorMessage"] = "No se recibio el campo de equipo a actualizar.";
                return RedirectToAction("Index");
            }

            equipoCampoModel model = new equipoCampoModel {
                IdEquipoCampo = idEquipoCampo
            };

            bool actualizado = model.CambiarEstatus(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Campo de equipo actualizado correctamente." : "No fue posible actualizar el campo de equipo.";

            return RedirectToAction("Index");
        }

        private equipoCampoModel CrearModeloPantalla() {
            equipoCampoModel data = new equipoCampoModel();

            return new equipoCampoModel {
                Campos = data.ObtenerCampos(_dbConector)
            };
        }

        private IActionResult? ValidarAcceso(string accion) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            if (!_permisosService.TienePermisoPorRuta(idUsuario, "equipoCampo", accion)) {
                TempData["ErrorMessage"] = "No tienes permiso para realizar esta accion.";
                return RedirectToAction("Index", "Dashboard");
            }

            return null;
        }
    }
}
