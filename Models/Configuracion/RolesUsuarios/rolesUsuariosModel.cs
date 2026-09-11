using HELPDESK.Services;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Models.Configuracion.RolesUsuarios {
    public class rolesUsuariosModel {
        public int IdRolesUsuario { get; set; }
        public int IdRol { get; set; }
        public int IdUsuario { get; set; }
        public string NombreRol { get; set; } = string.Empty;
        public string NombreUsuario { get; set; } = string.Empty;
        public DateTime FechaAlta { get; set; }
        public bool Activo { get; set; }
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<rolesUsuariosModel> RolesUsuarios { get; set; } = new List<rolesUsuariosModel>();
        public List<rolesUsuariosModel> Roles { get; set; } = new List<rolesUsuariosModel>();
        public List<rolesUsuariosModel> Usuarios { get; set; } = new List<rolesUsuariosModel>();

        public List<rolesUsuariosModel> ObtenerRolesUsuarios(DBConector dbConector) {
            List<rolesUsuariosModel> lista = new List<rolesUsuariosModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT
                    ru.id_RolesUsuario,
                    ru.id_Rol,
                    ru.id_Usuario,
                    r.nombre AS nombreRol,
                    LTRIM(RTRIM(u.nombre + ' ' + u.app + ' ' + u.apm)) AS nombreUsuario,
                    ru.fechaAlta,
                    ru.activo
                FROM dbo.tblRolesUsuarios ru
                INNER JOIN dbo.tblRoles r ON r.id_Rol = ru.id_Rol
                INNER JOIN dbo.tblUsuario u ON u.id_Usuario = ru.id_Usuario
                ORDER BY ru.activo DESC, nombreUsuario ASC, nombreRol ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new rolesUsuariosModel {
                    IdRolesUsuario = Convert.ToInt32(dr["id_RolesUsuario"]),
                    IdRol = Convert.ToInt32(dr["id_Rol"]),
                    IdUsuario = Convert.ToInt32(dr["id_Usuario"]),
                    NombreRol = dr["nombreRol"].ToString() ?? string.Empty,
                    NombreUsuario = dr["nombreUsuario"].ToString() ?? string.Empty,
                    FechaAlta = Convert.ToDateTime(dr["fechaAlta"]),
                    Activo = Convert.ToBoolean(dr["activo"])
                });
            }

            return lista;
        }

        public List<rolesUsuariosModel> ObtenerRolesActivos(DBConector dbConector) {
            List<rolesUsuariosModel> lista = new List<rolesUsuariosModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_Rol, nombre
                FROM dbo.tblRoles
                WHERE activo = 1
                ORDER BY nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new rolesUsuariosModel {
                    IdRol = Convert.ToInt32(dr["id_Rol"]),
                    NombreRol = dr["nombre"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public List<rolesUsuariosModel> ObtenerUsuariosActivos(DBConector dbConector) {
            List<rolesUsuariosModel> lista = new List<rolesUsuariosModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_Usuario, LTRIM(RTRIM(nombre + ' ' + app + ' ' + apm)) AS nombreUsuario
                FROM dbo.tblUsuario
                WHERE activo = 1
                ORDER BY nombreUsuario ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new rolesUsuariosModel {
                    IdUsuario = Convert.ToInt32(dr["id_Usuario"]),
                    NombreUsuario = dr["nombreUsuario"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public bool InsertarRolUsuario(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                INSERT INTO dbo.tblRolesUsuarios (id_Rol, id_Usuario, fechaAlta, activo)
                VALUES (@id_Rol, @id_Usuario, GETDATE(), 1)";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Rol", System.Data.SqlDbType.Int).Value = IdRol;
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = IdUsuario;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ReactivarRolUsuario(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblRolesUsuarios
                SET activo = 1
                WHERE id_Rol = @id_Rol
                  AND id_Usuario = @id_Usuario";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Rol", System.Data.SqlDbType.Int).Value = IdRol;
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = IdUsuario;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool CambiarEstatusRolUsuario(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblRolesUsuarios
                SET activo = CASE WHEN activo = 1 THEN 0 ELSE 1 END
                WHERE id_RolesUsuario = @id_RolesUsuario";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_RolesUsuario", System.Data.SqlDbType.Int).Value = IdRolesUsuario;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ExisteRelacion(DBConector dbConector, bool soloActivas) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string filtroActivo = soloActivas ? "AND activo = 1" : string.Empty;
            string query = $@"
                SELECT COUNT(1)
                FROM dbo.tblRolesUsuarios
                WHERE id_Rol = @id_Rol
                  AND id_Usuario = @id_Usuario
                  {filtroActivo}";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Rol", System.Data.SqlDbType.Int).Value = IdRol;
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = IdUsuario;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        public bool UsuarioTieneRolActivo(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblRolesUsuarios
                WHERE id_Usuario = @id_Usuario
                  AND activo = 1";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = IdUsuario;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }
    }
}
