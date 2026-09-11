using MailKit.Net.Smtp;
using MailKit.Security;
using Microsoft.Data.SqlClient;
using MimeKit;

namespace HELPDESK.Services {
    public class CorreoService {
        private readonly DBConector _dbConector;

        public CorreoService(DBConector dbConector) {
            _dbConector = dbConector;
        }

        public CorreoResultado EnviarCorreo(string destinatario, string asunto, string htmlCuerpo) {
            if (string.IsNullOrWhiteSpace(destinatario))
                return CorreoResultado.Fallo("No se recibio destinatario.");

            ConfiguracionCorreo config = ObtenerConfiguracion();
            if (!config.Activo)
                return CorreoResultado.Fallo("El envio de correos esta desactivado.");

            if (string.IsNullOrWhiteSpace(config.Servidor) ||
                string.IsNullOrWhiteSpace(config.Remitente) ||
                string.IsNullOrWhiteSpace(config.Usuario) ||
                string.IsNullOrWhiteSpace(config.Password)) {
                return CorreoResultado.Fallo("La configuracion de correo esta incompleta.");
            }

            try {
                MimeMessage mensaje = new MimeMessage();
                mensaje.From.Add(new MailboxAddress(config.NombreRemitente, config.Remitente));

                foreach (string correo in SepararDestinatarios(destinatario)) {
                    mensaje.To.Add(MailboxAddress.Parse(correo));
                }

                if (mensaje.To.Count == 0)
                    return CorreoResultado.Fallo("No se encontraron destinatarios validos.");

                mensaje.Subject = asunto ?? string.Empty;
                mensaje.Body = new BodyBuilder {
                    HtmlBody = htmlCuerpo ?? string.Empty
                }.ToMessageBody();

                using SmtpClient cliente = new SmtpClient();
                cliente.Connect(config.Servidor, config.Puerto, SecureSocketOptions.StartTls);
                cliente.AuthenticationMechanisms.Clear();
                cliente.AuthenticationMechanisms.Add("LOGIN");
                cliente.Authenticate(config.Usuario, config.Password);
                cliente.Send(mensaje);
                cliente.Disconnect(true);

                return CorreoResultado.Exito();
            } catch (Exception ex) {
                return CorreoResultado.Fallo(ex.Message);
            }
        }

        private ConfiguracionCorreo ObtenerConfiguracion() {
            Dictionary<string, string> valores = ObtenerValoresConfiguracion();

            return new ConfiguracionCorreo {
                Activo = ObtenerBool(valores, "CorreoNotificacionesActivo", false),
                Servidor = ObtenerValor(valores, "CorreoServidorSmtp"),
                Puerto = ObtenerInt(valores, "CorreoPuertoSmtp", 587),
                Remitente = ObtenerValor(valores, "CorreoRemitente"),
                Usuario = ObtenerValor(valores, "CorreoUsuario"),
                Password = ObtenerValor(valores, "CorreoPassword"),
                NombreRemitente = ObtenerValor(valores, "CorreoNombreRemitente", "Help Desk")
            };
        }

        private Dictionary<string, string> ObtenerValoresConfiguracion() {
            Dictionary<string, string> valores = new Dictionary<string, string>(StringComparer.OrdinalIgnoreCase);
            using SqlConnection? cn = _dbConector.AbrirConexion();
            if (cn == null)
                return valores;

            string query = @"
                SELECT clave, valor
                FROM dbo.tblConfiguracionSistema
                WHERE activo = 1
                  AND clave IN (
                    'CorreoNotificacionesActivo',
                    'CorreoServidorSmtp',
                    'CorreoPuertoSmtp',
                    'CorreoRemitente',
                    'CorreoUsuario',
                    'CorreoPassword',
                    'CorreoNombreRemitente'
                  )";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();
            while (dr.Read()) {
                string clave = dr["clave"].ToString() ?? string.Empty;
                if (!string.IsNullOrWhiteSpace(clave))
                    valores[clave] = dr["valor"].ToString() ?? string.Empty;
            }

            return valores;
        }

        private List<string> SepararDestinatarios(string destinatario) {
            return destinatario
                .Split(new[] { ';', ',' }, StringSplitOptions.RemoveEmptyEntries)
                .Select(correo => correo.Trim())
                .Where(correo => !string.IsNullOrWhiteSpace(correo))
                .Distinct(StringComparer.OrdinalIgnoreCase)
                .ToList();
        }

        private string ObtenerValor(Dictionary<string, string> valores, string clave, string valorDefault = "") {
            return valores.TryGetValue(clave, out string? valor) ? valor.Trim() : valorDefault;
        }

        private int ObtenerInt(Dictionary<string, string> valores, string clave, int valorDefault) {
            string valor = ObtenerValor(valores, clave);
            return int.TryParse(valor, out int numero) ? numero : valorDefault;
        }

        private bool ObtenerBool(Dictionary<string, string> valores, string clave, bool valorDefault) {
            string valor = ObtenerValor(valores, clave);
            if (string.IsNullOrWhiteSpace(valor))
                return valorDefault;

            return valor == "1" ||
                valor.Equals("true", StringComparison.OrdinalIgnoreCase) ||
                valor.Equals("si", StringComparison.OrdinalIgnoreCase);
        }

        private class ConfiguracionCorreo {
            public bool Activo { get; set; }
            public string Servidor { get; set; } = string.Empty;
            public int Puerto { get; set; }
            public string Remitente { get; set; } = string.Empty;
            public string Usuario { get; set; } = string.Empty;
            public string Password { get; set; } = string.Empty;
            public string NombreRemitente { get; set; } = string.Empty;
        }
    }

    public class CorreoResultado {
        public bool Ok { get; set; }
        public string Mensaje { get; set; } = string.Empty;

        public static CorreoResultado Exito() {
            return new CorreoResultado {
                Ok = true,
                Mensaje = "Correo enviado correctamente."
            };
        }

        public static CorreoResultado Fallo(string mensaje) {
            return new CorreoResultado {
                Ok = false,
                Mensaje = mensaje
            };
        }
    }
}
