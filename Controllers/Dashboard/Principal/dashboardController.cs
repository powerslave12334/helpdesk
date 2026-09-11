using HELPDESK.Models.Dashboard;
using Microsoft.AspNetCore.Mvc;

namespace HELPDESK.Controllers.Dashboard {
    public class dashboardController : Controller {
        [HttpGet]
        public IActionResult Index() {
            string? usuario = HttpContext.Session.GetString("Usuario");

            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Index", "login");

            dashboardModel model = new dashboardModel {
                NombreUsuario = usuario
            };

            return View("~/Views/Dashboard/Principal/dashboard.cshtml", model);
        }
    }
}