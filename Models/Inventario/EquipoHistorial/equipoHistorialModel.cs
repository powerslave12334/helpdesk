using HELPDESK.Services;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Models.Inventario.EquipoHistorial {
    public class equipoHistorialModel {
        public int IdHistorialEquipoUsuario { get; set; }
        public int IdEquipoUsuarioOriginal { get; set; }
        public int IdEquipo { get; set; }
        public string NombreEquipo { get; set; } = string.Empty;
        public int IdUsuario { get; set; }
        public string NombreUsuario { get; set; } = string.Empty;
        public DateTime FechaAsignacion { get; set; }
        public DateTime? FechaBaja { get; set; }
        public string Observaciones { get; set; } = string.Empty;
        public int? IdArea { get; set; }
        public string NombreArea { get; set; } = string.Empty;
        public DateTime FechaAlta { get; set; }
        public DateTime FechaAccion { get; set; }
        public int IdUsuarioAccion { get; set; }
        public string NombreUsuarioAccion { get; set; } = string.Empty;
        public string Accion { get; set; } = string.Empty;
        public string? Motivo { get; set; }
        public string? EquipoMarca { get; set; }
        public string? EquipoModelo { get; set; }
        public string? EquipoNombre { get; set; }
        public string? EquipoNumeroSerie { get; set; }
        public string? EquipoUbicacion { get; set; }
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<equipoHistorialModel> Historial { get; set; } = new List<equipoHistorialModel>();

        public List<equipoHistorialModel> ObtenerHistorial(DBConector dbConector) {
            List<equipoHistorialModel> lista = new List<equipoHistorialModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT
                    h.id_HistorialEquipoUsuario,
                    h.id_EquipoUsuarioOriginal,
                    h.id_Equipo,
                    ISNULL(h.equipoMarca, '') + ' ' + ISNULL(h.equipoModelo, '') + ' ' + ISNULL(h.equipoNombre, '') AS equipo,
                    h.id_Usuario,
                    LTRIM(RTRIM(ISNULL(u.nombre, '') + ' ' + ISNULL(u.app, '') + ' ' + ISNULL(u.apm, ''))) AS usuario,
                    h.fechaAsignacion,
                    h.fechaBaja,
                    h.observaciones,
                    h.id_Area,
                    ISNULL(a.nombre, '') AS area,
                    h.fechaAlta,
                    h.fechaAccion,
                    h.id_UsuarioAccion,
                    LTRIM(RTRIM(ISNULL(ua.nombre, '') + ' ' + ISNULL(ua.app, '') + ' ' + ISNULL(ua.apm, ''))) AS usuarioAccion,
                    h.accion,
                    h.motivo,
                    h.equipoMarca,
                    h.equipoModelo,
                    h.equipoNombre,
                    h.equipoNumeroSerie,
                    h.equipoUbicacion
                FROM dbo.tblHistorialEquipoUsuario h
                INNER JOIN dbo.tblUsuario u ON u.id_Usuario = h.id_Usuario
                INNER JOIN dbo.tblUsuario ua ON ua.id_Usuario = h.id_UsuarioAccion
                LEFT JOIN dbo.tblArea a ON a.id_Area = h.id_Area
                ORDER BY h.fechaAccion DESC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new equipoHistorialModel {
                    IdHistorialEquipoUsuario = Convert.ToInt32(dr["id_HistorialEquipoUsuario"]),
                    IdEquipoUsuarioOriginal = Convert.ToInt32(dr["id_EquipoUsuarioOriginal"]),
                    IdEquipo = Convert.ToInt32(dr["id_Equipo"]),
                    NombreEquipo = dr["equipo"].ToString() ?? string.Empty,
                    IdUsuario = Convert.ToInt32(dr["id_Usuario"]),
                    NombreUsuario = dr["usuario"].ToString() ?? string.Empty,
                    FechaAsignacion = Convert.ToDateTime(dr["fechaAsignacion"]),
                    FechaBaja = dr["fechaBaja"] == DBNull.Value ? null : Convert.ToDateTime(dr["fechaBaja"]),
                    Observaciones = dr["observaciones"].ToString() ?? string.Empty,
                    IdArea = dr["id_Area"] == DBNull.Value ? null : Convert.ToInt32(dr["id_Area"]),
                    NombreArea = dr["area"].ToString() ?? string.Empty,
                    FechaAlta = Convert.ToDateTime(dr["fechaAlta"]),
                    FechaAccion = Convert.ToDateTime(dr["fechaAccion"]),
                    IdUsuarioAccion = Convert.ToInt32(dr["id_UsuarioAccion"]),
                    NombreUsuarioAccion = dr["usuarioAccion"].ToString() ?? string.Empty,
                    Accion = dr["accion"].ToString() ?? string.Empty,
                    Motivo = dr["motivo"] == DBNull.Value ? null : dr["motivo"].ToString(),
                    EquipoMarca = dr["equipoMarca"] == DBNull.Value ? null : dr["equipoMarca"].ToString(),
                    EquipoModelo = dr["equipoModelo"] == DBNull.Value ? null : dr["equipoModelo"].ToString(),
                    EquipoNombre = dr["equipoNombre"] == DBNull.Value ? null : dr["equipoNombre"].ToString(),
                    EquipoNumeroSerie = dr["equipoNumeroSerie"] == DBNull.Value ? null : dr["equipoNumeroSerie"].ToString(),
                    EquipoUbicacion = dr["equipoUbicacion"] == DBNull.Value ? null : dr["equipoUbicacion"].ToString()
                });
            }

            return lista;
        }

        public bool InsertarHistorial(DBConector dbConector, int idUsuarioAccion, string accion) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                INSERT INTO dbo.tblHistorialEquipoUsuario
                    (id_EquipoUsuarioOriginal, id_Equipo, id_Usuario, fechaAsignacion, fechaBaja,
                     observaciones, id_Area, fechaAlta, fechaAccion, id_UsuarioAccion, accion,
                     motivo, equipoMarca, equipoModelo, equipoNombre, equipoNumeroSerie, equipoUbicacion)
                VALUES
                    (@id_EquipoUsuarioOriginal, @id_Equipo, @id_Usuario, @fechaAsignacion, @fechaBaja,
                     @observaciones, @id_Area, @fechaAlta, GETDATE(), @id_UsuarioAccion, @accion,
                     @motivo, @equipoMarca, @equipoModelo, @equipoNombre, @equipoNumeroSerie, @equipoUbicacion)";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_EquipoUsuarioOriginal", System.Data.SqlDbType.Int).Value = IdEquipoUsuarioOriginal;
            cmd.Parameters.Add("@id_Equipo", System.Data.SqlDbType.Int).Value = IdEquipo;
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = IdUsuario;
            cmd.Parameters.Add("@fechaAsignacion", System.Data.SqlDbType.DateTime).Value = FechaAsignacion;
            cmd.Parameters.Add("@fechaBaja", System.Data.SqlDbType.DateTime).Value = FechaBaja.HasValue ? FechaBaja.Value : (object)DBNull.Value;
            cmd.Parameters.Add("@observaciones", System.Data.SqlDbType.VarChar, -1).Value = string.IsNullOrWhiteSpace(Observaciones) ? (object)DBNull.Value : Observaciones.Trim();
            cmd.Parameters.Add("@id_Area", System.Data.SqlDbType.Int).Value = IdArea.HasValue && IdArea.Value > 0 ? IdArea.Value : (object)DBNull.Value;
            cmd.Parameters.Add("@fechaAlta", System.Data.SqlDbType.DateTime).Value = FechaAlta;
            cmd.Parameters.Add("@id_UsuarioAccion", System.Data.SqlDbType.Int).Value = idUsuarioAccion;
            cmd.Parameters.Add("@accion", System.Data.SqlDbType.VarChar, 50).Value = accion;
            cmd.Parameters.Add("@motivo", System.Data.SqlDbType.VarChar, -1).Value = string.IsNullOrWhiteSpace(Motivo) ? (object)DBNull.Value : Motivo.Trim();
            cmd.Parameters.Add("@equipoMarca", System.Data.SqlDbType.VarChar, 150).Value = string.IsNullOrWhiteSpace(EquipoMarca) ? (object)DBNull.Value : EquipoMarca.Trim();
            cmd.Parameters.Add("@equipoModelo", System.Data.SqlDbType.VarChar, 150).Value = string.IsNullOrWhiteSpace(EquipoModelo) ? (object)DBNull.Value : EquipoModelo.Trim();
            cmd.Parameters.Add("@equipoNombre", System.Data.SqlDbType.VarChar, 150).Value = string.IsNullOrWhiteSpace(EquipoNombre) ? (object)DBNull.Value : EquipoNombre.Trim();
            cmd.Parameters.Add("@equipoNumeroSerie", System.Data.SqlDbType.VarChar, 150).Value = string.IsNullOrWhiteSpace(EquipoNumeroSerie) ? (object)DBNull.Value : EquipoNumeroSerie.Trim();
            cmd.Parameters.Add("@equipoUbicacion", System.Data.SqlDbType.VarChar, 150).Value = string.IsNullOrWhiteSpace(EquipoUbicacion) ? (object)DBNull.Value : EquipoUbicacion.Trim();

            return cmd.ExecuteNonQuery() > 0;
        }
    }
}
