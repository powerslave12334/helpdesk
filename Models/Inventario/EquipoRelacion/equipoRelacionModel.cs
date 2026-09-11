using HELPDESK.Services;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Models.Inventario.EquipoRelacion {
    public class equipoRelacionModel {
        public int IdEquipoRelacion { get; set; }
        public int IdEquipoPadre { get; set; }
        public string EquipoPadre { get; set; } = string.Empty;
        public int IdEquipoHijo { get; set; }
        public string EquipoHijo { get; set; } = string.Empty;
        public int IdTipoRelacionEquipo { get; set; }
        public string TipoRelacion { get; set; } = string.Empty;
        public DateTime FechaAsignacion { get; set; }
        public DateTime? FechaBaja { get; set; }
        public string Observaciones { get; set; } = string.Empty;
        public DateTime FechaAlta { get; set; }
        public bool Activo { get; set; }
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<equipoRelacionModel> Relaciones { get; set; } = new List<equipoRelacionModel>();
        public List<equipoRelacionModel> Equipos { get; set; } = new List<equipoRelacionModel>();
        public List<equipoRelacionModel> TiposRelacion { get; set; } = new List<equipoRelacionModel>();

        public List<equipoRelacionModel> ObtenerRelaciones(DBConector dbConector) {
            List<equipoRelacionModel> lista = new List<equipoRelacionModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT
                    er.id_EquipoRelacion,
                    er.id_EquipoPadre,
                    LTRIM(RTRIM(ISNULL(ep.marca, '') + ' ' + ISNULL(ep.modelo, '') + ' ' + ISNULL(ep.nombreEquipo, '') + ' ' + ISNULL(ep.numeroSerie, ''))) AS equipoPadre,
                    er.id_EquipoHijo,
                    LTRIM(RTRIM(ISNULL(eh.marca, '') + ' ' + ISNULL(eh.modelo, '') + ' ' + ISNULL(eh.nombreEquipo, '') + ' ' + ISNULL(eh.numeroSerie, ''))) AS equipoHijo,
                    er.id_TipoRelacionEquipo,
                    tr.descripcion AS tipoRelacion,
                    er.fechaAsignacion,
                    er.fechaBaja,
                    er.observaciones,
                    er.fechaAlta,
                    er.activo
                FROM dbo.tblEquipoRelacion er
                INNER JOIN dbo.tblEquipo ep ON ep.id_Equipo = er.id_EquipoPadre
                INNER JOIN dbo.tblEquipo eh ON eh.id_Equipo = er.id_EquipoHijo
                INNER JOIN dbo.tblTipoRelacionEquipo tr ON tr.id_TipoRelacionEquipo = er.id_TipoRelacionEquipo
                ORDER BY er.activo DESC, er.fechaAsignacion DESC, equipoPadre ASC, equipoHijo ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new equipoRelacionModel {
                    IdEquipoRelacion = Convert.ToInt32(dr["id_EquipoRelacion"]),
                    IdEquipoPadre = Convert.ToInt32(dr["id_EquipoPadre"]),
                    EquipoPadre = dr["equipoPadre"].ToString() ?? string.Empty,
                    IdEquipoHijo = Convert.ToInt32(dr["id_EquipoHijo"]),
                    EquipoHijo = dr["equipoHijo"].ToString() ?? string.Empty,
                    IdTipoRelacionEquipo = Convert.ToInt32(dr["id_TipoRelacionEquipo"]),
                    TipoRelacion = dr["tipoRelacion"].ToString() ?? string.Empty,
                    FechaAsignacion = Convert.ToDateTime(dr["fechaAsignacion"]),
                    FechaBaja = dr["fechaBaja"] == DBNull.Value ? null : Convert.ToDateTime(dr["fechaBaja"]),
                    Observaciones = dr["observaciones"].ToString() ?? string.Empty,
                    FechaAlta = Convert.ToDateTime(dr["fechaAlta"]),
                    Activo = Convert.ToBoolean(dr["activo"])
                });
            }

            return lista;
        }

        public List<equipoRelacionModel> ObtenerEquiposActivos(DBConector dbConector) {
            List<equipoRelacionModel> lista = new List<equipoRelacionModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT
                    id_Equipo,
                    LTRIM(RTRIM(ISNULL(marca, '') + ' ' + ISNULL(modelo, '') + ' ' + ISNULL(nombreEquipo, '') + ' ' + ISNULL(numeroSerie, ''))) AS equipo
                FROM dbo.tblEquipo
                WHERE activo = 1
                ORDER BY marca ASC, modelo ASC, nombreEquipo ASC, numeroSerie ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new equipoRelacionModel {
                    IdEquipoPadre = Convert.ToInt32(dr["id_Equipo"]),
                    IdEquipoHijo = Convert.ToInt32(dr["id_Equipo"]),
                    EquipoPadre = dr["equipo"].ToString() ?? string.Empty,
                    EquipoHijo = dr["equipo"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public List<equipoRelacionModel> ObtenerTiposRelacionActivos(DBConector dbConector) {
            List<equipoRelacionModel> lista = new List<equipoRelacionModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_TipoRelacionEquipo, descripcion
                FROM dbo.tblTipoRelacionEquipo
                WHERE activo = 1
                ORDER BY descripcion ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new equipoRelacionModel {
                    IdTipoRelacionEquipo = Convert.ToInt32(dr["id_TipoRelacionEquipo"]),
                    TipoRelacion = dr["descripcion"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public bool InsertarRelacion(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                INSERT INTO dbo.tblEquipoRelacion
                    (id_EquipoPadre, id_EquipoHijo, id_TipoRelacionEquipo, fechaAsignacion, fechaBaja, observaciones, fechaAlta, activo)
                VALUES
                    (@id_EquipoPadre, @id_EquipoHijo, @id_TipoRelacionEquipo, @fechaAsignacion, @fechaBaja, @observaciones, GETDATE(), 1)";

            using SqlCommand cmd = new SqlCommand(query, cn);
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ActualizarRelacion(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblEquipoRelacion
                SET id_EquipoPadre = @id_EquipoPadre,
                    id_EquipoHijo = @id_EquipoHijo,
                    id_TipoRelacionEquipo = @id_TipoRelacionEquipo,
                    fechaAsignacion = @fechaAsignacion,
                    fechaBaja = @fechaBaja,
                    observaciones = @observaciones
                WHERE id_EquipoRelacion = @id_EquipoRelacion";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_EquipoRelacion", System.Data.SqlDbType.Int).Value = IdEquipoRelacion;
            AgregarParametros(cmd);

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool CambiarEstatus(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblEquipoRelacion
                SET activo = CASE WHEN activo = 1 THEN 0 ELSE 1 END,
                    fechaBaja = CASE WHEN activo = 1 AND fechaBaja IS NULL THEN GETDATE() ELSE fechaBaja END
                WHERE id_EquipoRelacion = @id_EquipoRelacion";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_EquipoRelacion", System.Data.SqlDbType.Int).Value = IdEquipoRelacion;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ExisteRelacionActiva(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblEquipoRelacion
                WHERE id_EquipoPadre = @id_EquipoPadre
                  AND id_EquipoHijo = @id_EquipoHijo
                  AND id_TipoRelacionEquipo = @id_TipoRelacionEquipo
                  AND activo = 1
                  AND fechaBaja IS NULL
                  AND id_EquipoRelacion <> @id_EquipoRelacion";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_EquipoPadre", System.Data.SqlDbType.Int).Value = IdEquipoPadre;
            cmd.Parameters.Add("@id_EquipoHijo", System.Data.SqlDbType.Int).Value = IdEquipoHijo;
            cmd.Parameters.Add("@id_TipoRelacionEquipo", System.Data.SqlDbType.Int).Value = IdTipoRelacionEquipo;
            cmd.Parameters.Add("@id_EquipoRelacion", System.Data.SqlDbType.Int).Value = IdEquipoRelacion;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        public bool HijoTieneRelacionActiva(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblEquipoRelacion
                WHERE id_EquipoHijo = @id_EquipoHijo
                  AND id_TipoRelacionEquipo = @id_TipoRelacionEquipo
                  AND activo = 1
                  AND fechaBaja IS NULL
                  AND id_EquipoRelacion <> @id_EquipoRelacion";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_EquipoHijo", System.Data.SqlDbType.Int).Value = IdEquipoHijo;
            cmd.Parameters.Add("@id_TipoRelacionEquipo", System.Data.SqlDbType.Int).Value = IdTipoRelacionEquipo;
            cmd.Parameters.Add("@id_EquipoRelacion", System.Data.SqlDbType.Int).Value = IdEquipoRelacion;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        private void AgregarParametros(SqlCommand cmd) {
            cmd.Parameters.Add("@id_EquipoPadre", System.Data.SqlDbType.Int).Value = IdEquipoPadre;
            cmd.Parameters.Add("@id_EquipoHijo", System.Data.SqlDbType.Int).Value = IdEquipoHijo;
            cmd.Parameters.Add("@id_TipoRelacionEquipo", System.Data.SqlDbType.Int).Value = IdTipoRelacionEquipo;
            cmd.Parameters.Add("@fechaAsignacion", System.Data.SqlDbType.DateTime).Value = FechaAsignacion;
            cmd.Parameters.Add("@fechaBaja", System.Data.SqlDbType.DateTime).Value = FechaBaja.HasValue ? FechaBaja.Value : (object)DBNull.Value;
            cmd.Parameters.Add("@observaciones", System.Data.SqlDbType.VarChar, -1).Value =
                string.IsNullOrWhiteSpace(Observaciones) ? (object)DBNull.Value : Observaciones.Trim();
        }
    }
}
