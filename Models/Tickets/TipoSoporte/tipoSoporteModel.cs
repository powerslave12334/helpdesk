using HELPDESK.Services;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Models.Tickets.TipoSoporte {
    public class tipoSoporteModel {
        public int IdTipoSoporte { get; set; }
        public string Descripcion { get; set; } = string.Empty;
        public int? IdArea { get; set; }
        public string NombreArea { get; set; } = string.Empty;
        public DateTime FechaAlta { get; set; }
        public bool Activo { get; set; }
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<tipoSoporteModel> TiposSoporte { get; set; } = new List<tipoSoporteModel>();
        public List<tipoSoporteModel> Areas { get; set; } = new List<tipoSoporteModel>();

        public List<tipoSoporteModel> ObtenerTiposSoporte(DBConector dbConector) {
            List<tipoSoporteModel> lista = new List<tipoSoporteModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT
                    ts.id_TipoSoporte,
                    ts.descripcion,
                    ts.id_Area,
                    ISNULL(a.nombre, '') AS area,
                    ts.fechaAlta,
                    ts.activo
                FROM dbo.tblTipoSoporte ts
                LEFT JOIN dbo.tblArea a ON a.id_Area = ts.id_Area
                ORDER BY ts.activo DESC, ts.descripcion ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new tipoSoporteModel {
                    IdTipoSoporte = Convert.ToInt32(dr["id_TipoSoporte"]),
                    Descripcion = dr["descripcion"].ToString() ?? string.Empty,
                    IdArea = dr["id_Area"] == DBNull.Value ? null : Convert.ToInt32(dr["id_Area"]),
                    NombreArea = dr["area"].ToString() ?? string.Empty,
                    FechaAlta = Convert.ToDateTime(dr["fechaAlta"]),
                    Activo = Convert.ToBoolean(dr["activo"])
                });
            }

            return lista;
        }

        public bool InsertarTipoSoporte(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                INSERT INTO dbo.tblTipoSoporte (descripcion, id_Area, fechaAlta, activo)
                VALUES (@descripcion, @id_Area, GETDATE(), 1)";

            using SqlCommand cmd = new SqlCommand(query, cn);
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ActualizarTipoSoporte(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblTipoSoporte
                SET descripcion = @descripcion,
                    id_Area = @id_Area
                WHERE id_TipoSoporte = @id_TipoSoporte";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_TipoSoporte", System.Data.SqlDbType.Int).Value = IdTipoSoporte;
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool CambiarEstatus(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblTipoSoporte
                SET activo = CASE WHEN activo = 1 THEN 0 ELSE 1 END
                WHERE id_TipoSoporte = @id_TipoSoporte";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_TipoSoporte", System.Data.SqlDbType.Int).Value = IdTipoSoporte;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ExisteDescripcion(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblTipoSoporte
                WHERE descripcion = @descripcion
                  AND id_TipoSoporte <> @id_TipoSoporte";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@descripcion", System.Data.SqlDbType.VarChar, 100).Value = Descripcion.Trim();
            cmd.Parameters.Add("@id_TipoSoporte", System.Data.SqlDbType.Int).Value = IdTipoSoporte;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        public List<tipoSoporteModel> ObtenerAreasActivas(DBConector dbConector) {
            List<tipoSoporteModel> lista = new List<tipoSoporteModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_Area, nombre
                FROM dbo.tblArea
                WHERE activo = 1
                ORDER BY nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new tipoSoporteModel {
                    IdArea = Convert.ToInt32(dr["id_Area"]),
                    NombreArea = dr["nombre"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        private void AgregarParametros(SqlCommand cmd) {
            cmd.Parameters.Add("@descripcion", System.Data.SqlDbType.VarChar, 100).Value = Descripcion.Trim();
            cmd.Parameters.Add("@id_Area", System.Data.SqlDbType.Int).Value = IdArea.HasValue && IdArea.Value > 0 ? IdArea.Value : (object)DBNull.Value;
        }
    }
}
