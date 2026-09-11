using HELPDESK.Models.Configuracion.Oficinas;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;

namespace HELPDESK.Controllers.Configuracion.Oficinas {
    public class oficinasController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;

        public oficinasController(DBConector dbConector, PermisosService permisosService) {
            _dbConector = dbConector;
            _permisosService = permisosService;
        }

        [HttpGet]
        public IActionResult Index() {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            oficinasModel model = CrearModeloPantalla();
            model.SuccessMessage = TempData["SuccessMessage"]?.ToString();
            model.ErrorMessage = TempData["ErrorMessage"]?.ToString();

            return View("~/Views/Configuracion/Oficinas/oficinas.cshtml", model);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Create(oficinasModel model) {
            IActionResult? acceso = ValidarAcceso("Crear");
            if (acceso != null)
                return acceso;

            string? error = ValidarModelo(model);
            if (!string.IsNullOrWhiteSpace(error)) {
                TempData["ErrorMessage"] = error;
                return RedirectToAction("Index");
            }

            Normalizar(model);

            if (model.ExisteNombre(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe una oficina con esa clave.";
                return RedirectToAction("Index");
            }

            bool insertado = model.InsertarOficina(_dbConector);
            TempData[insertado ? "SuccessMessage" : "ErrorMessage"] =
                insertado ? "Oficina agregada correctamente." : "No fue posible agregar la oficina.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Edit(oficinasModel model) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (model.IdOficinas <= 0) {
                TempData["ErrorMessage"] = "No se recibio la oficina a actualizar.";
                return RedirectToAction("Index");
            }

            string? error = ValidarModelo(model);
            if (!string.IsNullOrWhiteSpace(error)) {
                TempData["ErrorMessage"] = error;
                return RedirectToAction("Index");
            }

            Normalizar(model);

            if (model.ExisteNombre(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe otra oficina con esa clave.";
                return RedirectToAction("Index");
            }

            bool actualizado = model.ActualizarOficina(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Oficina actualizada correctamente." : "No fue posible actualizar la oficina.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult ToggleActivo(int idOficinas) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (idOficinas <= 0) {
                TempData["ErrorMessage"] = "No se recibio la oficina a actualizar.";
                return RedirectToAction("Index");
            }

            oficinasModel model = new oficinasModel {
                IdOficinas = idOficinas
            };

            bool actualizado = model.CambiarEstatus(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Oficina actualizada correctamente." : "No fue posible actualizar la oficina.";

            return RedirectToAction("Index");
        }

        private oficinasModel CrearModeloPantalla() {
            oficinasModel data = new oficinasModel();

            return new oficinasModel {
                Oficinas = data.ObtenerOficinas(_dbConector)
            };
        }

        private string? ValidarModelo(oficinasModel model) {
            if (string.IsNullOrWhiteSpace(model.Nombre))
                return "La clave de la oficina es obligatoria.";

            if (model.Nombre.Trim().Length > 5)
                return "La clave de la oficina no puede superar 5 caracteres.";

            if (string.IsNullOrWhiteSpace(model.Descripcion))
                return "La descripcion de la oficina es obligatoria.";

            return null;
        }

        private void Normalizar(oficinasModel model) {
            model.Nombre = model.Nombre.Trim().ToUpperInvariant();
            model.Descripcion = model.Descripcion.Trim();
        }

        private IActionResult? ValidarAcceso(string accion) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            if (!_permisosService.TienePermisoPorRuta(idUsuario, "oficinas", accion)) {
                TempData["ErrorMessage"] = "No tienes permiso para realizar esta accion.";
                return RedirectToAction("Index", "Dashboard");
            }

            return null;
        }
    }
}
