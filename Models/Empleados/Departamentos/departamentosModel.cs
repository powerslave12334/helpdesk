using HELPDESK.Services;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Models.Empleados.Departamentos {
    public class departamentosModel {
        public int IdDepartamento { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public DateTime FechaAlta { get; set; }
        public bool Activo { get; set; }
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<departamentosModel> Departamentos { get; set; } = new List<departamentosModel>();

        public List<departamentosModel> ObtenerDepartamentos(DBConector dbConector) {
            List<departamentosModel> lista = new List<departamentosModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_Depto, nombre, fechaAlta, activo
                FROM dbo.tblDepto
                ORDER BY activo DESC, nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new departamentosModel {
                    IdDepartamento = Convert.ToInt32(dr["id_Depto"]),
                    Nombre = dr["nombre"].ToString() ?? string.Empty,
                    FechaAlta = Convert.ToDateTime(dr["fechaAlta"]),
                    Activo = Convert.ToBoolean(dr["activo"])
                });
            }

            return lista;
        }

        public bool InsertarDepartamento(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                INSERT INTO dbo.tblDepto (nombre, fechaAlta, activo)
                VALUES (@nombre, GETDATE(), 1)";

            using SqlCommand cmd = new SqlCommand(query, cn);
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ActualizarDepartamento(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblDepto
                SET nombre = @nombre
                WHERE id_Depto = @id_Departamento";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Departamento", System.Data.SqlDbType.Int).Value = IdDepartamento;
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool CambiarEstatus(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblDepto
                SET activo = CASE WHEN activo = 1 THEN 0 ELSE 1 END
                WHERE id_Depto = @id_Departamento";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Departamento", System.Data.SqlDbType.Int).Value = IdDepartamento;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ExisteDescripcion(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblDepto
                WHERE nombre = @nombre
                  AND id_Depto <> @id_Departamento";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 100).Value = Nombre.Trim();
            cmd.Parameters.Add("@id_Departamento", System.Data.SqlDbType.Int).Value = IdDepartamento;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        private void AgregarParametros(SqlCommand cmd) {
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 100).Value = Nombre.Trim();
        }
    }
}
