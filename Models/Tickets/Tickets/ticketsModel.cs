using HELPDESK.Services;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Models.Tickets.Tickets {
    public class ticketsModel {
        public int IdTicket { get; set; }
        public int IdTicketOriginal { get; set; }
        public int TipoTicket { get; set; } = 1;
        public int IdUsuario { get; set; }
        public string Usuario { get; set; } = string.Empty;
        public int IdUsuarioSolicita { get; set; }
        public string UsuarioSolicita { get; set; } = string.Empty;
        public int IdTipoSoporte { get; set; }
        public string TipoSoporte { get; set; } = string.Empty;
        public int IdTipoDesarrollo { get; set; }
        public string TipoDesarrollo { get; set; } = string.Empty;
        public int IdSistemas { get; set; }
        public string Sistema { get; set; } = string.Empty;
        public string DescripcionTicket { get; set; } = string.Empty;
        public int? IdCategoriaSoporte { get; set; }
        public string CategoriaSoporte { get; set; } = string.Empty;
        public bool RequiereEquipo { get; set; }
        public int IdTipoEquipoTicket { get; set; }
        public string TipoEquipoTicket { get; set; } = "NINGUNO";
        public int? IdTecnico { get; set; }
        public string Tecnico { get; set; } = string.Empty;
        public List<ticketsModel> Desarrolladores { get; set; } = new List<ticketsModel>();
        public int? IdEstatus { get; set; }
        public string Estatus { get; set; } = string.Empty;
        public int? IdEquipo { get; set; }
        public string Equipo { get; set; } = string.Empty;
        public int? IdAreaAtiende { get; set; }
        public string AreaAtiende { get; set; } = string.Empty;
        public string Asunto { get; set; } = string.Empty;
        public string Problema { get; set; } = string.Empty;
        public string Img { get; set; } = string.Empty;
        public DateTime FechaAlta { get; set; }
        public DateTime? FechaCierre { get; set; }
        public DateTime UltimaActividad { get; set; }
        public int DiasSinActividad { get; set; }
        public bool Activo { get; set; }
        public bool PuedeActualizar { get; set; }
        public bool PuedeCerrarTicket { get; set; }
        public bool PuedeAutorizar { get; set; }
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<ticketsModel> Tickets { get; set; } = new List<ticketsModel>();
        public List<ticketsModel> MisTickets { get; set; } = new List<ticketsModel>();
        public List<ticketsModel> TicketsAsignados { get; set; } = new List<ticketsModel>();
        public List<ticketsModel> TicketsTodos { get; set; } = new List<ticketsModel>();
        public List<ticketsModel> TiposSoporte { get; set; } = new List<ticketsModel>();
        public List<ticketsModel> CategoriasSoporte { get; set; } = new List<ticketsModel>();
        public List<ticketsModel> AreasAtiende { get; set; } = new List<ticketsModel>();
        public List<ticketsModel> Equipos { get; set; } = new List<ticketsModel>();
        public List<ticketsModel> EquiposCompartidos { get; set; } = new List<ticketsModel>();
        public List<ticketsModel> EstatusTicket { get; set; } = new List<ticketsModel>();
        public List<ticketsModel> Tecnicos { get; set; } = new List<ticketsModel>();
        public List<ticketsModel> UsuariosActivos { get; set; } = new List<ticketsModel>();
        public List<ticketsModel> TiposDesarrollo { get; set; } = new List<ticketsModel>();
        public List<ticketsModel> SistemasDesarrollo { get; set; } = new List<ticketsModel>();
        public List<ticketsModel> ModulosDesarrollo { get; set; } = new List<ticketsModel>();
        public List<ticketsModel> SubModulosDesarrollo { get; set; } = new List<ticketsModel>();
        public List<ticketDetalleModel> Detalles { get; set; } = new List<ticketDetalleModel>();
        public List<ticketEvidenciaModel> Evidencias { get; set; } = new List<ticketEvidenciaModel>();
        public bool PuedeVerAsignados { get; set; }
        public bool PuedeVerTodos { get; set; }
        public int IdModulo { get; set; }
        public string NombreModulo { get; set; } = string.Empty;
        public int IdSubModulo { get; set; }
        public string NombreSubModulo { get; set; } = string.Empty;

        public List<ticketsModel> ObtenerMisTickets(DBConector dbConector, int idUsuario) {
            return ObtenerTickets(dbConector, "mis", idUsuario);
        }

        public List<ticketsModel> ObtenerTicketsAsignados(DBConector dbConector, int idUsuario) {
            return ObtenerTickets(dbConector, "asignados", idUsuario);
        }

        public List<ticketsModel> ObtenerTodosTickets(DBConector dbConector) {
            return ObtenerTickets(dbConector, "todos", null);
        }

        private List<ticketsModel> ObtenerTickets(DBConector dbConector, string tipoConsulta, int? idUsuario) {
            List<ticketsModel> lista = new List<ticketsModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;
            string query = @"
                SELECT
                    t.id_Ticket,
                    t.id_Ticket AS id_TicketOriginal,
                    t.id_Usuario,
                    LTRIM(RTRIM(ISNULL(u.nombre, '') + ' ' + ISNULL(u.app, '') + ' ' + ISNULL(u.apm, ''))) AS usuario,
                    t.id_TipoSoporte,
                    ISNULL(ts.descripcion, '') AS tipoSoporte,
                    CAST(NULL AS int) AS id_CategoriaSoporte,
                    '' AS categoriaSoporte,
                    CAST(0 AS bit) AS requiereEquipo,
                    0 AS id_TipoEquipoTicket,
                    'NINGUNO' AS tipoEquipoTicket,
                    t.id_Tecnico,
                    LTRIM(RTRIM(ISNULL(tec.nombre, '') + ' ' + ISNULL(tec.app, '') + ' ' + ISNULL(tec.apm, ''))) AS tecnico,
                    t.id_Estatus,
                    ISNULL(es.descripcion, '') AS estatus,
                    t.id_Equipo,
                    LTRIM(RTRIM(ISNULL(eq.marca, '') + ' ' + ISNULL(eq.modelo, '') + ' ' + ISNULL(eq.nombreEquipo, '') + ' ' + ISNULL(eq.numeroSerie, ''))) AS equipo,
                    ts.id_Area AS id_AreaAtiende,
                    ISNULL(a.nombre, '') AS areaAtiende,
                    t.asunto,
                    t.problema,
                    t.img,
                    t.fechaAlta,
                    t.fechaCierre,
                    ISNULL(ua.fechaUltimaActividad, t.fechaAlta) AS ultimaActividad,
                    DATEDIFF(DAY, ISNULL(ua.fechaUltimaActividad, t.fechaAlta), GETDATE()) AS diasSinActividad,
                    t.activo
                FROM dbo.tblTicket t
                INNER JOIN dbo.tblUsuario u ON u.id_Usuario = t.id_Usuario
                INNER JOIN dbo.tblTipoSoporte ts ON ts.id_TipoSoporte = t.id_TipoSoporte
                LEFT JOIN dbo.tblUsuario tec ON tec.id_Usuario = t.id_Tecnico
                LEFT JOIN dbo.tblEstatus es ON es.id_Estatus = t.id_Estatus
                LEFT JOIN dbo.tblEquipo eq ON eq.id_Equipo = t.id_Equipo
                LEFT JOIN dbo.tblArea a ON a.id_Area = ts.id_Area
                OUTER APPLY (
                    SELECT MAX(actividad.fechaActividad) AS fechaUltimaActividad
                    FROM (
                        SELECT tc.fechaComentario AS fechaActividad
                        FROM dbo.tblTicketComentarios tc
                        WHERE tc.id_Ticket = t.id_Ticket
                          AND tc.activo = 1

                        UNION ALL

                        SELECT te.fechaAlta AS fechaActividad
                        FROM dbo.tblTicketEvidencia te
                        WHERE te.id_Ticket = t.id_Ticket
                          AND te.activo = 1
                    ) actividad
                ) ua
                WHERE t.activo = 1
                  AND (
                      @tipoConsulta = 'todos'
                      OR (@tipoConsulta = 'mis' AND t.id_Usuario = @id_Usuario)
                      OR (@tipoConsulta = 'asignados' AND t.id_Tecnico = @id_Usuario)
                  )
                ORDER BY t.fechaAlta DESC, t.id_Ticket DESC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = idUsuario.HasValue ? idUsuario.Value : (object)DBNull.Value;
            cmd.Parameters.Add("@tipoConsulta", System.Data.SqlDbType.VarChar, 20).Value = tipoConsulta;
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                int idTicket = Convert.ToInt32(dr["id_Ticket"]);
                lista.Add(new ticketsModel {
                    IdTicket = idTicket,
                    IdTicketOriginal = ObtenerValorInt(dr, "id_TicketOriginal", idTicket),
                    TipoTicket = Convert.ToInt32(dr["id_TipoSoporte"]),
                    IdUsuario = Convert.ToInt32(dr["id_Usuario"]),
                    Usuario = dr["usuario"].ToString() ?? string.Empty,
                    IdTipoSoporte = Convert.ToInt32(dr["id_TipoSoporte"]),
                    TipoSoporte = dr["tipoSoporte"].ToString() ?? string.Empty,
                    IdCategoriaSoporte = dr["id_CategoriaSoporte"] == DBNull.Value ? null : Convert.ToInt32(dr["id_CategoriaSoporte"]),
                    CategoriaSoporte = dr["categoriaSoporte"].ToString() ?? string.Empty,
                    RequiereEquipo = Convert.ToBoolean(dr["requiereEquipo"]),
                    IdTipoEquipoTicket = Convert.ToInt32(dr["id_TipoEquipoTicket"]),
                    TipoEquipoTicket = (dr["tipoEquipoTicket"].ToString() ?? "NINGUNO").Trim().ToUpperInvariant(),
                    IdTecnico = dr["id_Tecnico"] == DBNull.Value ? null : Convert.ToInt32(dr["id_Tecnico"]),
                    Tecnico = dr["tecnico"].ToString() ?? string.Empty,
                    IdEstatus = dr["id_Estatus"] == DBNull.Value ? null : Convert.ToInt32(dr["id_Estatus"]),
                    Estatus = dr["estatus"].ToString() ?? string.Empty,
                    IdEquipo = dr["id_Equipo"] == DBNull.Value ? null : Convert.ToInt32(dr["id_Equipo"]),
                    Equipo = dr["equipo"].ToString() ?? string.Empty,
                    IdAreaAtiende = dr["id_AreaAtiende"] == DBNull.Value ? null : Convert.ToInt32(dr["id_AreaAtiende"]),
                    AreaAtiende = dr["areaAtiende"].ToString() ?? string.Empty,
                    Asunto = dr["asunto"].ToString() ?? string.Empty,
                    Problema = dr["problema"].ToString() ?? string.Empty,
                    Img = dr["img"].ToString() ?? string.Empty,
                    FechaAlta = Convert.ToDateTime(dr["fechaAlta"]),
                    FechaCierre = dr["fechaCierre"] == DBNull.Value ? null : Convert.ToDateTime(dr["fechaCierre"]),
                    UltimaActividad = Convert.ToDateTime(dr["ultimaActividad"]),
                    DiasSinActividad = Convert.ToInt32(dr["diasSinActividad"]),
                    Activo = Convert.ToBoolean(dr["activo"])
                });
            }

            lista = lista
                .GroupBy(ticket => CrearLlaveTicket(ticket.IdTicket, ticket.TipoTicket))
                .Select(grupo => grupo.First())
                .ToList();

            CargarDetallesTickets(cn, lista);
            CargarEvidenciasTickets(cn, lista);

            return lista;
        }

        public List<ticketsModel> ObtenerTiposSoporteActivos(DBConector dbConector) {
            List<ticketsModel> lista = new List<ticketsModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_TipoSoporte, descripcion, id_Area
                FROM dbo.tblTipoSoporte
                WHERE activo = 1 and id_TipoSoporte = 2
                ORDER BY descripcion ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new ticketsModel {
                    IdTipoSoporte = Convert.ToInt32(dr["id_TipoSoporte"]),
                    TipoSoporte = dr["descripcion"].ToString() ?? string.Empty,
                    IdAreaAtiende = dr["id_Area"] == DBNull.Value ? null : Convert.ToInt32(dr["id_Area"])
                });
            }

            return lista;
        }

        public List<ticketsModel> ObtenerAreasActivas(DBConector dbConector) {
            return ObtenerCatalogo(dbConector, @"
                SELECT id_Area AS id, nombre
                FROM dbo.tblArea
                WHERE activo = 1
                ORDER BY nombre ASC", "area");
        }

        public List<ticketsModel> ObtenerCategoriasSoporteActivas(DBConector dbConector) {
            return new List<ticketsModel>();
        }

        public List<ticketsModel> ObtenerEquiposAsignados(DBConector dbConector, int idUsuario) {
            List<ticketsModel> lista = new List<ticketsModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT
                    e.id_Equipo,
                    LTRIM(RTRIM(ISNULL(e.marca, '') + ' ' + ISNULL(e.modelo, '') + ' ' + ISNULL(e.nombreEquipo, '') + ' ' + ISNULL(e.numeroSerie, ''))) AS equipo
                FROM dbo.tblEquipoUsuario eu
                INNER JOIN dbo.tblEquipo e ON e.id_Equipo = eu.id_Equipo
                WHERE eu.id_Usuario = @id_Usuario
                  AND eu.activo = 1
                  AND eu.fechaBaja IS NULL
                  AND e.activo = 1
                  AND ISNULL(e.esCompartido, 0) = 0
                ORDER BY e.marca ASC, e.modelo ASC, e.nombreEquipo ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = idUsuario;
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new ticketsModel {
                    IdEquipo = Convert.ToInt32(dr["id_Equipo"]),
                    Equipo = dr["equipo"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public List<ticketsModel> ObtenerEquiposCompartidos(DBConector dbConector, int idUsuario) {
            List<ticketsModel> lista = new List<ticketsModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT
                    e.id_Equipo,
                    LTRIM(RTRIM(ISNULL(e.marca, '') + ' ' + ISNULL(e.modelo, '') + ' ' + ISNULL(e.nombreEquipo, '') + ' ' + ISNULL(e.numeroSerie, ''))) AS equipo,
                    ISNULL(e.id_Oficinas, 0) AS id_Oficinas,
                    ISNULL(u.id_Oficinas, 0) AS id_OficinasUsuario
                FROM dbo.tblEquipo e
                LEFT JOIN dbo.tblUsuario u ON u.id_Usuario = @id_Usuario
                WHERE e.activo = 1
                  AND ISNULL(e.esCompartido, 0) = 1
                ORDER BY
                    CASE WHEN ISNULL(e.id_Oficinas, 0) = ISNULL(u.id_Oficinas, 0) THEN 0 ELSE 1 END,
                    e.marca ASC,
                    e.modelo ASC,
                    e.nombreEquipo ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = idUsuario;
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new ticketsModel {
                    IdEquipo = Convert.ToInt32(dr["id_Equipo"]),
                    Equipo = dr["equipo"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public List<ticketsModel> ObtenerEstatusTickets(DBConector dbConector) {
            return ObtenerCatalogo(dbConector, @"
                SELECT e.id_Estatus AS id, e.descripcion AS nombre
                FROM dbo.tblEstatus e
                WHERE e.activo = 1
                  AND e.id_Modulos = 1
                ORDER BY e.descripcion ASC", "estatus");
        }

        public List<ticketsModel> ObtenerTecnicosActivos(DBConector dbConector) {
            List<ticketsModel> lista = new List<ticketsModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT
                    u.id_Usuario,
                    u.id_Area,
                    ISNULL(a.nombre, '') AS area,
                    LTRIM(RTRIM(ISNULL(u.nombre, '') + ' ' + ISNULL(u.app, '') + ' ' + ISNULL(u.apm, ''))) AS tecnico
                FROM dbo.tblUsuario u
                LEFT JOIN dbo.tblArea a ON a.id_Area = u.id_Area
                WHERE u.esTecnico = 1
                  AND u.activo = 1
                ORDER BY u.id_Area ASC, u.id_Usuario ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new ticketsModel {
                    IdTecnico = Convert.ToInt32(dr["id_Usuario"]),
                    IdAreaAtiende = dr["id_Area"] == DBNull.Value ? null : Convert.ToInt32(dr["id_Area"]),
                    AreaAtiende = dr["area"].ToString() ?? string.Empty,
                    Tecnico = dr["tecnico"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public List<ticketsModel> ObtenerDesarrolladoresActivos(DBConector dbConector) {
            List<ticketsModel> lista = new List<ticketsModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_Usuario, CONCAT_WS(' ', nombre, app) AS nombre
                FROM dbo.tblUsuario
                WHERE id_Area = 1
                  AND activo = 1
                ORDER BY nombre ASC, app ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new ticketsModel {
                    IdTecnico = Convert.ToInt32(dr["id_Usuario"]),
                    Tecnico = dr["nombre"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public List<ticketsModel> ObtenerUsuariosActivos(DBConector dbConector) {
            List<ticketsModel> lista = new List<ticketsModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT
                    id_Usuario,
                    LTRIM(RTRIM(ISNULL(nombre, '') + ' ' + ISNULL(app, '') + ' ' + ISNULL(apm, ''))) AS usuario
                FROM dbo.tblUsuario
                WHERE activo = 1
                ORDER BY nombre ASC, app ASC, apm ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new ticketsModel {
                    IdUsuario = Convert.ToInt32(dr["id_Usuario"]),
                    Usuario = dr["usuario"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public ticketsModel ObtenerUsuarioSolicita(DBConector dbConector, int idUsuario) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null || idUsuario <= 0)
                return new ticketsModel();

            string query = @"
                SELECT
                    id_Usuario,
                    LTRIM(RTRIM(ISNULL(nombre, '') + ' ' + ISNULL(app, ''))) AS usuario
                FROM dbo.tblUsuario
                WHERE id_Usuario = @id_Usuario
                  AND activo = 1";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = idUsuario;
            using SqlDataReader dr = cmd.ExecuteReader();

            if (!dr.Read())
                return new ticketsModel();

            return new ticketsModel {
                IdUsuarioSolicita = Convert.ToInt32(dr["id_Usuario"]),
                UsuarioSolicita = dr["usuario"].ToString() ?? string.Empty
            };
        }

        public List<ticketsModel> ObtenerTiposDesarrolloActivos(DBConector dbConector) {
            List<ticketsModel> lista = new List<ticketsModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_TipoDesarrollo, nombre
                FROM dbo.tblTipoDesarrollo
                WHERE enabled = 1
                ORDER BY nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new ticketsModel {
                    IdTipoDesarrollo = Convert.ToInt32(dr["id_TipoDesarrollo"]),
                    TipoDesarrollo = dr["nombre"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public List<ticketsModel> ObtenerSistemasActivos(DBConector dbConector) {
            List<ticketsModel> lista = new List<ticketsModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_sistemas, nombre
                FROM dbo.tblSistemas
                WHERE enabled = 1
                ORDER BY nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new ticketsModel {
                    IdSistemas = Convert.ToInt32(dr["id_sistemas"]),
                    Sistema = dr["nombre"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public List<ticketsModel> ObtenerModulosPorSistema(DBConector dbConector, int idSistemas) {
            List<ticketsModel> lista = new List<ticketsModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_ModulosSistemas, nombre
                FROM dbo.tblModulosSistemas
                WHERE id_sistemas = @id_sistemas
                  AND enabled = 1
                ORDER BY nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_sistemas", System.Data.SqlDbType.Int).Value = idSistemas;
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new ticketsModel {
                    IdModulo = Convert.ToInt32(dr["id_ModulosSistemas"]),
                    NombreModulo = dr["nombre"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public List<ticketsModel> ObtenerSubModulosPorModulo(DBConector dbConector, int idModulosSistemas) {
            List<ticketsModel> lista = new List<ticketsModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_SubmodulosSistemas, id_ModulosSistemas, nombre
                FROM dbo.tblSubmodulosSistemas
                WHERE id_ModulosSistemas = @id_ModulosSistemas
                  AND enabled = 1
                ORDER BY nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_ModulosSistemas", System.Data.SqlDbType.Int).Value = idModulosSistemas;
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new ticketsModel {
                    IdSubModulo = Convert.ToInt32(dr["id_SubmodulosSistemas"]),
                    IdModulo = Convert.ToInt32(dr["id_ModulosSistemas"]),
                    NombreSubModulo = dr["nombre"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public int ObtenerEstatusInicial(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return 0;

            string query = @"
                SELECT TOP 1 e.id_Estatus
                FROM dbo.tblEstatus e
                WHERE e.activo = 1
                  AND e.id_Modulos = 1
                ORDER BY CASE WHEN LOWER(e.descripcion) = 'abierto' THEN 0 ELSE 1 END, e.id_Estatus ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            object? result = cmd.ExecuteScalar();

            return result == null || result == DBNull.Value ? 0 : Convert.ToInt32(result);
        }

        public int ObtenerEstatusPorNombre(DBConector dbConector, string nombre) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return 0;

            string query = @"
                SELECT TOP 1 e.id_Estatus
                FROM dbo.tblEstatus e
                WHERE e.activo = 1
                  AND e.id_Modulos = 1
                  AND LOWER(e.descripcion) = LOWER(@nombre)
                ORDER BY e.id_Estatus ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 100).Value = nombre.Trim();
            object? result = cmd.ExecuteScalar();

            return result == null || result == DBNull.Value ? 0 : Convert.ToInt32(result);
        }

        public int ObtenerAreaPorTipoSoporte(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return 0;

            string query = @"
                SELECT ISNULL(id_Area, 0)
                FROM dbo.tblTipoSoporte
                WHERE id_TipoSoporte = @id_TipoSoporte
                  AND activo = 1";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_TipoSoporte", System.Data.SqlDbType.Int).Value = IdTipoSoporte;
            object? result = cmd.ExecuteScalar();

            return result == null || result == DBNull.Value ? 0 : Convert.ToInt32(result);
        }

        public bool CategoriaPerteneceTipoSoporte(DBConector dbConector) {
            return true;
        }

        public string ObtenerTipoEquipoTicket(DBConector dbConector) {
            return "NINGUNO";
        }

        public bool CategoriaRequiereEquipo(DBConector dbConector) {
            return ObtenerTipoEquipoTicket(dbConector) != "NINGUNO";
        }

        public bool EquipoAsignadoUsuario(DBConector dbConector) {
            if (!IdEquipo.HasValue || IdEquipo.Value <= 0)
                return true;

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null || IdUsuario <= 0)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblEquipoUsuario eu
                INNER JOIN dbo.tblEquipo e ON e.id_Equipo = eu.id_Equipo
                WHERE eu.id_Equipo = @id_Equipo
                  AND eu.id_Usuario = @id_Usuario
                  AND eu.activo = 1
                  AND eu.fechaBaja IS NULL
                  AND e.activo = 1
                  AND ISNULL(e.esCompartido, 0) = 0";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Equipo", System.Data.SqlDbType.Int).Value = IdEquipo.Value;
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = IdUsuario;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        public bool EquipoCompartidoActivo(DBConector dbConector) {
            if (!IdEquipo.HasValue || IdEquipo.Value <= 0)
                return true;

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblEquipo
                WHERE id_Equipo = @id_Equipo
                  AND activo = 1
                  AND ISNULL(esCompartido, 0) = 1";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Equipo", System.Data.SqlDbType.Int).Value = IdEquipo.Value;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        public bool CargarDatosClasificacionTicket(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null || IdTicket <= 0)
                return false;

            string query = @"
                SELECT
                    t.id_TipoSoporte,
                    ISNULL(ts.descripcion, '') AS tipoSoporte,
                    ts.id_Area AS id_AreaAtiende,
                    ISNULL(a.nombre, '') AS areaAtiende
                FROM dbo.tblTicket t
                INNER JOIN dbo.tblTipoSoporte ts ON ts.id_TipoSoporte = t.id_TipoSoporte
                LEFT JOIN dbo.tblArea a ON a.id_Area = ts.id_Area
                WHERE t.id_Ticket = @id_Ticket
                  AND t.activo = 1";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Ticket", System.Data.SqlDbType.Int).Value = IdTicket;
            using SqlDataReader dr = cmd.ExecuteReader();

            if (!dr.Read())
                return false;

            IdTipoSoporte = Convert.ToInt32(dr["id_TipoSoporte"]);
            TipoSoporte = dr["tipoSoporte"].ToString() ?? string.Empty;
            IdAreaAtiende = dr["id_AreaAtiende"] == DBNull.Value ? null : Convert.ToInt32(dr["id_AreaAtiende"]);
            AreaAtiende = dr["areaAtiende"].ToString() ?? string.Empty;

            return true;
        }

        public int ObtenerTecnicoPorArea(DBConector dbConector) {
            if (!IdAreaAtiende.HasValue || IdAreaAtiende.Value <= 0)
                return 0;

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return 0;

            string query = @"
                SELECT TOP 1 id_Usuario
                FROM dbo.tblUsuario
                WHERE id_Area = @id_Area
                  AND esTecnico = 1
                  AND activo = 1
                ORDER BY id_Usuario ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Area", System.Data.SqlDbType.Int).Value = IdAreaAtiende.Value;
            object? result = cmd.ExecuteScalar();

            return result == null || result == DBNull.Value ? 0 : Convert.ToInt32(result);
        }

        public bool TecnicoPerteneceArea(DBConector dbConector) {
            if (!IdTecnico.HasValue || IdTecnico.Value <= 0 || !IdAreaAtiende.HasValue || IdAreaAtiende.Value <= 0)
                return false;

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblUsuario
                WHERE id_Usuario = @id_Usuario
                  AND id_Area = @id_Area
                  AND esTecnico = 1
                  AND activo = 1";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = IdTecnico.Value;
            cmd.Parameters.Add("@id_Area", System.Data.SqlDbType.Int).Value = IdAreaAtiende.Value;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        public bool InsertarTicket(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            using SqlTransaction transaction = cn.BeginTransaction();

            string query = @"
                INSERT INTO dbo.tblTicket
                    (id_Usuario, id_TipoSoporte, id_Tecnico, id_Estatus, id_Equipo, asunto, problema, img, fechaAlta, fechaCierre, activo)
                OUTPUT INSERTED.id_Ticket
                VALUES
                    (@id_Usuario, @id_TipoSoporte, @id_Tecnico, @id_Estatus, @id_Equipo, @asunto, @problema, @img, GETDATE(), @fechaCierre, 1)";

            try {
                using SqlCommand cmd = new SqlCommand(query, cn, transaction);
                AgregarParametrosTicket(cmd);

                object? result = cmd.ExecuteScalar();
                if (result == null || result == DBNull.Value) {
                    transaction.Rollback();
                    return false;
                }

                IdTicket = Convert.ToInt32(result);
                InsertarDetalle(cn, transaction, IdTicket, IdUsuario, Problema, IdEstatus, IdTipoSoporte);
                transaction.Commit();

                return true;
            } catch {
                transaction.Rollback();
                return false;
            }
        }

        public bool InsertarTicketDesarrollo(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            using SqlTransaction transaction = cn.BeginTransaction();

            string query = @"
                INSERT INTO dbo.tblTicket
                    (id_Usuario, id_TipoSoporte, id_Tecnico, id_Estatus, id_Equipo, asunto, problema, img, fechaAlta, fechaCierre, activo)
                OUTPUT INSERTED.id_Ticket
                VALUES
                    (@id_Usuario, 1, @id_Tecnico, 1, NULL, @asunto, @problema, NULL, GETDATE(), NULL, 1)";

            try {
                using SqlCommand cmd = new SqlCommand(query, cn, transaction);
                cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = IdUsuarioSolicita;
                cmd.Parameters.Add("@id_Tecnico", System.Data.SqlDbType.Int).Value = IdTecnico.HasValue && IdTecnico.Value > 0 ? IdTecnico.Value : 1;
                cmd.Parameters.Add("@asunto", System.Data.SqlDbType.VarChar, 200).Value = Asunto.Trim();
                cmd.Parameters.Add("@problema", System.Data.SqlDbType.VarChar, -1).Value = DescripcionTicket.Trim();

                object? result = cmd.ExecuteScalar();
                if (result == null || result == DBNull.Value) {
                    transaction.Rollback();
                    return false;
                }

                IdTicket = Convert.ToInt32(result);
                InsertarDetalle(cn, transaction, IdTicket, IdUsuarioSolicita, DescripcionTicket, 1, 1);
                transaction.Commit();

                return true;
            } catch {
                transaction.Rollback();
                return false;
            }
        }

        public bool AgregarComentario(DBConector dbConector, int idTicket, int idUsuario, string comentario, int idTipoSoporte) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            try {
                int? idEstatus = EsTicketDesarrollo(idTipoSoporte) ? ObtenerEstatusActualTicketDesarrollo(cn, idTicket) : ObtenerEstatusActualTicket(cn, idTicket);
                InsertarDetalle(cn, null, idTicket, idUsuario, comentario, idEstatus, idTipoSoporte);
                return true;
            } catch {
                return false;
            }
        }

        public bool ActualizarGestion(DBConector dbConector, int idUsuarioGestion) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            using SqlTransaction transaction = cn.BeginTransaction();

            string query = @"
                UPDATE t
                SET id_Tecnico = @id_Tecnico,
                    id_Estatus = @id_Estatus,
                    fechaCierre = CASE
                        WHEN LOWER(ISNULL(e.descripcion, '')) = 'cerrado' THEN ISNULL(t.fechaCierre, GETDATE())
                        ELSE NULL
                    END
                FROM dbo.tblTicket t
                LEFT JOIN dbo.tblEstatus e ON e.id_Estatus = @id_Estatus
                WHERE t.id_Ticket = @id_Ticket";

            try {
                using SqlCommand cmd = new SqlCommand(query, cn, transaction);
                cmd.Parameters.Add("@id_Ticket", System.Data.SqlDbType.Int).Value = IdTicket;
                cmd.Parameters.Add("@id_Tecnico", System.Data.SqlDbType.Int).Value = IdTecnico.HasValue && IdTecnico.Value > 0 ? IdTecnico.Value : (object)DBNull.Value;
                cmd.Parameters.Add("@id_Estatus", System.Data.SqlDbType.Int).Value = IdEstatus.HasValue && IdEstatus.Value > 0 ? IdEstatus.Value : (object)DBNull.Value;

                bool actualizado = cmd.ExecuteNonQuery() > 0;
                if (!actualizado) {
                    transaction.Rollback();
                    return false;
                }

                string comentario = $"Gestion actualizada. Estatus: {Estatus}. Tecnico: {Tecnico}.";
                InsertarDetalle(cn, transaction, IdTicket, idUsuarioGestion, comentario, IdEstatus, 2);
                transaction.Commit();

                return true;
            } catch {
                transaction.Rollback();
                return false;
            }
        }

        public bool TicketPuedeCerrarUsuario(DBConector dbConector, int idTicket, int idUsuario) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblTicket t
                INNER JOIN dbo.tblEstatus e ON e.id_Estatus = t.id_Estatus
                WHERE t.id_Ticket = @id_Ticket
                  AND t.id_Usuario = @id_Usuario
                  AND t.activo = 1
                  AND LOWER(e.descripcion) = 'resuelto'";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Ticket", System.Data.SqlDbType.Int).Value = idTicket;
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = idUsuario;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        public bool CerrarTicketConVistoBueno(DBConector dbConector, int idTicket, int idUsuario, int idEstatusCerrado) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            using SqlTransaction transaction = cn.BeginTransaction();

            string query = @"
                UPDATE dbo.tblTicket
                SET id_Estatus = @id_Estatus,
                    fechaCierre = GETDATE()
                WHERE id_Ticket = @id_Ticket
                  AND id_Usuario = @id_Usuario
                  AND activo = 1";

            try {
                using SqlCommand cmd = new SqlCommand(query, cn, transaction);
                cmd.Parameters.Add("@id_Ticket", System.Data.SqlDbType.Int).Value = idTicket;
                cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = idUsuario;
                cmd.Parameters.Add("@id_Estatus", System.Data.SqlDbType.Int).Value = idEstatusCerrado;

                bool actualizado = cmd.ExecuteNonQuery() > 0;
                if (!actualizado) {
                    transaction.Rollback();
                    return false;
                }

                InsertarDetalle(cn, transaction, idTicket, idUsuario, "Ticket cerrado con visto bueno del usuario.", idEstatusCerrado, 2);
                transaction.Commit();

                return true;
            } catch {
                transaction.Rollback();
                return false;
            }
        }

        public bool TicketPerteneceUsuario(DBConector dbConector, int idTicket, int idUsuario) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblTicket
                WHERE id_Ticket = @id_Ticket
                  AND id_Usuario = @id_Usuario
                  AND activo = 1";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Ticket", System.Data.SqlDbType.Int).Value = idTicket;
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = idUsuario;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        public bool UsuarioPuedeVerTicket(DBConector dbConector, int idTicket, int idUsuario, bool puedeVerTodos) {
            return UsuarioPuedeVerTicket(dbConector, idTicket, idUsuario, puedeVerTodos, 2);
        }

        public bool UsuarioPuedeVerTicket(DBConector dbConector, int idTicket, int idUsuario, bool puedeVerTodos, int idTipoSoporte) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblTicket
                WHERE id_Ticket = @id_Ticket
                  AND id_TipoSoporte = @id_TipoSoporte
                  AND activo = 1
                  AND (
                      id_Usuario = @id_Usuario
                      OR id_Tecnico = @id_Usuario
                      OR @puedeVerTodos = 1
                  )";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Ticket", System.Data.SqlDbType.Int).Value = idTicket;
            cmd.Parameters.Add("@id_TipoSoporte", System.Data.SqlDbType.Int).Value = idTipoSoporte;
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = idUsuario;
            cmd.Parameters.Add("@puedeVerTodos", System.Data.SqlDbType.Bit).Value = puedeVerTodos;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        public bool UsuarioPuedeGestionarTicket(DBConector dbConector, int idTicket, int idUsuario, bool puedeGestionarTodos) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblTicket
                WHERE id_Ticket = @id_Ticket
                  AND activo = 1
                  AND (
                      id_Tecnico = @id_Usuario
                      OR @puedeGestionarTodos = 1
                  )";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Ticket", System.Data.SqlDbType.Int).Value = idTicket;
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = idUsuario;
            cmd.Parameters.Add("@puedeGestionarTodos", System.Data.SqlDbType.Bit).Value = puedeGestionarTodos;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        public bool TicketExiste(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblTicket
                WHERE id_Ticket = @id_Ticket
                  AND activo = 1";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Ticket", System.Data.SqlDbType.Int).Value = IdTicket;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        public bool TicketEstaCerrado(DBConector dbConector, int idTicket) {
            return TicketEstaCerrado(dbConector, idTicket, 2);
        }

        public bool TicketEstaCerrado(DBConector dbConector, int idTicket, int idTipoSoporte) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblTicket t
                INNER JOIN dbo.tblEstatus e ON e.id_Estatus = t.id_Estatus
                WHERE t.id_Ticket = @id_Ticket
                  AND t.id_TipoSoporte = @id_TipoSoporte
                  AND t.activo = 1
                  AND e.activo = 1
                  AND e.id_Modulos = 1
                  AND LOWER(e.descripcion) = 'cerrado'";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Ticket", System.Data.SqlDbType.Int).Value = idTicket;
            cmd.Parameters.Add("@id_TipoSoporte", System.Data.SqlDbType.Int).Value = idTipoSoporte;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        public int ObtenerIdEstatusActual(DBConector dbConector, int idTicket) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return 0;

            string query = @"
                SELECT ISNULL(id_Estatus, 0)
                FROM dbo.tblTicket
                WHERE id_Ticket = @id_Ticket
                  AND activo = 1";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Ticket", System.Data.SqlDbType.Int).Value = idTicket;
            object? result = cmd.ExecuteScalar();

            return result == null || result == DBNull.Value ? 0 : Convert.ToInt32(result);
        }

        public bool InsertarEvidencia(DBConector dbConector, ticketEvidenciaModel evidencia) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                INSERT INTO dbo.tblTicketEvidencia
                    (id_Ticket, nombreArchivo, rutaArchivo, extension, tipoContenido, tamanioBytes, id_UsuarioAlta, fechaAlta, activo)
                VALUES
                    (@id_Ticket, @nombreArchivo, @rutaArchivo, @extension, @tipoContenido, @tamanioBytes, @id_UsuarioAlta, GETDATE(), 1)";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Ticket", System.Data.SqlDbType.Int).Value = evidencia.IdTicket;
            cmd.Parameters.Add("@nombreArchivo", System.Data.SqlDbType.VarChar, 255).Value = evidencia.NombreArchivo;
            cmd.Parameters.Add("@rutaArchivo", System.Data.SqlDbType.VarChar, 500).Value = evidencia.RutaArchivo;
            cmd.Parameters.Add("@extension", System.Data.SqlDbType.VarChar, 20).Value = string.IsNullOrWhiteSpace(evidencia.Extension) ? (object)DBNull.Value : evidencia.Extension;
            cmd.Parameters.Add("@tipoContenido", System.Data.SqlDbType.VarChar, 100).Value = string.IsNullOrWhiteSpace(evidencia.TipoContenido) ? (object)DBNull.Value : evidencia.TipoContenido;
            cmd.Parameters.Add("@tamanioBytes", System.Data.SqlDbType.BigInt).Value = evidencia.TamanioBytes.HasValue ? evidencia.TamanioBytes.Value : (object)DBNull.Value;
            cmd.Parameters.Add("@id_UsuarioAlta", System.Data.SqlDbType.Int).Value = evidencia.IdUsuarioAlta;

            return cmd.ExecuteNonQuery() > 0;
        }

        public ticketEvidenciaModel? ObtenerEvidencia(DBConector dbConector, int idEvidencia) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return null;

            string query = @"
                SELECT
                    te.id_TicketEvidencia,
                    te.id_Ticket,
                    t.id_TipoSoporte,
                    te.nombreArchivo,
                    te.rutaArchivo,
                    te.extension,
                    te.tipoContenido,
                    te.tamanioBytes,
                    te.id_UsuarioAlta,
                    te.fechaAlta,
                    te.activo
                FROM dbo.tblTicketEvidencia te
                INNER JOIN dbo.tblTicket t ON t.id_Ticket = te.id_Ticket
                WHERE te.id_TicketEvidencia = @id_TicketEvidencia
                  AND te.activo = 1";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_TicketEvidencia", System.Data.SqlDbType.Int).Value = idEvidencia;
            using SqlDataReader dr = cmd.ExecuteReader();

            if (!dr.Read())
                return null;

            return new ticketEvidenciaModel {
                IdTicketEvidencia = Convert.ToInt32(dr["id_TicketEvidencia"]),
                IdTicket = Convert.ToInt32(dr["id_Ticket"]),
                IdTipoSoporte = Convert.ToInt32(dr["id_TipoSoporte"]),
                NombreArchivo = dr["nombreArchivo"].ToString() ?? string.Empty,
                RutaArchivo = dr["rutaArchivo"].ToString() ?? string.Empty,
                Extension = dr["extension"].ToString() ?? string.Empty,
                TipoContenido = dr["tipoContenido"].ToString() ?? string.Empty,
                TamanioBytes = dr["tamanioBytes"] == DBNull.Value ? null : Convert.ToInt64(dr["tamanioBytes"]),
                IdUsuarioAlta = Convert.ToInt32(dr["id_UsuarioAlta"]),
                FechaAlta = Convert.ToDateTime(dr["fechaAlta"]),
                Activo = Convert.ToBoolean(dr["activo"])
            };
        }

        public string ObtenerValorConfiguracion(DBConector dbConector, string clave) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return string.Empty;

            string query = @"
                SELECT TOP 1 valor
                FROM dbo.tblConfiguracionSistema
                WHERE clave = @clave
                  AND activo = 1
                ORDER BY id_ConfiguracionSistema DESC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@clave", System.Data.SqlDbType.VarChar, 100).Value = clave;
            object? result = cmd.ExecuteScalar();

            return result?.ToString() ?? string.Empty;
        }

        public string ObtenerNombreTipoSoporte(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return string.Empty;

            string query = @"
                SELECT descripcion
                FROM dbo.tblTipoSoporte
                WHERE id_TipoSoporte = @id_TipoSoporte";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_TipoSoporte", System.Data.SqlDbType.Int).Value = IdTipoSoporte;
            object? result = cmd.ExecuteScalar();

            return result?.ToString() ?? string.Empty;
        }

        public string ObtenerNombreCategoriaSoporte(DBConector dbConector) {
            return string.Empty;
        }

        public string ObtenerNombreEstatus(DBConector dbConector) {
            if (!IdEstatus.HasValue || IdEstatus.Value <= 0)
                return string.Empty;

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return string.Empty;

            string query = @"
                SELECT descripcion
                FROM dbo.tblEstatus
                WHERE id_Estatus = @id_Estatus";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Estatus", System.Data.SqlDbType.Int).Value = IdEstatus.Value;
            object? result = cmd.ExecuteScalar();

            return result?.ToString() ?? string.Empty;
        }

        public string ObtenerNombreTecnico(DBConector dbConector) {
            if (!IdTecnico.HasValue || IdTecnico.Value <= 0)
                return "Sin tecnico";

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return string.Empty;

            string query = @"
                SELECT LTRIM(RTRIM(ISNULL(nombre, '') + ' ' + ISNULL(app, '') + ' ' + ISNULL(apm, '')))
                FROM dbo.tblUsuario
                WHERE id_Usuario = @id_Usuario";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = IdTecnico.Value;
            object? result = cmd.ExecuteScalar();

            return result?.ToString() ?? string.Empty;
        }

        private List<ticketsModel> ObtenerCatalogo(DBConector dbConector, string query, string tipo) {
            List<ticketsModel> lista = new List<ticketsModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                ticketsModel item = new ticketsModel();
                int id = Convert.ToInt32(dr["id"]);
                string nombre = dr["nombre"].ToString() ?? string.Empty;

                if (tipo == "tipoSoporte") {
                    item.IdTipoSoporte = id;
                    item.TipoSoporte = nombre;
                } else if (tipo == "area") {
                    item.IdAreaAtiende = id;
                    item.AreaAtiende = nombre;
                } else if (tipo == "equipo") {
                    item.IdEquipo = id;
                    item.Equipo = nombre;
                } else {
                    item.IdEstatus = id;
                    item.Estatus = nombre;
                }

                lista.Add(item);
            }

            return lista;
        }

        private void CargarDetallesTickets(SqlConnection cn, List<ticketsModel> tickets) {
            if (tickets.Count == 0)
                return;

            string query = @"
                SELECT
                    td.id_TicketComentario,
                    td.id_Ticket,
                    td.comentario,
                    td.fechaComentario,
                    td.id_Usuario,
                    LTRIM(RTRIM(ISNULL(u.nombre, '') + ' ' + ISNULL(u.app, '') + ' ' + ISNULL(u.apm, ''))) AS usuario,
                    td.id_Estatus,
                    ISNULL(e.descripcion, '') AS estatus,
                    td.fechaAlta,
                    td.activo
                FROM dbo.tblTicketComentarios td
                INNER JOIN dbo.tblUsuario u ON u.id_Usuario = td.id_Usuario
                LEFT JOIN dbo.tblEstatus e ON e.id_Estatus = td.id_Estatus
                WHERE td.activo = 1
                ORDER BY td.fechaComentario ASC, td.id_TicketComentario ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            Dictionary<string, ticketsModel> ticketsPorLlave = tickets
                .GroupBy(ticket => ticket.IdTicketOriginal.ToString())
                .ToDictionary(grupo => grupo.Key, grupo => grupo.First());

            while (dr.Read()) {
                int idTicket = Convert.ToInt32(dr["id_Ticket"]);

                if (!ticketsPorLlave.TryGetValue(idTicket.ToString(), out ticketsModel? ticket))
                    continue;

                ticket.Detalles.Add(new ticketDetalleModel {
                    IdTicketComentario = Convert.ToInt32(dr["id_TicketComentario"]),
                    IdTicket = idTicket,
                    TipoTicket = ticket.TipoTicket,
                    Comentario = dr["comentario"].ToString() ?? string.Empty,
                    FechaComentario = Convert.ToDateTime(dr["fechaComentario"]),
                    IdUsuario = Convert.ToInt32(dr["id_Usuario"]),
                    Usuario = dr["usuario"].ToString() ?? string.Empty,
                    IdEstatus = dr["id_Estatus"] == DBNull.Value ? null : Convert.ToInt32(dr["id_Estatus"]),
                    Estatus = dr["estatus"].ToString() ?? string.Empty,
                    FechaAlta = Convert.ToDateTime(dr["fechaAlta"]),
                    Activo = Convert.ToBoolean(dr["activo"])
                });
            }
        }

        private void CargarEvidenciasTickets(SqlConnection cn, List<ticketsModel> tickets) {
            if (tickets.Count == 0)
                return;

            string query = @"
                SELECT
                    te.id_TicketEvidencia,
                    te.id_Ticket,
                    te.nombreArchivo,
                    te.rutaArchivo,
                    te.extension,
                    te.tipoContenido,
                    te.tamanioBytes,
                    te.id_UsuarioAlta,
                    LTRIM(RTRIM(ISNULL(u.nombre, '') + ' ' + ISNULL(u.app, '') + ' ' + ISNULL(u.apm, ''))) AS usuarioAlta,
                    te.fechaAlta,
                    te.activo
                FROM dbo.tblTicketEvidencia te
                INNER JOIN dbo.tblUsuario u ON u.id_Usuario = te.id_UsuarioAlta
                WHERE te.activo = 1
                ORDER BY te.fechaAlta ASC, te.id_TicketEvidencia ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            Dictionary<string, ticketsModel> ticketsPorLlave = tickets
                .GroupBy(ticket => ticket.IdTicketOriginal.ToString())
                .ToDictionary(grupo => grupo.Key, grupo => grupo.First());

            while (dr.Read()) {
                int idTicket = Convert.ToInt32(dr["id_Ticket"]);

                if (!ticketsPorLlave.TryGetValue(idTicket.ToString(), out ticketsModel? ticket))
                    continue;

                ticket.Evidencias.Add(new ticketEvidenciaModel {
                    IdTicketEvidencia = Convert.ToInt32(dr["id_TicketEvidencia"]),
                    IdTicket = idTicket,
                    IdTipoSoporte = ticket.IdTipoSoporte,
                    NombreArchivo = dr["nombreArchivo"].ToString() ?? string.Empty,
                    RutaArchivo = dr["rutaArchivo"].ToString() ?? string.Empty,
                    Extension = dr["extension"].ToString() ?? string.Empty,
                    TipoContenido = dr["tipoContenido"].ToString() ?? string.Empty,
                    TamanioBytes = dr["tamanioBytes"] == DBNull.Value ? null : Convert.ToInt64(dr["tamanioBytes"]),
                    IdUsuarioAlta = Convert.ToInt32(dr["id_UsuarioAlta"]),
                    UsuarioAlta = dr["usuarioAlta"].ToString() ?? string.Empty,
                    FechaAlta = Convert.ToDateTime(dr["fechaAlta"]),
                    Activo = Convert.ToBoolean(dr["activo"])
                });
            }
        }

        private void AgregarParametrosTicket(SqlCommand cmd) {
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = IdUsuario;
            cmd.Parameters.Add("@id_TipoSoporte", System.Data.SqlDbType.Int).Value = IdTipoSoporte;
            cmd.Parameters.Add("@id_Tecnico", System.Data.SqlDbType.Int).Value = IdTecnico.HasValue && IdTecnico.Value > 0 ? IdTecnico.Value : (object)DBNull.Value;
            cmd.Parameters.Add("@id_Estatus", System.Data.SqlDbType.Int).Value = IdEstatus.HasValue && IdEstatus.Value > 0 ? IdEstatus.Value : (object)DBNull.Value;
            cmd.Parameters.Add("@id_Equipo", System.Data.SqlDbType.Int).Value = IdEquipo.HasValue && IdEquipo.Value > 0 ? IdEquipo.Value : (object)DBNull.Value;
            cmd.Parameters.Add("@asunto", System.Data.SqlDbType.VarChar, 200).Value = Asunto.Trim();
            cmd.Parameters.Add("@problema", System.Data.SqlDbType.VarChar, -1).Value = Problema.Trim();
            cmd.Parameters.Add("@img", System.Data.SqlDbType.VarChar, 150).Value = string.IsNullOrWhiteSpace(Img) ? (object)DBNull.Value : Img.Trim();
            cmd.Parameters.Add("@fechaCierre", System.Data.SqlDbType.DateTime).Value = DBNull.Value;
        }

        private void InsertarDetalle(SqlConnection cn, SqlTransaction? transaction, int idTicket, int idUsuario, string comentario, int? idEstatus, int idTipoSoporte) {
            string query = @"
                INSERT INTO dbo.tblTicketComentarios
                    (id_Ticket, comentario, fechaComentario, id_Usuario, id_Estatus, fechaAlta, activo)
                VALUES
                    (@id_Ticket, @comentario, GETDATE(), @id_Usuario, @id_Estatus, GETDATE(), 1)";

            using SqlCommand cmd = transaction == null ? new SqlCommand(query, cn) : new SqlCommand(query, cn, transaction);
            cmd.Parameters.Add("@id_Ticket", System.Data.SqlDbType.Int).Value = idTicket;
            cmd.Parameters.Add("@comentario", System.Data.SqlDbType.VarChar, -1).Value = comentario.Trim();
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = idUsuario;
            cmd.Parameters.Add("@id_Estatus", System.Data.SqlDbType.Int).Value = idEstatus.HasValue && idEstatus.Value > 0 ? idEstatus.Value : (object)DBNull.Value;
            cmd.ExecuteNonQuery();
        }

        private int? ObtenerEstatusActualTicket(SqlConnection cn, int idTicket) {
            string query = @"
                SELECT id_Estatus
                FROM dbo.tblTicket
                WHERE id_Ticket = @id_Ticket
                  AND activo = 1";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Ticket", System.Data.SqlDbType.Int).Value = idTicket;
            object? result = cmd.ExecuteScalar();

            return result == null || result == DBNull.Value ? null : Convert.ToInt32(result);
        }

        private int? ObtenerEstatusActualTicketDesarrollo(SqlConnection cn, int idTicket) {
            string query = @"
                SELECT id_Estatus
                FROM dbo.tblTicket
                WHERE id_Ticket = @id_Ticket
                  AND id_TipoSoporte = 1
                  AND activo = 1";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Ticket", System.Data.SqlDbType.Int).Value = idTicket;
            object? result = cmd.ExecuteScalar();

            return result == null || result == DBNull.Value ? null : Convert.ToInt32(result);
        }

        private static bool EsTicketDesarrollo(int idTipoSoporte) {
            return idTipoSoporte == 1;
        }

        private static string CrearLlaveTicket(int idTicket, int idTipoSoporte) {
            return $"{idTicket}|{idTipoSoporte}";
        }

        private static int ObtenerValorInt(SqlDataReader dr, string columna, int valorDefault) {
            if (!TieneColumna(dr, columna) || dr[columna] == DBNull.Value)
                return valorDefault;

            return Convert.ToInt32(dr[columna]);
        }

        private static bool TieneColumna(SqlDataReader dr, string columna) {
            for (int index = 0; index < dr.FieldCount; index++) {
                if (string.Equals(dr.GetName(index), columna, StringComparison.OrdinalIgnoreCase))
                    return true;
            }

            return false;
        }
    }

    public class ticketDetalleModel {
        public int IdTicketComentario { get; set; }
        public int IdTicket { get; set; }
        public int TipoTicket { get; set; } = 1;
        public string Comentario { get; set; } = string.Empty;
        public DateTime FechaComentario { get; set; }
        public int IdUsuario { get; set; }
        public string Usuario { get; set; } = string.Empty;
        public int? IdEstatus { get; set; }
        public string Estatus { get; set; } = string.Empty;
        public DateTime FechaAlta { get; set; }
        public bool Activo { get; set; }
    }

    public class ticketEvidenciaModel {
        public int IdTicketEvidencia { get; set; }
        public int IdTicket { get; set; }
        public int IdTipoSoporte { get; set; } = 2;
        public string NombreArchivo { get; set; } = string.Empty;
        public string RutaArchivo { get; set; } = string.Empty;
        public string Extension { get; set; } = string.Empty;
        public string TipoContenido { get; set; } = string.Empty;
        public long? TamanioBytes { get; set; }
        public int IdUsuarioAlta { get; set; }
        public string UsuarioAlta { get; set; } = string.Empty;
        public DateTime FechaAlta { get; set; }
        public bool Activo { get; set; }
    }
}
