using HELPDESK.Services;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Models.Inventario.EquipoUsuario {
    public class equipoUsuarioModel {
        public int IdEquipoUsuario { get; set; }
        public int IdEquipo { get; set; }
        public string NombreEquipo { get; set; } = string.Empty;
        public int IdUsuario { get; set; }
        public string NombreUsuario { get; set; } = string.Empty;
        public DateTime FechaAsignacion { get; set; }
        public DateTime? FechaBaja { get; set; }
        public string Observaciones { get; set; } = string.Empty;
        public int? IdArea { get; set; }
        public string NombreArea { get; set; } = string.Empty;
        public string OficinaEquipo { get; set; } = string.Empty;
        public string OficinaUsuario { get; set; } = string.Empty;
        public DateTime FechaAlta { get; set; }
        public bool Activo { get; set; }
        public string? EquipoMarca { get; set; }
        public string? EquipoModelo { get; set; }
        public string? EquipoNombre { get; set; }
        public string? EquipoNumeroSerie { get; set; }
        public string? EquipoUbicacion { get; set; }
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<equipoUsuarioModel> Asignaciones { get; set; } = new List<equipoUsuarioModel>();
        public List<equipoUsuarioModel> Equipos { get; set; } = new List<equipoUsuarioModel>();
        public List<equipoUsuarioModel> Usuarios { get; set; } = new List<equipoUsuarioModel>();
        public List<equipoUsuarioModel> Areas { get; set; } = new List<equipoUsuarioModel>();
        public List<equipoUsuarioModel> UsuariosConEquipos { get; set; } = new List<equipoUsuarioModel>();

        public List<equipoUsuarioModel> ObtenerAsignaciones(DBConector dbConector) {
            List<equipoUsuarioModel> lista = new List<equipoUsuarioModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT
                    eu.id_EquipoUsuario,
                    eu.id_Equipo,
                    LTRIM(RTRIM(ISNULL(e.marca, '') + ' ' + ISNULL(e.modelo, '') + ' ' + ISNULL(e.nombreEquipo, ''))) AS equipo,
                    eu.id_Usuario,
                    LTRIM(RTRIM(ISNULL(u.nombre, '') + ' ' + ISNULL(u.app, '') + ' ' + ISNULL(u.apm, ''))) AS usuario,
                    eu.fechaAsignacion,
                    eu.fechaBaja,
                    eu.observaciones,
                    eu.id_Area,
                    ISNULL(a.nombre, '') AS area,
                    LTRIM(RTRIM(ISNULL(oe.nombre, '') + CASE WHEN ISNULL(oe.descripcion, '') = '' THEN '' ELSE ' - ' + oe.descripcion END)) AS oficinaEquipo,
                    LTRIM(RTRIM(ISNULL(ou.nombre, '') + CASE WHEN ISNULL(ou.descripcion, '') = '' THEN '' ELSE ' - ' + ou.descripcion END)) AS oficinaUsuario,
                    eu.fechaAlta,
                    eu.activo
                FROM dbo.tblEquipoUsuario eu
                INNER JOIN dbo.tblEquipo e ON e.id_Equipo = eu.id_Equipo
                INNER JOIN dbo.tblUsuario u ON u.id_Usuario = eu.id_Usuario
                LEFT JOIN dbo.tblArea a ON a.id_Area = eu.id_Area
                LEFT JOIN dbo.tblOficinas oe ON oe.id_Oficinas = e.id_Oficinas
                LEFT JOIN dbo.tblOficinas ou ON ou.id_Oficinas = u.id_Oficinas
                ORDER BY eu.activo DESC, eu.fechaAsignacion DESC, equipo ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new equipoUsuarioModel {
                    IdEquipoUsuario = Convert.ToInt32(dr["id_EquipoUsuario"]),
                    IdEquipo = Convert.ToInt32(dr["id_Equipo"]),
                    NombreEquipo = dr["equipo"].ToString() ?? string.Empty,
                    IdUsuario = Convert.ToInt32(dr["id_Usuario"]),
                    NombreUsuario = dr["usuario"].ToString() ?? string.Empty,
                    FechaAsignacion = Convert.ToDateTime(dr["fechaAsignacion"]),
                    FechaBaja = dr["fechaBaja"] == DBNull.Value ? null : Convert.ToDateTime(dr["fechaBaja"]),
                    Observaciones = dr["observaciones"].ToString() ?? string.Empty,
                    IdArea = dr["id_Area"] == DBNull.Value ? null : Convert.ToInt32(dr["id_Area"]),
                    NombreArea = dr["area"].ToString() ?? string.Empty,
                    OficinaEquipo = dr["oficinaEquipo"].ToString() ?? string.Empty,
                    OficinaUsuario = dr["oficinaUsuario"].ToString() ?? string.Empty,
                    FechaAlta = Convert.ToDateTime(dr["fechaAlta"]),
                    Activo = Convert.ToBoolean(dr["activo"])
                });
            }

            return lista;
        }

        public List<equipoUsuarioModel> ObtenerEquiposActivos(DBConector dbConector) {
            List<equipoUsuarioModel> lista = new List<equipoUsuarioModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT 
                    E.id_Equipo,
                    LTRIM(RTRIM(ISNULL(E.marca, '') + ' ' + ISNULL(E.modelo, '') + ' ' + ISNULL(nombreEquipo, '') + ' ' + ISNULL(E.numeroSerie, ''))) AS equipo
                FROM 
                    tblEquipo E
                WHERE ISNULL(E.esCompartido, 0) = 0
                AND NOT EXISTS (
                    SELECT 1
                    FROM tblEquipoUsuario EU
                    WHERE EU.id_Equipo = E.id_Equipo
                      AND EU.activo = 1
                );";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new equipoUsuarioModel {
                    IdEquipo = Convert.ToInt32(dr["id_Equipo"]),
                    NombreEquipo = dr["equipo"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public List<equipoUsuarioModel> ObtenerUsuariosActivos(DBConector dbConector) {
            List<equipoUsuarioModel> lista = new List<equipoUsuarioModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT
                    id_Usuario,
                    LTRIM(RTRIM(nombre + ' ' + app + ' ' + apm)) AS usuario
                FROM dbo.tblUsuario
                WHERE activo = 1
                ORDER BY nombre ASC, app ASC, apm ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new equipoUsuarioModel {
                    IdUsuario = Convert.ToInt32(dr["id_Usuario"]),
                    NombreUsuario = dr["usuario"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public List<equipoUsuarioModel> ObtenerAreasActivas(DBConector dbConector) {
            List<equipoUsuarioModel> lista = new List<equipoUsuarioModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_Area, nombre
                FROM dbo.tblArea
                WHERE activo = 1
                ORDER BY nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new equipoUsuarioModel {
                    IdArea = Convert.ToInt32(dr["id_Area"]),
                    NombreArea = dr["nombre"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public int InsertarAsignacion(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return -1;

            string query = @"
                INSERT INTO dbo.tblEquipoUsuario
                    (id_Equipo, id_Usuario, fechaAsignacion, fechaBaja, observaciones, id_Area, fechaAlta, activo)
                OUTPUT INSERTED.id_EquipoUsuario
                VALUES
                    (@id_Equipo, @id_Usuario, @fechaAsignacion, @fechaBaja, @observaciones, @id_Area, GETDATE(), 1)";

            using SqlCommand cmd = new SqlCommand(query, cn);
            AgregarParametros(cmd);

            object result = cmd.ExecuteScalar();
            return result != null ? Convert.ToInt32(result) : -1;
        }

        public bool ActualizarAsignacion(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblEquipoUsuario
                SET id_Equipo = @id_Equipo,
                    id_Usuario = @id_Usuario,
                    fechaAsignacion = @fechaAsignacion,
                    fechaBaja = @fechaBaja,
                    observaciones = @observaciones,
                    id_Area = @id_Area
                WHERE id_EquipoUsuario = @id_EquipoUsuario";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_EquipoUsuario", System.Data.SqlDbType.Int).Value = IdEquipoUsuario;
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool CambiarEstatus(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblEquipoUsuario
                SET activo = CASE WHEN activo = 1 THEN 0 ELSE 1 END,
                    fechaBaja = CASE WHEN activo = 1 AND fechaBaja IS NULL THEN GETDATE() ELSE fechaBaja END
                WHERE id_EquipoUsuario = @id_EquipoUsuario";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_EquipoUsuario", System.Data.SqlDbType.Int).Value = IdEquipoUsuario;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool EquipoTieneAsignacionActiva(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblEquipoUsuario
                WHERE id_Equipo = @id_Equipo
                  AND activo = 1
                  AND fechaBaja IS NULL
                  AND id_EquipoUsuario <> @id_EquipoUsuario";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Equipo", System.Data.SqlDbType.Int).Value = IdEquipo;
            cmd.Parameters.Add("@id_EquipoUsuario", System.Data.SqlDbType.Int).Value = IdEquipoUsuario;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        public List<equipoUsuarioModel> ObtenerEquiposAsignadosPorUsuario(DBConector dbConector) {
            List<equipoUsuarioModel> lista = new List<equipoUsuarioModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT
                    eu.id_EquipoUsuario,
                    eu.id_Equipo,
                    LTRIM(RTRIM(ISNULL(e.marca, '') + ' ' + ISNULL(e.modelo, '') + ' ' + ISNULL(e.nombreEquipo, ''))) AS equipo,
                    eu.id_Usuario,
                    LTRIM(RTRIM(ISNULL(u.nombre, '') + ' ' + ISNULL(u.app, '') + ' ' + ISNULL(u.apm, ''))) AS usuario,
                    eu.fechaAsignacion,
                    eu.observaciones
                FROM dbo.tblEquipoUsuario eu
                INNER JOIN dbo.tblEquipo e ON e.id_Equipo = eu.id_Equipo
                INNER JOIN dbo.tblUsuario u ON u.id_Usuario = eu.id_Usuario
                WHERE eu.id_Usuario = @id_Usuario
                  AND eu.activo = 1
                  AND eu.fechaBaja IS NULL
                ORDER BY equipo ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = IdUsuario;

            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new equipoUsuarioModel {
                    IdEquipoUsuario = Convert.ToInt32(dr["id_EquipoUsuario"]),
                    IdEquipo = Convert.ToInt32(dr["id_Equipo"]),
                    NombreEquipo = dr["equipo"].ToString() ?? string.Empty,
                    IdUsuario = Convert.ToInt32(dr["id_Usuario"]),
                    NombreUsuario = dr["usuario"].ToString() ?? string.Empty,
                    FechaAsignacion = Convert.ToDateTime(dr["fechaAsignacion"]),
                    Observaciones = dr["observaciones"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public List<equipoUsuarioModel> ObtenerUsuariosConEquiposActivos(DBConector dbConector) {
            List<equipoUsuarioModel> lista = new List<equipoUsuarioModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT DISTINCT
                    u.id_Usuario,
                    LTRIM(RTRIM(u.nombre + ' ' + u.app + ' ' + u.apm)) AS usuario
                FROM dbo.tblUsuario u
                INNER JOIN dbo.tblEquipoUsuario eu ON eu.id_Usuario = u.id_Usuario
                WHERE u.activo = 1
                  AND eu.activo = 1
                  AND eu.fechaBaja IS NULL
                ORDER BY usuario ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new equipoUsuarioModel {
                    IdUsuario = Convert.ToInt32(dr["id_Usuario"]),
                    NombreUsuario = dr["usuario"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public bool LiberarEquiposPorUsuario(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblEquipoUsuario
                SET activo = 0,
                    fechaBaja = CASE WHEN fechaBaja IS NULL THEN GETDATE() ELSE fechaBaja END
                WHERE id_Usuario = @id_Usuario
                  AND activo = 1
                  AND fechaBaja IS NULL";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = IdUsuario;

            return cmd.ExecuteNonQuery() > 0;
        }

        public equipoUsuarioModel? ObtenerAsignacionPorId(DBConector dbConector, int idEquipoUsuario) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return null;

            string query = @"
                SELECT
                    eu.id_EquipoUsuario,
                    eu.id_Equipo,
                    LTRIM(RTRIM(ISNULL(e.marca, '') + ' ' + ISNULL(e.modelo, '') + ' ' + ISNULL(e.nombreEquipo, ''))) AS equipo,
                    eu.id_Usuario,
                    LTRIM(RTRIM(ISNULL(u.nombre, '') + ' ' + ISNULL(u.app, '') + ' ' + ISNULL(u.apm, ''))) AS usuario,
                    eu.fechaAsignacion,
                    eu.fechaBaja,
                    eu.observaciones,
                    eu.id_Area,
                    ISNULL(a.nombre, '') AS area,
                    eu.fechaAlta,
                    eu.activo,
                    e.marca AS equipoMarca,
                    e.modelo AS equipoModelo,
                    e.nombreEquipo AS equipoNombre,
                    e.numeroSerie AS equipoNumeroSerie,
                    e.ubicacion AS equipoUbicacion
                FROM dbo.tblEquipoUsuario eu
                INNER JOIN dbo.tblEquipo e ON e.id_Equipo = eu.id_Equipo
                INNER JOIN dbo.tblUsuario u ON u.id_Usuario = eu.id_Usuario
                LEFT JOIN dbo.tblArea a ON a.id_Area = eu.id_Area
                WHERE eu.id_EquipoUsuario = @id_EquipoUsuario";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_EquipoUsuario", System.Data.SqlDbType.Int).Value = idEquipoUsuario;

            using SqlDataReader dr = cmd.ExecuteReader();
            if (dr.Read()) {
                return new equipoUsuarioModel {
                    IdEquipoUsuario = Convert.ToInt32(dr["id_EquipoUsuario"]),
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
                    Activo = Convert.ToBoolean(dr["activo"]),
                    EquipoMarca = dr["equipoMarca"] == DBNull.Value ? null : dr["equipoMarca"].ToString(),
                    EquipoModelo = dr["equipoModelo"] == DBNull.Value ? null : dr["equipoModelo"].ToString(),
                    EquipoNombre = dr["equipoNombre"] == DBNull.Value ? null : dr["equipoNombre"].ToString(),
                    EquipoNumeroSerie = dr["equipoNumeroSerie"] == DBNull.Value ? null : dr["equipoNumeroSerie"].ToString(),
                    EquipoUbicacion = dr["equipoUbicacion"] == DBNull.Value ? null : dr["equipoUbicacion"].ToString()
                };
            }

            return null;
        }

        public List<equipoUsuarioModel> ObtenerEquiposActivosDeUsuario(DBConector dbConector, int idUsuario) {
            List<equipoUsuarioModel> lista = new List<equipoUsuarioModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT
                    eu.id_EquipoUsuario,
                    eu.id_Equipo,
                    LTRIM(RTRIM(ISNULL(e.marca, '') + ' ' + ISNULL(e.modelo, '') + ' ' + ISNULL(e.nombreEquipo, ''))) AS equipo,
                    eu.id_Usuario,
                    LTRIM(RTRIM(ISNULL(u.nombre, '') + ' ' + ISNULL(u.app, '') + ' ' + ISNULL(u.apm, ''))) AS usuario,
                    eu.fechaAsignacion,
                    eu.fechaBaja,
                    eu.observaciones,
                    eu.id_Area,
                    ISNULL(a.nombre, '') AS area,
                    eu.fechaAlta,
                    eu.activo
                FROM dbo.tblEquipoUsuario eu
                INNER JOIN dbo.tblEquipo e ON e.id_Equipo = eu.id_Equipo
                INNER JOIN dbo.tblUsuario u ON u.id_Usuario = eu.id_Usuario
                LEFT JOIN dbo.tblArea a ON a.id_Area = eu.id_Area
                WHERE eu.id_Usuario = @id_Usuario
                  AND eu.activo = 1
                  AND eu.fechaBaja IS NULL";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = idUsuario;

            using SqlDataReader dr = cmd.ExecuteReader();
            while (dr.Read()) {
                lista.Add(new equipoUsuarioModel {
                    IdEquipoUsuario = Convert.ToInt32(dr["id_EquipoUsuario"]),
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
                    Activo = Convert.ToBoolean(dr["activo"])
                });
            }

            return lista;
        }

        public bool EliminarAsignacion(DBConector dbConector, int idEquipoUsuario) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = "DELETE FROM dbo.tblEquipoUsuario WHERE id_EquipoUsuario = @id_EquipoUsuario";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_EquipoUsuario", System.Data.SqlDbType.Int).Value = idEquipoUsuario;

            return cmd.ExecuteNonQuery() > 0;
        }

        private void AgregarParametros(SqlCommand cmd) {
            cmd.Parameters.Add("@id_Equipo", System.Data.SqlDbType.Int).Value = IdEquipo;
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = IdUsuario;
            cmd.Parameters.Add("@fechaAsignacion", System.Data.SqlDbType.DateTime).Value = FechaAsignacion;
            cmd.Parameters.Add("@fechaBaja", System.Data.SqlDbType.DateTime).Value = FechaBaja.HasValue ? FechaBaja.Value : (object)DBNull.Value;
            cmd.Parameters.Add("@observaciones", System.Data.SqlDbType.VarChar, -1).Value = string.IsNullOrWhiteSpace(Observaciones) ? (object)DBNull.Value : Observaciones.Trim();
            cmd.Parameters.Add("@id_Area", System.Data.SqlDbType.Int).Value = IdArea.HasValue && IdArea.Value > 0 ? IdArea.Value : (object)DBNull.Value;
        }
    }
}
