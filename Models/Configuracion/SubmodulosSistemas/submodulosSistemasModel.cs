using HELPDESK.Services;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Models.Configuracion.SubmodulosSistemas {
    public class submodulosSistemasModel {
        public int IdSubmodulosSistemas { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public int IdModulosSistemas { get; set; }
        public string ModuloSistema { get; set; } = string.Empty;
        public int IdSistemas { get; set; }
        public string Sistema { get; set; } = string.Empty;
        public bool Enabled { get; set; }
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<submodulosSistemasModel> SubmodulosSistemas { get; set; } = new List<submodulosSistemasModel>();
        public List<submodulosSistemasModel> Sistemas { get; set; } = new List<submodulosSistemasModel>();

        public List<submodulosSistemasModel> ObtenerSubmodulosSistemas(DBConector dbConector) {
            List<submodulosSistemasModel> lista = new List<submodulosSistemasModel>();
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT
                    ss.id_SubmodulosSistemas,
                    ss.nombre,
                    ss.id_ModulosSistemas,
                    ms.nombre AS moduloSistema,
                    ms.id_sistemas,
                    s.nombre AS sistema,
                    ss.enabled
                FROM dbo.tblSubmodulosSistemas ss
                INNER JOIN dbo.tblModulosSistemas ms ON ms.id_ModulosSistemas = ss.id_ModulosSistemas
                INNER JOIN dbo.tblSistemas s ON s.id_sistemas = ms.id_sistemas
                ORDER BY ss.enabled DESC, s.nombre ASC, ms.nombre ASC, ss.nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new submodulosSistemasModel {
                    IdSubmodulosSistemas = Convert.ToInt32(dr["id_SubmodulosSistemas"]),
                    Nombre = dr["nombre"].ToString() ?? string.Empty,
                    IdModulosSistemas = Convert.ToInt32(dr["id_ModulosSistemas"]),
                    ModuloSistema = dr["moduloSistema"].ToString() ?? string.Empty,
                    IdSistemas = Convert.ToInt32(dr["id_sistemas"]),
                    Sistema = dr["sistema"].ToString() ?? string.Empty,
                    Enabled = Convert.ToBoolean(dr["enabled"])
                });
            }

            return lista;
        }

        public List<submodulosSistemasModel> ObtenerSistemasActivos(DBConector dbConector) {
            List<submodulosSistemasModel> lista = new List<submodulosSistemasModel>();
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
                lista.Add(new submodulosSistemasModel {
                    IdSistemas = Convert.ToInt32(dr["id_sistemas"]),
                    Sistema = dr["nombre"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public List<submodulosSistemasModel> ObtenerModulosPorSistema(DBConector dbConector, int idSistemas) {
            List<submodulosSistemasModel> lista = new List<submodulosSistemasModel>();
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_ModulosSistemas, nombre
                FROM dbo.tblModulosSistemas
                WHERE id_sistemas = @id_sistemas
                  AND enabled = 1
                ORDER BY nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_sistemas", System.Data.SqlDbType.Int).Value = idSistemas;
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new submodulosSistemasModel {
                    IdModulosSistemas = Convert.ToInt32(dr["id_ModulosSistemas"]),
                    ModuloSistema = dr["nombre"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public bool InsertarSubmoduloSistema(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                INSERT INTO dbo.tblSubmodulosSistemas (nombre, id_ModulosSistemas, enabled)
                VALUES (@nombre, @id_ModulosSistemas, 1)";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 100).Value = Nombre.Trim();
            cmd.Parameters.Add("@id_ModulosSistemas", System.Data.SqlDbType.Int).Value = IdModulosSistemas;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ActualizarSubmoduloSistema(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblSubmodulosSistemas
                SET nombre = @nombre,
                    id_ModulosSistemas = @id_ModulosSistemas
                WHERE id_SubmodulosSistemas = @id_SubmodulosSistemas";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_SubmodulosSistemas", System.Data.SqlDbType.Int).Value = IdSubmodulosSistemas;
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 100).Value = Nombre.Trim();
            cmd.Parameters.Add("@id_ModulosSistemas", System.Data.SqlDbType.Int).Value = IdModulosSistemas;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool CambiarEstatus(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblSubmodulosSistemas
                SET enabled = CASE WHEN enabled = 1 THEN 0 ELSE 1 END
                WHERE id_SubmodulosSistemas = @id_SubmodulosSistemas";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_SubmodulosSistemas", System.Data.SqlDbType.Int).Value = IdSubmodulosSistemas;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ExisteNombre(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblSubmodulosSistemas
                WHERE nombre = @nombre
                  AND id_ModulosSistemas = @id_ModulosSistemas
                  AND id_SubmodulosSistemas <> @id_SubmodulosSistemas";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 100).Value = Nombre.Trim();
            cmd.Parameters.Add("@id_ModulosSistemas", System.Data.SqlDbType.Int).Value = IdModulosSistemas;
            cmd.Parameters.Add("@id_SubmodulosSistemas", System.Data.SqlDbType.Int).Value = IdSubmodulosSistemas;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }
    }
}
