using HELPDESK.Services;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Models.Inventario.EquipoCampo {
    public class equipoCampoModel {
        public int IdEquipoCampo { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public string Descripcion { get; set; } = string.Empty;
        public DateTime FechaAlta { get; set; }
        public bool Activo { get; set; }
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<equipoCampoModel> Campos { get; set; } = new List<equipoCampoModel>();

        public List<equipoCampoModel> ObtenerCampos(DBConector dbConector) {
            List<equipoCampoModel> lista = new List<equipoCampoModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_EquipoCampo, nombre, descripcion, fechaAlta, activo
                FROM dbo.tblEquipoCampo
                ORDER BY activo DESC, nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new equipoCampoModel {
                    IdEquipoCampo = Convert.ToInt32(dr["id_EquipoCampo"]),
                    Nombre = dr["nombre"].ToString() ?? string.Empty,
                    Descripcion = dr["descripcion"].ToString() ?? string.Empty,
                    FechaAlta = Convert.ToDateTime(dr["fechaAlta"]),
                    Activo = Convert.ToBoolean(dr["activo"])
                });
            }

            return lista;
        }

        public bool InsertarCampo(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                INSERT INTO dbo.tblEquipoCampo (nombre, descripcion, fechaAlta, activo)
                VALUES (@nombre, @descripcion, GETDATE(), 1)";

            using SqlCommand cmd = new SqlCommand(query, cn);
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ActualizarCampo(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblEquipoCampo
                SET nombre = @nombre,
                    descripcion = @descripcion
                WHERE id_EquipoCampo = @id_EquipoCampo";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_EquipoCampo", System.Data.SqlDbType.Int).Value = IdEquipoCampo;
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool CambiarEstatus(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblEquipoCampo
                SET activo = CASE WHEN activo = 1 THEN 0 ELSE 1 END
                WHERE id_EquipoCampo = @id_EquipoCampo";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_EquipoCampo", System.Data.SqlDbType.Int).Value = IdEquipoCampo;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ExisteNombre(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblEquipoCampo
                WHERE nombre = @nombre
                  AND id_EquipoCampo <> @id_EquipoCampo";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 100).Value = Nombre.Trim();
            cmd.Parameters.Add("@id_EquipoCampo", System.Data.SqlDbType.Int).Value = IdEquipoCampo;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        private void AgregarParametros(SqlCommand cmd) {
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 100).Value = Nombre.Trim();
            cmd.Parameters.Add("@descripcion", System.Data.SqlDbType.VarChar, 250).Value =
                string.IsNullOrWhiteSpace(Descripcion) ? (object)DBNull.Value : Descripcion.Trim();
        }
    }
}
