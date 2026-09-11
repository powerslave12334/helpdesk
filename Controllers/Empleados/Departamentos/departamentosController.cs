using HELPDESK.Models.Empleados.Departamentos;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;

namespace HELPDESK.Controllers.Empleados.Departamentos {
    public class departamentosController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;

        public departamentosController(DBConector dbConector, PermisosService permisosService) {
            _dbConector = dbConector;
            _permisosService = permisosService;
        }

        [HttpGet]
        public IActionResult Index() {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            departamentosModel model = CrearModeloPantalla();
            model.SuccessMessage = TempData["SuccessMessage"]?.ToString();
            model.ErrorMessage = TempData["ErrorMessage"]?.ToString();

            return View("~/Views/Empleados/Departamentos/departamentos.cshtml", model);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Create(departamentosModel model) {
            IActionResult? acceso = ValidarAcceso("Crear");
            if (acceso != null)
                return acceso;

            if (string.IsNullOrWhiteSpace(model.Nombre)) {
                TempData["ErrorMessage"] = "El nombre del departamento es obligatorio.";
                return RedirectToAction("Index");
            }

            model.Nombre = model.Nombre.Trim();

            if (model.ExisteDescripcion(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe un departamento con ese nombre.";
                return RedirectToAction("Index");
            }

            bool insertado = model.InsertarDepartamento(_dbConector);
            TempData[insertado ? "SuccessMessage" : "ErrorMessage"] =
                insertado ? "Departamento agregado correctamente." : "No fue posible agregar el departamento.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Edit(departamentosModel model) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (model.IdDepartamento <= 0 || string.IsNullOrWhiteSpace(model.Nombre)) {
                TempData["ErrorMessage"] = "Datos incompletos para actualizar el departamento.";
                return RedirectToAction("Index");
            }

            model.Nombre = model.Nombre.Trim();

            if (model.ExisteDescripcion(_dbConector)) {
                TempData["ErrorMessage"] = "Ya existe otro departamento con ese nombre.";
                return RedirectToAction("Index");
            }

            bool actualizado = model.ActualizarDepartamento(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Departamento actualizado correctamente." : "No fue posible actualizar el departamento.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult ToggleActivo(int idDepartamento) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (idDepartamento <= 0) {
                TempData["ErrorMessage"] = "No se recibio el departamento a actualizar.";
                return RedirectToAction("Index");
            }

            departamentosModel model = new departamentosModel {
                IdDepartamento = idDepartamento
            };

            bool actualizado = model.CambiarEstatus(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Departamento actualizado correctamente." : "No fue posible actualizar el departamento.";

            return RedirectToAction("Index");
        }

        private departamentosModel CrearModeloPantalla() {
            departamentosModel data = new departamentosModel();

            return new departamentosModel {
                Departamentos = data.ObtenerDepartamentos(_dbConector)
            };
        }

        private IActionResult? ValidarAcceso(string accion) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            if (!_permisosService.TienePermisoPorRuta(idUsuario, "departamentos", accion)) {
                TempData["ErrorMessage"] = "No tienes permiso para realizar esta accion.";
                return RedirectToAction("Index", "Dashboard");
            }

            return null;
        }
    }
}
