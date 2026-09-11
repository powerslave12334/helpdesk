using HELPDESK.Services;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Models.Empleados.TiposEmpleados {
    public class tiposEmpleadoModel {
        public int IdTipoEmpleado { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public DateTime FechaAlta { get; set; }
        public bool Activo { get; set; }
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<tiposEmpleadoModel> TiposEmpleado { get; set; } = new List<tiposEmpleadoModel>();

        public List<tiposEmpleadoModel> ObtenerTiposEmpleado(DBConector dbConector) {
            List<tiposEmpleadoModel> lista = new List<tiposEmpleadoModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_TipoEmpleado, nombre, fechaAlta, activo
                FROM dbo.tblTipoEmpleado
                ORDER BY activo DESC, nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new tiposEmpleadoModel {
                    IdTipoEmpleado = Convert.ToInt32(dr["id_TipoEmpleado"]),
                    Nombre = dr["nombre"].ToString() ?? string.Empty,
                    FechaAlta = Convert.ToDateTime(dr["fechaAlta"]),
                    Activo = Convert.ToBoolean(dr["activo"])
                });
            }

            return lista;
        }

        public bool InsertarTipoEmpleado(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                INSERT INTO dbo.tblTipoEmpleado (nombre, fechaAlta, activo)
                VALUES (@nombre, GETDATE(), 1)";

            using SqlCommand cmd = new SqlCommand(query, cn);
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ActualizarTipoEmpleado(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblTipoEmpleado
                SET nombre = @nombre
                WHERE id_TipoEmpleado = @id_TipoEmpleado";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_TipoEmpleado", System.Data.SqlDbType.Int).Value = IdTipoEmpleado;
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool CambiarEstatus(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblTipoEmpleado
                SET activo = CASE WHEN activo = 1 THEN 0 ELSE 1 END
                WHERE id_TipoEmpleado = @id_TipoEmpleado";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_TipoEmpleado", System.Data.SqlDbType.Int).Value = IdTipoEmpleado;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ExisteDescripcion(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblTipoEmpleado
                WHERE nombre = @nombre
                  AND id_TipoEmpleado <> @id_TipoEmpleado";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 50).Value = Nombre.Trim();
            cmd.Parameters.Add("@id_TipoEmpleado", System.Data.SqlDbType.Int).Value = IdTipoEmpleado;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        private void AgregarParametros(SqlCommand cmd) {
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 50).Value = Nombre.Trim();
        }
    }
}
