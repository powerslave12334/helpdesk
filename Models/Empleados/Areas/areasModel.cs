using HELPDESK.Services;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Models.Empleados.Areas {
    public class areasModel {
        public int IdArea { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public DateTime FechaAlta { get; set; }
        public bool Activo { get; set; }
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<areasModel> Areas { get; set; } = new List<areasModel>();

        public List<areasModel> ObtenerAreas(DBConector dbConector) {
            List<areasModel> lista = new List<areasModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_Area, nombre, fechaAlta, activo
                FROM dbo.tblArea
                ORDER BY activo DESC, nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new areasModel {
                    IdArea = Convert.ToInt32(dr["id_Area"]),
                    Nombre = dr["nombre"].ToString() ?? string.Empty,
                    FechaAlta = Convert.ToDateTime(dr["fechaAlta"]),
                    Activo = Convert.ToBoolean(dr["activo"])
                });
            }

            return lista;
        }

        public bool InsertarArea(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                INSERT INTO dbo.tblArea (nombre, fechaAlta, activo)
                VALUES (@nombre, GETDATE(), 1)";

            using SqlCommand cmd = new SqlCommand(query, cn);
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ActualizarArea(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblArea
                SET nombre = @nombre
                WHERE id_Area = @id_Area";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Area", System.Data.SqlDbType.Int).Value = IdArea;
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool CambiarEstatus(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblArea
                SET activo = CASE WHEN activo = 1 THEN 0 ELSE 1 END
                WHERE id_Area = @id_Area";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Area", System.Data.SqlDbType.Int).Value = IdArea;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ExisteNombre(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblArea
                WHERE nombre = @nombre
                  AND id_Area <> @id_Area";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 100).Value = Nombre.Trim();
            cmd.Parameters.Add("@id_Area", System.Data.SqlDbType.Int).Value = IdArea;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        private void AgregarParametros(SqlCommand cmd) {
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 100).Value = Nombre.Trim();
        }
    }
}
