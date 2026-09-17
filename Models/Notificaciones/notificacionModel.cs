using System.Data;
using Microsoft.Data.SqlClient;
using HELPDESK.Services;

namespace HELPDESK.Models.Notificaciones {
    public class notificacionModel {
        public int IdNotificacion { get; set; }
        public int IdUsuarioDestino { get; set; }
        public string Mensaje { get; set; } = string.Empty;
        public string Tipo { get; set; } = string.Empty;
        public string? UrlAccion { get; set; }
        public bool Leido { get; set; }
        public DateTime FechaCreacion { get; set; }

        public static int Insertar(DBConector db, notificacionModel model) {
            using SqlConnection? cn = db.AbrirConexion();
            if (cn == null) return -1;

            string sql = @"INSERT INTO tblNotificaciones (id_UsuarioDestino, Mensaje, Tipo, UrlAccion, Leido, FechaCreacion)
                           VALUES (@id_UsuarioDestino, @Mensaje, @Tipo, @UrlAccion, 0, GETDATE());
                           SELECT SCOPE_IDENTITY();";

            using SqlCommand cmd = new SqlCommand(sql, cn);
            cmd.Parameters.AddWithValue("@id_UsuarioDestino", model.IdUsuarioDestino);
            cmd.Parameters.AddWithValue("@Mensaje", model.Mensaje);
            cmd.Parameters.AddWithValue("@Tipo", model.Tipo);
            cmd.Parameters.AddWithValue("@UrlAccion", (object?)model.UrlAccion ?? DBNull.Value);

            try {
                object? result = cmd.ExecuteScalar();
                return result != null ? Convert.ToInt32(result) : -1;
            } catch {
                return -1;
            }
        }

        public static List<notificacionModel> ObtenerPorUsuario(DBConector db, int idUsuario, int limite = 20) {
            using SqlConnection? cn = db.AbrirConexion();
            if (cn == null) return new List<notificacionModel>();

            string sql = @"SELECT TOP (@Limite) id_Notificacion, id_UsuarioDestino, Mensaje, Tipo, UrlAccion, Leido, FechaCreacion
                           FROM tblNotificaciones
                           WHERE id_UsuarioDestino = @id_Usuario
                           ORDER BY FechaCreacion DESC";

            using SqlCommand cmd = new SqlCommand(sql, cn);
            cmd.Parameters.AddWithValue("@id_Usuario", idUsuario);
            cmd.Parameters.AddWithValue("@Limite", limite);

            List<notificacionModel> lista = new List<notificacionModel>();

            try {
                using SqlDataReader reader = cmd.ExecuteReader();
                while (reader.Read()) {
                    lista.Add(new notificacionModel {
                        IdNotificacion = Convert.ToInt32(reader["id_Notificacion"]),
                        IdUsuarioDestino = Convert.ToInt32(reader["id_UsuarioDestino"]),
                        Mensaje = reader["Mensaje"].ToString() ?? string.Empty,
                        Tipo = reader["Tipo"].ToString() ?? string.Empty,
                        UrlAccion = reader["UrlAccion"] as string,
                        Leido = Convert.ToBoolean(reader["Leido"]),
                        FechaCreacion = Convert.ToDateTime(reader["FechaCreacion"])
                    });
                }
            } catch { }

            return lista;
        }

        public static int ContarNoLeidas(DBConector db, int idUsuario) {
            using SqlConnection? cn = db.AbrirConexion();
            if (cn == null) return 0;

            string sql = @"SELECT COUNT(*) FROM tblNotificaciones WHERE id_UsuarioDestino = @id_Usuario AND Leido = 0";

            using SqlCommand cmd = new SqlCommand(sql, cn);
            cmd.Parameters.AddWithValue("@id_Usuario", idUsuario);

            try {
                object? result = cmd.ExecuteScalar();
                return result != null ? Convert.ToInt32(result) : 0;
            } catch {
                return 0;
            }
        }

        public static void MarcarLeido(DBConector db, int idNotificacion) {
            using SqlConnection? cn = db.AbrirConexion();
            if (cn == null) return;

            string sql = @"UPDATE tblNotificaciones SET Leido = 1 WHERE id_Notificacion = @id";

            using SqlCommand cmd = new SqlCommand(sql, cn);
            cmd.Parameters.AddWithValue("@id", idNotificacion);

            try { cmd.ExecuteNonQuery(); } catch { }
        }

        public static void MarcarTodasLeido(DBConector db, int idUsuario) {
            using SqlConnection? cn = db.AbrirConexion();
            if (cn == null) return;

            string sql = @"UPDATE tblNotificaciones SET Leido = 1 WHERE id_UsuarioDestino = @id_Usuario AND Leido = 0";

            using SqlCommand cmd = new SqlCommand(sql, cn);
            cmd.Parameters.AddWithValue("@id_Usuario", idUsuario);

            try { cmd.ExecuteNonQuery(); } catch { }
        }
    }
}
