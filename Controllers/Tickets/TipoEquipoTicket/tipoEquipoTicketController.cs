using HELPDESK.Models.Tickets.TipoEquipoTicket;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;

namespace HELPDESK.Controllers.Tickets.TipoEquipoTicket {
    public class tipoEquipoTicketController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;

        public tipoEquipoTicketController(DBConector dbConector, PermisosService permisosService) {
            _dbConector = dbConector;
            _permisosService = permisosService;
        }

        [HttpGet]
        public IActionResult Index() {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            tipoEquipoTicketModel model = CrearModeloPantalla();
            model.SuccessMessage = TempData["SuccessMessage"]?.ToString();
            model.ErrorMessage = TempData["ErrorMessage"]?.ToString();

            return View("~/Views/Tickets/TipoEquipoTicket/tipoEquipoTicket.cshtml", model);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Create(tipoEquipoTicketModel model) {
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
                TempData["ErrorMessage"] = "Ya existe un tipo de equipo ticket con esa clave.";
                return RedirectToAction("Index");
            }

            bool insertado = model.InsertarTipoEquipoTicket(_dbConector);
            TempData[insertado ? "SuccessMessage" : "ErrorMessage"] =
                insertado ? "Tipo de equipo ticket agregado correctamente." : "No fue posible agregar el tipo de equipo ticket.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Edit(tipoEquipoTicketModel model) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (model.IdTipoEquipoTicket <= 0) {
                TempData["ErrorMessage"] = "No se recibio el tipo de equipo ticket a actualizar.";
                return RedirectToAction("Index");
            }

            string? error = ValidarModelo(model);
            if (!string.IsNullOrWhiteSpace(error)) {
                TempData["ErrorMessage"] = error;
                return RedirectToAction("Index");
            }

            Normalizar(model);

            if (model.ExisteClave(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe otro tipo de equipo ticket con esa clave.";
                return RedirectToAction("Index");
            }

            bool actualizado = model.ActualizarTipoEquipoTicket(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Tipo de equipo ticket actualizado correctamente." : "No fue posible actualizar el tipo de equipo ticket.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult ToggleActivo(int idTipoEquipoTicket) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (idTipoEquipoTicket <= 0) {
                TempData["ErrorMessage"] = "No se recibio el tipo de equipo ticket a actualizar.";
                return RedirectToAction("Index");
            }

            tipoEquipoTicketModel model = new tipoEquipoTicketModel {
                IdTipoEquipoTicket = idTipoEquipoTicket
            };

            bool actualizado = model.CambiarEstatus(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Tipo de equipo ticket actualizado correctamente." : "No fue posible actualizar el tipo de equipo ticket.";

            return RedirectToAction("Index");
        }

        private tipoEquipoTicketModel CrearModeloPantalla() {
            tipoEquipoTicketModel data = new tipoEquipoTicketModel();

            return new tipoEquipoTicketModel {
                TiposEquipoTicket = data.ObtenerTiposEquipoTicket(_dbConector)
            };
        }

        private string? ValidarModelo(tipoEquipoTicketModel model) {
            if (string.IsNullOrWhiteSpace(model.Clave))
                return "La clave es obligatoria.";

            if (model.Clave.Trim().Length > 20)
                return "La clave no puede superar 20 caracteres.";

            if (string.IsNullOrWhiteSpace(model.Descripcion))
                return "La descripcion es obligatoria.";

            if (model.Descripcion.Trim().Length > 100)
                return "La descripcion no puede superar 100 caracteres.";

            return null;
        }

        private void Normalizar(tipoEquipoTicketModel model) {
            model.Clave = model.Clave.Trim().ToUpperInvariant();
            model.Descripcion = model.Descripcion.Trim();
        }

        private IActionResult? ValidarAcceso(string accion) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            if (!_permisosService.TienePermisoPorRuta(idUsuario, "tipoEquipoTicket", accion)) {
                TempData["ErrorMessage"] = "No tienes permiso para realizar esta accion.";
                return RedirectToAction("Index", "Dashboard");
            }

            return null;
        }
    }
}
