using HELPDESK.Models.Dashboard;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Controllers.Dashboard {
    public class dashboardController : Controller {
        private readonly DBConector _db;

        public dashboardController(DBConector db) {
            _db = db;
        }

        [HttpGet]
        public async Task<IActionResult> Index() {
            string? usuario = HttpContext.Session.GetString("Usuario");
            string? idUsuarioStr = HttpContext.Session.GetString("IdUsuario");

            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Index", "login");

            int idUsuario = 0;
            int.TryParse(idUsuarioStr, out idUsuario);

            string rol = await ObtenerRol(idUsuario);
            bool esTecnico = await EsTecnico(idUsuario);

            dashboardModel model = new dashboardModel {
                NombreUsuario = usuario,
                RolUsuario = rol,
                EsTecnico = esTecnico
            };

            if (rol == "root" || rol.ToLower().Contains("admin")) {
                CargarEstadisticasAdmin(model, idUsuario);
                return View("~/Views/Dashboard/Principal/dashboard.cshtml", model);
            } else if (esTecnico) {
                CargarEstadisticasTecnico(model, idUsuario);
                return View("~/Views/Dashboard/Principal/dashboard_tecnico.cshtml", model);
            } else {
                CargarEstadisticasEmpleado(model, idUsuario);
                return View("~/Views/Dashboard/Principal/dashboard_empleado.cshtml", model);
            }
        }

        private async Task<string> ObtenerRol(int idUsuario) {
            using SqlConnection? cn = _db.AbrirConexion();
            if (cn == null)
                return string.Empty;

            string query = @"
                SELECT TOP 1 ISNULL(r.nombre, '')
                FROM dbo.tblRolesUsuarios ru
                INNER JOIN dbo.tblRoles r ON r.id_Rol = ru.id_Rol
                WHERE ru.id_Usuario = @idUsuario
                  AND ru.activo = 1
                ORDER BY r.nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@idUsuario", System.Data.SqlDbType.Int).Value = idUsuario;
            object? result = await cmd.ExecuteScalarAsync();
            return result?.ToString() ?? string.Empty;
        }

        private async Task<bool> EsTecnico(int idUsuario) {
            using SqlConnection? cn = _db.AbrirConexion();
            if (cn == null)
                return false;

            string query = "SELECT ISNULL(esTecnico, 0) FROM dbo.tblUsuario WHERE id_Usuario = @idUsuario";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@idUsuario", System.Data.SqlDbType.Int).Value = idUsuario;
            object? result = await cmd.ExecuteScalarAsync();
            return result != null && result != DBNull.Value && Convert.ToBoolean(result);
        }

        // ==================== ADMIN ====================
        private void CargarEstadisticasAdmin(dashboardModel model, int idUsuario) {
            using SqlConnection? cn = _db.AbrirConexion();
            if (cn == null)
                return;

            ContarTicketsAbiertos(cn, model);
            ContarEquipos(cn, model);
            ContarUsuarios(cn, model);
            ContarNotificaciones(cn, model, idUsuario);
            CargarTicketsPorEstatus(cn, model);
            CargarTicketsPorMes(cn, model);
            CargarTicketsRecientes(cn, model);
        }

        // ==================== TECNICO ====================
        private void CargarEstadisticasTecnico(dashboardModel model, int idUsuario) {
            using SqlConnection? cn = _db.AbrirConexion();
            if (cn == null)
                return;

            ContarNotificaciones(cn, model, idUsuario);
            ContarTicketsAsignadosTecnico(cn, model, idUsuario);
            ContarTicketsEnProcesoTecnico(cn, model, idUsuario);
            ContarTicketsResueltosTecnico(cn, model, idUsuario);
            CargarTicketsDelTecnico(cn, model, idUsuario);
        }

        private void ContarTicketsAsignadosTecnico(SqlConnection cn, dashboardModel model, int idUsuario) {
            string query = @"
                SELECT COUNT(*)
                FROM dbo.tblTicket t
                INNER JOIN dbo.tblEstatus e ON e.id_Estatus = t.id_Estatus
                WHERE t.id_Tecnico = @idUsuario
                  AND t.activo = 1
                  AND e.id_Modulos = 1
                  AND LOWER(e.descripcion) != 'cerrado'";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@idUsuario", System.Data.SqlDbType.Int).Value = idUsuario;
            object? result = cmd.ExecuteScalar();
            model.TicketsAsignados = result != null && result != DBNull.Value ? Convert.ToInt32(result) : 0;
        }

        private void ContarTicketsEnProcesoTecnico(SqlConnection cn, dashboardModel model, int idUsuario) {
            string query = @"
                SELECT COUNT(*)
                FROM dbo.tblTicket t
                INNER JOIN dbo.tblEstatus e ON e.id_Estatus = t.id_Estatus
                WHERE t.id_Tecnico = @idUsuario
                  AND t.activo = 1
                  AND e.id_Modulos = 1
                  AND LOWER(e.descripcion) IN ('en proceso', 'en procesoso')";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@idUsuario", System.Data.SqlDbType.Int).Value = idUsuario;
            object? result = cmd.ExecuteScalar();
            model.TicketsEnProceso = result != null && result != DBNull.Value ? Convert.ToInt32(result) : 0;
        }

        private void ContarTicketsResueltosTecnico(SqlConnection cn, dashboardModel model, int idUsuario) {
            string query = @"
                SELECT COUNT(*)
                FROM dbo.tblTicket t
                INNER JOIN dbo.tblEstatus e ON e.id_Estatus = t.id_Estatus
                WHERE t.id_Tecnico = @idUsuario
                  AND t.activo = 1
                  AND e.id_Modulos = 1
                  AND LOWER(e.descripcion) = 'resuelto'";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@idUsuario", System.Data.SqlDbType.Int).Value = idUsuario;
            object? result = cmd.ExecuteScalar();
            model.TicketsResueltos = result != null && result != DBNull.Value ? Convert.ToInt32(result) : 0;
        }

        private void CargarTicketsDelTecnico(SqlConnection cn, dashboardModel model, int idUsuario) {
            string query = @"
                SELECT
                    t.id_Ticket,
                    t.asunto,
                    LTRIM(RTRIM(ISNULL(u.nombre, '') + ' ' + ISNULL(u.app, '') + ' ' + ISNULL(u.apm, ''))) AS usuario,
                    ISNULL(es.descripcion, '') AS estatus,
                    ISNULL(ts.descripcion, '') AS tipoSoporte,
                    t.fechaAlta
                FROM dbo.tblTicket t
                INNER JOIN dbo.tblUsuario u ON u.id_Usuario = t.id_Usuario
                LEFT JOIN dbo.tblEstatus es ON es.id_Estatus = t.id_Estatus
                LEFT JOIN dbo.tblTipoSoporte ts ON ts.id_TipoSoporte = t.id_TipoSoporte
                WHERE t.id_Tecnico = @idUsuario
                  AND t.activo = 1
                ORDER BY t.fechaAlta DESC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@idUsuario", System.Data.SqlDbType.Int).Value = idUsuario;
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                model.TicketsDelTecnico.Add(new ticketReciente {
                    IdTicket = Convert.ToInt32(dr["id_Ticket"]),
                    Asunto = dr["asunto"].ToString() ?? string.Empty,
                    Usuario = dr["usuario"].ToString() ?? string.Empty,
                    Estatus = dr["estatus"].ToString() ?? string.Empty,
                    TipoSoporte = dr["tipoSoporte"].ToString() ?? string.Empty,
                    FechaAlta = Convert.ToDateTime(dr["fechaAlta"])
                });
            }
        }

        // ==================== EMPLEADO ====================
        private void CargarEstadisticasEmpleado(dashboardModel model, int idUsuario) {
            using SqlConnection? cn = _db.AbrirConexion();
            if (cn == null)
                return;

            ContarNotificaciones(cn, model, idUsuario);
            CargarMisTickets(cn, model, idUsuario);
        }

        private void CargarMisTickets(SqlConnection cn, dashboardModel model, int idUsuario) {
            string query = @"
                SELECT
                    t.id_Ticket,
                    t.asunto,
                    LTRIM(RTRIM(ISNULL(u.nombre, '') + ' ' + ISNULL(u.app, '') + ' ' + ISNULL(u.apm, ''))) AS usuario,
                    ISNULL(es.descripcion, '') AS estatus,
                    ISNULL(ts.descripcion, '') AS tipoSoporte,
                    t.fechaAlta
                FROM dbo.tblTicket t
                INNER JOIN dbo.tblUsuario u ON u.id_Usuario = t.id_Usuario
                LEFT JOIN dbo.tblEstatus es ON es.id_Estatus = t.id_Estatus
                LEFT JOIN dbo.tblTipoSoporte ts ON ts.id_TipoSoporte = t.id_TipoSoporte
                WHERE t.id_Usuario = @idUsuario
                  AND t.activo = 1
                ORDER BY t.fechaAlta DESC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@idUsuario", System.Data.SqlDbType.Int).Value = idUsuario;
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                model.MisTickets.Add(new ticketReciente {
                    IdTicket = Convert.ToInt32(dr["id_Ticket"]),
                    Asunto = dr["asunto"].ToString() ?? string.Empty,
                    Usuario = dr["usuario"].ToString() ?? string.Empty,
                    Estatus = dr["estatus"].ToString() ?? string.Empty,
                    TipoSoporte = dr["tipoSoporte"].ToString() ?? string.Empty,
                    FechaAlta = Convert.ToDateTime(dr["fechaAlta"])
                });
            }
        }

        // ==================== COMUNES ====================
        private void ContarTicketsAbiertos(SqlConnection cn, dashboardModel model) {
            string query = @"
                SELECT COUNT(*)
                FROM dbo.tblTicket t
                INNER JOIN dbo.tblEstatus e ON e.id_Estatus = t.id_Estatus
                WHERE t.activo = 1
                  AND e.id_Modulos = 1
                  AND LOWER(e.descripcion) != 'cerrado'";

            using SqlCommand cmd = new SqlCommand(query, cn);
            object? result = cmd.ExecuteScalar();
            model.TicketsAbiertos = result != null && result != DBNull.Value ? Convert.ToInt32(result) : 0;
        }

        private void ContarEquipos(SqlConnection cn, dashboardModel model) {
            string query = "SELECT COUNT(*) FROM dbo.tblEquipo WHERE activo = 1";

            using SqlCommand cmd = new SqlCommand(query, cn);
            object? result = cmd.ExecuteScalar();
            model.TotalEquipos = result != null && result != DBNull.Value ? Convert.ToInt32(result) : 0;
        }

        private void ContarUsuarios(SqlConnection cn, dashboardModel model) {
            string query = "SELECT COUNT(*) FROM dbo.tblUsuario WHERE activo = 1";

            using SqlCommand cmd = new SqlCommand(query, cn);
            object? result = cmd.ExecuteScalar();
            model.TotalUsuarios = result != null && result != DBNull.Value ? Convert.ToInt32(result) : 0;
        }

        private void ContarNotificaciones(SqlConnection cn, dashboardModel model, int idUsuario) {
            string query = @"
                SELECT COUNT(*)
                FROM dbo.tblNotificaciones
                WHERE id_UsuarioDestino = @idUsuario
                  AND Leido = 0";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@idUsuario", System.Data.SqlDbType.Int).Value = idUsuario;
            object? result = cmd.ExecuteScalar();
            model.NotificacionesNoLeidas = result != null && result != DBNull.Value ? Convert.ToInt32(result) : 0;
        }

        private void CargarTicketsPorEstatus(SqlConnection cn, dashboardModel model) {
            string query = @"
                SELECT
                    ISNULL(e.descripcion, 'Sin estatus') AS estatus,
                    COUNT(*) AS total
                FROM dbo.tblTicket t
                INNER JOIN dbo.tblEstatus e ON e.id_Estatus = t.id_Estatus
                WHERE t.activo = 1
                  AND e.id_Modulos = 1
                GROUP BY e.descripcion
                ORDER BY total DESC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                model.TicketsPorEstatus.Add(new chartData {
                    Label = dr["estatus"].ToString() ?? string.Empty,
                    Value = Convert.ToInt32(dr["total"])
                });
            }
        }

        private void CargarTicketsPorMes(SqlConnection cn, dashboardModel model) {
            string query = @"
                SELECT
                    FORMAT(fechaAlta, 'yyyy-MM') AS mes,
                    COUNT(*) AS total
                FROM dbo.tblTicket
                WHERE activo = 1
                  AND fechaAlta >= DATEADD(MONTH, -6, GETDATE())
                GROUP BY FORMAT(fechaAlta, 'yyyy-MM')
                ORDER BY mes ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                string mesRaw = dr["mes"].ToString() ?? "";
                string label = mesRaw;

                if (DateTime.TryParse(mesRaw + "-01", out DateTime dt)) {
                    label = dt.ToString("MMM yyyy");
                }

                model.TicketsPorMes.Add(new chartData {
                    Label = label,
                    Value = Convert.ToInt32(dr["total"])
                });
            }
        }

        private void CargarTicketsRecientes(SqlConnection cn, dashboardModel model) {
            string query = @"
                SELECT TOP 5
                    t.id_Ticket,
                    t.asunto,
                    LTRIM(RTRIM(ISNULL(u.nombre, '') + ' ' + ISNULL(u.app, '') + ' ' + ISNULL(u.apm, ''))) AS usuario,
                    ISNULL(es.descripcion, '') AS estatus,
                    ISNULL(ts.descripcion, '') AS tipoSoporte,
                    t.fechaAlta
                FROM dbo.tblTicket t
                INNER JOIN dbo.tblUsuario u ON u.id_Usuario = t.id_Usuario
                LEFT JOIN dbo.tblEstatus es ON es.id_Estatus = t.id_Estatus
                LEFT JOIN dbo.tblTipoSoporte ts ON ts.id_TipoSoporte = t.id_TipoSoporte
                WHERE t.activo = 1
                ORDER BY t.fechaAlta DESC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                model.TicketsRecientes.Add(new ticketReciente {
                    IdTicket = Convert.ToInt32(dr["id_Ticket"]),
                    Asunto = dr["asunto"].ToString() ?? string.Empty,
                    Usuario = dr["usuario"].ToString() ?? string.Empty,
                    Estatus = dr["estatus"].ToString() ?? string.Empty,
                    TipoSoporte = dr["tipoSoporte"].ToString() ?? string.Empty,
                    FechaAlta = Convert.ToDateTime(dr["fechaAlta"])
                });
            }
        }
    }
}
