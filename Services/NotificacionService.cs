using Microsoft.AspNetCore.SignalR;
using HELPDESK.Hubs;
using HELPDESK.Models.Notificaciones;

namespace HELPDESK.Services {
    public class NotificacionService {
        private readonly DBConector _db;
        private readonly IHubContext<NotificationHub> _hub;

        public NotificacionService(DBConector db, IHubContext<NotificationHub> hub) {
            _db = db;
            _hub = hub;
        }

        public async Task NotificarAsync(int idUsuarioDestino, string mensaje, string tipo, string? urlAccion = null) {
            var model = new notificacionModel {
                IdUsuarioDestino = idUsuarioDestino,
                Mensaje = mensaje,
                Tipo = tipo,
                UrlAccion = urlAccion
            };

            int id = notificacionModel.Insertar(_db, model);

            await _hub.Clients.Group($"user_{idUsuarioDestino}").SendAsync("ReceiveNotification", new {
                id,
                mensaje,
                tipo,
                urlAccion,
                fechaCreacion = DateTime.Now
            });
        }

        public List<notificacionModel> ObtenerPorUsuario(int idUsuario, int limite = 20) {
            return notificacionModel.ObtenerPorUsuario(_db, idUsuario, limite);
        }

        public int ContarNoLeidas(int idUsuario) {
            return notificacionModel.ContarNoLeidas(_db, idUsuario);
        }

        public void MarcarLeido(int idNotificacion) {
            notificacionModel.MarcarLeido(_db, idNotificacion);
        }

        public void MarcarTodasLeido(int idUsuario) {
            notificacionModel.MarcarTodasLeido(_db, idUsuario);
        }
    }
}
