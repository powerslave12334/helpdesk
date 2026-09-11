using HELPDESK.Services;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Models.Inventario.Equipo {
    public class equipoModel {
        public int IdEquipo { get; set; }
        public string Marca { get; set; } = string.Empty;
        public string Modelo { get; set; } = string.Empty;
        public int IdTipoEquipo { get; set; }
        public string NombreTipoEquipo { get; set; } = string.Empty;
        public int IdEstatus { get; set; }
        public string NombreEstatus { get; set; } = string.Empty;
        public DateTime? FechaAdquisicion { get; set; }
        public string NumeroSerie { get; set; } = string.Empty;
        public string NombreEquipo { get; set; } = string.Empty;
        public string Ubicacion { get; set; } = string.Empty;
        public string Observaciones { get; set; } = string.Empty;
        public int? IdOficinas { get; set; }
        public string NombreOficina { get; set; } = string.Empty;
        public int IdPeriodoMantenimiento { get; set; }
        public bool EsCompartido { get; set; }
        public DateTime FechaAlta { get; set; }
        public bool Activo { get; set; }
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<equipoModel> Equipos { get; set; } = new List<equipoModel>();
        public List<equipoModel> TiposEquipo { get; set; } = new List<equipoModel>();
        public List<equipoModel> Estatus { get; set; } = new List<equipoModel>();
        public List<equipoModel> Oficinas { get; set; } = new List<equipoModel>();
        public List<equipoDetalleCampoModel> CamposPorTipo { get; set; } = new List<equipoDetalleCampoModel>();
        public List<equipoDetalleCampoModel> Detalles { get; set; } = new List<equipoDetalleCampoModel>();
        public List<equipoRelacionResumenModel> Relaciones { get; set; } = new List<equipoRelacionResumenModel>();

        public List<equipoModel> ObtenerEquipos(DBConector dbConector) {
            List<equipoModel> lista = new List<equipoModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT
                    e.id_Equipo,
                    e.marca,
                    e.modelo,
                    e.id_TipoEquipo,
                    ISNULL(te.descripcion, '') AS tipoEquipo,
                    e.id_Estatus,
                    ISNULL(es.descripcion, '') AS estatus,
                    e.fechaAdquisicion,
                    e.numeroSerie,
                    e.nombreEquipo,
                    e.ubicacion,
                    e.observaciones,
                    e.id_Oficinas,
                    ISNULL(o.nombre + ' - ' + o.descripcion, '') AS oficina,
                    e.id_PeriodoMantenimiento,
                    ISNULL(e.esCompartido, 0) AS esCompartido,
                    e.fechaAlta,
                    e.activo
                FROM dbo.tblEquipo e
                LEFT JOIN dbo.tblTipoEquipo te ON te.id_TipoEquipo = e.id_TipoEquipo
                LEFT JOIN dbo.tblEstatus es ON es.id_Estatus = e.id_Estatus
                LEFT JOIN dbo.tblOficinas o ON o.id_Oficinas = e.id_Oficinas
                ORDER BY e.activo DESC, e.marca ASC, e.modelo ASC, e.nombreEquipo ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new equipoModel {
                    IdEquipo = Convert.ToInt32(dr["id_Equipo"]),
                    Marca = dr["marca"].ToString() ?? string.Empty,
                    Modelo = dr["modelo"].ToString() ?? string.Empty,
                    IdTipoEquipo = Convert.ToInt32(dr["id_TipoEquipo"]),
                    NombreTipoEquipo = dr["tipoEquipo"].ToString() ?? string.Empty,
                    IdEstatus = Convert.ToInt32(dr["id_Estatus"]),
                    NombreEstatus = dr["estatus"].ToString() ?? string.Empty,
                    FechaAdquisicion = dr["fechaAdquisicion"] == DBNull.Value ? null : Convert.ToDateTime(dr["fechaAdquisicion"]),
                    NumeroSerie = dr["numeroSerie"].ToString() ?? string.Empty,
                    NombreEquipo = dr["nombreEquipo"].ToString() ?? string.Empty,
                    Ubicacion = dr["ubicacion"].ToString() ?? string.Empty,
                    Observaciones = dr["observaciones"].ToString() ?? string.Empty,
                    IdOficinas = dr["id_Oficinas"] == DBNull.Value ? null : Convert.ToInt32(dr["id_Oficinas"]),
                    NombreOficina = dr["oficina"].ToString() ?? string.Empty,
                    IdPeriodoMantenimiento = Convert.ToInt32(dr["id_PeriodoMantenimiento"]),
                    EsCompartido = Convert.ToBoolean(dr["esCompartido"]),
                    FechaAlta = Convert.ToDateTime(dr["fechaAlta"]),
                    Activo = Convert.ToBoolean(dr["activo"])
                });
            }

            CargarDetallesEquipos(cn, lista);
            CargarRelacionesEquipos(cn, lista);

            return lista;
        }

        public List<equipoModel> ObtenerTiposEquipoActivos(DBConector dbConector) {
            List<equipoModel> lista = new List<equipoModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_TipoEquipo, descripcion
                FROM dbo.tblTipoEquipo
                WHERE activo = 1
                ORDER BY descripcion ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new equipoModel {
                    IdTipoEquipo = Convert.ToInt32(dr["id_TipoEquipo"]),
                    NombreTipoEquipo = dr["descripcion"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public List<equipoModel> ObtenerEstatusActivos(DBConector dbConector) {
            List<equipoModel> lista = new List<equipoModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_Estatus, descripcion
                FROM dbo.tblEstatus
                WHERE activo = 1
                  AND id_Modulos = 2
                ORDER BY descripcion ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new equipoModel {
                    IdEstatus = Convert.ToInt32(dr["id_Estatus"]),
                    NombreEstatus = dr["descripcion"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public List<equipoModel> ObtenerOficinasActivas(DBConector dbConector) {
            List<equipoModel> lista = new List<equipoModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_Oficinas, nombre, descripcion
                FROM dbo.tblOficinas
                WHERE activo = 1
                ORDER BY nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new equipoModel {
                    IdOficinas = Convert.ToInt32(dr["id_Oficinas"]),
                    NombreOficina = $"{dr["nombre"]} - {dr["descripcion"]}"
                });
            }

            return lista;
        }

        public List<equipoDetalleCampoModel> ObtenerCamposPorTipo(DBConector dbConector, int? idTipoEquipo = null) {
            List<equipoDetalleCampoModel> lista = new List<equipoDetalleCampoModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT
                    tec.id_TipoEquipo,
                    tec.id_EquipoCampo,
                    ec.nombre AS campo,
                    tec.requerido
                FROM dbo.tblTipoEquipoCampo tec
                INNER JOIN dbo.tblEquipoCampo ec ON ec.id_EquipoCampo = tec.id_EquipoCampo
                WHERE tec.activo = 1
                  AND ec.activo = 1
                  AND (@id_TipoEquipo IS NULL OR tec.id_TipoEquipo = @id_TipoEquipo)
                ORDER BY tec.id_TipoEquipo ASC, ec.nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_TipoEquipo", System.Data.SqlDbType.Int).Value =
                idTipoEquipo.HasValue && idTipoEquipo.Value > 0 ? idTipoEquipo.Value : (object)DBNull.Value;

            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new equipoDetalleCampoModel {
                    IdTipoEquipo = Convert.ToInt32(dr["id_TipoEquipo"]),
                    IdEquipoCampo = Convert.ToInt32(dr["id_EquipoCampo"]),
                    NombreCampo = dr["campo"].ToString() ?? string.Empty,
                    Requerido = Convert.ToBoolean(dr["requerido"])
                });
            }

            return lista;
        }

        public bool InsertarEquipo(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            using SqlTransaction transaction = cn.BeginTransaction();

            string query = @"
                INSERT INTO dbo.tblEquipo
                    (marca, modelo, id_TipoEquipo, id_Estatus, fechaAdquisicion, numeroSerie, nombreEquipo, ubicacion, observaciones, id_Oficinas, id_PeriodoMantenimiento, esCompartido, fechaAlta, activo)
                OUTPUT INSERTED.id_Equipo
                VALUES
                    (@marca, @modelo, @id_TipoEquipo, @id_Estatus, @fechaAdquisicion, @numeroSerie, @nombreEquipo, @ubicacion, @observaciones, @id_Oficinas, @id_PeriodoMantenimiento, @esCompartido, GETDATE(), 1)";

            try {
                using SqlCommand cmd = new SqlCommand(query, cn, transaction);
                AgregarParametros(cmd);

                object? result = cmd.ExecuteScalar();
                if (result == null) {
                    transaction.Rollback();
                    return false;
                }

                IdEquipo = Convert.ToInt32(result);
                GuardarDetalles(cn, transaction);
                transaction.Commit();

                return true;
            } catch {
                transaction.Rollback();
                return false;
            }
        }

        public bool ActualizarEquipo(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            using SqlTransaction transaction = cn.BeginTransaction();

            string query = @"
                UPDATE dbo.tblEquipo
                SET marca = @marca,
                    modelo = @modelo,
                    id_TipoEquipo = @id_TipoEquipo,
                    id_Estatus = @id_Estatus,
                    fechaAdquisicion = @fechaAdquisicion,
                    numeroSerie = @numeroSerie,
                    nombreEquipo = @nombreEquipo,
                    ubicacion = @ubicacion,
                    observaciones = @observaciones,
                    id_Oficinas = @id_Oficinas,
                    id_PeriodoMantenimiento = @id_PeriodoMantenimiento,
                    esCompartido = @esCompartido
                WHERE id_Equipo = @id_Equipo";

            try {
                using SqlCommand cmd = new SqlCommand(query, cn, transaction);
                cmd.Parameters.Add("@id_Equipo", System.Data.SqlDbType.Int).Value = IdEquipo;
                AgregarParametros(cmd);

                bool actualizado = cmd.ExecuteNonQuery() > 0;
                if (!actualizado) {
                    transaction.Rollback();
                    return false;
                }

                DesactivarDetallesTipo(cn, transaction);
                GuardarDetalles(cn, transaction);
                transaction.Commit();

                return true;
            } catch {
                transaction.Rollback();
                return false;
            }
        }

        public bool CambiarEstatus(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblEquipo
                SET activo = CASE WHEN activo = 1 THEN 0 ELSE 1 END
                WHERE id_Equipo = @id_Equipo";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Equipo", System.Data.SqlDbType.Int).Value = IdEquipo;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ExisteNumeroSerie(DBConector dbConector) {
            if (string.IsNullOrWhiteSpace(NumeroSerie))
                return false;

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblEquipo
                WHERE LOWER(numeroSerie) = LOWER(@numeroSerie)
                  AND id_Equipo <> @id_Equipo";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@numeroSerie", System.Data.SqlDbType.VarChar, 150).Value = NumeroSerie.Trim();
            cmd.Parameters.Add("@id_Equipo", System.Data.SqlDbType.Int).Value = IdEquipo;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        private void AgregarParametros(SqlCommand cmd) {
            cmd.Parameters.Add("@marca", System.Data.SqlDbType.VarChar, 150).Value = Marca.Trim();
            cmd.Parameters.Add("@modelo", System.Data.SqlDbType.VarChar, 150).Value = Modelo.Trim();
            cmd.Parameters.Add("@id_TipoEquipo", System.Data.SqlDbType.Int).Value = IdTipoEquipo;
            cmd.Parameters.Add("@id_Estatus", System.Data.SqlDbType.Int).Value = IdEstatus;
            cmd.Parameters.Add("@fechaAdquisicion", System.Data.SqlDbType.DateTime).Value = FechaAdquisicion.HasValue ? FechaAdquisicion.Value : (object)DBNull.Value;
            cmd.Parameters.Add("@numeroSerie", System.Data.SqlDbType.VarChar, 150).Value = string.IsNullOrWhiteSpace(NumeroSerie) ? (object)DBNull.Value : NumeroSerie.Trim();
            cmd.Parameters.Add("@nombreEquipo", System.Data.SqlDbType.VarChar, 150).Value = string.IsNullOrWhiteSpace(NombreEquipo) ? (object)DBNull.Value : NombreEquipo.Trim();
            cmd.Parameters.Add("@ubicacion", System.Data.SqlDbType.VarChar, 150).Value = string.IsNullOrWhiteSpace(Ubicacion) ? (object)DBNull.Value : Ubicacion.Trim();
            cmd.Parameters.Add("@observaciones", System.Data.SqlDbType.VarChar, -1).Value = string.IsNullOrWhiteSpace(Observaciones) ? (object)DBNull.Value : Observaciones.Trim();
            cmd.Parameters.Add("@id_Oficinas", System.Data.SqlDbType.Int).Value = IdOficinas.HasValue && IdOficinas.Value > 0 ? IdOficinas.Value : (object)DBNull.Value;
            cmd.Parameters.Add("@id_PeriodoMantenimiento", System.Data.SqlDbType.Int).Value = IdPeriodoMantenimiento;
            cmd.Parameters.Add("@esCompartido", System.Data.SqlDbType.Bit).Value = EsCompartido;
        }

        private void CargarDetallesEquipos(SqlConnection cn, List<equipoModel> equipos) {
            if (equipos.Count == 0)
                return;

            string query = @"
                SELECT
                    ed.id_Equipo,
                    ed.id_EquipoCampo,
                    ec.nombre AS campo,
                    ed.valor
                FROM dbo.tblEquipoDetalle ed
                INNER JOIN dbo.tblEquipoCampo ec ON ec.id_EquipoCampo = ed.id_EquipoCampo
                WHERE ed.activo = 1
                ORDER BY ec.nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            Dictionary<int, equipoModel> equiposPorId = equipos.ToDictionary(e => e.IdEquipo);

            while (dr.Read()) {
                int idEquipo = Convert.ToInt32(dr["id_Equipo"]);
                if (!equiposPorId.TryGetValue(idEquipo, out equipoModel? equipo))
                    continue;

                equipo.Detalles.Add(new equipoDetalleCampoModel {
                    IdEquipo = idEquipo,
                    IdEquipoCampo = Convert.ToInt32(dr["id_EquipoCampo"]),
                    NombreCampo = dr["campo"].ToString() ?? string.Empty,
                    Valor = dr["valor"].ToString() ?? string.Empty
                });
            }
        }

        private void CargarRelacionesEquipos(SqlConnection cn, List<equipoModel> equipos) {
            if (equipos.Count == 0)
                return;

            string query = @"
                SELECT
                    er.id_EquipoPadre,
                    er.id_EquipoHijo,
                    tr.descripcion AS tipoRelacion,
                    LTRIM(RTRIM(ISNULL(ep.marca, '') + ' ' + ISNULL(ep.modelo, '') + ' ' + ISNULL(ep.nombreEquipo, '') + ' ' + ISNULL(ep.numeroSerie, ''))) AS equipoPadre,
                    LTRIM(RTRIM(ISNULL(eh.marca, '') + ' ' + ISNULL(eh.modelo, '') + ' ' + ISNULL(eh.nombreEquipo, '') + ' ' + ISNULL(eh.numeroSerie, ''))) AS equipoHijo,
                    er.fechaAsignacion,
                    er.fechaBaja
                FROM dbo.tblEquipoRelacion er
                INNER JOIN dbo.tblEquipo ep ON ep.id_Equipo = er.id_EquipoPadre
                INNER JOIN dbo.tblEquipo eh ON eh.id_Equipo = er.id_EquipoHijo
                INNER JOIN dbo.tblTipoRelacionEquipo tr ON tr.id_TipoRelacionEquipo = er.id_TipoRelacionEquipo
                WHERE er.activo = 1
                  AND er.fechaBaja IS NULL
                ORDER BY tr.descripcion ASC, equipoPadre ASC, equipoHijo ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            Dictionary<int, equipoModel> equiposPorId = equipos.ToDictionary(e => e.IdEquipo);

            while (dr.Read()) {
                int idPadre = Convert.ToInt32(dr["id_EquipoPadre"]);
                int idHijo = Convert.ToInt32(dr["id_EquipoHijo"]);
                string tipoRelacion = dr["tipoRelacion"].ToString() ?? string.Empty;
                string equipoPadre = dr["equipoPadre"].ToString() ?? string.Empty;
                string equipoHijo = dr["equipoHijo"].ToString() ?? string.Empty;
                DateTime fechaAsignacion = Convert.ToDateTime(dr["fechaAsignacion"]);
                DateTime? fechaBaja = dr["fechaBaja"] == DBNull.Value ? null : Convert.ToDateTime(dr["fechaBaja"]);

                if (equiposPorId.TryGetValue(idPadre, out equipoModel? padre)) {
                    padre.Relaciones.Add(new equipoRelacionResumenModel {
                        Direccion = "Padre",
                        TipoRelacion = tipoRelacion,
                        EquipoRelacionado = equipoHijo,
                        FechaAsignacion = fechaAsignacion,
                        FechaBaja = fechaBaja
                    });
                }

                if (equiposPorId.TryGetValue(idHijo, out equipoModel? hijo)) {
                    hijo.Relaciones.Add(new equipoRelacionResumenModel {
                        Direccion = "Hijo",
                        TipoRelacion = tipoRelacion,
                        EquipoRelacionado = equipoPadre,
                        FechaAsignacion = fechaAsignacion,
                        FechaBaja = fechaBaja
                    });
                }
            }
        }

        private void DesactivarDetallesTipo(SqlConnection cn, SqlTransaction transaction) {
            string query = @"
                UPDATE dbo.tblEquipoDetalle
                SET activo = 0
                WHERE id_Equipo = @id_Equipo";

            using SqlCommand cmd = new SqlCommand(query, cn, transaction);
            cmd.Parameters.Add("@id_Equipo", System.Data.SqlDbType.Int).Value = IdEquipo;
            cmd.ExecuteNonQuery();
        }

        private void GuardarDetalles(SqlConnection cn, SqlTransaction transaction) {
            foreach (equipoDetalleCampoModel detalle in Detalles.Where(d => d.IdEquipoCampo > 0 && !string.IsNullOrWhiteSpace(d.Valor))) {
                string query = @"
                    IF EXISTS (
                        SELECT 1
                        FROM dbo.tblEquipoDetalle
                        WHERE id_Equipo = @id_Equipo
                          AND id_EquipoCampo = @id_EquipoCampo
                    )
                    BEGIN
                        UPDATE dbo.tblEquipoDetalle
                        SET valor = @valor,
                            activo = 1
                        WHERE id_Equipo = @id_Equipo
                          AND id_EquipoCampo = @id_EquipoCampo
                    END
                    ELSE
                    BEGIN
                        INSERT INTO dbo.tblEquipoDetalle
                            (id_Equipo, id_EquipoCampo, valor, fechaAlta, activo)
                        VALUES
                            (@id_Equipo, @id_EquipoCampo, @valor, GETDATE(), 1)
                    END";

                using SqlCommand cmd = new SqlCommand(query, cn, transaction);
                cmd.Parameters.Add("@id_Equipo", System.Data.SqlDbType.Int).Value = IdEquipo;
                cmd.Parameters.Add("@id_EquipoCampo", System.Data.SqlDbType.Int).Value = detalle.IdEquipoCampo;
                cmd.Parameters.Add("@valor", System.Data.SqlDbType.VarChar, 250).Value = detalle.Valor.Trim();
                cmd.ExecuteNonQuery();
            }
        }
    }

    public class equipoDetalleCampoModel {
        public int IdEquipo { get; set; }
        public int IdTipoEquipo { get; set; }
        public int IdEquipoCampo { get; set; }
        public string NombreCampo { get; set; } = string.Empty;
        public string Valor { get; set; } = string.Empty;
        public bool Requerido { get; set; }
    }

    public class equipoRelacionResumenModel {
        public string Direccion { get; set; } = string.Empty;
        public string TipoRelacion { get; set; } = string.Empty;
        public string EquipoRelacionado { get; set; } = string.Empty;
        public DateTime FechaAsignacion { get; set; }
        public DateTime? FechaBaja { get; set; }
    }
}
