using Microsoft.Data.SqlClient;
using System.Collections.Generic;

namespace HELPDESK.Services {
    public class PermisosService {
        private readonly DBConector _dbConector;

        public PermisosService(DBConector dbConector) {
            _dbConector = dbConector;
        }

        public bool TienePermiso(int idUsuario, int idModulo, int idSubModulo, int idAccion) {
            if (UsuarioEsAdministrador(idUsuario))
                return true;

            bool? permisoUsuario = ObtenerPermisoUsuario(idUsuario, idModulo, idSubModulo, idAccion);

            if (permisoUsuario.HasValue)
                return permisoUsuario.Value;

            bool? permisoRol = ObtenerPermisoRol(idUsuario, idModulo, idSubModulo, idAccion);

            if (permisoRol.HasValue)
                return permisoRol.Value;

            return false;
        }

        public bool TienePermisoPorRuta(int idUsuario, string controller, string accion) {
            if (idUsuario <= 0)
                return false;

            if (UsuarioEsAdministrador(idUsuario))
                return true;

            (int idModulo, int idSubModulo) = ObtenerModuloSubModuloPorController(controller);
            if (idModulo <= 0 || idSubModulo <= 0)
                return false;

            List<int> idsAccion = ObtenerIdsAccion(accion);
            if (idsAccion.Count == 0)
                return false;

            foreach (int idAccion in idsAccion) {
                if (TienePermiso(idUsuario, idModulo, idSubModulo, idAccion))
                    return true;
            }

            return false;
        }

        public int ObtenerIdUsuarioPorLogin(string usuario) {
            using SqlConnection? cn = _dbConector.AbrirConexion();
            if (cn == null)
                return 0;

            string query = @"
                SELECT TOP 1 id_Usuario
                FROM dbo.tblLogin
                WHERE usuario = @usuario
                  AND activo = 1";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@usuario", System.Data.SqlDbType.VarChar, 50).Value = usuario;

            object? result = cmd.ExecuteScalar();
            return result == null || result == DBNull.Value ? 0 : Convert.ToInt32(result);
        }

        private bool UsuarioEsAdministrador(int idUsuario) {
            using SqlConnection? cn = _dbConector.AbrirConexion();
            if (cn == null || idUsuario <= 0)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblRolesUsuarios ru
                INNER JOIN dbo.tblRoles r ON r.id_Rol = ru.id_Rol
                WHERE ru.id_Usuario = @id_Usuario
                  AND ru.activo = 1
                  AND r.activo = 1
                  AND (
                      r.nombre LIKE '%admin%'
                      OR LOWER(r.nombre) = 'root'
                  )";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = idUsuario;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        private (int idModulo, int idSubModulo) ObtenerModuloSubModuloPorController(string controller) {
            using SqlConnection? cn = _dbConector.AbrirConexion();
            if (cn == null || string.IsNullOrWhiteSpace(controller))
                return (0, 0);

            string query = @"
                SELECT TOP 1 id_Modulos, id_SubModulos
                FROM dbo.tblSubModulos
                WHERE LOWER(controller) = LOWER(@controller)
                  AND activo = 1";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@controller", System.Data.SqlDbType.VarChar, 100).Value = controller.Trim();

            using SqlDataReader dr = cmd.ExecuteReader();
            if (!dr.Read())
                return (0, 0);

            return (Convert.ToInt32(dr["id_Modulos"]), Convert.ToInt32(dr["id_SubModulos"]));
        }

        private int ObtenerIdAccion(string accion) {
            List<int> ids = ObtenerIdsAccion(accion);
            return ids.Count == 0 ? 0 : ids[0];
        }

        private List<int> ObtenerIdsAccion(string accion) {
            List<int> ids = new List<int>();
            using SqlConnection? cn = _dbConector.AbrirConexion();
            if (cn == null || string.IsNullOrWhiteSpace(accion))
                return ids;

            List<string> nombres = ObtenerAliasesAccion(accion);

            foreach (string nombre in nombres) {
                string query = @"
                    SELECT TOP 1 id_Accion
                    FROM dbo.tblAcciones
                    WHERE LOWER(nombre) = LOWER(@nombre)
                      AND activo = 1";

                using SqlCommand cmd = new SqlCommand(query, cn);
                cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 50).Value = nombre;

                object? result = cmd.ExecuteScalar();
                if (result != null && result != DBNull.Value) {
                    int idAccion = Convert.ToInt32(result);
                    if (!ids.Contains(idAccion))
                        ids.Add(idAccion);
                }
            }

            return ids;
        }

        private List<string> ObtenerAliasesAccion(string accion) {
            string nombre = accion.Trim();
            string llave = nombre.ToLowerInvariant();
            List<string> aliases = new List<string> { nombre };

            if (llave == "consultar" || llave == "leer" || llave == "lectura") {
                aliases.AddRange(new[] { "Consultar", "Consulta", "Leer", "Lectura", "Read" });
            } else if (llave == "crear") {
                aliases.AddRange(new[] { "Crear", "Alta", "Agregar", "Escritura", "Create" });
            } else if (llave == "actualizar" || llave == "editar") {
                aliases.AddRange(new[] { "Actualizar", "Editar", "Cambios", "Modificar", "Update" });
            } else if (llave == "eliminar") {
                aliases.AddRange(new[] { "Eliminar", "Baja", "Delete" });
            }

            return aliases;
        }

        private bool? ObtenerPermisoUsuario(int idUsuario, int idModulo, int idSubModulo, int idAccion) {
            using SqlConnection? cn = _dbConector.AbrirConexion();
            if (cn == null)
                return null;

            string query = @"
                SELECT TOP 1 permitido
                FROM dbo.tblPermisoUsuarios
                WHERE id_Usuario = @id_Usuario
                  AND id_Modulos = @id_Modulo
                  AND id_SubModulos = @id_SubModulo
                  AND id_Accion = @id_Accion
                  AND activo = 1
                ORDER BY id_PermisoUsuarios DESC";

            using SqlCommand cmd = CrearComandoPermiso(query, cn, idUsuario, idModulo, idSubModulo, idAccion);
            object? result = cmd.ExecuteScalar();

            if (result == null || result == DBNull.Value)
                return null;

            bool permitido = Convert.ToBoolean(result);
            return permitido ? true : null;
        }

        private bool? ObtenerPermisoRol(int idUsuario, int idModulo, int idSubModulo, int idAccion) {
            using SqlConnection? cn = _dbConector.AbrirConexion();
            if (cn == null)
                return null;

            string query = @"
                SELECT
                    CASE
                        WHEN SUM(CASE WHEN rp.permitido = 1 THEN 1 ELSE 0 END) > 0 THEN CAST(1 AS bit)
                        ELSE CAST(0 AS bit)
                    END AS permitido
                FROM dbo.tblRolesUsuarios ru
                INNER JOIN dbo.tblRolPermiso rp ON rp.id_Rol = ru.id_Rol
                INNER JOIN dbo.tblRoles r ON r.id_Rol = ru.id_Rol
                WHERE ru.id_Usuario = @id_Usuario
                  AND ru.activo = 1
                  AND r.activo = 1
                  AND rp.id_Modulos = @id_Modulo
                  AND rp.id_SubModulos = @id_SubModulo
                  AND rp.id_Accion = @id_Accion
                  AND rp.activo = 1
                HAVING COUNT(1) > 0";

            using SqlCommand cmd = CrearComandoPermiso(query, cn, idUsuario, idModulo, idSubModulo, idAccion);
            object? result = cmd.ExecuteScalar();

            if (result == null || result == DBNull.Value)
                return null;

            return Convert.ToBoolean(result);
        }

        private SqlCommand CrearComandoPermiso(string query, SqlConnection cn, int idUsuario, int idModulo, int idSubModulo, int idAccion) {
            SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = idUsuario;
            cmd.Parameters.Add("@id_Modulo", System.Data.SqlDbType.Int).Value = idModulo;
            cmd.Parameters.Add("@id_SubModulo", System.Data.SqlDbType.Int).Value = idSubModulo;
            cmd.Parameters.Add("@id_Accion", System.Data.SqlDbType.Int).Value = idAccion;
            return cmd;
        }
    }
}
