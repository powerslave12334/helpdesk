using HELPDESK.Services;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Models.Configuracion.Modulos {
    public class modulosModel {
        public int IdModulos { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public int Orden { get; set; }
        public DateTime FechaAlta { get; set; }
        public bool Activo { get; set; }
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<modulosModel> Modulos { get; set; } = new List<modulosModel>();

        public List<modulosModel> ObtenerModulos(DBConector dbConector) {
            List<modulosModel> lista = new List<modulosModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_Modulos, nombre, ISNULL(orden, 0) AS orden, fechaAlta, activo
                FROM dbo.tblModulos
                ORDER BY activo DESC, ISNULL(orden, 9999), nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new modulosModel {
                    IdModulos = Convert.ToInt32(dr["id_Modulos"]),
                    Nombre = dr["nombre"].ToString() ?? string.Empty,
                    Orden = Convert.ToInt32(dr["orden"]),
                    FechaAlta = Convert.ToDateTime(dr["fechaAlta"]),
                    Activo = Convert.ToBoolean(dr["activo"])
                });
            }

            return lista;
        }

        public bool InsertarModulo(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                INSERT INTO dbo.tblModulos (nombre, orden, fechaAlta, activo)
                VALUES (@nombre, @orden, GETDATE(), 1)";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 50).Value = Nombre.Trim();
            cmd.Parameters.Add("@orden", System.Data.SqlDbType.Int).Value = Orden;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ActualizarModulo(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblModulos
                SET nombre = @nombre,
                    orden = @orden
                WHERE id_Modulos = @id_Modulos";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Modulos", System.Data.SqlDbType.Int).Value = IdModulos;
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 50).Value = Nombre.Trim();
            cmd.Parameters.Add("@orden", System.Data.SqlDbType.Int).Value = Orden;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool CambiarEstatusModulo(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblModulos
                SET activo = CASE WHEN activo = 1 THEN 0 ELSE 1 END
                WHERE id_Modulos = @id_Modulos";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Modulos", System.Data.SqlDbType.Int).Value = IdModulos;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ExisteNombre(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblModulos
                WHERE nombre = @nombre
                  AND id_Modulos <> @id_Modulos";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 50).Value = Nombre.Trim();
            cmd.Parameters.Add("@id_Modulos", System.Data.SqlDbType.Int).Value = IdModulos;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        public bool ExisteOrden(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblModulos
                WHERE orden = @orden
                  AND id_Modulos <> @id_Modulos";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@orden", System.Data.SqlDbType.Int).Value = Orden;
            cmd.Parameters.Add("@id_Modulos", System.Data.SqlDbType.Int).Value = IdModulos;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }
    }
}
