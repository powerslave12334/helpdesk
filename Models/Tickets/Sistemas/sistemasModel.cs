using HELPDESK.Services;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Models.Tickets.Sistemas {
    public class sistemasModel {
        public int IdSistemas { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public bool Enabled { get; set; }
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<sistemasModel> Sistemas { get; set; } = new List<sistemasModel>();

        public List<sistemasModel> ObtenerSistemas(DBConector dbConector) {
            List<sistemasModel> lista = new List<sistemasModel>();
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string tabla = ResolverTabla(cn, "tblSistemas");
            if (string.IsNullOrWhiteSpace(tabla))
                return lista;

            string query = $@"SELECT id_sistemas, nombre, enabled FROM {tabla} ORDER BY enabled DESC, nombre ASC";
            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new sistemasModel {
                    IdSistemas = Convert.ToInt32(dr["id_sistemas"]),
                    Nombre = dr["nombre"].ToString() ?? string.Empty,
                    Enabled = Convert.ToBoolean(dr["enabled"])
                });
            }

            return lista;
        }

        public bool InsertarSistema(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string tabla = ResolverTabla(cn, "tblSistemas");
            if (string.IsNullOrWhiteSpace(tabla))
                return false;

            string query = $@"INSERT INTO {tabla} (nombre, enabled) VALUES (@nombre, 1)";
            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 100).Value = Nombre.Trim();

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ActualizarSistema(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string tabla = ResolverTabla(cn, "tblSistemas");
            if (string.IsNullOrWhiteSpace(tabla))
                return false;

            string query = $@"UPDATE {tabla} SET nombre = @nombre WHERE id_sistemas = @id_sistemas";
            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_sistemas", System.Data.SqlDbType.Int).Value = IdSistemas;
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 100).Value = Nombre.Trim();

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool CambiarEstatus(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string tabla = ResolverTabla(cn, "tblSistemas");
            if (string.IsNullOrWhiteSpace(tabla))
                return false;

            string query = $@"UPDATE {tabla} SET enabled = CASE WHEN enabled = 1 THEN 0 ELSE 1 END WHERE id_sistemas = @id_sistemas";
            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_sistemas", System.Data.SqlDbType.Int).Value = IdSistemas;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ExisteNombre(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string tabla = ResolverTabla(cn, "tblSistemas");
            if (string.IsNullOrWhiteSpace(tabla))
                return false;

            string query = $@"SELECT COUNT(1) FROM {tabla} WHERE nombre = @nombre AND id_sistemas <> @id_sistemas";
            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 100).Value = Nombre.Trim();
            cmd.Parameters.Add("@id_sistemas", System.Data.SqlDbType.Int).Value = IdSistemas;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        private string ResolverTabla(SqlConnection cn, string nombreTabla) {
            string query = @"
                SELECT TOP 1 QUOTENAME(s.name) + '.' + QUOTENAME(t.name)
                FROM sys.tables t
                INNER JOIN sys.schemas s ON s.schema_id = t.schema_id
                WHERE t.name = @nombreTabla
                ORDER BY CASE WHEN s.name = 'dbo' THEN 0 ELSE 1 END, s.name ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@nombreTabla", System.Data.SqlDbType.VarChar, 128).Value = nombreTabla;
            object? result = cmd.ExecuteScalar();

            return result?.ToString() ?? string.Empty;
        }
    }
}
