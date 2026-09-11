using HELPDESK.Services;
using Microsoft.Data.SqlClient;
using System.Collections.Generic;
using System.Linq;

namespace HELPDESK.Models.Configuracion.PermisoUsuarios {
    public class permisoUsuariosModel {
        public int IdPermisoUsuarios { get; set; }
        public int IdUsuario { get; set; }
        public int IdModulo { get; set; }
        public int IdSubModulo { get; set; }
        public int IdAccion { get; set; }
        public string NombreUsuario { get; set; } = string.Empty;
        public string NombreModulo { get; set; } = string.Empty;
        public string NombreSubModulo { get; set; } = string.Empty;
        public string NombreAccion { get; set; } = string.Empty;
        public bool Permitido { get; set; }
        public DateTime FechaAlta { get; set; }
        public bool Activo { get; set; }
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<string> PermisosSeleccionados { get; set; } = new List<string>();
        public List<permisoUsuariosModel> Permisos { get; set; } = new List<permisoUsuariosModel>();
        public List<permisoUsuariosModel> Usuarios { get; set; } = new List<permisoUsuariosModel>();
        public List<permisoUsuariosModel> Modulos { get; set; } = new List<permisoUsuariosModel>();
        public List<permisoUsuariosModel> SubModulos { get; set; } = new List<permisoUsuariosModel>();
        public List<permisoUsuariosModel> Acciones { get; set; } = new List<permisoUsuariosModel>();

        public List<permisoUsuariosModel> ObtenerPermisos(DBConector dbConector) {
            return ObtenerPermisos(dbConector, 0);
        }

        public List<permisoUsuariosModel> ObtenerPermisos(DBConector dbConector, int idUsuario) {
            List<permisoUsuariosModel> lista = new List<permisoUsuariosModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT
                    pu.id_PermisoUsuarios,
                    pu.id_Usuario,
                    pu.id_Modulos AS id_Modulo,
                    pu.id_SubModulos AS id_SubModulo,
                    pu.id_Accion,
                    LTRIM(RTRIM(u.nombre + ' ' + u.app + ' ' + u.apm)) AS nombreUsuario,
                    m.nombre AS nombreModulo,
                    sm.nombre AS nombreSubModulo,
                    a.nombre AS nombreAccion,
                    pu.permitido,
                    pu.fechaAlta,
                    pu.activo
                FROM dbo.tblPermisoUsuarios pu
                INNER JOIN dbo.tblUsuario u ON u.id_Usuario = pu.id_Usuario
                INNER JOIN dbo.tblModulos m ON m.id_Modulos = pu.id_Modulos
                INNER JOIN dbo.tblSubModulos sm ON sm.id_SubModulos = pu.id_SubModulos
                INNER JOIN dbo.tblAcciones a ON a.id_Accion = pu.id_Accion
                WHERE (@id_Usuario = 0 OR pu.id_Usuario = @id_Usuario)
                ORDER BY pu.activo DESC, nombreUsuario ASC, m.nombre ASC, sm.nombre ASC, a.nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = idUsuario;
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new permisoUsuariosModel {
                    IdPermisoUsuarios = Convert.ToInt32(dr["id_PermisoUsuarios"]),
                    IdUsuario = Convert.ToInt32(dr["id_Usuario"]),
                    IdModulo = Convert.ToInt32(dr["id_Modulo"]),
                    IdSubModulo = Convert.ToInt32(dr["id_SubModulo"]),
                    IdAccion = Convert.ToInt32(dr["id_Accion"]),
                    NombreUsuario = dr["nombreUsuario"].ToString() ?? string.Empty,
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

        public List<permisoUsuariosModel> ObtenerUsuariosActivos(DBConector dbConector) {
            List<permisoUsuariosModel> lista = new List<permisoUsuariosModel>();

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
                lista.Add(new permisoUsuariosModel {
                    IdUsuario = Convert.ToInt32(dr["id_Usuario"]),
                    NombreUsuario = dr["nombreUsuario"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public List<permisoUsuariosModel> ObtenerModulosActivos(DBConector dbConector) {
            return ObtenerCatalogo(dbConector, "SELECT id_Modulos AS id, nombre FROM dbo.tblModulos WHERE activo = 1 ORDER BY nombre ASC", "modulo");
        }

        public List<permisoUsuariosModel> ObtenerAccionesActivas(DBConector dbConector) {
            return ObtenerCatalogo(dbConector, "SELECT id_Accion AS id, nombre FROM dbo.tblAcciones WHERE activo = 1 ORDER BY nombre ASC", "accion");
        }

        public List<permisoUsuariosModel> ObtenerSubModulosActivos(DBConector dbConector) {
            List<permisoUsuariosModel> lista = new List<permisoUsuariosModel>();

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
                lista.Add(new permisoUsuariosModel {
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
                INSERT INTO dbo.tblPermisoUsuarios (id_Usuario, id_Modulos, id_SubModulos, id_Accion, permitido, fechaAlta, activo)
                VALUES (@id_Usuario, @id_Modulo, @id_SubModulo, @id_Accion, @permitido, GETDATE(), 1)";

            using SqlCommand cmd = CrearComandoPermiso(query, cn);
            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ActualizarPermisoExistente(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblPermisoUsuarios
                SET permitido = @permitido,
                    activo = 1
                WHERE id_Usuario = @id_Usuario
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
                UPDATE dbo.tblPermisoUsuarios
                SET activo = CASE WHEN activo = 1 THEN 0 ELSE 1 END
                WHERE id_PermisoUsuarios = @id_PermisoUsuarios";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_PermisoUsuarios", System.Data.SqlDbType.Int).Value = IdPermisoUsuarios;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ExistePermiso(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblPermisoUsuarios
                WHERE id_Usuario = @id_Usuario
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
                List<permisoUsuariosModel> combinaciones = ObtenerCombinacionesPermisos(cn, transaction);

                foreach (permisoUsuariosModel item in combinaciones) {
                    string llave = CrearLlavePermiso(item.IdModulo, item.IdSubModulo, item.IdAccion);
                    item.IdUsuario = IdUsuario;
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
                item.IdUsuario == IdUsuario
                && item.IdModulo == idModulo
                && item.IdSubModulo == idSubModulo
                && item.IdAccion == idAccion
                && item.Permitido
                && item.Activo);
        }

        private SqlCommand CrearComandoPermiso(string query, SqlConnection cn) {
            SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = IdUsuario;
            cmd.Parameters.Add("@id_Modulo", System.Data.SqlDbType.Int).Value = IdModulo;
            cmd.Parameters.Add("@id_SubModulo", System.Data.SqlDbType.Int).Value = IdSubModulo;
            cmd.Parameters.Add("@id_Accion", System.Data.SqlDbType.Int).Value = IdAccion;
            cmd.Parameters.Add("@permitido", System.Data.SqlDbType.Bit).Value = Permitido;
            return cmd;
        }

        private List<permisoUsuariosModel> ObtenerCombinacionesPermisos(SqlConnection cn, SqlTransaction transaction) {
            List<permisoUsuariosModel> lista = new List<permisoUsuariosModel>();

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
                lista.Add(new permisoUsuariosModel {
                    IdModulo = Convert.ToInt32(dr["id_Modulos"]),
                    IdSubModulo = Convert.ToInt32(dr["id_SubModulos"]),
                    IdAccion = Convert.ToInt32(dr["id_Accion"])
                });
            }

            return lista;
        }

        private bool ExistePermiso(SqlConnection cn, SqlTransaction transaction, permisoUsuariosModel item) {
            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblPermisoUsuarios
                WHERE id_Usuario = @id_Usuario
                  AND id_Modulos = @id_Modulo
                  AND id_SubModulos = @id_SubModulo
                  AND id_Accion = @id_Accion";

            using SqlCommand cmd = CrearComandoPermiso(query, cn, transaction, item);
            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        private void InsertarPermiso(SqlConnection cn, SqlTransaction transaction, permisoUsuariosModel item) {
            string query = @"
                INSERT INTO dbo.tblPermisoUsuarios (id_Usuario, id_Modulos, id_SubModulos, id_Accion, permitido, fechaAlta, activo)
                VALUES (@id_Usuario, @id_Modulo, @id_SubModulo, @id_Accion, @permitido, GETDATE(), 1)";

            using SqlCommand cmd = CrearComandoPermiso(query, cn, transaction, item);
            cmd.ExecuteNonQuery();
        }

        private void ActualizarPermiso(SqlConnection cn, SqlTransaction transaction, permisoUsuariosModel item) {
            string query = @"
                UPDATE dbo.tblPermisoUsuarios
                SET permitido = @permitido,
                    activo = @activo
                WHERE id_Usuario = @id_Usuario
                  AND id_Modulos = @id_Modulo
                  AND id_SubModulos = @id_SubModulo
                  AND id_Accion = @id_Accion";

            using SqlCommand cmd = CrearComandoPermiso(query, cn, transaction, item);
            cmd.ExecuteNonQuery();
        }

        private SqlCommand CrearComandoPermiso(string query, SqlConnection cn, SqlTransaction transaction, permisoUsuariosModel item) {
            SqlCommand cmd = new SqlCommand(query, cn, transaction);
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = item.IdUsuario;
            cmd.Parameters.Add("@id_Modulo", System.Data.SqlDbType.Int).Value = item.IdModulo;
            cmd.Parameters.Add("@id_SubModulo", System.Data.SqlDbType.Int).Value = item.IdSubModulo;
            cmd.Parameters.Add("@id_Accion", System.Data.SqlDbType.Int).Value = item.IdAccion;
            cmd.Parameters.Add("@permitido", System.Data.SqlDbType.Bit).Value = item.Permitido;
            cmd.Parameters.Add("@activo", System.Data.SqlDbType.Bit).Value = item.Permitido;
            return cmd;
        }

        private List<permisoUsuariosModel> ObtenerCatalogo(DBConector dbConector, string query, string tipo) {
            List<permisoUsuariosModel> lista = new List<permisoUsuariosModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                permisoUsuariosModel item = new permisoUsuariosModel();
                int id = Convert.ToInt32(dr["id"]);
                string nombre = dr["nombre"].ToString() ?? string.Empty;

                if (tipo == "modulo") {
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
