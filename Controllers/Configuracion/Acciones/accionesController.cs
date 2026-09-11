using HELPDESK.Models.Configuracion.Acciones;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;

namespace HELPDESK.Controllers.Configuracion.Acciones {
    public class accionesController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;

        public accionesController(DBConector dbConector, PermisosService permisosService) {
            _dbConector = dbConector;
            _permisosService = permisosService;
        }

        [HttpGet]
        public IActionResult Index() {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            accionesModel model = new accionesModel {
                Acciones = new accionesModel().ObtenerAcciones(_dbConector),
                SuccessMessage = TempData["SuccessMessage"]?.ToString(),
                ErrorMessage = TempData["ErrorMessage"]?.ToString()
            };

            return View("~/Views/Configuracion/Acciones/acciones.cshtml", model);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Create(accionesModel model) {
            IActionResult? acceso = ValidarAcceso("Crear");
            if (acceso != null)
                return acceso;

            if (string.IsNullOrWhiteSpace(model.Nombre)) {
                TempData["ErrorMessage"] = "El nombre de la accion es obligatorio.";
                return RedirectToAction("Index");
            }

            model.Nombre = model.Nombre.Trim();

            if (model.ExisteNombre(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe una accion con ese nombre.";
                return RedirectToAction("Index");
            }

            bool insertado = model.InsertarAccion(_dbConector);
            TempData[insertado ? "SuccessMessage" : "ErrorMessage"] =
                insertado ? "Accion agregada correctamente." : "No fue posible agregar la accion.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Edit(accionesModel model) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (model.IdAccion <= 0 || string.IsNullOrWhiteSpace(model.Nombre)) {
                TempData["ErrorMessage"] = "Datos incompletos para actualizar la accion.";
                return RedirectToAction("Index");
            }

            model.Nombre = model.Nombre.Trim();

            if (model.ExisteNombre(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe otra accion con ese nombre.";
                return RedirectToAction("Index");
            }

            bool actualizado = model.ActualizarAccion(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Accion actualizada correctamente." : "No fue posible actualizar la accion.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult ToggleActivo(int idAccion) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (idAccion <= 0) {
                TempData["ErrorMessage"] = "No se recibio la accion a actualizar.";
                return RedirectToAction("Index");
            }

            accionesModel model = new accionesModel {
                IdAccion = idAccion
            };

            bool actualizado = model.CambiarEstatusAccion(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Estatus actualizado correctamente." : "No fue posible actualizar el estatus.";

            return RedirectToAction("Index");
        }

        private IActionResult? ValidarAcceso(string accion) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            if (!_permisosService.TienePermisoPorRuta(idUsuario, "acciones", accion)) {
                TempData["ErrorMessage"] = "No tienes permiso para realizar esta accion.";
                return RedirectToAction("Index", "Dashboard");
            }

            return null;
        }
    }
}
