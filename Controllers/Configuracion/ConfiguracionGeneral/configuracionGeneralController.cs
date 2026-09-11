using HELPDESK.Models.Configuracion.ConfiguracionGeneral;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;

namespace HELPDESK.Controllers.Configuracion.ConfiguracionGeneral {
    public class configuracionGeneralController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;

        public configuracionGeneralController(DBConector dbConector, PermisosService permisosService) {
            _dbConector = dbConector;
            _permisosService = permisosService;
        }

        [HttpGet]
        public IActionResult Index() {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            configuracionGeneralModel model = CrearModeloPantalla();
            model.SuccessMessage = TempData["SuccessMessage"]?.ToString();
            model.ErrorMessage = TempData["ErrorMessage"]?.ToString();

            return View("~/Views/Configuracion/ConfiguracionGeneral/configuracionGeneral.cshtml", model);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Create(configuracionGeneralModel model) {
            IActionResult? acceso = ValidarAcceso("Crear");
            if (acceso != null)
                return acceso;

            string? error = ValidarModelo(model);
            if (!string.IsNullOrWhiteSpace(error)) {
                TempData["ErrorMessage"] = error;
                return RedirectToAction("Index");
            }

            Normalizar(model);

            if (model.ExisteClave(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe una configuracion con ese nombre de parametro.";
                return RedirectToAction("Index");
            }

            bool insertado = model.InsertarConfiguracion(_dbConector);
            TempData[insertado ? "SuccessMessage" : "ErrorMessage"] =
                insertado ? "Configuracion agregada correctamente." : "No fue posible agregar la configuracion.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Edit(configuracionGeneralModel model) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (model.IdConfiguracionSistema <= 0) {
                TempData["ErrorMessage"] = "No se recibio la configuracion a actualizar.";
                return RedirectToAction("Index");
            }

            string? error = ValidarModelo(model);
            if (!string.IsNullOrWhiteSpace(error)) {
                TempData["ErrorMessage"] = error;
                return RedirectToAction("Index");
            }

            Normalizar(model);

            if (model.ExisteClave(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe otra configuracion con ese nombre de parametro.";
                return RedirectToAction("Index");
            }

            bool actualizado = model.ActualizarConfiguracion(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Configuracion actualizada correctamente." : "No fue posible actualizar la configuracion.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult ToggleActivo(int idConfiguracionSistema) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (idConfiguracionSistema <= 0) {
                TempData["ErrorMessage"] = "No se recibio la configuracion a actualizar.";
                return RedirectToAction("Index");
            }

            configuracionGeneralModel model = new configuracionGeneralModel {
                IdConfiguracionSistema = idConfiguracionSistema
            };

            bool actualizado = model.CambiarEstatus(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Configuracion actualizada correctamente." : "No fue posible actualizar la configuracion.";

            return RedirectToAction("Index");
        }

        private configuracionGeneralModel CrearModeloPantalla() {
            configuracionGeneralModel data = new configuracionGeneralModel();

            return new configuracionGeneralModel {
                Configuraciones = data.ObtenerConfiguraciones(_dbConector)
            };
        }

        private string? ValidarModelo(configuracionGeneralModel model) {
            if (string.IsNullOrWhiteSpace(model.Clave))
                return "El nombre del parametro es obligatorio.";

            if (model.Clave.Trim().Length > 100)
                return "El nombre del parametro no puede superar 100 caracteres.";

            if (string.IsNullOrWhiteSpace(model.Valor))
                return "El valor configurado es obligatorio.";

            if (!string.IsNullOrWhiteSpace(model.Descripcion) && model.Descripcion.Trim().Length > 250)
                return "El campo para que se utiliza no puede superar 250 caracteres.";

            return null;
        }

        private void Normalizar(configuracionGeneralModel model) {
            model.Clave = model.Clave.Trim();
            model.Valor = model.Valor.Trim();
            model.Descripcion = model.Descripcion?.Trim() ?? string.Empty;
        }

        private IActionResult? ValidarAcceso(string accion) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            if (!_permisosService.TienePermisoPorRuta(idUsuario, "configuracionGeneral", accion)) {
                TempData["ErrorMessage"] = "No tienes permiso para realizar esta accion.";
                return RedirectToAction("Index", "Dashboard");
            }

            return null;
        }
    }
}
