using System.Data;
using System.Net;
using System.Security.Cryptography;
using HELPDESK.Models.Login;
using HELPDESK.Services;
using Microsoft.AspNetCore.Mvc;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Controllers.Login {
    public class LoginController : Controller {
        private readonly DBConector _dbConector;
        private readonly CorreoService _correoService;

        public LoginController(DBConector dbConector, CorreoService correoService) {
            _dbConector = dbConector;
            _correoService = correoService;
        }

        [HttpGet]
        public IActionResult Login() {
            return View("~/Views/Login/login.cshtml", new LoginModel {
                SuccessMessage = TempData["SuccessMessage"]?.ToString(),
                ErrorMessage = TempData["ErrorMessage"]?.ToString()
            });
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Login(LoginModel model) {
            model.Usuario = model.Usuario?.Trim() ?? string.Empty;
            model.Password = model.Password?.Trim() ?? string.Empty;

            if (!ModelState.IsValid) {
                return View("~/Views/Login/login.cshtml", model);
            }

            DataTable? dt = _dbConector.EjecutarSP(
                "sp_LoginGet",
                new SqlParameter("@user", SqlDbType.VarChar, 100) { Value = model.Usuario },
                new SqlParameter("@pass", SqlDbType.VarChar, 200) { Value = model.Password }
            );

            if (dt == null) {
                model.ErrorMessage = "Ocurrió un error al validar el acceso.";
                return View("~/Views/Login/login.cshtml", model);
            }

            if (dt.Rows.Count == 0) {
                model.ErrorMessage = "Usuario o contraseña incorrectos.";
                return View("~/Views/Login/login.cshtml", model);
            }

            string idUsuario = dt.Columns.Contains("id_Usuario") && dt.Rows[0]["id_Usuario"] != DBNull.Value
                ? dt.Rows[0]["id_Usuario"]?.ToString() ?? string.Empty
                : string.Empty;

            string nombreUsuario = model.Usuario;
            string[] posiblesColumnas = { "nombre", "Nombre", "username", "Usuario", "user" };

            foreach (string columna in posiblesColumnas) {
                if (dt.Columns.Contains(columna) && dt.Rows[0][columna] != DBNull.Value) {
                    nombreUsuario = dt.Rows[0][columna]?.ToString() ?? model.Usuario;
                    break;
                }
            }

            HttpContext.Session.SetString("Usuario", model.Usuario);
            HttpContext.Session.SetString("NombreUsuario", nombreUsuario);
            HttpContext.Session.SetString("IdUsuario", idUsuario);

            return RedirectToAction("Index", "Dashboard");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult RecuperarPassword(string usuarioRecuperacion) {
            usuarioRecuperacion = usuarioRecuperacion?.Trim() ?? string.Empty;

            if (string.IsNullOrWhiteSpace(usuarioRecuperacion)) {
                TempData["ErrorMessage"] = "Captura tu usuario o correo para recuperar la contraseña.";
                return RedirectToAction("Login");
            }

            AccesoRecuperacion? acceso = ObtenerAccesoRecuperacion(usuarioRecuperacion);
            if (acceso == null || string.IsNullOrWhiteSpace(acceso.Correo)) {
                TempData["SuccessMessage"] = "Si el usuario o correo existe, se enviarán las instrucciones de acceso.";
                return RedirectToAction("Login");
            }

            string passwordTemporal = GenerarPasswordTemporal();
            bool actualizado = ActualizarPassword(acceso.IdLogin, passwordTemporal);

            if (!actualizado) {
                TempData["ErrorMessage"] = "No fue posible generar la contraseña temporal.";
                return RedirectToAction("Login");
            }

            string asunto = "Help Desk - Recuperacion de contraseña";
            string html = ConstruirHtmlRecuperacion(acceso.Nombre, acceso.Usuario, passwordTemporal);
            CorreoResultado resultado = _correoService.EnviarCorreo(acceso.Correo, asunto, html);

            if (!resultado.Ok) {
                RestaurarPassword(acceso.IdLogin, acceso.PassHash);
                TempData["ErrorMessage"] = $"No fue posible enviar el correo, la contraseña no fue cambiada. {resultado.Mensaje}";
                return RedirectToAction("Login");
            }

            TempData["SuccessMessage"] = "Se enviaron las instrucciones de acceso al correo registrado.";
            return RedirectToAction("Login");
        }

        [HttpGet]
        public IActionResult Logout() {
            HttpContext.Session.Clear();
            return RedirectToAction("Login", "Login");
        }

        private AccesoRecuperacion? ObtenerAccesoRecuperacion(string usuarioOCorreo) {
            using SqlConnection? cn = _dbConector.AbrirConexion();
            if (cn == null)
                return null;

            string query = @"
                SELECT TOP 1
                    L.id_Login,
                    L.usuario,
                    L.pass AS passHash,
                    LTRIM(RTRIM(ISNULL(U.nombre, '') + ' ' + ISNULL(U.app, '') + ' ' + ISNULL(U.apm, ''))) AS nombre,
                    ISNULL(U.correo, '') AS correo
                FROM dbo.tblLogin L
                INNER JOIN dbo.tblUsuario U ON U.id_Usuario = L.id_Usuario
                WHERE L.activo = 1
                  AND U.activo = 1
                  AND (L.usuario = @usuarioOCorreo OR U.correo = @usuarioOCorreo)";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@usuarioOCorreo", SqlDbType.VarChar, 100).Value = usuarioOCorreo;
            using SqlDataReader dr = cmd.ExecuteReader();

            if (!dr.Read())
                return null;

            return new AccesoRecuperacion {
                IdLogin = dr["id_Login"] == DBNull.Value ? 0 : Convert.ToInt32(dr["id_Login"]),
                Usuario = dr["usuario"]?.ToString() ?? string.Empty,
                PassHash = dr["passHash"]?.ToString() ?? string.Empty,
                Nombre = dr["nombre"]?.ToString() ?? string.Empty,
                Correo = dr["correo"]?.ToString() ?? string.Empty
            };
        }

        private bool ActualizarPassword(int idLogin, string passwordTemporal) {
            using SqlConnection? cn = _dbConector.AbrirConexion();
            if (cn == null || idLogin <= 0 || string.IsNullOrWhiteSpace(passwordTemporal))
                return false;

            string query = @"
                UPDATE dbo.tblLogin
                SET pass = CONVERT(VARCHAR(128), HASHBYTES('SHA2_512', @pass), 2)
                WHERE id_Login = @id_Login
                  AND activo = 1";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@pass", SqlDbType.VarChar, 200).Value = passwordTemporal;
            cmd.Parameters.Add("@id_Login", SqlDbType.Int).Value = idLogin;

            return cmd.ExecuteNonQuery() > 0;
        }

        private bool RestaurarPassword(int idLogin, string passHash) {
            using SqlConnection? cn = _dbConector.AbrirConexion();
            if (cn == null || idLogin <= 0 || string.IsNullOrWhiteSpace(passHash))
                return false;

            string query = @"
                UPDATE dbo.tblLogin
                SET pass = @pass
                WHERE id_Login = @id_Login
                  AND activo = 1";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@pass", SqlDbType.VarChar, 128).Value = passHash;
            cmd.Parameters.Add("@id_Login", SqlDbType.Int).Value = idLogin;

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

        private string ConstruirHtmlRecuperacion(string nombre, string usuario, string passwordTemporal) {
            string nombreSeguro = WebUtility.HtmlEncode(string.IsNullOrWhiteSpace(nombre) ? "Usuario" : nombre);
            string usuarioSeguro = WebUtility.HtmlEncode(usuario);
            string passwordSeguro = WebUtility.HtmlEncode(passwordTemporal);

            return $@"
                <div style=""font-family: Arial, sans-serif; color: #1f2937;"">
                    <h2 style=""margin-bottom: 8px;"">Recuperacion de contraseña</h2>
                    <p>Hola {nombreSeguro},</p>
                    <p>Se genero una contraseña temporal para ingresar al sistema Help Desk.</p>
                    <table style=""border-collapse: collapse; width: 100%; max-width: 620px;"">
                        <tr><td style=""padding: 6px 0; font-weight: bold;"">Usuario:</td><td>{usuarioSeguro}</td></tr>
                        <tr><td style=""padding: 6px 0; font-weight: bold;"">Contraseña temporal:</td><td>{passwordSeguro}</td></tr>
                    </table>
                    <p style=""margin-top: 16px;"">Por seguridad, cambia tu contraseña despues de ingresar.</p>
                </div>";
        }

        private class AccesoRecuperacion {
            public int IdLogin { get; set; }
            public string Usuario { get; set; } = string.Empty;
            public string PassHash { get; set; } = string.Empty;
            public string Nombre { get; set; } = string.Empty;
            public string Correo { get; set; } = string.Empty;
        }
    }
}
