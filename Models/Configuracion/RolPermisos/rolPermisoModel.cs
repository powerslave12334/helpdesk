using HELPDESK.Services;
using Microsoft.Data.SqlClient;
using System.Collections.Generic;
using System.Linq;

namespace HELPDESK.Models.Configuracion.RolPermisos {
    public class rolPermisoModel {
        public int IdRolPermiso { get; set; }
        public int IdRol { get; set; }
        public int IdModulo { get; set; }
        public int IdSubModulo { get; set; }
        public int IdAccion { get; set; }
        public string NombreRol { get; set; } = string.Empty;
        public string NombreModulo { get; set; } = string.Empty;
        public string NombreSubModulo { get; set; } = string.Empty;
        public string NombreAccion { get; set; } = string.Empty;
        public bool Permitido { get; set; }
        public DateTime FechaAlta { get; set; }
        public bool Activo { get; set; }
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<string> PermisosSeleccionados { get; set; } = new List<string>();
        public List<rolPermisoModel> Permisos { get; set; } = new List<rolPermisoModel>();
        public List<rolPermisoModel> Roles { get; set; } = new List<rolPermisoModel>();
        public List<rolPermisoModel> Modulos { get; set; } = new List<rolPermisoModel>();
        public List<rolPermisoModel> SubModulos { get; set; } = new List<rolPermisoModel>();
        public List<rolPermisoModel> Acciones { get; set; } = new List<rolPermisoModel>();

        public List<rolPermisoModel> ObtenerPermisos(DBConector dbConector) {
            return ObtenerPermisos(dbConector, 0);
        }

        public List<rolPermisoModel> ObtenerPermisos(DBConector dbConector, int idRol) {
            List<rolPermisoModel> lista = new List<rolPermisoModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT
                    rp.id_RolPermiso,
                    rp.id_Rol,
                    rp.id_Modulos AS id_Modulo,
                    rp.id_SubModulos AS id_SubModulo,
                    rp.id_Accion,
                    r.nombre AS nombreRol,
                    m.nombre AS nombreModulo,
                    sm.nombre AS nombreSubModulo,
                    a.nombre AS nombreAccion,
                    rp.permitido,
                    rp.fechaAlta,
                    rp.activo
                FROM dbo.tblRolPermiso rp
                INNER JOIN dbo.tblRoles r ON r.id_Rol = rp.id_Rol
                INNER JOIN dbo.tblModulos m ON m.id_Modulos = rp.id_Modulos
                INNER JOIN dbo.tblSubModulos sm ON sm.id_SubModulos = rp.id_SubModulos
                INNER JOIN dbo.tblAcciones a ON a.id_Accion = rp.id_Accion
                WHERE (@id_Rol = 0 OR rp.id_Rol = @id_Rol)
                ORDER BY rp.activo DESC, r.nombre ASC, m.nombre ASC, sm.nombre ASC, a.nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Rol", System.Data.SqlDbType.Int).Value = idRol;
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new rolPermisoModel {
                    IdRolPermiso = Convert.ToInt32(dr["id_RolPermiso"]),
                    IdRol = Convert.ToInt32(dr["id_Rol"]),
                    IdModulo = Convert.ToInt32(dr["id_Modulo"]),
                    IdSubModulo = Convert.ToInt32(dr["id_SubModulo"]),
                    IdAccion = Convert.ToInt32(dr["id_Accion"]),
                    NombreRol = dr["nombreRol"].ToString() ?? string.Empty,
                    NombreModulo = dr["nombreModulo"].ToString() ?? string.Empty,
                    NombreSubModulo = dr["nombreSubModulo"].ToString() ?? string.Empty,
                    NombreAccion = dr["nombreAccion"].ToString() ?? string.Empty,
                    Permitido = Convert.ToBoolean(dr["permitido"]),
                    FechaAlta = Convert.ToDateTime(dr["fechaAlta"]),
                    Activo = Convert.ToBoolean(dr["activo"])
                });
            }

            return lista;
        }

        public List<rolPermisoModel> ObtenerRolesActivos(DBConector dbConector) {
            return ObtenerCatalogo(dbConector, "SELECT id_Rol AS id, nombre FROM dbo.tblRoles WHERE activo = 1 ORDER BY nombre ASC", "rol");
        }

        public List<rolPermisoModel> ObtenerModulosActivos(DBConector dbConector) {
            return ObtenerCatalogo(dbConector, "SELECT id_Modulos AS id, nombre FROM dbo.tblModulos WHERE activo = 1 ORDER BY nombre ASC", "modulo");
        }

        public List<rolPermisoModel> ObtenerAccionesActivas(DBConector dbConector) {
            return ObtenerCatalogo(dbConector, "SELECT id_Accion AS id, nombre FROM dbo.tblAcciones WHERE activo = 1 ORDER BY nombre ASC", "accion");
        }

        public List<rolPermisoModel> ObtenerSubModulosActivos(DBConector dbConector) {
            List<rolPermisoModel> lista = new List<rolPermisoModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_SubModulos, id_Modulos AS id_Modulo, nombre
                FROM dbo.tblSubModulos
                WHERE activo = 1
                ORDER BY nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new rolPermisoModel {
                    IdSubModulo = Convert.ToInt32(dr["id_SubModulos"]),
                    IdModulo = Convert.ToInt32(dr["id_Modulo"]),
                    NombreSubModulo = dr["nombre"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public bool InsertarPermiso(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                INSERT INTO dbo.tblRolPermiso (id_Rol, id_Modulos, id_SubModulos, id_Accion, permitido, fechaAlta, activo)
                VALUES (@id_Rol, @id_Modulo, @id_SubModulo, @id_Accion, @permitido, GETDATE(), 1)";

            using SqlCommand cmd = CrearComandoPermiso(query, cn);
            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ActualizarPermisoExistente(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblRolPermiso
                SET permitido = @permitido,
                    activo = 1
                WHERE id_Rol = @id_Rol
                  AND id_Modulos = @id_Modulo
                  AND id_SubModulos = @id_SubModulo
                  AND id_Accion = @id_Accion";

            using SqlCommand cmd = CrearComandoPermiso(query, cn);
            return cmd.ExecuteNonQuery() > 0;
        }

        public bool CambiarEstatusPermiso(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblRolPermiso
                SET activo = CASE WHEN activo = 1 THEN 0 ELSE 1 END
                WHERE id_RolPermiso = @id_RolPermiso";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_RolPermiso", System.Data.SqlDbType.Int).Value = IdRolPermiso;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ExistePermiso(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblRolPermiso
                WHERE id_Rol = @id_Rol
                  AND id_Modulos = @id_Modulo
                  AND id_SubModulos = @id_SubModulo
                  AND id_Accion = @id_Accion";

            using SqlCommand cmd = CrearComandoPermiso(query, cn);
            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        public bool GuardarMatrizPermisos(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            using SqlTransaction transaction = cn.BeginTransaction();

            try {
                HashSet<string> seleccionados = new HashSet<string>(PermisosSeleccionados ?? new List<string>());
                List<rolPermisoModel> combinaciones = ObtenerCombinacionesPermisos(cn, transaction);

                foreach (rolPermisoModel item in combinaciones) {
                    string llave = CrearLlavePermiso(item.IdModulo, item.IdSubModulo, item.IdAccion);
                    item.IdRol = IdRol;
                    item.Permitido = seleccionados.Contains(llave);

                    if (ExistePermiso(cn, transaction, item)) {
                        ActualizarPermiso(cn, transaction, item);
                    } else if (item.Permitido) {
                        InsertarPermiso(cn, transaction, item);
                    }
                }

                transaction.Commit();
                return true;
            } catch {
                transaction.Rollback();
                return false;
            }
        }

        public static string CrearLlavePermiso(int idModulo, int idSubModulo, int idAccion) {
            return $"{idModulo}|{idSubModulo}|{idAccion}";
        }

        public bool PermisoActivo(int idModulo, int idSubModulo, int idAccion) {
            return Permisos.Any(item =>
                item.IdRol == IdRol
                && item.IdModulo == idModulo
                && item.IdSubModulo == idSubModulo
                && item.IdAccion == idAccion
                && item.Permitido
                && item.Activo);
        }

        private SqlCommand CrearComandoPermiso(string query, SqlConnection cn) {
            SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Rol", System.Data.SqlDbType.Int).Value = IdRol;
            cmd.Parameters.Add("@id_Modulo", System.Data.SqlDbType.Int).Value = IdModulo;
            cmd.Parameters.Add("@id_SubModulo", System.Data.SqlDbType.Int).Value = IdSubModulo;
            cmd.Parameters.Add("@id_Accion", System.Data.SqlDbType.Int).Value = IdAccion;
            cmd.Parameters.Add("@permitido", System.Data.SqlDbType.Bit).Value = Permitido;
            return cmd;
        }

        private List<rolPermisoModel> ObtenerCombinacionesPermisos(SqlConnection cn, SqlTransaction transaction) {
            List<rolPermisoModel> lista = new List<rolPermisoModel>();

            string query = @"
                SELECT
                    m.id_Modulos,
                    sm.id_SubModulos,
                    a.id_Accion
                FROM dbo.tblModulos m
                INNER JOIN dbo.tblSubModulos sm ON sm.id_Modulos = m.id_Modulos
                CROSS JOIN dbo.tblAcciones a
                WHERE m.activo = 1
                  AND sm.activo = 1
                  AND a.activo = 1";

            using SqlCommand cmd = new SqlCommand(query, cn, transaction);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new rolPermisoModel {
                    IdModulo = Convert.ToInt32(dr["id_Modulos"]),
                    IdSubModulo = Convert.ToInt32(dr["id_SubModulos"]),
                    IdAccion = Convert.ToInt32(dr["id_Accion"])
                });
            }

            return lista;
        }

        private bool ExistePermiso(SqlConnection cn, SqlTransaction transaction, rolPermisoModel item) {
            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblRolPermiso
                WHERE id_Rol = @id_Rol
                  AND id_Modulos = @id_Modulo
                  AND id_SubModulos = @id_SubModulo
                  AND id_Accion = @id_Accion";

            using SqlCommand cmd = CrearComandoPermiso(query, cn, transaction, item);
            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        private void InsertarPermiso(SqlConnection cn, SqlTransaction transaction, rolPermisoModel item) {
            string query = @"
                INSERT INTO dbo.tblRolPermiso (id_Rol, id_Modulos, id_SubModulos, id_Accion, permitido, fechaAlta, activo)
                VALUES (@id_Rol, @id_Modulo, @id_SubModulo, @id_Accion, @permitido, GETDATE(), 1)";

            using SqlCommand cmd = CrearComandoPermiso(query, cn, transaction, item);
            cmd.ExecuteNonQuery();
        }

        private void ActualizarPermiso(SqlConnection cn, SqlTransaction transaction, rolPermisoModel item) {
            string query = @"
                UPDATE dbo.tblRolPermiso
                SET permitido = @permitido,
                    activo = 1
                WHERE id_Rol = @id_Rol
                  AND id_Modulos = @id_Modulo
                  AND id_SubModulos = @id_SubModulo
                  AND id_Accion = @id_Accion";

            using SqlCommand cmd = CrearComandoPermiso(query, cn, transaction, item);
            cmd.ExecuteNonQuery();
        }

        private SqlCommand CrearComandoPermiso(string query, SqlConnection cn, SqlTransaction transaction, rolPermisoModel item) {
            SqlCommand cmd = new SqlCommand(query, cn, transaction);
            cmd.Parameters.Add("@id_Rol", System.Data.SqlDbType.Int).Value = item.IdRol;
            cmd.Parameters.Add("@id_Modulo", System.Data.SqlDbType.Int).Value = item.IdModulo;
            cmd.Parameters.Add("@id_SubModulo", System.Data.SqlDbType.Int).Value = item.IdSubModulo;
            cmd.Parameters.Add("@id_Accion", System.Data.SqlDbType.Int).Value = item.IdAccion;
            cmd.Parameters.Add("@permitido", System.Data.SqlDbType.Bit).Value = item.Permitido;
            return cmd;
        }

        private List<rolPermisoModel> ObtenerCatalogo(DBConector dbConector, string query, string tipo) {
            List<rolPermisoModel> lista = new List<rolPermisoModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                rolPermisoModel item = new rolPermisoModel();
                int id = Convert.ToInt32(dr["id"]);
                string nombre = dr["nombre"].ToString() ?? string.Empty;

                if (tipo == "rol") {
                    item.IdRol = id;
                    item.NombreRol = nombre;
                } else if (tipo == "modulo") {
                    item.IdModulo = id;
                    item.NombreModulo = nombre;
                } else {
                    item.IdAccion = id;
                    item.NombreAccion = nombre;
                }

                lista.Add(item);
            }

            return lista;
        }
    }
}
