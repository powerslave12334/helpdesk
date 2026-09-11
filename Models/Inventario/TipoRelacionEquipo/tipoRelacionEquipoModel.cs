using HELPDESK.Services;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Models.Inventario.TipoRelacionEquipo {
    public class tipoRelacionEquipoModel {
        public int IdTipoRelacionEquipo { get; set; }
        public string Descripcion { get; set; } = string.Empty;
        public DateTime FechaAlta { get; set; }
        public bool Activo { get; set; }
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<tipoRelacionEquipoModel> TiposRelacion { get; set; } = new List<tipoRelacionEquipoModel>();

        public List<tipoRelacionEquipoModel> ObtenerTiposRelacion(DBConector dbConector) {
            List<tipoRelacionEquipoModel> lista = new List<tipoRelacionEquipoModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_TipoRelacionEquipo, descripcion, fechaAlta, activo
                FROM dbo.tblTipoRelacionEquipo
                ORDER BY activo DESC, descripcion ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new tipoRelacionEquipoModel {
                    IdTipoRelacionEquipo = Convert.ToInt32(dr["id_TipoRelacionEquipo"]),
                    Descripcion = dr["descripcion"].ToString() ?? string.Empty,
                    FechaAlta = Convert.ToDateTime(dr["fechaAlta"]),
                    Activo = Convert.ToBoolean(dr["activo"])
                });
            }

            return lista;
        }

        public bool InsertarTipoRelacion(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                INSERT INTO dbo.tblTipoRelacionEquipo (descripcion, fechaAlta, activo)
                VALUES (@descripcion, GETDATE(), 1)";

            using SqlCommand cmd = new SqlCommand(query, cn);
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ActualizarTipoRelacion(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblTipoRelacionEquipo
                SET descripcion = @descripcion
                WHERE id_TipoRelacionEquipo = @id_TipoRelacionEquipo";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_TipoRelacionEquipo", System.Data.SqlDbType.Int).Value = IdTipoRelacionEquipo;
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool CambiarEstatus(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblTipoRelacionEquipo
                SET activo = CASE WHEN activo = 1 THEN 0 ELSE 1 END
                WHERE id_TipoRelacionEquipo = @id_TipoRelacionEquipo";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_TipoRelacionEquipo", System.Data.SqlDbType.Int).Value = IdTipoRelacionEquipo;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ExisteDescripcion(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblTipoRelacionEquipo
                WHERE descripcion = @descripcion
                  AND id_TipoRelacionEquipo <> @id_TipoRelacionEquipo";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@descripcion", System.Data.SqlDbType.VarChar, 100).Value = Descripcion.Trim();
            cmd.Parameters.Add("@id_TipoRelacionEquipo", System.Data.SqlDbType.Int).Value = IdTipoRelacionEquipo;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        private void AgregarParametros(SqlCommand cmd) {
            cmd.Parameters.Add("@descripcion", System.Data.SqlDbType.VarChar, 100).Value = Descripcion.Trim();
        }
    }
}
