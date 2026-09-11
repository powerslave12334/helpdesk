using HELPDESK.Services;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Models.Empleados.Usuarios {
    public class usuariosModel {
        public int IdUsuario { get; set; }
        public string Nombre { get; set; } = string.Empty;
        public string App { get; set; } = string.Empty;
        public string Apm { get; set; } = string.Empty;
        public string Correo { get; set; } = string.Empty;
        public string Telefono { get; set; } = string.Empty;
        public int IdDepto { get; set; }
        public string NombreDepto { get; set; } = string.Empty;
        public int IdArea { get; set; }
        public string NombreArea { get; set; } = string.Empty;
        public int? IdOficinas { get; set; }
        public string NombreOficina { get; set; } = string.Empty;
        public bool EsTecnico { get; set; }
        public int IdTipoEmpleado { get; set; }
        public string NombreTipoEmpleado { get; set; } = string.Empty;
        public int IdLogin { get; set; }
        public string UsuarioAcceso { get; set; } = string.Empty;
        public string PasswordActual { get; set; } = string.Empty;
        public string PasswordAcceso { get; set; } = string.Empty;
        public string ConfirmarPasswordAcceso { get; set; } = string.Empty;
        public bool CrearAcceso { get; set; }
        public bool TieneAcceso { get; set; }
        public bool AccesoActivo { get; set; }
        public DateTime FechaAlta { get; set; }
        public bool Activo { get; set; }
        public bool PuedeCrear { get; set; }
        public bool PuedeActualizar { get; set; }
        public bool PuedeEditarDatosLaborales { get; set; } = true;
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<usuariosModel> Usuarios { get; set; } = new List<usuariosModel>();
        public List<usuariosModel> Departamentos { get; set; } = new List<usuariosModel>();
        public List<usuariosModel> Areas { get; set; } = new List<usuariosModel>();
        public List<usuariosModel> Oficinas { get; set; } = new List<usuariosModel>();
        public List<usuariosModel> TiposEmpleado { get; set; } = new List<usuariosModel>();

        public List<usuariosModel> ObtenerUsuarios(DBConector dbConector, int idUsuarioFiltro = 0) {
            List<usuariosModel> lista = new List<usuariosModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT
                    u.id_Usuario,
                    u.nombre,
                    u.app,
                    u.apm,
                    u.correo,
                    u.telefono,
                    u.id_Depto,
                    ISNULL(d.nombre, '') AS nombreDepto,
                    u.id_Area,
                    ISNULL(a.nombre, '') AS nombreArea,
                    u.id_Oficinas,
                    ISNULL(o.nombre + ' - ' + o.descripcion, '') AS nombreOficina,
                    u.esTecnico,
                    u.id_TipoEmpleado,
                    ISNULL(te.nombre, '') AS nombreTipoEmpleado,
                    ISNULL(l.id_Login, 0) AS id_Login,
                    ISNULL(l.usuario, '') AS usuarioAcceso,
                    ISNULL(l.activo, 0) AS accesoActivo,
                    u.fechaAlta,
                    u.activo
                FROM dbo.tblUsuario u
                LEFT JOIN dbo.tblDepto d ON d.id_Depto = u.id_Depto
                LEFT JOIN dbo.tblArea a ON a.id_Area = u.id_Area
                LEFT JOIN dbo.tblOficinas o ON o.id_Oficinas = u.id_Oficinas
                LEFT JOIN dbo.tblTipoEmpleado te ON te.id_TipoEmpleado = u.id_TipoEmpleado
                OUTER APPLY (
                    SELECT TOP 1 id_Login, usuario, activo
                    FROM dbo.tblLogin
                    WHERE id_Usuario = u.id_Usuario
                    ORDER BY activo DESC, id_Login DESC
                ) l
                WHERE (@id_UsuarioFiltro = 0 OR u.id_Usuario = @id_UsuarioFiltro)
                ORDER BY u.activo DESC, u.nombre ASC, u.app ASC, u.apm ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_UsuarioFiltro", System.Data.SqlDbType.Int).Value = idUsuarioFiltro;
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new usuariosModel {
                    IdUsuario = Convert.ToInt32(dr["id_Usuario"]),
                    Nombre = dr["nombre"].ToString() ?? string.Empty,
                    App = dr["app"].ToString() ?? string.Empty,
                    Apm = dr["apm"].ToString() ?? string.Empty,
                    Correo = dr["correo"].ToString() ?? string.Empty,
                    Telefono = dr["telefono"].ToString() ?? string.Empty,
                    IdDepto = Convert.ToInt32(dr["id_Depto"]),
                    NombreDepto = dr["nombreDepto"].ToString() ?? string.Empty,
                    IdArea = Convert.ToInt32(dr["id_Area"]),
                    NombreArea = dr["nombreArea"].ToString() ?? string.Empty,
                    IdOficinas = dr["id_Oficinas"] == DBNull.Value ? null : Convert.ToInt32(dr["id_Oficinas"]),
                    NombreOficina = dr["nombreOficina"].ToString() ?? string.Empty,
                    EsTecnico = Convert.ToBoolean(dr["esTecnico"]),
                    IdTipoEmpleado = Convert.ToInt32(dr["id_TipoEmpleado"]),
                    NombreTipoEmpleado = dr["nombreTipoEmpleado"].ToString() ?? string.Empty,
                    IdLogin = Convert.ToInt32(dr["id_Login"]),
                    UsuarioAcceso = dr["usuarioAcceso"].ToString() ?? string.Empty,
                    TieneAcceso = Convert.ToInt32(dr["id_Login"]) > 0,
                    AccesoActivo = Convert.ToBoolean(dr["accesoActivo"]),
                    FechaAlta = Convert.ToDateTime(dr["fechaAlta"]),
                    Activo = Convert.ToBoolean(dr["activo"])
                });
            }

            return lista;
        }

        public List<usuariosModel> ObtenerDepartamentosActivos(DBConector dbConector) {
            return ObtenerCatalogo(dbConector, "SELECT id_Depto AS id, nombre FROM dbo.tblDepto WHERE activo = 1 ORDER BY nombre ASC", "depto");
        }

        public List<usuariosModel> ObtenerAreasActivas(DBConector dbConector) {
            return ObtenerCatalogo(dbConector, "SELECT id_Area AS id, nombre FROM dbo.tblArea WHERE activo = 1 ORDER BY nombre ASC", "area");
        }

        public List<usuariosModel> ObtenerOficinasActivas(DBConector dbConector) {
            return ObtenerCatalogo(dbConector, "SELECT id_Oficinas AS id, nombre + ' - ' + descripcion AS nombre FROM dbo.tblOficinas WHERE activo = 1 ORDER BY nombre ASC", "oficina");
        }

        public List<usuariosModel> ObtenerTiposEmpleadoActivos(DBConector dbConector) {
            return ObtenerCatalogo(dbConector, "SELECT id_TipoEmpleado AS id, nombre FROM dbo.tblTipoEmpleado WHERE activo = 1 ORDER BY nombre ASC", "tipo");
        }

        public int InsertarUsuario(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return 0;

            string query = @"
                INSERT INTO dbo.tblUsuario
                    (nombre, app, apm, correo, telefono, id_Depto, id_Area, id_Oficinas, esTecnico, id_TipoEmpleado, fechaAlta, activo)
                VALUES
                    (@nombre, @app, @apm, @correo, @telefono, @id_Depto, @id_Area, @id_Oficinas, @esTecnico, @id_TipoEmpleado, GETDATE(), 1);

                SELECT CONVERT(int, SCOPE_IDENTITY());";

            using SqlCommand cmd = new SqlCommand(query, cn);
            AgregarParametros(cmd);

            object? result = cmd.ExecuteScalar();
            return result == null || result == DBNull.Value ? 0 : Convert.ToInt32(result);
        }

        public bool ActualizarUsuario(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblUsuario
                SET nombre = @nombre,
                    app = @app,
                    apm = @apm,
                    correo = @correo,
                    telefono = @telefono,
                    id_Depto = @id_Depto,
                    id_Area = @id_Area,
                    id_Oficinas = @id_Oficinas,
                    esTecnico = @esTecnico,
                    id_TipoEmpleado = @id_TipoEmpleado
                WHERE id_Usuario = @id_Usuario";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = IdUsuario;
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool CambiarEstatus(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblUsuario
                SET activo = CASE WHEN activo = 1 THEN 0 ELSE 1 END
                WHERE id_Usuario = @id_Usuario";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = IdUsuario;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool GuardarAcceso(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            if (IdLogin > 0)
                return ActualizarAcceso(cn);

            return InsertarAcceso(cn);
        }

        public bool ExisteUsuarioAcceso(DBConector dbConector) {
            if (string.IsNullOrWhiteSpace(UsuarioAcceso))
                return false;

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblLogin
                WHERE LOWER(usuario) = LOWER(@usuario)
                  AND id_Login <> @id_Login";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@usuario", System.Data.SqlDbType.VarChar, 50).Value = UsuarioAcceso.Trim();
            cmd.Parameters.Add("@id_Login", System.Data.SqlDbType.Int).Value = IdLogin;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        public int ObtenerIdLogin(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null || IdUsuario <= 0)
                return 0;

            string query = @"
                SELECT TOP 1 id_Login
                FROM dbo.tblLogin
                WHERE id_Usuario = @id_Usuario";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = IdUsuario;

            object? result = cmd.ExecuteScalar();
            return result == null || result == DBNull.Value ? 0 : Convert.ToInt32(result);
        }

        public bool PasswordActualCoincide(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null || IdLogin <= 0 || string.IsNullOrWhiteSpace(PasswordActual))
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblLogin
                WHERE id_Login = @id_Login
                  AND pass = CONVERT(VARCHAR(128), HASHBYTES('SHA2_512', @passActual), 2)";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Login", System.Data.SqlDbType.Int).Value = IdLogin;
            cmd.Parameters.Add("@passActual", System.Data.SqlDbType.VarChar, 100).Value = PasswordActual.Trim();

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        public bool ExisteCorreo(DBConector dbConector) {
            if (string.IsNullOrWhiteSpace(Correo))
                return false;

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblUsuario
                WHERE LOWER(correo) = LOWER(@correo)
                  AND id_Usuario <> @id_Usuario";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@correo", System.Data.SqlDbType.VarChar, 100).Value = Correo.Trim();
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = IdUsuario;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        private List<usuariosModel> ObtenerCatalogo(DBConector dbConector, string query, string tipo) {
            List<usuariosModel> lista = new List<usuariosModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                usuariosModel item = new usuariosModel();

                if (tipo == "depto") {
                    item.IdDepto = Convert.ToInt32(dr["id"]);
                    item.NombreDepto = dr["nombre"].ToString() ?? string.Empty;
                } else if (tipo == "area") {
                    item.IdArea = Convert.ToInt32(dr["id"]);
                    item.NombreArea = dr["nombre"].ToString() ?? string.Empty;
                } else if (tipo == "oficina") {
                    item.IdOficinas = Convert.ToInt32(dr["id"]);
                    item.NombreOficina = dr["nombre"].ToString() ?? string.Empty;
                } else {
                    item.IdTipoEmpleado = Convert.ToInt32(dr["id"]);
                    item.NombreTipoEmpleado = dr["nombre"].ToString() ?? string.Empty;
                }

                lista.Add(item);
            }

            return lista;
        }

        private void AgregarParametros(SqlCommand cmd) {
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 100).Value = Nombre.Trim();
            cmd.Parameters.Add("@app", System.Data.SqlDbType.VarChar, 50).Value = App.Trim();
            cmd.Parameters.Add("@apm", System.Data.SqlDbType.VarChar, 50).Value = Apm.Trim();
            cmd.Parameters.Add("@correo", System.Data.SqlDbType.VarChar, 100).Value = Correo.Trim();
            cmd.Parameters.Add("@telefono", System.Data.SqlDbType.VarChar, 15).Value = Telefono.Trim();
            cmd.Parameters.Add("@id_Depto", System.Data.SqlDbType.Int).Value = IdDepto;
            cmd.Parameters.Add("@id_Area", System.Data.SqlDbType.Int).Value = IdArea;
            cmd.Parameters.Add("@id_Oficinas", System.Data.SqlDbType.Int).Value = IdOficinas.HasValue && IdOficinas.Value > 0 ? IdOficinas.Value : (object)DBNull.Value;
            cmd.Parameters.Add("@esTecnico", System.Data.SqlDbType.Bit).Value = EsTecnico;
            cmd.Parameters.Add("@id_TipoEmpleado", System.Data.SqlDbType.Int).Value = IdTipoEmpleado;
        }

        private bool InsertarAcceso(SqlConnection cn) {
            string query = @"
                INSERT INTO dbo.tblLogin (id_Usuario, usuario, pass, fechaAlta, activo)
                VALUES (
                    @id_Usuario,
                    @usuario,
                    CONVERT(VARCHAR(128), HASHBYTES('SHA2_512', @pass), 2),
                    GETDATE(),
                    1
                )";

            using SqlCommand cmd = new SqlCommand(query, cn);
            AgregarParametrosAcceso(cmd, incluirPassword: true);

            return cmd.ExecuteNonQuery() > 0;
        }

        private bool ActualizarAcceso(SqlConnection cn) {
            string query = string.IsNullOrWhiteSpace(PasswordAcceso)
                ? @"
                    UPDATE dbo.tblLogin
                    SET usuario = @usuario,
                        activo = @activo
                    WHERE id_Login = @id_Login"
                : @"
                    UPDATE dbo.tblLogin
                    SET usuario = @usuario,
                        pass = CONVERT(VARCHAR(128), HASHBYTES('SHA2_512', @pass), 2),
                        activo = @activo
                    WHERE id_Login = @id_Login";

            using SqlCommand cmd = new SqlCommand(query, cn);
            AgregarParametrosAcceso(cmd, incluirPassword: !string.IsNullOrWhiteSpace(PasswordAcceso));
            cmd.Parameters.Add("@id_Login", System.Data.SqlDbType.Int).Value = IdLogin;
            cmd.Parameters.Add("@activo", System.Data.SqlDbType.Bit).Value = AccesoActivo;

            return cmd.ExecuteNonQuery() > 0;
        }

        private void AgregarParametrosAcceso(SqlCommand cmd, bool incluirPassword) {
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = IdUsuario;
            cmd.Parameters.Add("@usuario", System.Data.SqlDbType.VarChar, 50).Value = UsuarioAcceso.Trim();

            if (incluirPassword)
                cmd.Parameters.Add("@pass", System.Data.SqlDbType.VarChar, 100).Value = PasswordAcceso.Trim();
        }
    }
}
