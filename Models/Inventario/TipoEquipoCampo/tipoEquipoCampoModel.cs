using HELPDESK.Services;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Models.Inventario.TipoEquipoCampo {
    public class tipoEquipoCampoModel {
        public int IdTipoEquipoCampo { get; set; }
        public int IdTipoEquipo { get; set; }
        public string TipoEquipo { get; set; } = string.Empty;
        public int IdEquipoCampo { get; set; }
        public string EquipoCampo { get; set; } = string.Empty;
        public bool Requerido { get; set; }
        public DateTime FechaAlta { get; set; }
        public bool Activo { get; set; }
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<tipoEquipoCampoModel> Relaciones { get; set; } = new List<tipoEquipoCampoModel>();
        public List<tipoEquipoCampoModel> TiposEquipo { get; set; } = new List<tipoEquipoCampoModel>();
        public List<tipoEquipoCampoModel> Campos { get; set; } = new List<tipoEquipoCampoModel>();

        public List<tipoEquipoCampoModel> ObtenerRelaciones(DBConector dbConector) {
            List<tipoEquipoCampoModel> lista = new List<tipoEquipoCampoModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT
                    tec.id_TipoEquipoCampo,
                    tec.id_TipoEquipo,
                    te.descripcion AS tipoEquipo,
                    tec.id_EquipoCampo,
                    ec.nombre AS equipoCampo,
                    tec.requerido,
                    tec.fechaAlta,
                    tec.activo
                FROM dbo.tblTipoEquipoCampo tec
                INNER JOIN dbo.tblTipoEquipo te ON te.id_TipoEquipo = tec.id_TipoEquipo
                INNER JOIN dbo.tblEquipoCampo ec ON ec.id_EquipoCampo = tec.id_EquipoCampo
                ORDER BY tec.activo DESC, te.descripcion ASC, ec.nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new tipoEquipoCampoModel {
                    IdTipoEquipoCampo = Convert.ToInt32(dr["id_TipoEquipoCampo"]),
                    IdTipoEquipo = Convert.ToInt32(dr["id_TipoEquipo"]),
                    TipoEquipo = dr["tipoEquipo"].ToString() ?? string.Empty,
                    IdEquipoCampo = Convert.ToInt32(dr["id_EquipoCampo"]),
                    EquipoCampo = dr["equipoCampo"].ToString() ?? string.Empty,
                    Requerido = Convert.ToBoolean(dr["requerido"]),
                    FechaAlta = Convert.ToDateTime(dr["fechaAlta"]),
                    Activo = Convert.ToBoolean(dr["activo"])
                });
            }

            return lista;
        }

        public List<tipoEquipoCampoModel> ObtenerTiposEquipoActivos(DBConector dbConector) {
            List<tipoEquipoCampoModel> lista = new List<tipoEquipoCampoModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_TipoEquipo, descripcion
                FROM dbo.tblTipoEquipo
                WHERE activo = 1
                ORDER BY descripcion ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new tipoEquipoCampoModel {
                    IdTipoEquipo = Convert.ToInt32(dr["id_TipoEquipo"]),
                    TipoEquipo = dr["descripcion"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public List<tipoEquipoCampoModel> ObtenerCamposActivos(DBConector dbConector) {
            List<tipoEquipoCampoModel> lista = new List<tipoEquipoCampoModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_EquipoCampo, nombre
                FROM dbo.tblEquipoCampo
                WHERE activo = 1
                ORDER BY nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new tipoEquipoCampoModel {
                    IdEquipoCampo = Convert.ToInt32(dr["id_EquipoCampo"]),
                    EquipoCampo = dr["nombre"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public bool InsertarRelacion(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                INSERT INTO dbo.tblTipoEquipoCampo
                    (id_TipoEquipo, id_EquipoCampo, requerido, fechaAlta, activo)
                VALUES
                    (@id_TipoEquipo, @id_EquipoCampo, @requerido, GETDATE(), 1)";

            using SqlCommand cmd = new SqlCommand(query, cn);
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ActualizarRelacion(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblTipoEquipoCampo
                SET id_TipoEquipo = @id_TipoEquipo,
                    id_EquipoCampo = @id_EquipoCampo,
                    requerido = @requerido
                WHERE id_TipoEquipoCampo = @id_TipoEquipoCampo";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_TipoEquipoCampo", System.Data.SqlDbType.Int).Value = IdTipoEquipoCampo;
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool CambiarEstatus(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblTipoEquipoCampo
                SET activo = CASE WHEN activo = 1 THEN 0 ELSE 1 END
                WHERE id_TipoEquipoCampo = @id_TipoEquipoCampo";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_TipoEquipoCampo", System.Data.SqlDbType.Int).Value = IdTipoEquipoCampo;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ExisteRelacion(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblTipoEquipoCampo
                WHERE id_TipoEquipo = @id_TipoEquipo
                  AND id_EquipoCampo = @id_EquipoCampo
                  AND id_TipoEquipoCampo <> @id_TipoEquipoCampo";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_TipoEquipo", System.Data.SqlDbType.Int).Value = IdTipoEquipo;
            cmd.Parameters.Add("@id_EquipoCampo", System.Data.SqlDbType.Int).Value = IdEquipoCampo;
            cmd.Parameters.Add("@id_TipoEquipoCampo", System.Data.SqlDbType.Int).Value = IdTipoEquipoCampo;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        private void AgregarParametros(SqlCommand cmd) {
            cmd.Parameters.Add("@id_TipoEquipo", System.Data.SqlDbType.Int).Value = IdTipoEquipo;
            cmd.Parameters.Add("@id_EquipoCampo", System.Data.SqlDbType.Int).Value = IdEquipoCampo;
            cmd.Parameters.Add("@requerido", System.Data.SqlDbType.Bit).Value = Requerido;
        }
    }
}
