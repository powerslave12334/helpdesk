using HELPDESK.Services;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Models.Tickets.CategoriaSoporte {
    public class categoriaSoporteModel {
        public int IdCategoriaSoporte { get; set; }
        public int IdTipoSoporte { get; set; }
        public string TipoSoporte { get; set; } = string.Empty;
        public string Descripcion { get; set; } = string.Empty;
        public bool RequiereEquipo { get; set; }
        public int IdTipoEquipoTicket { get; set; }
        public string TipoEquipoTicket { get; set; } = "NINGUNO";
        public string TipoEquipoTicketNombre {
            get {
                return TipoEquipoTicket switch {
                    "ASIGNADO" => "Equipo asignado",
                    "COMPARTIDO" => "Equipo compartido",
                    "AMBOS" => "Asignado y compartido",
                    _ => "No requiere equipo"
                };
            }
        }
        public DateTime FechaAlta { get; set; }
        public bool Activo { get; set; }
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<categoriaSoporteModel> CategoriasSoporte { get; set; } = new List<categoriaSoporteModel>();
        public List<categoriaSoporteModel> TiposSoporte { get; set; } = new List<categoriaSoporteModel>();
        public List<categoriaSoporteModel> TiposEquipoTicket { get; set; } = new List<categoriaSoporteModel>();

        public List<categoriaSoporteModel> ObtenerCategoriasSoporte(DBConector dbConector) {
            return new List<categoriaSoporteModel>();
        }

        public List<categoriaSoporteModel> ObtenerTiposEquipoTicketActivos(DBConector dbConector) {
            List<categoriaSoporteModel> lista = new List<categoriaSoporteModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_TipoEquipoTicket, clave, descripcion
                FROM dbo.tblTipoEquipoTicket
                WHERE activo = 1
                ORDER BY id_TipoEquipoTicket ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new categoriaSoporteModel {
                    IdTipoEquipoTicket = Convert.ToInt32(dr["id_TipoEquipoTicket"]),
                    TipoEquipoTicket = (dr["clave"].ToString() ?? "NINGUNO").Trim().ToUpperInvariant(),
                    Descripcion = dr["descripcion"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public List<categoriaSoporteModel> ObtenerTiposSoporteActivos(DBConector dbConector) {
            List<categoriaSoporteModel> lista = new List<categoriaSoporteModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_TipoSoporte, descripcion
                FROM dbo.tblTipoSoporte
                WHERE activo = 1
                ORDER BY descripcion ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new categoriaSoporteModel {
                    IdTipoSoporte = Convert.ToInt32(dr["id_TipoSoporte"]),
                    TipoSoporte = dr["descripcion"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public bool InsertarCategoriaSoporte(DBConector dbConector) {
            return false;
        }

        public bool ActualizarCategoriaSoporte(DBConector dbConector) {
            return false;
        }

        public bool CambiarEstatus(DBConector dbConector) {
            return false;
        }

        public bool ExisteDescripcion(DBConector dbConector) {
            return false;
        }

        public string ObtenerClaveTipoEquipoTicket(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null || IdTipoEquipoTicket <= 0)
                return "NINGUNO";

            string query = @"
                SELECT clave
                FROM dbo.tblTipoEquipoTicket
                WHERE id_TipoEquipoTicket = @id_TipoEquipoTicket
                  AND activo = 1";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_TipoEquipoTicket", System.Data.SqlDbType.Int).Value = IdTipoEquipoTicket;
            object? result = cmd.ExecuteScalar();

            return result == null || result == DBNull.Value ? "NINGUNO" : (result.ToString() ?? "NINGUNO").Trim().ToUpperInvariant();
        }

        private void AgregarParametros(SqlCommand cmd) {
            cmd.Parameters.Add("@id_TipoSoporte", System.Data.SqlDbType.Int).Value = IdTipoSoporte;
            cmd.Parameters.Add("@descripcion", System.Data.SqlDbType.VarChar, 100).Value = Descripcion.Trim();
            cmd.Parameters.Add("@requiereEquipo", System.Data.SqlDbType.Bit).Value = RequiereEquipo;
            cmd.Parameters.Add("@id_TipoEquipoTicket", System.Data.SqlDbType.Int).Value = IdTipoEquipoTicket;
        }
    }
}
