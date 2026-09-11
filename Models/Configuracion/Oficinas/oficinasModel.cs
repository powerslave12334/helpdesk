using HELPDESK.Services;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Models.Configuracion.Oficinas {
    public class oficinasModel {
        public int IdOficinas { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public string Descripcion { get; set; } = string.Empty;
        public DateTime FechaAlta { get; set; }
        public bool Activo { get; set; }
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<oficinasModel> Oficinas { get; set; } = new List<oficinasModel>();

        public List<oficinasModel> ObtenerOficinas(DBConector dbConector) {
            List<oficinasModel> lista = new List<oficinasModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_Oficinas, nombre, descripcion, fechaAlta, activo
                FROM dbo.tblOficinas
                ORDER BY activo DESC, nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new oficinasModel {
                    IdOficinas = Convert.ToInt32(dr["id_Oficinas"]),
                    Nombre = dr["nombre"].ToString() ?? string.Empty,
                    Descripcion = dr["descripcion"].ToString() ?? string.Empty,
                    FechaAlta = Convert.ToDateTime(dr["fechaAlta"]),
                    Activo = Convert.ToBoolean(dr["activo"])
                });
            }

            return lista;
        }

        public bool InsertarOficina(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                INSERT INTO dbo.tblOficinas (nombre, descripcion, fechaAlta, activo)
                VALUES (@nombre, @descripcion, GETDATE(), 1)";

            using SqlCommand cmd = new SqlCommand(query, cn);
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ActualizarOficina(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblOficinas
                SET nombre = @nombre,
                    descripcion = @descripcion
                WHERE id_Oficinas = @id_Oficinas";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Oficinas", System.Data.SqlDbType.Int).Value = IdOficinas;
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool CambiarEstatus(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblOficinas
                SET activo = CASE WHEN activo = 1 THEN 0 ELSE 1 END
                WHERE id_Oficinas = @id_Oficinas";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Oficinas", System.Data.SqlDbType.Int).Value = IdOficinas;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ExisteNombre(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblOficinas
                WHERE LOWER(nombre) = LOWER(@nombre)
                  AND id_Oficinas <> @id_Oficinas";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 5).Value = Nombre.Trim();
            cmd.Parameters.Add("@id_Oficinas", System.Data.SqlDbType.Int).Value = IdOficinas;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        private void AgregarParametros(SqlCommand cmd) {
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 5).Value = Nombre.Trim();
            cmd.Parameters.Add("@descripcion", System.Data.SqlDbType.VarChar, 250).Value = Descripcion.Trim();
        }
    }
}
