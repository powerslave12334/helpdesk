using HELPDESK.Services;
using Microsoft.Data.SqlClient;
using System.Linq;
using System.Text;

namespace HELPDESK.Models.Menu {
    public class menu {
        public List<menuModulo> Modulos { get; set; } = new List<menuModulo>();

        public List<menuModulo> ObtenerMenu(DBConector dbConector, int idUsuario = 0, bool aplicarPermisos = false) {
            List<menuModulo> modulos = new List<menuModulo>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return modulos;

            if (aplicarPermisos && UsuarioEsAdministrador(cn, idUsuario)) {
                aplicarPermisos = false;
            }

            string query = @"
                WITH PermisosMenu AS (
                    SELECT
                        pu.id_Modulos,
                        pu.id_SubModulos
                    FROM dbo.tblPermisoUsuarios pu
                    WHERE pu.id_Usuario = @id_Usuario
                      AND pu.permitido = 1
                      AND pu.activo = 1

                    UNION

                    SELECT
                        rp.id_Modulos,
                        rp.id_SubModulos
                    FROM dbo.tblRolesUsuarios ru
                    INNER JOIN dbo.tblRoles r ON r.id_Rol = ru.id_Rol
                    INNER JOIN dbo.tblRolPermiso rp ON rp.id_Rol = ru.id_Rol
                    WHERE ru.id_Usuario = @id_Usuario
                      AND ru.activo = 1
                      AND r.activo = 1
                      AND rp.permitido = 1
                      AND rp.activo = 1
                )
                SELECT
                    m.id_Modulos,
                    m.nombre AS modulo,
                    ISNULL(m.orden, 0) AS ordenModulo,
                    sm.id_SubModulos,
                    sm.nombre AS subModulo,
                    ISNULL(sm.orden, 0) AS ordenSubModulo,
                    ISNULL(sm.controller, '') AS controller,
                    ISNULL(sm.action, '') AS action
                FROM dbo.tblModulos m
                LEFT JOIN dbo.tblSubModulos sm
                    ON sm.id_Modulos = m.id_Modulos
                   AND sm.activo = 1
                   AND (
                        @aplicarPermisos = 0
                        OR EXISTS (
                            SELECT 1
                            FROM PermisosMenu pm
                            WHERE pm.id_Modulos = m.id_Modulos
                              AND pm.id_SubModulos = sm.id_SubModulos
                        )
                   )
                WHERE m.activo = 1
                  AND (
                        @aplicarPermisos = 0
                        OR m.nombre LIKE '%Dashboard%'
                        OR EXISTS (
                            SELECT 1
                            FROM dbo.tblSubModulos smCheck
                            WHERE smCheck.id_Modulos = m.id_Modulos
                              AND smCheck.activo = 1
                              AND EXISTS (
                                  SELECT 1
                                  FROM PermisosMenu pm
                                  WHERE pm.id_Modulos = m.id_Modulos
                                    AND pm.id_SubModulos = smCheck.id_SubModulos
                              )
                        )
                  )
                ORDER BY ISNULL(m.orden, 9999), m.nombre ASC, ISNULL(sm.orden, 9999), sm.nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = idUsuario;
            cmd.Parameters.Add("@aplicarPermisos", System.Data.SqlDbType.Bit).Value = aplicarPermisos;
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                int idModulo = Convert.ToInt32(dr["id_Modulos"]);
                menuModulo? modulo = modulos.FirstOrDefault(item => item.IdModulo == idModulo);

                if (modulo == null) {
                    modulo = new menuModulo {
                        IdModulo = idModulo,
                        Nombre = dr["modulo"].ToString() ?? string.Empty,
                        Orden = Convert.ToInt32(dr["ordenModulo"])
                    };

                    modulos.Add(modulo);
                }

                if (dr["id_SubModulos"] != DBNull.Value) {
                    modulo.SubModulos.Add(new menuSubModulo {
                        IdSubModulo = Convert.ToInt32(dr["id_SubModulos"]),
                        IdModulo = idModulo,
                        Nombre = dr["subModulo"].ToString() ?? string.Empty,
                        Orden = Convert.ToInt32(dr["ordenSubModulo"]),
                        Controller = dr["controller"].ToString() ?? string.Empty,
                        Action = string.IsNullOrWhiteSpace(dr["action"].ToString()) ? "Index" : dr["action"].ToString() ?? string.Empty
                    });
                }
            }

            return modulos;
        }

        private bool UsuarioEsAdministrador(SqlConnection cn, int idUsuario) {
            if (idUsuario <= 0)
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
    }

    public class menuModulo {
        public int IdModulo { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public int Orden { get; set; }
        public List<menuSubModulo> SubModulos { get; set; } = new List<menuSubModulo>();
        public string Icono => ObtenerIcono(Nombre);
        public string Llave => CrearLlave(Nombre, IdModulo);

        private static string ObtenerIcono(string nombre) {
            string llave = CrearLlave(nombre, 0);

            if (llave.Contains("dashboard"))
                return "fa-house";
            if (llave.Contains("ticket"))
                return "fa-ticket";
            if (llave.Contains("inventario"))
                return "fa-computer";
            if (llave.Contains("empleado"))
                return "fa-users";
            if (llave.Contains("gps"))
                return "fa-location-dot";
            if (llave.Contains("configuracion"))
                return "fa-gear";

            return "fa-folder";
        }

        private static string CrearLlave(string texto, int id) {
            string normalizado = texto.Normalize(NormalizationForm.FormD);
            StringBuilder limpio = new StringBuilder();

            foreach (char caracter in normalizado) {
                System.Globalization.UnicodeCategory categoria = System.Globalization.CharUnicodeInfo.GetUnicodeCategory(caracter);
                if (categoria != System.Globalization.UnicodeCategory.NonSpacingMark && char.IsLetterOrDigit(caracter)) {
                    limpio.Append(char.ToLowerInvariant(caracter));
                }
            }

            return limpio.Length > 0 ? limpio.ToString() : $"modulo{id}";
        }
    }

    public class menuSubModulo {
        public int IdSubModulo { get; set; }
        public int IdModulo { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public int Orden { get; set; }
        public string Controller { get; set; } = string.Empty;
        public string Action { get; set; } = string.Empty;
        public bool TieneRuta => !string.IsNullOrWhiteSpace(Controller) && !string.IsNullOrWhiteSpace(Action);
    }
}
