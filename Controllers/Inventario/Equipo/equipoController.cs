using HELPDESK.Models.Inventario.Equipo;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;

namespace HELPDESK.Controllers.Inventario.Equipo {
    public class equipoController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;

        public equipoController(DBConector dbConector, PermisosService permisosService) {
            _dbConector = dbConector;
            _permisosService = permisosService;
        }

        [HttpGet]
        public IActionResult Index() {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            equipoModel model = CrearModeloPantalla();
            model.SuccessMessage = TempData["SuccessMessage"]?.ToString();
            model.ErrorMessage = TempData["ErrorMessage"]?.ToString();

            return View("~/Views/Inventario/Equipo/equipo.cshtml", model);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Create(equipoModel model) {
            IActionResult? acceso = ValidarAcceso("Crear");
            if (acceso != null)
                return acceso;

            string? error = ValidarModelo(model);
            if (!string.IsNullOrWhiteSpace(error)) {
                TempData["ErrorMessage"] = error;
                return RedirectToAction("Index");
            }

            Normalizar(model);

            string? errorDetalles = ValidarDetalles(model);
            if (!string.IsNullOrWhiteSpace(errorDetalles)) {
                TempData["ErrorMessage"] = errorDetalles;
                return RedirectToAction("Index");
            }

            if (model.ExisteNumeroSerie(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe un equipo con ese numero de serie.";
                return RedirectToAction("Index");
            }

            bool insertado = model.InsertarEquipo(_dbConector);
            TempData[insertado ? "SuccessMessage" : "ErrorMessage"] =
                insertado ? "Equipo registrado correctamente." : "No fue posible registrar el equipo.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Edit(equipoModel model) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (model.IdEquipo <= 0) {
                TempData["ErrorMessage"] = "No se recibio el equipo a actualizar.";
                return RedirectToAction("Index");
            }

            string? error = ValidarModelo(model);
            if (!string.IsNullOrWhiteSpace(error)) {
                TempData["ErrorMessage"] = error;
                return RedirectToAction("Index");
            }

            Normalizar(model);

            string? errorDetalles = ValidarDetalles(model);
            if (!string.IsNullOrWhiteSpace(errorDetalles)) {
                TempData["ErrorMessage"] = errorDetalles;
                return RedirectToAction("Index");
            }

            if (model.ExisteNumeroSerie(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe otro equipo con ese numero de serie.";
                return RedirectToAction("Index");
            }

            bool actualizado = model.ActualizarEquipo(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Equipo actualizado correctamente." : "No fue posible actualizar el equipo.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult ToggleActivo(int idEquipo) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (idEquipo <= 0) {
                TempData["ErrorMessage"] = "No se recibio el equipo a actualizar.";
                return RedirectToAction("Index");
            }

            equipoModel model = new equipoModel {
                IdEquipo = idEquipo
            };

            bool actualizado = model.CambiarEstatus(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Equipo actualizado correctamente." : "No fue posible actualizar el equipo.";

            return RedirectToAction("Index");
        }

        private equipoModel CrearModeloPantalla() {
            equipoModel data = new equipoModel();

            return new equipoModel {
                Equipos = data.ObtenerEquipos(_dbConector),
                TiposEquipo = data.ObtenerTiposEquipoActivos(_dbConector),
                Estatus = data.ObtenerEstatusActivos(_dbConector),
                Oficinas = data.ObtenerOficinasActivas(_dbConector),
                CamposPorTipo = data.ObtenerCamposPorTipo(_dbConector)
            };
        }

        private string? ValidarModelo(equipoModel model) {
            if (string.IsNullOrWhiteSpace(model.Marca))
                return "La marca del equipo es obligatoria.";

            if (string.IsNullOrWhiteSpace(model.Modelo))
                return "El modelo del equipo es obligatorio.";

            if (model.IdTipoEquipo <= 0)
                return "Selecciona un tipo de equipo.";

            if (model.IdEstatus <= 0)
                return "Selecciona un estatus.";

            if (model.IdPeriodoMantenimiento < 0)
                return "El periodo de mantenimiento no puede ser menor a cero.";

            return null;
        }

        private string? ValidarDetalles(equipoModel model) {
            equipoModel data = new equipoModel();
            List<equipoDetalleCampoModel> camposTipo = data.ObtenerCamposPorTipo(_dbConector, model.IdTipoEquipo);
            HashSet<int> camposValidos = camposTipo.Select(c => c.IdEquipoCampo).ToHashSet();

            model.Detalles = model.Detalles
                .Where(d => camposValidos.Contains(d.IdEquipoCampo))
                .Select(d => new equipoDetalleCampoModel {
                    IdEquipoCampo = d.IdEquipoCampo,
                    Valor = d.Valor?.Trim() ?? string.Empty
                })
                .ToList();

            foreach (equipoDetalleCampoModel campo in camposTipo.Where(c => c.Requerido)) {
                bool tieneValor = model.Detalles.Any(d =>
                    d.IdEquipoCampo == campo.IdEquipoCampo &&
                    !string.IsNullOrWhiteSpace(d.Valor));

                if (!tieneValor)
                    return $"El campo {campo.NombreCampo} es obligatorio para el tipo de equipo seleccionado.";
            }

            return null;
        }

        private void Normalizar(equipoModel model) {
            model.Marca = model.Marca.Trim();
            model.Modelo = model.Modelo.Trim();
            model.NumeroSerie = model.NumeroSerie?.Trim() ?? string.Empty;
            model.NombreEquipo = model.NombreEquipo?.Trim() ?? string.Empty;
            model.Ubicacion = model.Ubicacion?.Trim() ?? string.Empty;
            model.Observaciones = model.Observaciones?.Trim() ?? string.Empty;
        }

        private IActionResult? ValidarAcceso(string accion) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            if (!_permisosService.TienePermisoPorRuta(idUsuario, "equipo", accion)) {
                TempData["ErrorMessage"] = "No tienes permiso para realizar esta accion.";
                return RedirectToAction("Index", "Dashboard");
            }

            return null;
        }
    }
}
