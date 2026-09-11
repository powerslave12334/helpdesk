using HELPDESK.Services;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Models.Configuracion.ModulosSistemas {
    public class modulosSistemasModel {
        public int IdModulosSistemas { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public int IdSistemas { get; set; }
        public string Sistema { get; set; } = string.Empty;
        public bool Enabled { get; set; }
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<modulosSistemasModel> ModulosSistemas { get; set; } = new List<modulosSistemasModel>();
        public List<modulosSistemasModel> Sistemas { get; set; } = new List<modulosSistemasModel>();

        public List<modulosSistemasModel> ObtenerModulosSistemas(DBConector dbConector) {
            List<modulosSistemasModel> lista = new List<modulosSistemasModel>();
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT
                    ms.id_ModulosSistemas,
                    ms.nombre,
                    ms.id_sistemas,
                    ISNULL(s.nombre, '') AS sistema,
                    ms.enabled
                FROM dbo.tblModulosSistemas ms
                INNER JOIN dbo.tblSistemas s ON s.id_sistemas = ms.id_sistemas
                ORDER BY ms.enabled DESC, s.nombre ASC, ms.nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new modulosSistemasModel {
                    IdModulosSistemas = Convert.ToInt32(dr["id_ModulosSistemas"]),
                    Nombre = dr["nombre"].ToString() ?? string.Empty,
                    IdSistemas = Convert.ToInt32(dr["id_sistemas"]),
                    Sistema = dr["sistema"].ToString() ?? string.Empty,
                    Enabled = Convert.ToBoolean(dr["enabled"])
                });
            }

            return lista;
        }

        public List<modulosSistemasModel> ObtenerSistemasActivos(DBConector dbConector) {
            List<modulosSistemasModel> lista = new List<modulosSistemasModel>();
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_sistemas, nombre
                FROM dbo.tblSistemas
                WHERE enabled = 1
                ORDER BY nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new modulosSistemasModel {
                    IdSistemas = Convert.ToInt32(dr["id_sistemas"]),
                    Sistema = dr["nombre"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public bool InsertarModuloSistema(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                INSERT INTO dbo.tblModulosSistemas (nombre, id_sistemas, enabled)
                VALUES (@nombre, @id_sistemas, 1)";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 100).Value = Nombre.Trim();
            cmd.Parameters.Add("@id_sistemas", System.Data.SqlDbType.Int).Value = IdSistemas;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ActualizarModuloSistema(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblModulosSistemas
                SET nombre = @nombre,
                    id_sistemas = @id_sistemas
                WHERE id_ModulosSistemas = @id_ModulosSistemas";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_ModulosSistemas", System.Data.SqlDbType.Int).Value = IdModulosSistemas;
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 100).Value = Nombre.Trim();
            cmd.Parameters.Add("@id_sistemas", System.Data.SqlDbType.Int).Value = IdSistemas;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool CambiarEstatus(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblModulosSistemas
                SET enabled = CASE WHEN enabled = 1 THEN 0 ELSE 1 END
                WHERE id_ModulosSistemas = @id_ModulosSistemas";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_ModulosSistemas", System.Data.SqlDbType.Int).Value = IdModulosSistemas;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ExisteNombre(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblModulosSistemas
                WHERE nombre = @nombre
                  AND id_sistemas = @id_sistemas
                  AND id_ModulosSistemas <> @id_ModulosSistemas";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 100).Value = Nombre.Trim();
            cmd.Parameters.Add("@id_sistemas", System.Data.SqlDbType.Int).Value = IdSistemas;
            cmd.Parameters.Add("@id_ModulosSistemas", System.Data.SqlDbType.Int).Value = IdModulosSistemas;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }
    }
}
