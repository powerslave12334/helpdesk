using HELPDESK.Services;
using Microsoft.Data.SqlClient;

namespace HELPDESK.Models.Configuracion.SubModulos {
    public class subModulosModel {
        public int IdSubModulos { get; set; }
        public int IdModulo { get; set; }
        public string NombreModulo { get; set; } = string.Empty;
        public string Nombre { get; set; } = string.Empty;
        public int Orden { get; set; }
        public string Controller { get; set; } = string.Empty;
        public string Action { get; set; } = string.Empty;
        public DateTime FechaAlta { get; set; }
        public bool Activo { get; set; }
        public string? ErrorMessage { get; set; }
        public string? SuccessMessage { get; set; }
        public List<subModulosModel> SubModulos { get; set; } = new List<subModulosModel>();
        public List<subModulosModel> Modulos { get; set; } = new List<subModulosModel>();

        public List<subModulosModel> ObtenerSubModulos(DBConector dbConector) {
            List<subModulosModel> lista = new List<subModulosModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT
                    sm.id_SubModulos,
                    sm.id_Modulos AS id_Modulo,
                    m.nombre AS nombreModulo,
                    sm.nombre,
                    ISNULL(sm.orden, 0) AS orden,
                    sm.controller,
                    sm.action,
                    sm.fechaAlta,
                    sm.activo
                FROM dbo.tblSubModulos sm
                INNER JOIN dbo.tblModulos m ON m.id_Modulos = sm.id_Modulos
                ORDER BY sm.activo DESC, ISNULL(m.orden, 9999), m.nombre ASC, ISNULL(sm.orden, 9999), sm.nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new subModulosModel {
                    IdSubModulos = Convert.ToInt32(dr["id_SubModulos"]),
                    IdModulo = Convert.ToInt32(dr["id_Modulo"]),
                    NombreModulo = dr["nombreModulo"].ToString() ?? string.Empty,
                    Nombre = dr["nombre"].ToString() ?? string.Empty,
                    Orden = Convert.ToInt32(dr["orden"]),
                    Controller = dr["controller"].ToString() ?? string.Empty,
                    Action = dr["action"].ToString() ?? string.Empty,
                    FechaAlta = Convert.ToDateTime(dr["fechaAlta"]),
                    Activo = Convert.ToBoolean(dr["activo"])
                });
            }

            return lista;
        }

        public List<subModulosModel> ObtenerModulosActivos(DBConector dbConector) {
            List<subModulosModel> lista = new List<subModulosModel>();

            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return lista;

            string query = @"
                SELECT id_Modulos, nombre
                FROM dbo.tblModulos
                WHERE activo = 1
                ORDER BY ISNULL(orden, 9999), nombre ASC";

            using SqlCommand cmd = new SqlCommand(query, cn);
            using SqlDataReader dr = cmd.ExecuteReader();

            while (dr.Read()) {
                lista.Add(new subModulosModel {
                    IdModulo = Convert.ToInt32(dr["id_Modulos"]),
                    NombreModulo = dr["nombre"].ToString() ?? string.Empty
                });
            }

            return lista;
        }

        public bool InsertarSubModulo(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                INSERT INTO dbo.tblSubModulos (id_Modulos, nombre, orden, controller, action, fechaAlta, activo)
                VALUES (@id_Modulo, @nombre, @orden, @controller, @action, GETDATE(), 1)";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Modulo", System.Data.SqlDbType.Int).Value = IdModulo;
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 50).Value = Nombre.Trim();
            cmd.Parameters.Add("@orden", System.Data.SqlDbType.Int).Value = Orden;
            cmd.Parameters.Add("@controller", System.Data.SqlDbType.VarChar, 100).Value = Controller.Trim();
            cmd.Parameters.Add("@action", System.Data.SqlDbType.VarChar, 50).Value = Action.Trim();

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ActualizarSubModulo(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblSubModulos
                SET id_Modulos = @id_Modulo,
                    nombre = @nombre,
                    orden = @orden,
                    controller = @controller,
                    action = @action
                WHERE id_SubModulos = @id_SubModulos";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_SubModulos", System.Data.SqlDbType.Int).Value = IdSubModulos;
            cmd.Parameters.Add("@id_Modulo", System.Data.SqlDbType.Int).Value = IdModulo;
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 50).Value = Nombre.Trim();
            cmd.Parameters.Add("@orden", System.Data.SqlDbType.Int).Value = Orden;
            cmd.Parameters.Add("@controller", System.Data.SqlDbType.VarChar, 100).Value = Controller.Trim();
            cmd.Parameters.Add("@action", System.Data.SqlDbType.VarChar, 50).Value = Action.Trim();

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool CambiarEstatusSubModulo(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                UPDATE dbo.tblSubModulos
                SET activo = CASE WHEN activo = 1 THEN 0 ELSE 1 END
                WHERE id_SubModulos = @id_SubModulos";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_SubModulos", System.Data.SqlDbType.Int).Value = IdSubModulos;

            return cmd.ExecuteNonQuery() > 0;
        }

        public bool ExisteNombre(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblSubModulos
                WHERE id_Modulos = @id_Modulo
                  AND nombre = @nombre
                  AND id_SubModulos <> @id_SubModulos";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Modulo", System.Data.SqlDbType.Int).Value = IdModulo;
            cmd.Parameters.Add("@nombre", System.Data.SqlDbType.VarChar, 50).Value = Nombre.Trim();
            cmd.Parameters.Add("@id_SubModulos", System.Data.SqlDbType.Int).Value = IdSubModulos;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }

        public bool ExisteOrden(DBConector dbConector) {
            using SqlConnection? cn = dbConector.AbrirConexion();
            if (cn == null)
                return false;

            string query = @"
                SELECT COUNT(1)
                FROM dbo.tblSubModulos
                WHERE id_Modulos = @id_Modulo
                  AND orden = @orden
                  AND id_SubModulos <> @id_SubModulos";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Modulo", System.Data.SqlDbType.Int).Value = IdModulo;
            cmd.Parameters.Add("@orden", System.Data.SqlDbType.Int).Value = Orden;
            cmd.Parameters.Add("@id_SubModulos", System.Data.SqlDbType.Int).Value = IdSubModulos;

            return Convert.ToInt32(cmd.ExecuteScalar()) > 0;
        }
    }
}
