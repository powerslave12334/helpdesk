using HELPDESK.Services;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Models.Configuracion.Acciones {
    public class accionesModel {
        public int IdAccion { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public DateTime FechaAlta { get; set; }
        public bool Activo { get; set; }
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<accionesModel> Acciones { get; set; } = new List<accionesModel>();

        public List<accionesModel> ObtenerAcciones(DBConector dbConector) {
            List<accionesModel> lista = new List<accionesModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_Accion, nombre, fechaAlta, activo
                FROM dbo.tblAcciones
                ORDER BY activo DESC, nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new accionesModel {
                    IdAccion = Convert.ToInt32(dr["id_Accion"]),
                    Nombre = dr["nombre"].ToString() ?? string.Empty,
                    FechaAlta = Convert.ToDateTime(dr["fechaAlta"]),
                    Activo = Convert.ToBoolean(dr["activo"])
                });
            }

            return lista;
        }

        public bool InsertarAccion(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                INSERT INTO dbo.tblAcciones (nombre, fechaAlta, activo)
                VALUES (@nombre, GETDATE(), 1)";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 50).Value = Nombre.Trim();

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ActualizarAccion(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblAcciones
                SET nombre = @nombre
                WHERE id_Accion = @id_Accion";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Accion", System.Data.SqlDbType.Int).Value = IdAccion;
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 50).Value = Nombre.Trim();

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool CambiarEstatusAccion(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblAcciones
                SET activo = CASE WHEN activo = 1 THEN 0 ELSE 1 END
                WHERE id_Accion = @id_Accion";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Accion", System.Data.SqlDbType.Int).Value = IdAccion;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ExisteNombre(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblAcciones
                WHERE nombre = @nombre
                  AND id_Accion <> @id_Accion";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 50).Value = Nombre.Trim();
            cmd.Parameters.Add("@id_Accion", System.Data.SqlDbType.Int).Value = IdAccion;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }
    }
}
