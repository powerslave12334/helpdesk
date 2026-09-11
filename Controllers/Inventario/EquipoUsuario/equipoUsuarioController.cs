using HELPDESK.Models.Inventario.EquipoUsuario;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;

namespace HELPDESK.Controllers.Inventario.EquipoUsuario {
    public class equipoUsuarioController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;

        public equipoUsuarioController(DBConector dbConector, PermisosService permisosService) {
            _dbConector = dbConector;
            _permisosService = permisosService;
        }

        [HttpGet]
        public IActionResult Index() {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            equipoUsuarioModel model = CrearModeloPantalla();
            model.SuccessMessage = TempData["SuccessMessage"]?.ToString();
            model.ErrorMessage = TempData["ErrorMessage"]?.ToString();

            return View("~/Views/Inventario/EquipoUsuario/equipoUsuario.cshtml", model);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Create(equipoUsuarioModel model) {
            IActionResult? acceso = ValidarAcceso("Crear");
            if (acceso != null)
                return acceso;

            string? error = ValidarModelo(model);
            if (!string.IsNullOrWhiteSpace(error)) {
                TempData["ErrorMessage"] = error;
                return RedirectToAction("Index");
            }

            Normalizar(model);

            if (model.EquipoTieneAsignacionActiva(_dbConector)) {
                TempData["ErrorMessage"] = "El equipo ya tiene una asignacion activa.";
                return RedirectToAction("Index");
            }

            bool insertado = model.InsertarAsignacion(_dbConector);
            TempData[insertado ? "SuccessMessage" : "ErrorMessage"] =
                insertado ? "Equipo asignado correctamente." : "No fue posible asignar el equipo.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Edit(equipoUsuarioModel model) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (model.IdEquipoUsuario <= 0) {
                TempData["ErrorMessage"] = "No se recibio la asignacion a actualizar.";
                return RedirectToAction("Index");
            }

            string? error = ValidarModelo(model);
            if (!string.IsNullOrWhiteSpace(error)) {
                TempData["ErrorMessage"] = error;
                return RedirectToAction("Index");
            }

            Normalizar(model);

            if (model.EquipoTieneAsignacionActiva(_dbConector)) {
                TempData["ErrorMessage"] = "El equipo ya tiene otra asignacion activa.";
                return RedirectToAction("Index");
            }

            bool actualizado = model.ActualizarAsignacion(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Asignacion actualizada correctamente." : "No fue posible actualizar la asignacion.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult ToggleActivo(int idEquipoUsuario) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (idEquipoUsuario <= 0) {
                TempData["ErrorMessage"] = "No se recibio la asignacion a actualizar.";
                return RedirectToAction("Index");
            }

            equipoUsuarioModel model = new equipoUsuarioModel {
                IdEquipoUsuario = idEquipoUsuario
            };

            bool actualizado = model.CambiarEstatus(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Asignacion actualizada correctamente." : "No fue posible actualizar la asignacion.";

            return RedirectToAction("Index");
        }

        private equipoUsuarioModel CrearModeloPantalla() {
            equipoUsuarioModel data = new equipoUsuarioModel();

            return new equipoUsuarioModel {
                Asignaciones = data.ObtenerAsignaciones(_dbConector),
                Equipos = data.ObtenerEquiposActivos(_dbConector),
                Usuarios = data.ObtenerUsuariosActivos(_dbConector),
                Areas = data.ObtenerAreasActivas(_dbConector),
                FechaAsignacion = DateTime.Today
            };
        }

        private string? ValidarModelo(equipoUsuarioModel model) {
            if (model.IdEquipo <= 0)
                return "Selecciona un equipo.";

            if (model.IdUsuario <= 0)
                return "Selecciona un usuario.";

            if (model.FechaAsignacion == default)
                return "La fecha de asignacion es obligatoria.";

            if (model.FechaBaja.HasValue && model.FechaBaja.Value.Date < model.FechaAsignacion.Date)
                return "La fecha de baja no puede ser menor a la fecha de asignacion.";

            return null;
        }

        private void Normalizar(equipoUsuarioModel model) {
            model.Observaciones = model.Observaciones?.Trim() ?? string.Empty;
        }

        private IActionResult? ValidarAcceso(string accion) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            if (!_permisosService.TienePermisoPorRuta(idUsuario, "equipoUsuario", accion)) {
                TempData["ErrorMessage"] = "No tienes permiso para realizar esta accion.";
                return RedirectToAction("Index", "Dashboard");
            }

            return null;
        }
    }
}
