using HELPDESK.Models.Empleados.Usuarios;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;
using Microsoft.Data.SqlClient;
using System.Net;
using System.Security.Cryptography;

namespace HELPDESK.Controllers.Empleados.Usuarios {
    public class usuariosController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;
        private readonly CorreoService _correoService;

        public usuariosController(DBConector dbConector, PermisosService permisosService, CorreoService correoService) {
            _dbConector = dbConector;
            _permisosService = permisosService;
            _correoService = correoService;
        }

        [HttpGet]
        public IActionResult Index() {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            int idUsuarioSesion = ObtenerIdUsuarioSesion();
            bool esAdministradorUsuarios = EsRolAdministradorUsuarios(idUsuarioSesion);
            bool esEmpleado = EsRolEmpleado(idUsuarioSesion);
            bool puedeCrear = _permisosService.TienePermisoPorRuta(idUsuarioSesion, "usuarios", "Crear");
            bool puedeActualizar = _permisosService.TienePermisoPorRuta(idUsuarioSesion, "usuarios", "Actualizar");
            usuariosModel model = CrearModeloPantalla(esAdministradorUsuarios ? 0 : idUsuarioSesion);
            model.PuedeCrear = puedeCrear && esAdministradorUsuarios;
            model.PuedeActualizar = puedeActualizar;
            model.PuedeEditarDatosLaborales = !esEmpleado;
            model.SuccessMessage = TempData["SuccessMessage"]?.ToString();
            model.ErrorMessage = TempData["ErrorMessage"]?.ToString();
            ViewBag.IdUsuarioSesion = idUsuarioSesion;
            ViewBag.EsAdministradorUsuarios = esAdministradorUsuarios;

            return View("~/Views/Empleados/Usuarios/usuarios.cshtml", model);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Create(usuariosModel model) {
            IActionResult? acceso = ValidarAcceso("Crear");
            if (acceso != null)
                return acceso;

            string? error = ValidarModelo(model);
            if (!string.IsNullOrWhiteSpace(error)) {
                TempData["ErrorMessage"] = error;
                return RedirectToAction("Index");
            }

            Normalizar(model);

            int idUsuario = model.InsertarUsuario(_dbConector);
            bool insertado = idUsuario > 0;

            TempData[insertado ? "SuccessMessage" : "ErrorMessage"] =
                insertado ? "Empleado registrado correctamente." : "No fue posible registrar el empleado.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Edit(usuariosModel model) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            if (model.IdUsuario <= 0) {
                TempData["ErrorMessage"] = "No se recibio el empleado a actualizar.";
                return RedirectToAction("Index");
            }

            int idUsuarioSesion = ObtenerIdUsuarioSesion();
            bool esAdministradorUsuarios = EsRolAdministradorUsuarios(idUsuarioSesion);
            bool esEmpleado = EsRolEmpleado(idUsuarioSesion);
            if (!esAdministradorUsuarios && model.IdUsuario != idUsuarioSesion) {
                TempData["ErrorMessage"] = "Solo puedes actualizar tu propio usuario.";
                return RedirectToAction("Index");
            }

            if (esEmpleado)
                AplicarDatosLaboralesActuales(model);

            string? error = ValidarModelo(model);
            if (!string.IsNullOrWhiteSpace(error)) {
                TempData["ErrorMessage"] = error;
                return RedirectToAction("Index");
            }

            Normalizar(model);

            if (!esAdministradorUsuarios)
                model.EsTecnico = ObtenerEsTecnicoActual(model.IdUsuario);

            bool actualizado = model.ActualizarUsuario(_dbConector);

            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Empleado actualizado correctamente." : "No fue posible actualizar el empleado.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult GuardarAcceso(usuariosModel model) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            if (model.IdUsuario <= 0) {
                TempData["ErrorMessage"] = "No se recibio el empleado para administrar el acceso.";
                return RedirectToAction("Index");
            }

            int idUsuarioSesion = ObtenerIdUsuarioSesion();
            bool esAccesoPropio = model.IdUsuario == idUsuarioSesion;
            if (!esAccesoPropio) {
                IActionResult? acceso = ValidarAcceso("Actualizar");
                if (acceso != null)
                    return acceso;
            }

            bool puedeCrear = _permisosService.TienePermisoPorRuta(idUsuarioSesion, "usuarios", "Crear");
            bool esAdministradorUsuarios = EsRolAdministradorUsuarios(idUsuarioSesion);
            bool puedeAdministrarAccesos = puedeCrear && esAdministradorUsuarios;
            if (!puedeAdministrarAccesos && !esAccesoPropio) {
                TempData["ErrorMessage"] = "No tienes permiso para administrar accesos.";
                return RedirectToAction("Index");
            }

            Normalizar(model);
            model.CrearAcceso = true;
            model.IdLogin = model.ObtenerIdLogin(_dbConector);
            bool esNuevoAcceso = model.IdLogin <= 0;
            bool cambiaPassword = !string.IsNullOrWhiteSpace(model.PasswordAcceso);

            if (!puedeAdministrarAccesos) {
                AccesoUsuarioInfo? accesoPropio = ObtenerAccesoUsuario(idUsuarioSesion);
                if (accesoPropio == null || accesoPropio.IdLogin <= 0) {
                    TempData["ErrorMessage"] = "No tienes acceso registrado para cambiar la contrasena.";
                    return RedirectToAction("Index");
                }

                if (!cambiaPassword) {
                    TempData["ErrorMessage"] = "Captura la nueva contrasena.";
                    return RedirectToAction("Index");
                }

                model.IdLogin = accesoPropio.IdLogin;
                model.UsuarioAcceso = accesoPropio.UsuarioAcceso;
                model.AccesoActivo = accesoPropio.AccesoActivo;
                esNuevoAcceso = false;
            }

            string? errorAcceso = ValidarAccesoLogin(model, esNuevoAcceso);
            if (!string.IsNullOrWhiteSpace(errorAcceso)) {
                TempData["ErrorMessage"] = errorAcceso;
                return RedirectToAction("Index");
            }

            if (model.IdLogin <= 0)
                model.AccesoActivo = true;

            bool guardado = model.GuardarAcceso(_dbConector);
            TempData[guardado ? "SuccessMessage" : "ErrorMessage"] =
                guardado ? "Acceso guardado correctamente." : "No fue posible guardar el acceso.";

            if (guardado && (esNuevoAcceso || cambiaPassword)) {
                NotificarAccesoUsuario(model, esNuevoAcceso);
            }

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult RestaurarPassword(int idUsuario) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            int idUsuarioSesion = ObtenerIdUsuarioSesion();
            bool puedeCrear = _permisosService.TienePermisoPorRuta(idUsuarioSesion, "usuarios", "Crear");
            bool esAdministradorUsuarios = EsRolAdministradorUsuarios(idUsuarioSesion);
            if (!puedeCrear || !esAdministradorUsuarios) {
                TempData["ErrorMessage"] = "No tienes permiso para restaurar contraseñas.";
                return RedirectToAction("Index");
            }

            if (idUsuario <= 0) {
                TempData["ErrorMessage"] = "No se recibio el empleado para restaurar contraseña.";
                return RedirectToAction("Index");
            }

            AccesoUsuarioInfo? accesoDestino = ObtenerAccesoUsuario(idUsuario);
            if (accesoDestino == null || accesoDestino.IdLogin <= 0) {
                TempData["ErrorMessage"] = "El empleado no tiene acceso registrado.";
                return RedirectToAction("Index");
            }

            if (string.IsNullOrWhiteSpace(accesoDestino.Correo)) {
                TempData["ErrorMessage"] = "El empleado no tiene correo registrado.";
                return RedirectToAction("Index");
            }

            string rolSesion = ObtenerRolPrincipal(idUsuarioSesion);
            string rolDestino = ObtenerRolPrincipal(idUsuario);
            if (!PuedeRestaurarPassword(rolSesion, rolDestino)) {
                TempData["ErrorMessage"] = "No tienes permiso para restaurar la contraseña de este usuario.";
                return RedirectToAction("Index");
            }

            string passwordTemporal = GenerarPasswordTemporal();
            bool actualizado = ActualizarPasswordLogin(accesoDestino.IdLogin, passwordTemporal);
            if (!actualizado) {
                TempData["ErrorMessage"] = "No fue posible restaurar la contraseña.";
                return RedirectToAction("Index");
            }

            string html = ConstruirHtmlAccesoUsuario(accesoDestino.Nombre, accesoDestino.UsuarioAcceso, passwordTemporal, false);
            CorreoResultado resultado = _correoService.EnviarCorreo(accesoDestino.Correo, "Help Desk - Contraseña restaurada", html);

            if (!resultado.Ok) {
                RestaurarPasswordHash(accesoDestino.IdLogin, accesoDestino.PassHash);
                TempData["ErrorMessage"] = $"No fue posible enviar el correo, la contraseña no fue cambiada. {resultado.Mensaje}";
                return RedirectToAction("Index");
            }

            TempData["SuccessMessage"] = "Contraseña restaurada correctamente. Se envio la contraseña temporal al correo del empleado.";
            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult ToggleActivo(int idUsuario) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            int idUsuarioSesion = ObtenerIdUsuarioSesion();
            bool puedeCrear = _permisosService.TienePermisoPorRuta(idUsuarioSesion, "usuarios", "Crear");
            bool esAdministradorUsuarios = EsRolAdministradorUsuarios(idUsuarioSesion);
            if (!puedeCrear || !esAdministradorUsuarios) {
                TempData["ErrorMessage"] = "No tienes permiso para activar o desactivar empleados.";
                return RedirectToAction("Index");
            }

            if (idUsuario <= 0) {
                TempData["ErrorMessage"] = "No se recibio el empleado a actualizar.";
                return RedirectToAction("Index");
            }

            usuariosModel model = new usuariosModel {
                IdUsuario = idUsuario
            };

            bool actualizado = model.CambiarEstatus(_dbConector);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Empleado actualizado correctamente." : "No fue posible actualizar el empleado.";

            return RedirectToAction("Index");
        }

        private usuariosModel CrearModeloPantalla(int idUsuarioFiltro) {
            usuariosModel data = new usuariosModel();

            return new usuariosModel {
                Usuarios = data.ObtenerUsuarios(_dbConector, idUsuarioFiltro),
                Departamentos = data.ObtenerDepartamentosActivos(_dbConector),
                Areas = data.ObtenerAreasActivas(_dbConector),
                Oficinas = data.ObtenerOficinasActivas(_dbConector),
                TiposEmpleado = data.ObtenerTiposEmpleadoActivos(_dbConector)
            };
        }

        private string? ValidarModelo(usuariosModel model) {
            if (string.IsNullOrWhiteSpace(model.Nombre))
                return "El nombre del empleado es obligatorio.";

            if (string.IsNullOrWhiteSpace(model.App))
                return "El apellido paterno del empleado es obligatorio.";

            if (model.IdDepto <= 0)
                return "Selecciona un departamento.";

            if (model.IdArea <= 0)
                return "Selecciona un area.";

            if (model.IdTipoEmpleado <= 0)
                return "Selecciona un tipo de empleado.";

            if (!string.IsNullOrWhiteSpace(model.Telefono)) {
                string telefono = model.Telefono.Trim();

                if (telefono.Length != 10 || !telefono.All(char.IsDigit))
                    return "El telefono debe tener 10 digitos.";
            }

            return null;
        }

        private string? ValidarAccesoLogin(usuariosModel model, bool esNuevo) {
            if (!model.CrearAcceso)
                return null;

            if (string.IsNullOrWhiteSpace(model.UsuarioAcceso))
                return "El usuario de acceso es obligatorio.";

            if (model.UsuarioAcceso.Trim().Length > 50)
                return "El usuario de acceso no puede superar 50 caracteres.";

            if (esNuevo || model.IdLogin <= 0) {
                if (string.IsNullOrWhiteSpace(model.PasswordAcceso))
                    return "La contrasena de acceso es obligatoria.";
            }

            if (!string.IsNullOrWhiteSpace(model.PasswordAcceso) && model.PasswordAcceso.Trim().Length > 100)
                return "La contrasena no puede superar 100 caracteres.";

            if (model.IdLogin > 0 && !string.IsNullOrWhiteSpace(model.PasswordAcceso)) {
                if (string.IsNullOrWhiteSpace(model.PasswordActual))
                    return "La contrasena actual es obligatoria para cambiar la contrasena.";

                if (!model.PasswordActualCoincide(_dbConector))
                    return "La contrasena actual no coincide.";
            }

            if (!string.IsNullOrWhiteSpace(model.PasswordAcceso) &&
                model.PasswordAcceso.Trim() != (model.ConfirmarPasswordAcceso ?? string.Empty).Trim())
                return "La contrasena y su confirmacion no coinciden.";

            if (model.ExisteUsuarioAcceso(_dbConector))
                return "Ya existe un acceso con ese usuario.";

            return null;
        }

        private void Normalizar(usuariosModel model) {
            model.Nombre = model.Nombre?.Trim() ?? string.Empty;
            model.App = model.App?.Trim() ?? string.Empty;
            model.Apm = model.Apm?.Trim() ?? string.Empty;
            model.Correo = model.Correo?.Trim() ?? string.Empty;
            model.Telefono = model.Telefono?.Trim() ?? string.Empty;
            model.UsuarioAcceso = model.UsuarioAcceso?.Trim() ?? string.Empty;
            model.PasswordActual = model.PasswordActual?.Trim() ?? string.Empty;
            model.PasswordAcceso = model.PasswordAcceso?.Trim() ?? string.Empty;
            model.ConfirmarPasswordAcceso = model.ConfirmarPasswordAcceso?.Trim() ?? string.Empty;
        }

        private void NotificarAccesoUsuario(usuariosModel model, bool esNuevoAcceso) {
            UsuarioCorreoInfo usuario = ObtenerUsuarioCorreo(model.IdUsuario);
            if (string.IsNullOrWhiteSpace(usuario.Correo))
                return;

            string asunto = esNuevoAcceso
                ? "Help Desk - Acceso creado"
                : "Help Desk - Contrasena actualizada";

            string html = ConstruirHtmlAccesoUsuario(usuario.Nombre, model.UsuarioAcceso, model.PasswordAcceso, esNuevoAcceso);
            CorreoResultado resultado = _correoService.EnviarCorreo(usuario.Correo, asunto, html);

            if (!resultado.Ok) {
                TempData["ErrorMessage"] = $"Acceso guardado correctamente, pero no fue posible enviar el correo. {resultado.Mensaje}";
            }
        }

        private UsuarioCorreoInfo ObtenerUsuarioCorreo(int idUsuario) {
            using SqlConnection? cn = _dbConector.AbrirConexion();
            if (cn == null || idUsuario <= 0)
                return new UsuarioCorreoInfo();

            string query = @"
                SELECT
                    LTRIM(RTRIM(ISNULL(nombre, '') + ' ' + ISNULL(app, '') + ' ' + ISNULL(apm, ''))) AS nombre,
                    ISNULL(correo, '') AS correo
                FROM dbo.tblUsuario
                WHERE id_Usuario = @id_Usuario
                  AND activo = 1";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = idUsuario;
            using SqlDataReader dr = cmd.ExecuteReader();

            if (!dr.Read())
                return new UsuarioCorreoInfo();

            return new UsuarioCorreoInfo {
                Nombre = dr["nombre"].ToString() ?? string.Empty,
                Correo = dr["correo"].ToString() ?? string.Empty
            };
        }

        private string ConstruirHtmlAccesoUsuario(string nombre, string usuarioAcceso, string password, bool esNuevoAcceso) {
            string titulo = esNuevoAcceso ? "Acceso creado" : "Contrasena actualizada";
            string mensaje = esNuevoAcceso
                ? "Se creo tu acceso al sistema Help Desk."
                : "Se actualizo tu contrasena de acceso al sistema Help Desk.";
            string nombreSeguro = WebUtility.HtmlEncode(string.IsNullOrWhiteSpace(nombre) ? "Usuario" : nombre);
            string usuarioSeguro = WebUtility.HtmlEncode(usuarioAcceso);
            string passwordSeguro = WebUtility.HtmlEncode(password);

            return $@"
                <div style=""font-family: Arial, sans-serif; color: #1f2937;"">
                    <h2 style=""margin-bottom: 8px;"">{titulo}</h2>
                    <p>Hola {nombreSeguro},</p>
                    <p>{mensaje}</p>
                    <table style=""border-collapse: collapse; width: 100%; max-width: 620px;"">
                        <tr><td style=""padding: 6px 0; font-weight: bold;"">Usuario:</td><td>{usuarioSeguro}</td></tr>
                        <tr><td style=""padding: 6px 0; font-weight: bold;"">Contrasena:</td><td>{passwordSeguro}</td></tr>
                    </table>
                    <p style=""margin-top: 16px;"">Por seguridad, conserva estos datos en un lugar seguro.</p>
                </div>";
        }

        private AccesoUsuarioInfo? ObtenerAccesoUsuario(int idUsuario) {
            using SqlConnection? cn = _dbConector.AbrirConexion();
            if (cn == null || idUsuario <= 0)
                return null;

            string query = @"
                SELECT TOP 1
                    L.id_Login,
                    L.usuario,
                    L.pass AS passHash,
                    L.activo AS accesoActivo,
                    LTRIM(RTRIM(ISNULL(U.nombre, '') + ' ' + ISNULL(U.app, '') + ' ' + ISNULL(U.apm, ''))) AS nombre,
                    ISNULL(U.correo, '') AS correo
                FROM dbo.tblUsuario U
                INNER JOIN dbo.tblLogin L ON L.id_Usuario = U.id_Usuario
                WHERE U.id_Usuario = @id_Usuario
                  AND U.activo = 1
                ORDER BY L.activo DESC, L.id_Login DESC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = idUsuario;
            using SqlDataReader dr = cmd.ExecuteReader();

            if (!dr.Read())
                return null;

            return new AccesoUsuarioInfo {
                IdLogin = dr["id_Login"] == DBNull.Value ? 0 : Convert.ToInt32(dr["id_Login"]),
                UsuarioAcceso = dr["usuario"]?.ToString() ?? string.Empty,
                PassHash = dr["passHash"]?.ToString() ?? string.Empty,
                AccesoActivo = dr["accesoActivo"] != DBNull.Value && Convert.ToBoolean(dr["accesoActivo"]),
                Nombre = dr["nombre"]?.ToString() ?? string.Empty,
                Correo = dr["correo"]?.ToString() ?? string.Empty
            };
        }

        private bool ActualizarPasswordLogin(int idLogin, string passwordTemporal) {
            using SqlConnection? cn = _dbConector.AbrirConexion();
            if (cn == null || idLogin <= 0 || string.IsNullOrWhiteSpace(passwordTemporal))
                return false;

            string query = @"
                UPDATE dbo.tblLogin
                SET pass = CONVERT(VARCHAR(128), HASHBYTES('SHA2_512', @pass), 2)
                WHERE id_Login = @id_Login";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@pass", System.Data.SqlDbType.VarChar, 200).Value = passwordTemporal;
            cmd.Parameters.Add("@id_Login", System.Data.SqlDbType.Int).Value = idLogin;

            return cmd.ExecuteNonQuery() > 0;
        }

        private bool RestaurarPasswordHash(int idLogin, string passHash) {
            using SqlConnection? cn = _dbConector.AbrirConexion();
            if (cn == null || idLogin <= 0 || string.IsNullOrWhiteSpace(passHash))
                return false;

            string query = @"
                UPDATE dbo.tblLogin
                SET pass = @pass
                WHERE id_Login = @id_Login";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@pass", System.Data.SqlDbType.VarChar, 128).Value = passHash;
            cmd.Parameters.Add("@id_Login", System.Data.SqlDbType.Int).Value = idLogin;

            return cmd.ExecuteNonQuery() > 0;
        }

        private string GenerarPasswordTemporal() {
            const string caracteres = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789";
            char[] password = new char[10];
            byte[] bytes = RandomNumberGenerator.GetBytes(password.Length);

            for (int i = 0; i < password.Length; i++) {
                password[i] = caracteres[bytes[i] % caracteres.Length];
            }

            return $"Hd-{new string(password)}";
        }

        private string ObtenerRolPrincipal(int idUsuario) {
            using SqlConnection? cn = _dbConector.AbrirConexion();
            if (cn == null || idUsuario <= 0)
                return string.Empty;

            string query = @"
                SELECT TOP 1 R.nombre
                FROM dbo.tblRolesUsuarios RU
                INNER JOIN dbo.tblRoles R ON R.id_Rol = RU.id_Rol
                WHERE RU.id_Usuario = @id_Usuario
                  AND RU.activo = 1
                  AND R.activo = 1
                ORDER BY
                    CASE
                        WHEN LOWER(R.nombre) = 'root' THEN 1
                        WHEN LOWER(R.nombre) IN ('administrador', 'admin') THEN 2
                        ELSE 3
                    END,
                    R.id_Rol ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = idUsuario;
            return cmd.ExecuteScalar()?.ToString() ?? string.Empty;
        }

        private bool PuedeRestaurarPassword(string rolSesion, string rolDestino) {
            rolSesion = NormalizarRol(rolSesion);
            rolDestino = NormalizarRol(rolDestino);

            if (rolSesion == "root")
                return true;

            if (rolSesion == "administrador" || rolSesion == "admin")
                return rolDestino != "root";

            return false;
        }

        private bool EsRolAdministradorUsuarios(int idUsuario) {
            string rol = NormalizarRol(ObtenerRolPrincipal(idUsuario));
            return rol == "root" || rol == "administrador" || rol == "admin";
        }

        private bool EsRolEmpleado(int idUsuario) {
            string rol = NormalizarRol(ObtenerRolPrincipal(idUsuario));
            return rol == "empleado";
        }

        private void AplicarDatosLaboralesActuales(usuariosModel model) {
            using SqlConnection? cn = _dbConector.AbrirConexion();
            if (cn == null || model.IdUsuario <= 0)
                return;

            string query = @"
                SELECT id_Depto, id_Area, id_Oficinas, id_TipoEmpleado
                FROM dbo.tblUsuario
                WHERE id_Usuario = @id_Usuario";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = model.IdUsuario;
            using SqlDataReader dr = cmd.ExecuteReader();

            if (!dr.Read())
                return;

            model.IdDepto = dr["id_Depto"] == DBNull.Value ? 0 : Convert.ToInt32(dr["id_Depto"]);
            model.IdArea = dr["id_Area"] == DBNull.Value ? 0 : Convert.ToInt32(dr["id_Area"]);
            model.IdOficinas = dr["id_Oficinas"] == DBNull.Value ? null : Convert.ToInt32(dr["id_Oficinas"]);
            model.IdTipoEmpleado = dr["id_TipoEmpleado"] == DBNull.Value ? 0 : Convert.ToInt32(dr["id_TipoEmpleado"]);
        }

        private bool ObtenerEsTecnicoActual(int idUsuario) {
            using SqlConnection? cn = _dbConector.AbrirConexion();
            if (cn == null || idUsuario <= 0)
                return false;

            string query = @"
                SELECT ISNULL(esTecnico, 0)
                FROM dbo.tblUsuario
                WHERE id_Usuario = @id_Usuario";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = idUsuario;

            object? result = cmd.ExecuteScalar();
            return result != null && result != DBNull.Value && Convert.ToBoolean(result);
        }

        private string NormalizarRol(string rol) {
            return (rol ?? string.Empty).Trim().ToLowerInvariant();
        }

        private IActionResult? ValidarAcceso(string accion) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int idUsuario = ObtenerIdUsuarioSesion();
            if (!_permisosService.TienePermisoPorRuta(idUsuario, "usuarios", accion)) {
                TempData["ErrorMessage"] = "No tienes permiso para realizar esta accion.";
                return RedirectToAction("Index", "Dashboard");
            }

            return null;
        }

        private int ObtenerIdUsuarioSesion() {
            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            return idUsuario;
        }

        private class UsuarioCorreoInfo {
            public string Nombre { get; set; } = string.Empty;
            public string Correo { get; set; } = string.Empty;
        }

        private class AccesoUsuarioInfo {
            public int IdLogin { get; set; }
            public string UsuarioAcceso { get; set; } = string.Empty;
            public string PassHash { get; set; } = string.Empty;
            public bool AccesoActivo { get; set; }
            public string Nombre { get; set; } = string.Empty;
            public string Correo { get; set; } = string.Empty;
        }
    }
}
