using HELPDESK.Models.Tickets.CategoriaSoporte;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;

namespace HELPDESK.Controllers.Tickets.CategoriaSoporte {
    public class categoriaSoporteController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;

        public categoriaSoporteController(DBConector dbConector, PermisosService permisosService) {
            _dbConector = dbConector;
            _permisosService = permisosService;
        }

        [HttpGet]
        public IActionResult Index() {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            categoriaSoporteModel model = CrearModeloPantalla();
            model.SuccessMessage = TempData["SuccessMessage"]?.ToString();
            model.ErrorMessage = TempData["ErrorMessage"]?.ToString();

            return View("~/Views/Tickets/CategoriaSoporte/categoriaSoporte.cshtml", model);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Create(categoriaSoporteModel model) {
            IActionResult? acceso = ValidarAcceso("Crear");
            if (acceso != null)
                return acceso;

            string? error = ValidarModelo(model);
            if (!string.IsNullOrWhiteSpace(error)) {
                TempData["ErrorMessage"] = error;
                return RedirectToAction("Index");
            }

            Normalizar(model);

            if (model.ExisteDescripcion(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe una categoria con esa descripcion para el tipo de soporte seleccionado.";
                return RedirectToAction("Index");
            }

            bool insertado = model.InsertarCategoriaSoporte(_dbConector);
            TempData[insertado ? "SuccessMessage" : "ErrorMessage"] =
                insertado ? "Categoria de soporte agregada correctamente." : "No fue posible agregar la categoria de soporte.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Edit(categoriaSoporteModel model) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (model.IdCategoriaSoporte <= 0) {
                TempData["ErrorMessage"] = "No se recibio la categoria de soporte a actualizar.";
                return RedirectToAction("Index");
            }

            string? error = ValidarModelo(model);
            if (!string.IsNullOrWhiteSpace(error)) {
                TempData["ErrorMessage"] = error;
                return RedirectToAction("Index");
            }

            Normalizar(model);

            if (model.ExisteDescripcion(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe otra categoria con esa descripcion para el tipo de soporte seleccionado.";
                return RedirectToAction("Index");
            }

            bool actualizado = model.ActualizarCategoriaSoporte(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Categoria de soporte actualizada correctamente." : "No fue posible actualizar la categoria de soporte.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult ToggleActivo(int idCategoriaSoporte) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (idCategoriaSoporte <= 0) {
                TempData["ErrorMessage"] = "No se recibio la categoria de soporte a actualizar.";
                return RedirectToAction("Index");
            }

            categoriaSoporteModel model = new categoriaSoporteModel {
                IdCategoriaSoporte = idCategoriaSoporte
            };

            bool actualizado = model.CambiarEstatus(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Categoria de soporte actualizada correctamente." : "No fue posible actualizar la categoria de soporte.";

            return RedirectToAction("Index");
        }

        private categoriaSoporteModel CrearModeloPantalla() {
            categoriaSoporteModel data = new categoriaSoporteModel();

            return new categoriaSoporteModel {
                CategoriasSoporte = data.ObtenerCategoriasSoporte(_dbConector),
                TiposSoporte = data.ObtenerTiposSoporteActivos(_dbConector),
                TiposEquipoTicket = data.ObtenerTiposEquipoTicketActivos(_dbConector)
            };
        }

        private string? ValidarModelo(categoriaSoporteModel model) {
            if (model.IdTipoSoporte <= 0)
                return "Selecciona el tipo de soporte.";

            if (string.IsNullOrWhiteSpace(model.Descripcion))
                return "La descripcion de la categoria es obligatoria.";

            if (model.Descripcion.Trim().Length > 100)
                return "La descripcion no puede superar 100 caracteres.";

            if (model.IdTipoEquipoTicket <= 0)
                return "Selecciona el tipo de equipo para el ticket.";

            return null;
        }

        private void Normalizar(categoriaSoporteModel model) {
            model.Descripcion = model.Descripcion.Trim();
            model.TipoEquipoTicket = model.ObtenerClaveTipoEquipoTicket(_dbConector);
            model.RequiereEquipo = model.TipoEquipoTicket != "NINGUNO";
        }

        private IActionResult? ValidarAcceso(string accion) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            if (!_permisosService.TienePermisoPorRuta(idUsuario, "categoriaSoporte", accion)) {
                TempData["ErrorMessage"] = "No tienes permiso para realizar esta accion.";
                return RedirectToAction("Index", "Dashboard");
            }

            return null;
        }
    }
}
