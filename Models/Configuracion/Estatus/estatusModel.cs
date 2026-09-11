using HELPDESK.Services;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Models.Configuracion.Estatus {
    public class estatusModel {
        public int IdEstatus { get; set; }
        public int? IdModulos { get; set; }
        public string NombreModulo { get; set; } = "Global";
        public string Descripcion { get; set; } = string.Empty;
        public DateTime FechaAlta { get; set; }
        public bool Activo { get; set; }
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<estatusModel> Estatus { get; set; } = new List<estatusModel>();
        public List<estatusModel> Modulos { get; set; } = new List<estatusModel>();

        public List<estatusModel> ObtenerEstatus(DBConector dbConector) {
            List<estatusModel> lista = new List<estatusModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT
                    e.id_Estatus,
                    e.id_Modulos,
                    ISNULL(m.nombre, 'Global') AS nombreModulo,
                    e.descripcion,
                    e.fechaAlta,
                    e.activo
                FROM dbo.tblEstatus e
                LEFT JOIN dbo.tblModulos m ON m.id_Modulos = e.id_Modulos
                ORDER BY e.activo DESC, ISNULL(m.orden, 0), nombreModulo ASC, e.descripcion ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new estatusModel {
                    IdEstatus = Convert.ToInt32(dr["id_Estatus"]),
                    IdModulos = dr["id_Modulos"] == DBNull.Value ? null : Convert.ToInt32(dr["id_Modulos"]),
                    NombreModulo = dr["nombreModulo"].ToString() ?? "Global",
                    Descripcion = dr["descripcion"].ToString() ?? string.Empty,
                    FechaAlta = Convert.ToDateTime(dr["fechaAlta"]),
                    Activo = Convert.ToBoolean(dr["activo"])
                });
            }

            return lista;
        }

        public List<estatusModel> ObtenerModulosActivos(DBConector dbConector) {
            List<estatusModel> lista = new List<estatusModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_Modulos, nombre
                FROM dbo.tblModulos
                WHERE activo = 1
                ORDER BY ISNULL(orden, 9999), nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new estatusModel {
                    IdModulos = Convert.ToInt32(dr["id_Modulos"]),
                    NombreModulo = dr["nombre"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public bool InsertarEstatus(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                INSERT INTO dbo.tblEstatus (id_Modulos, descripcion, fechaAlta, activo)
                VALUES (@id_Modulos, @descripcion, GETDATE(), 1)";

            using SqlCommand cmd = new SqlCommand(query, cn);
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ActualizarEstatus(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblEstatus
                SET id_Modulos = @id_Modulos,
                    descripcion = @descripcion
                WHERE id_Estatus = @id_Estatus";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Estatus", System.Data.SqlDbType.Int).Value = IdEstatus;
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool CambiarEstatus(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblEstatus
                SET activo = CASE WHEN activo = 1 THEN 0 ELSE 1 END
                WHERE id_Estatus = @id_Estatus";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Estatus", System.Data.SqlDbType.Int).Value = IdEstatus;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ExisteDescripcion(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblEstatus
                WHERE descripcion = @descripcion
                  AND ISNULL(id_Modulos, 0) = ISNULL(@id_Modulos, 0)
                  AND id_Estatus <> @id_Estatus";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@descripcion", System.Data.SqlDbType.VarChar, 100).Value = Descripcion.Trim();
            cmd.Parameters.Add("@id_Modulos", System.Data.SqlDbType.Int).Value = IdModulos.HasValue ? IdModulos.Value : (object)DBNull.Value;
            cmd.Parameters.Add("@id_Estatus", System.Data.SqlDbType.Int).Value = IdEstatus;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        private void AgregarParametros(SqlCommand cmd) {
            cmd.Parameters.Add("@id_Modulos", System.Data.SqlDbType.Int).Value = IdModulos.HasValue && IdModulos.Value > 0
                ? IdModulos.Value
                : (object)DBNull.Value;
            cmd.Parameters.Add("@descripcion", System.Data.SqlDbType.VarChar, 100).Value = Descripcion.Trim();
        }
    }
}
