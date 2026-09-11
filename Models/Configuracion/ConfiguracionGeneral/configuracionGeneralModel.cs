using HELPDESK.Services;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Models.Configuracion.ConfiguracionGeneral {
    public class configuracionGeneralModel {
        public int IdConfiguracionSistema { get; set; }
        public string Clave { get; set; } = string.Empty;
        public string Valor { get; set; } = string.Empty;
        public string Descripcion { get; set; } = string.Empty;
        public DateTime FechaAlta { get; set; }
        public bool Activo { get; set; }
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<configuracionGeneralModel> Configuraciones { get; set; } = new List<configuracionGeneralModel>();

        public List<configuracionGeneralModel> ObtenerConfiguraciones(DBConector dbConector) {
            List<configuracionGeneralModel> lista = new List<configuracionGeneralModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_ConfiguracionSistema, clave, valor, descripcion, fechaAlta, activo
                FROM dbo.tblConfiguracionSistema
                ORDER BY activo DESC, clave ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new configuracionGeneralModel {
                    IdConfiguracionSistema = Convert.ToInt32(dr["id_ConfiguracionSistema"]),
                    Clave = dr["clave"].ToString() ?? string.Empty,
                    Valor = dr["valor"].ToString() ?? string.Empty,
                    Descripcion = dr["descripcion"].ToString() ?? string.Empty,
                    FechaAlta = Convert.ToDateTime(dr["fechaAlta"]),
                    Activo = Convert.ToBoolean(dr["activo"])
                });
            }

            return lista;
        }

        public bool InsertarConfiguracion(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                INSERT INTO dbo.tblConfiguracionSistema (clave, valor, descripcion, fechaAlta, activo)
                VALUES (@clave, @valor, @descripcion, GETDATE(), 1)";

            using SqlCommand cmd = new SqlCommand(query, cn);
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ActualizarConfiguracion(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblConfiguracionSistema
                SET clave = @clave,
                    valor = @valor,
                    descripcion = @descripcion
                WHERE id_ConfiguracionSistema = @id_ConfiguracionSistema";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_ConfiguracionSistema", System.Data.SqlDbType.Int).Value = IdConfiguracionSistema;
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool CambiarEstatus(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblConfiguracionSistema
                SET activo = CASE WHEN activo = 1 THEN 0 ELSE 1 END
                WHERE id_ConfiguracionSistema = @id_ConfiguracionSistema";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_ConfiguracionSistema", System.Data.SqlDbType.Int).Value = IdConfiguracionSistema;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ExisteClave(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblConfiguracionSistema
                WHERE LOWER(clave) = LOWER(@clave)
                  AND id_ConfiguracionSistema <> @id_ConfiguracionSistema";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@clave", System.Data.SqlDbType.VarChar, 100).Value = Clave.Trim();
            cmd.Parameters.Add("@id_ConfiguracionSistema", System.Data.SqlDbType.Int).Value = IdConfiguracionSistema;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        private void AgregarParametros(SqlCommand cmd) {
            cmd.Parameters.Add("@clave", System.Data.SqlDbType.VarChar, 100).Value = Clave.Trim();
            cmd.Parameters.Add("@valor", System.Data.SqlDbType.VarChar, -1).Value = Valor.Trim();
            cmd.Parameters.Add("@descripcion", System.Data.SqlDbType.VarChar, 250).Value = string.IsNullOrWhiteSpace(Descripcion) ? (object)DBNull.Value : Descripcion.Trim();
        }
    }
}
