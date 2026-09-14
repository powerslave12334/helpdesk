using HELPDESK.Models.Inventario.EquipoUsuario;
using HELPDESK.Models.Inventario.EquipoHistorial;
using HELPDESK.Services;
using HELPDESK.Services.Pdf;
using Microsoft.AspNetCore.Mvc;

namespace HELPDESK.Controllers.Inventario.EquipoUsuario {
    public class equipoUsuarioController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;
        private readonly actaEntregaService _actaEntregaService;

        public equipoUsuarioController(DBConector dbConector, PermisosService permisosService, actaEntregaService actaEntregaService) {
            _dbConector = dbConector;
            _permisosService = permisosService;
            _actaEntregaService = actaEntregaService;
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

        [HttpGet]
        public IActionResult GenerarActa(int id) {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            equipoUsuarioModel model = new equipoUsuarioModel();
            equipoUsuarioModel? asignacion = model.ObtenerAsignacionPorId(_dbConector, id);

            if (asignacion == null) {
                TempData["ErrorMessage"] = "No se encontro la asignacion.";
                return RedirectToAction("Index");
            }

            string? usuario = HttpContext.Session.GetString("Usuario") ?? "Soporte Tecnico";

            byte[] pdfBytes = _actaEntregaService.GenerarActa(asignacion, usuario);

            Response.Headers.Append("Content-Disposition", $"inline; filename=\"Acta_Entrega_{asignacion.NombreEquipo.Replace(" ", "_")}_{asignacion.FechaAsignacion:yyyyMMdd}.pdf\"");

            return File(pdfBytes, "application/pdf");
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

            int nuevoId = model.InsertarAsignacion(_dbConector);
            bool insertado = nuevoId > 0;

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuarioSesion);

            if (insertado) {

                if (nuevoId > 0) {
                    equipoUsuarioModel? asignacion = model.ObtenerAsignacionPorId(_dbConector, nuevoId);
                    if (asignacion != null) {
                        equipoHistorialModel historial = new equipoHistorialModel {
                            IdEquipoUsuarioOriginal = asignacion.IdEquipoUsuario,
                            IdEquipo = asignacion.IdEquipo,
                            IdUsuario = asignacion.IdUsuario,
                            FechaAsignacion = asignacion.FechaAsignacion,
                            FechaBaja = asignacion.FechaBaja,
                            Observaciones = asignacion.Observaciones,
                            IdArea = asignacion.IdArea,
                            FechaAlta = asignacion.FechaAlta,
                            EquipoMarca = asignacion.EquipoMarca,
                            EquipoModelo = asignacion.EquipoModelo,
                            EquipoNombre = asignacion.EquipoNombre,
                            EquipoNumeroSerie = asignacion.EquipoNumeroSerie,
                            EquipoUbicacion = asignacion.EquipoUbicacion
                        };
                        historial.InsertarHistorial(_dbConector, idUsuarioSesion, "Vincular");
                    }
                }
            }

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

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuarioSesion);

            equipoUsuarioModel? anterior = model.ObtenerAsignacionPorId(_dbConector, model.IdEquipoUsuario);
            if (anterior != null) {
                equipoHistorialModel historial = new equipoHistorialModel {
                    IdEquipoUsuarioOriginal = anterior.IdEquipoUsuario,
                    IdEquipo = anterior.IdEquipo,
                    IdUsuario = anterior.IdUsuario,
                    FechaAsignacion = anterior.FechaAsignacion,
                    FechaBaja = anterior.FechaBaja,
                    Observaciones = anterior.Observaciones,
                    IdArea = anterior.IdArea,
                    FechaAlta = anterior.FechaAlta,
                    EquipoMarca = anterior.EquipoMarca,
                    EquipoModelo = anterior.EquipoModelo,
                    EquipoNombre = anterior.EquipoNombre,
                    EquipoNumeroSerie = anterior.EquipoNumeroSerie,
                    EquipoUbicacion = anterior.EquipoUbicacion
                };
                historial.InsertarHistorial(_dbConector, idUsuarioSesion, "Re-vincular");
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

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Desvincular(int idEquipoUsuario, string? motivo) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (idEquipoUsuario <= 0) {
                TempData["ErrorMessage"] = "No se recibio la asignacion a desvincular.";
                return RedirectToAction("Index");
            }

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuarioSesion);

            equipoUsuarioModel model = new equipoUsuarioModel();
            equipoUsuarioModel? asignacion = model.ObtenerAsignacionPorId(_dbConector, idEquipoUsuario);

            if (asignacion == null) {
                TempData["ErrorMessage"] = "No se encontro la asignacion.";
                return RedirectToAction("Index");
            }

            equipoHistorialModel historial = new equipoHistorialModel {
                IdEquipoUsuarioOriginal = asignacion.IdEquipoUsuario,
                IdEquipo = asignacion.IdEquipo,
                IdUsuario = asignacion.IdUsuario,
                FechaAsignacion = asignacion.FechaAsignacion,
                FechaBaja = asignacion.FechaBaja,
                Observaciones = asignacion.Observaciones,
                IdArea = asignacion.IdArea,
                FechaAlta = asignacion.FechaAlta,
                Motivo = motivo,
                EquipoMarca = asignacion.EquipoMarca,
                EquipoModelo = asignacion.EquipoModelo,
                EquipoNombre = asignacion.EquipoNombre,
                EquipoNumeroSerie = asignacion.EquipoNumeroSerie,
                EquipoUbicacion = asignacion.EquipoUbicacion
            };

            bool insertado = historial.InsertarHistorial(_dbConector, idUsuarioSesion, "Desvincular");
            bool eliminado = model.EliminarAsignacion(_dbConector, idEquipoUsuario);

            if (insertado && eliminado)
                TempData["SuccessMessage"] = "Equipo desvinculado correctamente.";
            else
                TempData["ErrorMessage"] = "No fue posible desvincular el equipo.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult DesvincularSeleccionados(int[] ids, string? motivo) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (ids == null || ids.Length == 0) {
                TempData["ErrorMessage"] = "Selecciona al menos un equipo para desvincular.";
                return RedirectToAction("Index");
            }

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuarioSesion);

            equipoUsuarioModel model = new equipoUsuarioModel();
            int transferidos = 0;

            foreach (int id in ids) {
                equipoUsuarioModel? asignacion = model.ObtenerAsignacionPorId(_dbConector, id);
                if (asignacion == null || !asignacion.Activo)
                    continue;

                equipoHistorialModel historial = new equipoHistorialModel {
                    IdEquipoUsuarioOriginal = asignacion.IdEquipoUsuario,
                    IdEquipo = asignacion.IdEquipo,
                    IdUsuario = asignacion.IdUsuario,
                    FechaAsignacion = asignacion.FechaAsignacion,
                    FechaBaja = asignacion.FechaBaja,
                    Observaciones = asignacion.Observaciones,
                    IdArea = asignacion.IdArea,
                    FechaAlta = asignacion.FechaAlta,
                    Motivo = motivo,
                    EquipoMarca = asignacion.EquipoMarca,
                    EquipoModelo = asignacion.EquipoModelo,
                    EquipoNombre = asignacion.EquipoNombre,
                    EquipoNumeroSerie = asignacion.EquipoNumeroSerie,
                    EquipoUbicacion = asignacion.EquipoUbicacion
                };

                bool insertado = historial.InsertarHistorial(_dbConector, idUsuarioSesion, "Desvincular");
                bool eliminado = model.EliminarAsignacion(_dbConector, id);

                if (insertado && eliminado)
                    transferidos++;
            }

            TempData[transferidos > 0 ? "SuccessMessage" : "ErrorMessage"] =
                transferidos > 0
                    ? $"{transferidos} equipo(s) desvinculado(s) correctamente."
                    : "No fue posible desvincular los equipos.";

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
