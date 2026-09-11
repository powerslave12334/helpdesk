using HELPDESK.Models.Empleados.TiposEmpleados;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;

namespace HELPDESK.Controllers.Empleados.TiposEmpleados {
    public class tipoEmpleadoController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;

        public tipoEmpleadoController(DBConector dbConector, PermisosService permisosService) {
            _dbConector = dbConector;
            _permisosService = permisosService;
        }

        [HttpGet]
        public IActionResult Index() {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            tiposEmpleadoModel model = CrearModeloPantalla();
            model.SuccessMessage = TempData["SuccessMessage"]?.ToString();
            model.ErrorMessage = TempData["ErrorMessage"]?.ToString();

            return View("~/Views/Empleados/TiposEmpleados/tiposEmpleado.cshtml", model);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Create(tiposEmpleadoModel model) {
            IActionResult? acceso = ValidarAcceso("Crear");
            if (acceso != null)
                return acceso;

            if (string.IsNullOrWhiteSpace(model.Nombre)) {
                TempData["ErrorMessage"] = "El nombre del tipo de empleado es obligatorio.";
                return RedirectToAction("Index");
            }

            model.Nombre = model.Nombre.Trim();

            if (model.ExisteDescripcion(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe un tipo de empleado con ese nombre.";
                return RedirectToAction("Index");
            }

            bool insertado = model.InsertarTipoEmpleado(_dbConector);
            TempData[insertado ? "SuccessMessage" : "ErrorMessage"] =
                insertado ? "Tipo de empleado agregado correctamente." : "No fue posible agregar el tipo de empleado.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Edit(tiposEmpleadoModel model) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (model.IdTipoEmpleado <= 0 || string.IsNullOrWhiteSpace(model.Nombre)) {
                TempData["ErrorMessage"] = "Datos incompletos para actualizar el tipo de empleado.";
                return RedirectToAction("Index");
            }

            model.Nombre = model.Nombre.Trim();

            if (model.ExisteDescripcion(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe otro tipo de empleado con ese nombre.";
                return RedirectToAction("Index");
            }

            bool actualizado = model.ActualizarTipoEmpleado(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Tipo de empleado actualizado correctamente." : "No fue posible actualizar el tipo de empleado.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult ToggleActivo(int idTipoEmpleado) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (idTipoEmpleado <= 0) {
                TempData["ErrorMessage"] = "No se recibio el tipo de empleado a actualizar.";
                return RedirectToAction("Index");
            }

            tiposEmpleadoModel model = new tiposEmpleadoModel {
                IdTipoEmpleado = idTipoEmpleado
            };

            bool actualizado = model.CambiarEstatus(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Tipo de empleado actualizado correctamente." : "No fue posible actualizar el tipo de empleado.";

            return RedirectToAction("Index");
        }

        private tiposEmpleadoModel CrearModeloPantalla() {
            tiposEmpleadoModel data = new tiposEmpleadoModel();

            return new tiposEmpleadoModel {
                TiposEmpleado = data.ObtenerTiposEmpleado(_dbConector)
            };
        }

        private IActionResult? ValidarAcceso(string accion) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            if (!_permisosService.TienePermisoPorRuta(idUsuario, "tipoEmpleado", accion)) {
                TempData["ErrorMessage"] = "No tienes permiso para realizar esta accion.";
                return RedirectToAction("Index", "Dashboard");
            }

            return null;
        }
    }
}
