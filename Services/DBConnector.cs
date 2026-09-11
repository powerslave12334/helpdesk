using System;
using System.Data;
using System.IO;
using Microsoft.Data.SqlClient;
using Microsoft.Extensions.Configuration;

namespace HELPDESK.Services {
    public class DBConector {
        private readonly IConfiguration _configuration;

        public DBConector(IConfiguration configuration) {
            _configuration = configuration;
        }

        public SqlConnection? AbrirConexion() {
            string connectionString = _configuration.GetConnectionString("DefaultConnection") ?? string.Empty;
            SqlConnection connection = new SqlConnection(connectionString);

            try {
                connection.Open();
                return connection;
            } catch (Exception ex) {
                Log("Error al abrir la conexión: " + ex.Message);
                return null;
            }
        }

        public DataTable? EjecutarSP(string nombreSp, params SqlParameter[] parametros) {
            using SqlConnection? cn = AbrirConexion();
            if (cn == null)
                return null;

            using SqlCommand cmd = new SqlCommand(nombreSp, cn) {
                CommandType = CommandType.StoredProcedure
            };

            if (parametros != null && parametros.Length > 0)
                cmd.Parameters.AddRange(parametros);

            using SqlDataAdapter da = new SqlDataAdapter(cmd);
            DataTable dt = new DataTable();

            try {
                da.Fill(dt);
                return dt;
            } catch (Exception ex) {
                Log($"Error al ejecutar SP '{nombreSp}': {ex.Message}");
                return null;
            }
        }

        public int EjecutarSPNonQuery(string nombreSp, params SqlParameter[] parametros) {
            using SqlConnection? cn = AbrirConexion();
            if (cn == null)
                return -1;

            using SqlCommand cmd = new SqlCommand(nombreSp, cn) {
                CommandType = CommandType.StoredProcedure
            };

            if (parametros != null && parametros.Length > 0)
                cmd.Parameters.AddRange(parametros);

            try {
                int filas = cmd.ExecuteNonQuery();

                if (filas < 0)
                    filas = 1;

                return filas;
            } catch (Exception ex) {
                Log($"Error al ejecutar SP NonQuery '{nombreSp}': {ex.Message}");
                return -1;
            }
        }

        private void Log(string message) {
            try {
                string? logPath = _configuration["LogSettings:Path"];

                if (string.IsNullOrWhiteSpace(logPath))
                    return;

                string? dir = Path.GetDirectoryName(logPath);
                if (!string.IsNullOrEmpty(dir) && !Directory.Exists(dir)) {
                    Directory.CreateDirectory(dir);
                }

                string line = $"[{DateTime.Now:yyyy-MM-dd HH:mm:ss}] {message}{Environment.NewLine}";
                File.AppendAllText(logPath, line);
            } catch {
            }
        }
    }
}
