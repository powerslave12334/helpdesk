using HELPDESK.Models.Inventario.EquipoRelacion;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;

namespace HELPDESK.Controllers.Inventario.EquipoRelacion {
    public class equipoRelacionController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;

        public equipoRelacionController(DBConector dbConector, PermisosService permisosService) {
            _dbConector = dbConector;
            _permisosService = permisosService;
        }

        [HttpGet]
        public IActionResult Index() {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            equipoRelacionModel model = CrearModeloPantalla();
            model.SuccessMessage = TempData["SuccessMessage"]?.ToString();
            model.ErrorMessage = TempData["ErrorMessage"]?.ToString();
            model.FechaAsignacion = DateTime.Today;

            return View("~/Views/Inventario/EquipoRelacion/equipoRelacion.cshtml", model);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Create(equipoRelacionModel model) {
            IActionResult? acceso = ValidarAcceso("Crear");
            if (acceso != null)
                return acceso;

            string? error = ValidarModelo(model);
            if (!string.IsNullOrWhiteSpace(error)) {
                TempData["ErrorMessage"] = error;
                return RedirectToAction("Index");
            }

            Normalizar(model);

            if (model.ExisteRelacionActiva(_dbConector)) {
                TempData["ErrorMessage"] = "La relacion entre esos equipos ya esta activa.";
                return RedirectToAction("Index");
            }

            if (model.HijoTieneRelacionActiva(_dbConector)) {
                TempData["ErrorMessage"] = "El equipo hijo ya tiene una relacion activa con ese tipo de relacion.";
                return RedirectToAction("Index");
            }

            bool insertado = model.InsertarRelacion(_dbConector);
            TempData[insertado ? "SuccessMessage" : "ErrorMessage"] =
                insertado ? "Relacion de equipos agregada correctamente." : "No fue posible agregar la relacion de equipos.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Edit(equipoRelacionModel model) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (model.IdEquipoRelacion <= 0) {
                TempData["ErrorMessage"] = "No se recibio la relacion a actualizar.";
                return RedirectToAction("Index");
            }

            string? error = ValidarModelo(model);
            if (!string.IsNullOrWhiteSpace(error)) {
                TempData["ErrorMessage"] = error;
                return RedirectToAction("Index");
            }

            Normalizar(model);

            if (model.ExisteRelacionActiva(_dbConector)) {
                TempData["ErrorMessage"] = "La relacion entre esos equipos ya esta activa.";
                return RedirectToAction("Index");
            }

            if (model.HijoTieneRelacionActiva(_dbConector)) {
                TempData["ErrorMessage"] = "El equipo hijo ya tiene una relacion activa con ese tipo de relacion.";
                return RedirectToAction("Index");
            }

            bool actualizado = model.ActualizarRelacion(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Relacion de equipos actualizada correctamente." : "No fue posible actualizar la relacion de equipos.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult ToggleActivo(int idEquipoRelacion) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (idEquipoRelacion <= 0) {
                TempData["ErrorMessage"] = "No se recibio la relacion a actualizar.";
                return RedirectToAction("Index");
            }

            equipoRelacionModel model = new equipoRelacionModel {
                IdEquipoRelacion = idEquipoRelacion
            };

            bool actualizado = model.CambiarEstatus(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Relacion de equipos actualizada correctamente." : "No fue posible actualizar la relacion de equipos.";

            return RedirectToAction("Index");
        }

        private equipoRelacionModel CrearModeloPantalla() {
            equipoRelacionModel data = new equipoRelacionModel();

            return new equipoRelacionModel {
                Relaciones = data.ObtenerRelaciones(_dbConector),
                Equipos = data.ObtenerEquiposActivos(_dbConector),
                TiposRelacion = data.ObtenerTiposRelacionActivos(_dbConector)
            };
        }

        private string? ValidarModelo(equipoRelacionModel model) {
            if (model.IdEquipoPadre <= 0)
                return "Selecciona el equipo padre.";

            if (model.IdEquipoHijo <= 0)
                return "Selecciona el equipo hijo.";

            if (model.IdEquipoPadre == model.IdEquipoHijo)
                return "El equipo padre y el equipo hijo no pueden ser el mismo.";

            if (model.IdTipoRelacionEquipo <= 0)
                return "Selecciona el tipo de relacion.";

            if (model.FechaAsignacion == DateTime.MinValue)
                return "La fecha de asignacion es obligatoria.";

            if (model.FechaBaja.HasValue && model.FechaBaja.Value.Date < model.FechaAsignacion.Date)
                return "La fecha de baja no puede ser menor a la fecha de asignacion.";

            return null;
        }

        private void Normalizar(equipoRelacionModel model) {
            model.Observaciones = model.Observaciones?.Trim() ?? string.Empty;
        }

        private IActionResult? ValidarAcceso(string accion) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            if (!_permisosService.TienePermisoPorRuta(idUsuario, "equipoRelacion", accion)) {
                TempData["ErrorMessage"] = "No tienes permiso para realizar esta accion.";
                return RedirectToAction("Index", "Dashboard");
            }

            return null;
        }
    }
}
