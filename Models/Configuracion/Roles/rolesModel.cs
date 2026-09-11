using HELPDESK.Services;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Models.Configuracion.Roles {
    public class rolesModel {
        public int IdRol { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public string Descripcion { get; set; } = string.Empty;
        public DateTime FechaAlta { get; set; }
        public bool Activo { get; set; }
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<rolesModel> Roles { get; set; } = new List<rolesModel>();

        public List<rolesModel> ObtenerRoles(DBConector dbConector) {
            List<rolesModel> lista = new List<rolesModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_Rol, nombre, descipcion, fechaAlta, activo
                FROM dbo.tblRoles
                ORDER BY activo DESC, nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new rolesModel {
                    IdRol = Convert.ToInt32(dr["id_Rol"]),
                    Nombre = dr["nombre"].ToString() ?? string.Empty,
                    Descripcion = dr["descipcion"].ToString() ?? string.Empty,
                    FechaAlta = Convert.ToDateTime(dr["fechaAlta"]),
                    Activo = Convert.ToBoolean(dr["activo"])
                });
            }

            return lista;
        }

        public bool InsertarRol(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                INSERT INTO dbo.tblRoles (nombre, descipcion, fechaAlta, activo)
                VALUES (@nombre, @descipcion, GETDATE(), 1)";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 50).Value = Nombre.Trim();
            cmd.Parameters.Add("@descipcion", System.Data.SqlDbType.VarChar, 50).Value = Descripcion.Trim();

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ActualizarRol(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblRoles
                SET nombre = @nombre,
                    descipcion = @descipcion
                WHERE id_Rol = @id_Rol";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Rol", System.Data.SqlDbType.Int).Value = IdRol;
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 50).Value = Nombre.Trim();
            cmd.Parameters.Add("@descipcion", System.Data.SqlDbType.VarChar, 50).Value = Descripcion.Trim();

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool CambiarEstatusRol(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblRoles
                SET activo = CASE WHEN activo = 1 THEN 0 ELSE 1 END
                WHERE id_Rol = @id_Rol";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Rol", System.Data.SqlDbType.Int).Value = IdRol;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ExisteNombre(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblRoles
                WHERE nombre = @nombre
                  AND id_Rol <> @id_Rol";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 50).Value = Nombre.Trim();
            cmd.Parameters.Add("@id_Rol", System.Data.SqlDbType.Int).Value = IdRol;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }
    }
}
