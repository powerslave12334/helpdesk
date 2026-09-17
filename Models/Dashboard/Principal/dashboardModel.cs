namespace HELPDESK.Models.Dashboard {
    public class dashboardModel {
        public string NombreUsuario { get; set; } = string.Empty;
        public string RolUsuario { get; set; } = string.Empty;
        public bool EsTecnico { get; set; }

        public int TicketsAbiertos { get; set; }
        public int TotalEquipos { get; set; }
        public int TotalUsuarios { get; set; }
        public int NotificacionesNoLeidas { get; set; }

        public List<chartData> TicketsPorEstatus { get; set; } = new List<chartData>();
        public List<chartData> TicketsPorMes { get; set; } = new List<chartData>();

        public List<ticketReciente> TicketsRecientes { get; set; } = new List<ticketReciente>();

        // Tecnico
        public int TicketsAsignados { get; set; }
        public int TicketsEnProceso { get; set; }
        public int TicketsResueltos { get; set; }
        public List<ticketReciente> TicketsDelTecnico { get; set; } = new List<ticketReciente>();

        // Empleado
        public List<ticketReciente> MisTickets { get; set; } = new List<ticketReciente>();
    }

    public class chartData {
        public string Label { get; set; } = string.Empty;
        public int Value { get; set; }
    }

    public class ticketReciente {
        public int IdTicket { get; set; }
        public string Asunto { get; set; } = string.Empty;
        public string Usuario { get; set; } = string.Empty;
        public string Estatus { get; set; } = string.Empty;
        public string TipoSoporte { get; set; } = string.Empty;
        public DateTime FechaAlta { get; set; }
    }
}
