using HELPDESK.Services;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Models.Inventario.TipoEquipo {
    public class tipoEquipoModel {
        public int IdTipoEquipo { get; set; }
        public string Descripcion { get; set; } = string.Empty;
        public DateTime FechaAlta { get; set; }
        public bool Activo { get; set; }
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<tipoEquipoModel> TiposEquipo { get; set; } = new List<tipoEquipoModel>();

        public List<tipoEquipoModel> ObtenerTiposEquipo(DBConector dbConector) {
            List<tipoEquipoModel> lista = new List<tipoEquipoModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_TipoEquipo, descripcion, fechaAlta, activo
                FROM dbo.tblTipoEquipo
                ORDER BY activo DESC, descripcion ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new tipoEquipoModel {
                    IdTipoEquipo = Convert.ToInt32(dr["id_TipoEquipo"]),
                    Descripcion = dr["descripcion"].ToString() ?? string.Empty,
                    FechaAlta = Convert.ToDateTime(dr["fechaAlta"]),
                    Activo = Convert.ToBoolean(dr["activo"])
                });
            }

            return lista;
        }

        public bool InsertarTipoEquipo(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                INSERT INTO dbo.tblTipoEquipo (descripcion, fechaAlta, activo)
                VALUES (@descripcion, GETDATE(), 1)";

            using SqlCommand cmd = new SqlCommand(query, cn);
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ActualizarTipoEquipo(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblTipoEquipo
                SET descripcion = @descripcion
                WHERE id_TipoEquipo = @id_TipoEquipo";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_TipoEquipo", System.Data.SqlDbType.Int).Value = IdTipoEquipo;
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool CambiarEstatus(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblTipoEquipo
                SET activo = CASE WHEN activo = 1 THEN 0 ELSE 1 END
                WHERE id_TipoEquipo = @id_TipoEquipo";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_TipoEquipo", System.Data.SqlDbType.Int).Value = IdTipoEquipo;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ExisteDescripcion(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblTipoEquipo
                WHERE descripcion = @descripcion
                  AND id_TipoEquipo <> @id_TipoEquipo";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@descripcion", System.Data.SqlDbType.VarChar, 250).Value = Descripcion.Trim();
            cmd.Parameters.Add("@id_TipoEquipo", System.Data.SqlDbType.Int).Value = IdTipoEquipo;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        private void AgregarParametros(SqlCommand cmd) {
            cmd.Parameters.Add("@descripcion", System.Data.SqlDbType.VarChar, 250).Value = Descripcion.Trim();
        }
    }
}
