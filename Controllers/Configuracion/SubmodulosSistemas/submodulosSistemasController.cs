using HELPDESK.Models.Configuracion.SubmodulosSistemas;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;

namespace HELPDESK.Controllers.Configuracion.SubmodulosSistemas {
    public class submodulosSistemasController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;

        public submodulosSistemasController(DBConector dbConector, PermisosService permisosService) {
            _dbConector = dbConector;
            _permisosService = permisosService;
        }

        [HttpGet]
        public IActionResult Index() {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            submodulosSistemasModel data = new submodulosSistemasModel();
            submodulosSistemasModel model = new submodulosSistemasModel {
                SubmodulosSistemas = data.ObtenerSubmodulosSistemas(_dbConector),
                Sistemas = data.ObtenerSistemasActivos(_dbConector),
                SuccessMessage = TempData["SuccessMessage"]?.ToString(),
                ErrorMessage = TempData["ErrorMessage"]?.ToString()
            };

            return View("~/Views/Configuracion/SubmodulosSistemas/submodulosSistemas.cshtml", model);
        }

        [HttpGet]
        public IActionResult ObtenerModulosPorSistema(int idSistemas) {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return Unauthorized();

            if (idSistemas <= 0)
                return Json(new List<object>());

            var modulos = new submodulosSistemasModel()
                .ObtenerModulosPorSistema(_dbConector, idSistemas)
                .Select(item => new {
                    idModulosSistemas = item.IdModulosSistemas,
                    nombre = item.ModuloSistema
                })
                .ToList();

            return Json(modulos);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Create(submodulosSistemasModel model) {
            IActionResult? acceso = ValidarAcceso("Crear");
            if (acceso != null)
                return acceso;

            if (string.IsNullOrWhiteSpace(model.Nombre) || model.IdSistemas <= 0 || model.IdModulosSistemas <= 0) {
                TempData["ErrorMessage"] = "Sistema, modulo y submodulo son obligatorios.";
                return RedirectToAction("Index");
            }

            model.Nombre = model.Nombre.Trim();
            if (model.ExisteNombre(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe un submodulo con ese nombre para el modulo seleccionado.";
                return RedirectToAction("Index");
            }

            bool insertado = model.InsertarSubmoduloSistema(_dbConector);
            TempData[insertado ? "SuccessMessage" : "ErrorMessage"] =
                insertado ? "Submodulo de sistema agregado correctamente." : "No fue posible agregar el submodulo de sistema.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Edit(submodulosSistemasModel model) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (model.IdSubmodulosSistemas <= 0 || string.IsNullOrWhiteSpace(model.Nombre) || model.IdSistemas <= 0 || model.IdModulosSistemas <= 0) {
                TempData["ErrorMessage"] = "Datos incompletos para actualizar el submodulo de sistema.";
                return RedirectToAction("Index");
            }

            model.Nombre = model.Nombre.Trim();
            if (model.ExisteNombre(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe otro submodulo con ese nombre para el modulo seleccionado.";
                return RedirectToAction("Index");
            }

            bool actualizado = model.ActualizarSubmoduloSistema(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Submodulo de sistema actualizado correctamente." : "No fue posible actualizar el submodulo de sistema.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult ToggleEnabled(int idSubmodulosSistemas) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (idSubmodulosSistemas <= 0) {
                TempData["ErrorMessage"] = "No se recibio el submodulo de sistema a actualizar.";
                return RedirectToAction("Index");
            }

            bool actualizado = new submodulosSistemasModel { IdSubmodulosSistemas = idSubmodulosSistemas }.CambiarEstatus(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Submodulo de sistema actualizado correctamente." : "No fue posible actualizar el submodulo de sistema.";

            return RedirectToAction("Index");
        }

        private IActionResult? ValidarAcceso(string accion) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            if (!_permisosService.TienePermisoPorRuta(idUsuario, "submodulosSistemas", accion)) {
                TempData["ErrorMessage"] = "No tienes permiso para realizar esta accion.";
                return RedirectToAction("Index", "Dashboard");
            }

            return null;
        }
    }
}
