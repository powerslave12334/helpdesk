using HELPDESK.Services;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Models.Tickets.TipoEquipoTicket {
    public class tipoEquipoTicketModel {
        public int IdTipoEquipoTicket { get; set; }
        public string Clave { get; set; } = string.Empty;
        public string Descripcion { get; set; } = string.Empty;
        public DateTime FechaAlta { get; set; }
        public bool Activo { get; set; }
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<tipoEquipoTicketModel> TiposEquipoTicket { get; set; } = new List<tipoEquipoTicketModel>();

        public List<tipoEquipoTicketModel> ObtenerTiposEquipoTicket(DBConector dbConector) {
            List<tipoEquipoTicketModel> lista = new List<tipoEquipoTicketModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_TipoEquipoTicket, clave, descripcion, fechaAlta, activo
                FROM dbo.tblTipoEquipoTicket
                ORDER BY activo DESC, id_TipoEquipoTicket ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new tipoEquipoTicketModel {
                    IdTipoEquipoTicket = Convert.ToInt32(dr["id_TipoEquipoTicket"]),
                    Clave = dr["clave"].ToString() ?? string.Empty,
                    Descripcion = dr["descripcion"].ToString() ?? string.Empty,
                    FechaAlta = Convert.ToDateTime(dr["fechaAlta"]),
                    Activo = Convert.ToBoolean(dr["activo"])
                });
            }

            return lista;
        }

        public bool InsertarTipoEquipoTicket(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                INSERT INTO dbo.tblTipoEquipoTicket (clave, descripcion, fechaAlta, activo)
                VALUES (@clave, @descripcion, GETDATE(), 1)";

            using SqlCommand cmd = new SqlCommand(query, cn);
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ActualizarTipoEquipoTicket(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblTipoEquipoTicket
                SET clave = @clave,
                    descripcion = @descripcion
                WHERE id_TipoEquipoTicket = @id_TipoEquipoTicket";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_TipoEquipoTicket", System.Data.SqlDbType.Int).Value = IdTipoEquipoTicket;
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool CambiarEstatus(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblTipoEquipoTicket
                SET activo = CASE WHEN activo = 1 THEN 0 ELSE 1 END
                WHERE id_TipoEquipoTicket = @id_TipoEquipoTicket";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_TipoEquipoTicket", System.Data.SqlDbType.Int).Value = IdTipoEquipoTicket;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ExisteClave(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblTipoEquipoTicket
                WHERE LOWER(clave) = LOWER(@clave)
                  AND id_TipoEquipoTicket <> @id_TipoEquipoTicket";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@clave", System.Data.SqlDbType.VarChar, 20).Value = Clave.Trim();
            cmd.Parameters.Add("@id_TipoEquipoTicket", System.Data.SqlDbType.Int).Value = IdTipoEquipoTicket;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        private void AgregarParametros(SqlCommand cmd) {
            cmd.Parameters.Add("@clave", System.Data.SqlDbType.VarChar, 20).Value = Clave.Trim().ToUpperInvariant();
            cmd.Parameters.Add("@descripcion", System.Data.SqlDbType.VarChar, 100).Value = Descripcion.Trim();
        }
    }
}
