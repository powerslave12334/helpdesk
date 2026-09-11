using HELPDESK.Services;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Models.Tickets.TipoDesarrollo {
    public class tipoDesarrolloModel {
        public int IdTipoDesarrollo { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public string Descripcion { get; set; } = string.Empty;
        public bool Enabled { get; set; }
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<tipoDesarrolloModel> TiposDesarrollo { get; set; } = new List<tipoDesarrolloModel>();

        public List<tipoDesarrolloModel> ObtenerTiposDesarrollo(DBConector dbConector) {
            List<tipoDesarrolloModel> lista = new List<tipoDesarrolloModel>();
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string tabla = ResolverTabla(cn, "tblTipoDesarrollo");
            if (string.IsNullOrWhiteSpace(tabla))
                return lista;

            string query = $@"SELECT id_TipoDesarrollo, nombre, descripcion, enabled FROM {tabla} ORDER BY enabled DESC, nombre ASC";
            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new tipoDesarrolloModel {
                    IdTipoDesarrollo = Convert.ToInt32(dr["id_TipoDesarrollo"]),
                    Nombre = dr["nombre"].ToString() ?? string.Empty,
                    Descripcion = dr["descripcion"].ToString() ?? string.Empty,
                    Enabled = Convert.ToBoolean(dr["enabled"])
                });
            }

            return lista;
        }

        public bool InsertarTipoDesarrollo(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string tabla = ResolverTabla(cn, "tblTipoDesarrollo");
            if (string.IsNullOrWhiteSpace(tabla))
                return false;

            string query = $@"INSERT INTO {tabla} (nombre, descripcion, enabled) VALUES (@nombre, @descripcion, 1)";
            using SqlCommand cmd = new SqlCommand(query, cn);
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ActualizarTipoDesarrollo(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string tabla = ResolverTabla(cn, "tblTipoDesarrollo");
            if (string.IsNullOrWhiteSpace(tabla))
                return false;

            string query = $@"UPDATE {tabla} SET nombre = @nombre, descripcion = @descripcion WHERE id_TipoDesarrollo = @id_TipoDesarrollo";
            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_TipoDesarrollo", System.Data.SqlDbType.Int).Value = IdTipoDesarrollo;
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool CambiarEstatus(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string tabla = ResolverTabla(cn, "tblTipoDesarrollo");
            if (string.IsNullOrWhiteSpace(tabla))
                return false;

            string query = $@"UPDATE {tabla} SET enabled = CASE WHEN enabled = 1 THEN 0 ELSE 1 END WHERE id_TipoDesarrollo = @id_TipoDesarrollo";
            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_TipoDesarrollo", System.Data.SqlDbType.Int).Value = IdTipoDesarrollo;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ExisteNombre(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string tabla = ResolverTabla(cn, "tblTipoDesarrollo");
            if (string.IsNullOrWhiteSpace(tabla))
                return false;

            string query = $@"SELECT COUNT(1) FROM {tabla} WHERE nombre = @nombre AND id_TipoDesarrollo <> @id_TipoDesarrollo";
            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 100).Value = Nombre.Trim();
            cmd.Parameters.Add("@id_TipoDesarrollo", System.Data.SqlDbType.Int).Value = IdTipoDesarrollo;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        private void AgregarParametros(SqlCommand cmd) {
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 100).Value = Nombre.Trim();
            cmd.Parameters.Add("@descripcion", System.Data.SqlDbType.VarChar, -1).Value = string.IsNullOrWhiteSpace(Descripcion) ? (object)DBNull.Value : Descripcion.Trim();
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
