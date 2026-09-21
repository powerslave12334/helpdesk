using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Http;
using HELPDESK.Services;

namespace HELPDESK.Controllers.Notificaciones {
    public class notificacionesController : Controller {
        private readonly NotificacionService _notificacionService;

        public notificacionesController(NotificacionService notificacionService) {
            _notificacionService = notificacionService;
        }

        [HttpGet]
        public JsonResult ObtenerNotificaciones() {
            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            if (idUsuario == 0) return Json(new { success = false });

            var notificaciones = _notificacionService.ObtenerPorUsuario(idUsuario);
            int noLeidas = _notificacionService.ContarNoLeidas(idUsuario);

            return Json(new { success = true, notificaciones, noLeidas });
        }

        [HttpPost]
        public JsonResult MarcarLeido([FromBody] int idNotificacion) {
            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            if (idUsuario == 0) return Json(new { success = false });

            _notificacionService.MarcarLeido(idNotificacion);
            int noLeidas = _notificacionService.ContarNoLeidas(idUsuario);

            return Json(new { success = true, noLeidas });
        }

        [HttpPost]
        public JsonResult MarcarTodasLeido() {
            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            if (idUsuario == 0) return Json(new { success = false });

            _notificacionService.MarcarTodasLeido(idUsuario);

            return Json(new { success = true, noLeidas = 0 });
        }
    }
}
