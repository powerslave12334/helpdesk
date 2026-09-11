using HELPDESK.Models.Tickets.Tickets;
using HELPDESK.Services;
using Microsoft.AspNetCore.Hosting;
using Microsoft.AspNetCore.Http;
using Microsoft.AspNetCore.Mvc;
using Microsoft.Data.SqlClient;
using System.Net;

namespace HELPDESK.Controllers.Tickets.Tickets {
    public class ticketsController : Controller {
        private readonly DBConector _dbConector;
        private readonly PermisosService _permisosService;
        private readonly CorreoService _correoService;
        private readonly IWebHostEnvironment _environment;

        public ticketsController(DBConector dbConector, PermisosService permisosService, CorreoService correoService, IWebHostEnvironment environment) {
            _dbConector = dbConector;
            _permisosService = permisosService;
            _correoService = correoService;
            _environment = environment;
        }

        [HttpGet]
        public IActionResult Index() {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return acceso;

            int idUsuario = ObtenerIdUsuarioSesion();
            bool puedeVerTicketsGlobales = EsRolAdministradorTickets(idUsuario);
            ticketsModel model = CrearModeloPantalla(idUsuario);
            model.PuedeActualizar = _permisosService.TienePermisoPorRuta(idUsuario, "tickets", "Actualizar");
            model.PuedeVerAsignados = puedeVerTicketsGlobales;
            model.PuedeVerTodos = puedeVerTicketsGlobales;
            model.PuedeAutorizar = puedeVerTicketsGlobales && PuedeAutorizarTickets(idUsuario);
            model.SuccessMessage = TempData["SuccessMessage"]?.ToString();
            model.ErrorMessage = TempData["ErrorMessage"]?.ToString();

            return View("~/Views/Tickets/Tickets/tickets.cshtml", model);
        }

        [HttpGet]
        public IActionResult ObtenerModulosSistema(int idSistemas) {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return Unauthorized();

            if (idSistemas <= 0)
                return Json(new List<object>());

            var modulos = new ticketsModel()
                .ObtenerModulosPorSistema(_dbConector, idSistemas)
                .Select(item => new {
                    idModulo = item.IdModulo,
                    nombre = item.NombreModulo
                })
                .ToList();

            return Json(modulos);
        }

        [HttpGet]
        public IActionResult ObtenerSubModulosSistema(int idModulo) {
            IActionResult? acceso = ValidarAcceso("Consultar");
            if (acceso != null)
                return Unauthorized();

            if (idModulo <= 0)
                return Json(new List<object>());

            var subModulos = new ticketsModel()
                .ObtenerSubModulosPorModulo(_dbConector, idModulo)
                .Select(item => new {
                    idSubModulo = item.IdSubModulo,
                    nombre = item.NombreSubModulo
                })
                .ToList();

            return Json(subModulos);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult Create(ticketsModel model, IFormFile? evidenciaTicket) {
            IActionResult? acceso = ValidarAcceso("Crear");
            if (acceso != null)
                return acceso;

            evidenciaTicket = ObtenerArchivoFormulario(evidenciaTicket);
            model.IdUsuario = ObtenerIdUsuarioSesion();
            model.IdEstatus = model.ObtenerEstatusInicial(_dbConector);
            model.IdAreaAtiende = model.ObtenerAreaPorTipoSoporte(_dbConector);
            model.TipoEquipoTicket = model.ObtenerTipoEquipoTicket(_dbConector);
            model.RequiereEquipo = model.TipoEquipoTicket != "NINGUNO";
            if (model.TipoEquipoTicket == "NINGUNO")
                model.IdEquipo = null;
            int idTecnico = model.ObtenerTecnicoPorArea(_dbConector);
            model.IdTecnico = idTecnico > 0 ? idTecnico : null;

            string? error = ValidarModelo(model);
            if (!string.IsNullOrWhiteSpace(error)) {
                TempData["ErrorMessage"] = error;
                return RedirectToAction("Index");
            }

            Normalizar(model);
            model.TipoSoporte = model.ObtenerNombreTipoSoporte(_dbConector);
            bool insertado = model.InsertarTicket(_dbConector);
            if (!insertado) {
                TempData["ErrorMessage"] = "No fue posible registrar el ticket.";
                return RedirectToAction("Index");
            }

            if (evidenciaTicket != null && evidenciaTicket.Length > 0) {
                bool evidenciaGuardada = GuardarArchivoEvidencia(model.IdTicket, model.IdTipoSoporte, model.IdUsuario, evidenciaTicket, out string errorEvidencia);
                TempData[evidenciaGuardada ? "SuccessMessage" : "ErrorMessage"] =
                    evidenciaGuardada ? "Ticket registrado correctamente con evidencia." : $"Ticket registrado, pero no fue posible guardar la evidencia. {errorEvidencia}";
            } else {
                TempData["SuccessMessage"] = "Ticket registrado correctamente.";
            }

            NotificarTicketCreado(model);

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult CreateDevelopment(ticketsModel model) {
            IActionResult? acceso = ValidarAcceso("Crear");
            if (acceso != null)
                return acceso;

            model.IdUsuarioSolicita = ObtenerIdUsuarioSesion();
            model.Asunto = model.Asunto?.Trim() ?? string.Empty;
            model.DescripcionTicket = model.DescripcionTicket?.Trim() ?? string.Empty;

            string? error = ValidarModeloDesarrollo(model);
            if (!string.IsNullOrWhiteSpace(error)) {
                TempData["ErrorMessage"] = error;
                return RedirectToAction("Index");
            }

            bool insertado = model.InsertarTicketDesarrollo(_dbConector);
            TempData[insertado ? "SuccessMessage" : "ErrorMessage"] =
                insertado ? "Ticket de desarrollo registrado correctamente." : "No fue posible registrar el ticket de desarrollo.";

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult AddComment(int idTicket, int idTipoSoporte, string comentario) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int idUsuario = ObtenerIdUsuarioSesion();
            bool puedeComentar = _permisosService.TienePermisoPorRuta(idUsuario, "tickets", "Crear")
                || _permisosService.TienePermisoPorRuta(idUsuario, "tickets", "Actualizar");

            if (!puedeComentar) {
                TempData["ErrorMessage"] = "No tienes permiso para comentar tickets.";
                return RedirectToAction("Index");
            }

            if (idTicket <= 0) {
                TempData["ErrorMessage"] = "No se recibio el ticket para comentar.";
                return RedirectToAction("Index");
            }

            if (string.IsNullOrWhiteSpace(comentario)) {
                TempData["ErrorMessage"] = "El comentario es obligatorio.";
                return RedirectToAction("Index");
            }

            ticketsModel data = new ticketsModel();
            idTipoSoporte = idTipoSoporte == 1 ? 1 : 2;
            if (!data.UsuarioPuedeVerTicket(_dbConector, idTicket, idUsuario, PuedeVerTodosTickets(idUsuario), idTipoSoporte)) {
                TempData["ErrorMessage"] = "No tienes permiso para comentar este ticket.";
                return RedirectToAction("Index");
            }

            if (data.TicketEstaCerrado(_dbConector, idTicket, idTipoSoporte)) {
                TempData["ErrorMessage"] = "El ticket ya esta cerrado y no permite nuevos comentarios.";
                return RedirectToAction("Index");
            }

            bool insertado = data.AgregarComentario(_dbConector, idTicket, idUsuario, comentario.Trim(), idTipoSoporte);
            TempData[insertado ? "SuccessMessage" : "ErrorMessage"] =
                insertado ? "Comentario agregado correctamente." : "No fue posible agregar el comentario.";

            if (insertado) {
                NotificarComentarioTicket(idTicket, idUsuario, comentario.Trim());
            }

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult AddEvidence(int idTicket, int idTipoSoporte, IFormFile? evidenciaTicket) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            evidenciaTicket = ObtenerArchivoFormulario(evidenciaTicket);
            int idUsuario = ObtenerIdUsuarioSesion();
            bool puedeAgregar = _permisosService.TienePermisoPorRuta(idUsuario, "tickets", "Crear")
                || _permisosService.TienePermisoPorRuta(idUsuario, "tickets", "Actualizar");

            if (!puedeAgregar) {
                TempData["ErrorMessage"] = "No tienes permiso para agregar evidencias.";
                return RedirectToAction("Index");
            }

            if (idTicket <= 0) {
                TempData["ErrorMessage"] = "No se recibio el ticket para agregar evidencia.";
                return RedirectToAction("Index");
            }

            ticketsModel data = new ticketsModel();
            idTipoSoporte = idTipoSoporte == 1 ? 1 : 2;
            if (!data.UsuarioPuedeVerTicket(_dbConector, idTicket, idUsuario, PuedeVerTodosTickets(idUsuario), idTipoSoporte)) {
                TempData["ErrorMessage"] = "No tienes permiso para agregar evidencia a este ticket.";
                return RedirectToAction("Index");
            }

            if (data.TicketEstaCerrado(_dbConector, idTicket, idTipoSoporte)) {
                TempData["ErrorMessage"] = "El ticket ya esta cerrado y no permite nuevas evidencias.";
                return RedirectToAction("Index");
            }

            bool guardado = GuardarArchivoEvidencia(idTicket, idTipoSoporte, idUsuario, evidenciaTicket, out string error);
            TempData[guardado ? "SuccessMessage" : "ErrorMessage"] =
                guardado ? "Evidencia agregada correctamente." : error;

            if (guardado) {
                NotificarEvidenciaTicket(idTicket, idUsuario);
            }

            return RedirectToAction("Index");
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult CloseWithApproval(int idTicket) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int idUsuario = ObtenerIdUsuarioSesion();

            if (idTicket <= 0) {
                TempData["ErrorMessage"] = "No se recibio el ticket a cerrar.";
                return RedirectToAction("Index");
            }

            ticketsModel data = new ticketsModel();
            if (!data.TicketPuedeCerrarUsuario(_dbConector, idTicket, idUsuario)) {
                TempData["ErrorMessage"] = "Solo puedes cerrar tus tickets cuando esten en estatus Resuelto.";
                return RedirectToAction("Index");
            }

            int idEstatusCerrado = data.ObtenerEstatusPorNombre(_dbConector, "Cerrado");
            if (idEstatusCerrado <= 0) {
                TempData["ErrorMessage"] = "No se encontro el estatus Cerrado para tickets.";
                return RedirectToAction("Index");
            }

            bool cerrado = data.CerrarTicketConVistoBueno(_dbConector, idTicket, idUsuario, idEstatusCerrado);
            TempData[cerrado ? "SuccessMessage" : "ErrorMessage"] =
                cerrado ? "Ticket cerrado con visto bueno correctamente." : "No fue posible cerrar el ticket.";

            if (cerrado) {
                NotificarTicketCerrado(idTicket);
            }

            return RedirectToAction("Index");
        }

        [HttpGet]
        public IActionResult ViewEvidence(int idEvidencia) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            if (idEvidencia <= 0)
                return NotFound();

            int idUsuario = ObtenerIdUsuarioSesion();
            ticketsModel data = new ticketsModel();
            ticketEvidenciaModel? evidencia = data.ObtenerEvidencia(_dbConector, idEvidencia);

            if (evidencia == null)
                return NotFound();

            if (!data.UsuarioPuedeVerTicket(_dbConector, evidencia.IdTicket, idUsuario, PuedeVerTodosTickets(idUsuario), evidencia.IdTipoSoporte))
                return RedirectToAction("Index", "Dashboard");

            string rutaFisica = ResolverRutaFisica(evidencia.RutaArchivo);
            if (!System.IO.File.Exists(rutaFisica))
                return NotFound();

            string tipoContenido = string.IsNullOrWhiteSpace(evidencia.TipoContenido) ? "application/octet-stream" : evidencia.TipoContenido;
            return PhysicalFile(rutaFisica, tipoContenido);
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public IActionResult UpdateManagement(ticketsModel model) {
            IActionResult? acceso = ValidarAcceso("Actualizar");
            if (acceso != null)
                return acceso;

            int idUsuario = ObtenerIdUsuarioSesion();

            if (model.IdTicket <= 0) {
                TempData["ErrorMessage"] = "No se recibio el ticket a actualizar.";
                return RedirectToAction("Index");
            }

            if (!model.CargarDatosClasificacionTicket(_dbConector)) {
                TempData["ErrorMessage"] = "No se encontro la clasificacion del ticket.";
                return RedirectToAction("Index");
            }

            if (!model.IdEstatus.HasValue || model.IdEstatus.Value <= 0) {
                TempData["ErrorMessage"] = "Selecciona el estatus.";
                return RedirectToAction("Index");
            }

            if (!model.TicketExiste(_dbConector)) {
                TempData["ErrorMessage"] = "No se encontro el ticket a actualizar.";
                return RedirectToAction("Index");
            }

            if (model.TicketEstaCerrado(_dbConector, model.IdTicket)) {
                TempData["ErrorMessage"] = "El ticket ya esta cerrado y no se puede volver a modificar.";
                return RedirectToAction("Index");
            }

            bool puedeGestionarTodos = PuedeGestionarTodosTickets(idUsuario);
            bool esTecnicoAsignado = model.UsuarioPuedeGestionarTicket(_dbConector, model.IdTicket, idUsuario, false);
            if (!model.UsuarioPuedeGestionarTicket(_dbConector, model.IdTicket, idUsuario, puedeGestionarTodos)) {
                TempData["ErrorMessage"] = "Solo el tecnico asignado o un usuario autorizado puede gestionar este ticket.";
                return RedirectToAction("Index");
            }

            int idEstatusActual = model.ObtenerIdEstatusActual(_dbConector, model.IdTicket);
            bool cambioEstatus = idEstatusActual > 0 && model.IdEstatus.HasValue && idEstatusActual != model.IdEstatus.Value;
            if (cambioEstatus && !esTecnicoAsignado) {
                TempData["ErrorMessage"] = "Solo el tecnico asignado puede cambiar el estatus del ticket.";
                return RedirectToAction("Index");
            }

            if (!model.IdTecnico.HasValue || model.IdTecnico.Value <= 0) {
                int idTecnico = model.ObtenerTecnicoPorArea(_dbConector);
                model.IdTecnico = idTecnico > 0 ? idTecnico : null;
            } else if (!model.TecnicoPerteneceArea(_dbConector)) {
                TempData["ErrorMessage"] = "El tecnico seleccionado no pertenece al area del tipo de soporte.";
                return RedirectToAction("Index");
            }
            model.Estatus = model.ObtenerNombreEstatus(_dbConector);
            model.Tecnico = model.ObtenerNombreTecnico(_dbConector);

            bool esEstatusResuelto = string.Equals(model.Estatus, "Resuelto", StringComparison.OrdinalIgnoreCase);
            bool esEstatusCerrado = string.Equals(model.Estatus, "Cerrado", StringComparison.OrdinalIgnoreCase);
            if (esEstatusResuelto && !esTecnicoAsignado) {
                TempData["ErrorMessage"] = "Solo el tecnico asignado puede marcar el ticket como Resuelto.";
                return RedirectToAction("Index");
            }

            if (esEstatusCerrado) {
                TempData["ErrorMessage"] = "El ticket no se puede cerrar desde gestion. El tecnico debe dejarlo en Resuelto y el usuario debe dar el visto bueno.";
                return RedirectToAction("Index");
            }

            if (!model.IdAreaAtiende.HasValue || model.IdAreaAtiende.Value <= 0) {
                TempData["ErrorMessage"] = "El tipo de soporte del ticket no tiene un area asignada.";
                return RedirectToAction("Index");
            }

            if (!model.IdTecnico.HasValue || model.IdTecnico.Value <= 0) {
                TempData["ErrorMessage"] = "No se encontro un tecnico activo para el area del tipo de soporte.";
                return RedirectToAction("Index");
            }

            ticketsModel? ticketAnterior = ObtenerTicketCorreo(model.IdTicket);
            bool actualizado = model.ActualizarGestion(_dbConector, idUsuario);
            TempData[actualizado ? "SuccessMessage" : "ErrorMessage"] =
                actualizado ? "Gestion del ticket actualizada correctamente." : "No fue posible actualizar la gestion del ticket.";

            if (actualizado) {
                NotificarGestionTicket(ticketAnterior, model.IdTicket);
            }

            return RedirectToAction("Index");
        }

        private ticketsModel CrearModeloPantalla(int idUsuario) {
            ticketsModel data = new ticketsModel();
            bool puedeVerTicketsGlobales = EsRolAdministradorTickets(idUsuario);
            bool puedeVerAsignados = puedeVerTicketsGlobales;
            bool puedeVerTodos = puedeVerTicketsGlobales;
            List<ticketsModel> misTickets = data.ObtenerMisTickets(_dbConector, idUsuario);
            List<ticketsModel> ticketsAsignados = puedeVerAsignados ? data.ObtenerTicketsAsignados(_dbConector, idUsuario) : new List<ticketsModel>();
            List<ticketsModel> ticketsTodos = puedeVerTodos ? data.ObtenerTodosTickets(_dbConector) : new List<ticketsModel>();
            MarcarTicketsCerrables(misTickets, idUsuario);
            MarcarTicketsCerrables(ticketsAsignados, idUsuario);
            MarcarTicketsCerrables(ticketsTodos, idUsuario);
            ticketsModel usuarioSolicita = data.ObtenerUsuarioSolicita(_dbConector, idUsuario);
            List<ticketsModel> ticketsDetalle = misTickets
                .Concat(ticketsAsignados)
                .Concat(ticketsTodos)
                .GroupBy(ticket => ticket.IdTicket)
                .Select(grupo => grupo.First())
                .ToList();

            return new ticketsModel {
                Tickets = ticketsDetalle,
                MisTickets = misTickets,
                TicketsAsignados = ticketsAsignados,
                TicketsTodos = ticketsTodos,
                PuedeVerAsignados = puedeVerAsignados,
                PuedeVerTodos = puedeVerTodos,
                PuedeAutorizar = puedeVerTicketsGlobales && PuedeAutorizarTickets(idUsuario),
                IdUsuarioSolicita = usuarioSolicita.IdUsuarioSolicita,
                UsuarioSolicita = usuarioSolicita.UsuarioSolicita,
                TiposSoporte = data.ObtenerTiposSoporteActivos(_dbConector),
                CategoriasSoporte = new List<ticketsModel>(),
                Equipos = data.ObtenerEquiposAsignados(_dbConector, idUsuario),
                EquiposCompartidos = data.ObtenerEquiposCompartidos(_dbConector, idUsuario),
                EstatusTicket = data.ObtenerEstatusTickets(_dbConector),
                Tecnicos = data.ObtenerTecnicosActivos(_dbConector),
                Desarrolladores = data.ObtenerDesarrolladoresActivos(_dbConector),
                UsuariosActivos = data.ObtenerUsuariosActivos(_dbConector),
                TiposDesarrollo = data.ObtenerTiposDesarrolloActivos(_dbConector),
                SistemasDesarrollo = data.ObtenerSistemasActivos(_dbConector)
            };
        }

        private string? ValidarModelo(ticketsModel model) {
            if (model.IdUsuario <= 0)
                return "No se pudo identificar el usuario que registra el ticket.";

            if (model.IdTipoSoporte <= 0)
                return "Selecciona el tipo de soporte.";

            if (model.TipoEquipoTicket == "ASIGNADO" && !model.EquipoAsignadoUsuario(_dbConector))
                return "El equipo seleccionado no esta asignado a tu usuario.";

            if (model.TipoEquipoTicket == "COMPARTIDO" && !model.EquipoCompartidoActivo(_dbConector))
                return "El equipo compartido seleccionado no esta activo.";

            if (model.TipoEquipoTicket == "AMBOS" && !model.EquipoAsignadoUsuario(_dbConector) && !model.EquipoCompartidoActivo(_dbConector))
                return "El equipo seleccionado no esta asignado a tu usuario o no esta marcado como compartido.";

            if (!model.IdAreaAtiende.HasValue || model.IdAreaAtiende.Value <= 0)
                return "El tipo de soporte seleccionado no tiene un area asignada.";

            if (!model.IdTecnico.HasValue || model.IdTecnico.Value <= 0)
                return "No se encontro un tecnico activo para el area del tipo de soporte.";

            if (!model.IdEstatus.HasValue || model.IdEstatus.Value <= 0)
                return "No se encontro un estatus inicial para tickets.";

            if (string.IsNullOrWhiteSpace(model.Asunto))
                return "El asunto es obligatorio.";

            if (model.Asunto.Trim().Length > 200)
                return "El asunto no puede superar 200 caracteres.";

            if (string.IsNullOrWhiteSpace(model.Problema))
                return "La descripcion del problema es obligatoria.";

            if (!string.IsNullOrWhiteSpace(model.Img) && model.Img.Trim().Length > 150)
                return "La ruta de la imagen no puede superar 150 caracteres.";

            return null;
        }

        private string? ValidarModeloDesarrollo(ticketsModel model) {
            if (model.IdUsuarioSolicita <= 0)
                return "No se pudo identificar el usuario solicitante.";

            if (model.IdTipoDesarrollo <= 0)
                return "Selecciona el tipo de requerimiento.";

            if (model.IdSistemas <= 0)
                return "Selecciona el tipo de sistema.";

            if (string.IsNullOrWhiteSpace(model.Asunto))
                return "El asunto es obligatorio.";

            if (model.Asunto.Trim().Length > 100)
                return "El asunto no puede superar 100 caracteres.";

            if (model.IdModulo <= 0)
                return "Selecciona el modulo.";

            if (string.IsNullOrWhiteSpace(model.DescripcionTicket))
                return "La descripcion del desarrollo es obligatoria.";

            return null;
        }

        private void Normalizar(ticketsModel model) {
            model.Asunto = model.Asunto.Trim();
            model.Problema = model.Problema.Trim();
            model.Img = model.Img?.Trim() ?? string.Empty;
        }

        private bool GuardarArchivoEvidencia(int idTicket, int idTipoSoporte, int idUsuario, IFormFile? archivo, out string error) {
            error = string.Empty;

            if (archivo == null || archivo.Length == 0) {
                error = "Selecciona un archivo de evidencia.";
                return false;
            }

            ticketsModel data = new ticketsModel();
            string extension = Path.GetExtension(archivo.FileName).ToLowerInvariant();
            string[] extensionesPermitidas = ObtenerExtensionesPermitidas(data);
            if (!extensionesPermitidas.Contains(extension)) {
                error = $"Solo se permiten archivos con estas extensiones: {string.Join(", ", extensionesPermitidas)}.";
                return false;
            }

            int tamanoMaximoMb = ObtenerTamanoMaximoMb(data);
            long tamanoMaximoBytes = tamanoMaximoMb * 1024L * 1024L;
            if (archivo.Length > tamanoMaximoBytes) {
                error = $"El archivo supera el tamano maximo permitido de {tamanoMaximoMb} MB.";
                return false;
            }

            string rutaConfigurada = data.ObtenerValorConfiguracion(_dbConector, "RutaEvidenciasTickets");
            if (string.IsNullOrWhiteSpace(rutaConfigurada))
                rutaConfigurada = "EvidenciasTickets";
            rutaConfigurada = rutaConfigurada.Trim();

            string rutaBaseFisica = ResolverRutaFisica(rutaConfigurada);
            string carpetaTicket = Path.Combine(rutaBaseFisica, idTicket.ToString());

            try {
                Directory.CreateDirectory(carpetaTicket);

                string nombreArchivo = $"{DateTime.Now:yyyyMMddHHmmssfff}_{Guid.NewGuid():N}{extension}";
                string rutaFisicaArchivo = Path.Combine(carpetaTicket, nombreArchivo);

                using (FileStream stream = new FileStream(rutaFisicaArchivo, FileMode.CreateNew)) {
                    archivo.CopyTo(stream);
                }

                string rutaGuardar = Path.Combine(rutaConfigurada, idTicket.ToString(), nombreArchivo);
                ticketEvidenciaModel evidencia = new ticketEvidenciaModel {
                    IdTicket = idTicket,
                    IdTipoSoporte = idTipoSoporte,
                    NombreArchivo = nombreArchivo,
                    RutaArchivo = rutaGuardar,
                    Extension = extension,
                    TipoContenido = archivo.ContentType,
                    TamanioBytes = archivo.Length,
                    IdUsuarioAlta = idUsuario
                };

                bool insertado = data.InsertarEvidencia(_dbConector, evidencia);
                if (!insertado) {
                    System.IO.File.Delete(rutaFisicaArchivo);
                    error = "No se pudo registrar la evidencia en base de datos.";
                    return false;
                }

                return true;
            } catch (Exception ex) {
                error = $"No se pudo guardar el archivo en la ruta configurada: {carpetaTicket}. Detalle: {ex.Message}";
                return false;
            }
        }

        private string ResolverRutaFisica(string ruta) {
            if (Path.IsPathRooted(ruta))
                return ruta;

            string rutaBase = Directory.GetParent(_environment.ContentRootPath)?.FullName ?? _environment.ContentRootPath;
            return Path.Combine(rutaBase, ruta);
        }

        private IFormFile? ObtenerArchivoFormulario(IFormFile? archivo) {
            if (archivo != null && archivo.Length > 0)
                return archivo;

            if (Request.Form.Files.Count == 0)
                return archivo;

            return Request.Form.Files["evidenciaTicket"] ?? Request.Form.Files.FirstOrDefault();
        }

        private string[] ObtenerExtensionesPermitidas(ticketsModel data) {
            string valor = data.ObtenerValorConfiguracion(_dbConector, "ExtensionesEvidenciasTickets");
            if (string.IsNullOrWhiteSpace(valor))
                valor = ".jpg,.jpeg,.png,.gif,.webp,.bmp";

            return valor
                .Split(',', StringSplitOptions.RemoveEmptyEntries | StringSplitOptions.TrimEntries)
                .Select(extension => extension.StartsWith(".") ? extension.ToLowerInvariant() : $".{extension.ToLowerInvariant()}")
                .Distinct()
                .ToArray();
        }

        private int ObtenerTamanoMaximoMb(ticketsModel data) {
            string valor = data.ObtenerValorConfiguracion(_dbConector, "TamanoMaximoEvidenciaTicketMB");
            if (int.TryParse(valor, out int tamanoMaximoMb) && tamanoMaximoMb > 0)
                return tamanoMaximoMb;

            return 5;
        }

        private IActionResult? ValidarAcceso(string accion) {
            string? usuario = HttpContext.Session.GetString("Usuario");
            if (string.IsNullOrEmpty(usuario))
                return RedirectToAction("Login", "Login");

            int idUsuario = ObtenerIdUsuarioSesion();
            if (!_permisosService.TienePermisoPorRuta(idUsuario, "tickets", accion)) {
                TempData["ErrorMessage"] = "No tienes permiso para realizar esta accion.";
                return RedirectToAction("Index", "Dashboard");
            }

            return null;
        }

        private int ObtenerIdUsuarioSesion() {
            int.TryParse(HttpContext.Session.GetString("IdUsuario"), out int idUsuario);
            return idUsuario;
        }

        private bool EsRolAdministradorTickets(int idUsuario) {
            string rol = ObtenerRolPrincipal(idUsuario).Trim().ToLowerInvariant();
            return rol == "root" || rol == "administrador" || rol == "admin";
        }

        private string ObtenerRolPrincipal(int idUsuario) {
            using SqlConnection? cn = _dbConector.AbrirConexion();
            if (cn == null)
                return string.Empty;

            string query = @"
                SELECT TOP 1 r.nombre
                FROM dbo.tblRolesUsuarios ru
                INNER JOIN dbo.tblRoles r ON r.id_Rol = ru.id_Rol
                WHERE ru.id_Usuario = @id_Usuario
                  AND ru.activo = 1
                  AND r.activo = 1
                ORDER BY
                    CASE
                        WHEN LOWER(r.nombre) = 'root' THEN 1
                        WHEN LOWER(r.nombre) IN ('administrador', 'admin') THEN 2
                        ELSE 3
                    END,
                    r.id_Rol";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = idUsuario;

            return cmd.ExecuteScalar()?.ToString() ?? string.Empty;
        }

        private bool PuedeVerTodosTickets(int idUsuario) {
            return EsRolAdministradorTickets(idUsuario)
                && (_permisosService.TienePermisoPorRuta(idUsuario, "tickets", "Asignar")
                || _permisosService.TienePermisoPorRuta(idUsuario, "tickets", "Autorizar"));
        }

        private bool PuedeGestionarTodosTickets(int idUsuario) {
            return EsRolAdministradorTickets(idUsuario)
                && (_permisosService.TienePermisoPorRuta(idUsuario, "tickets", "Asignar")
                || _permisosService.TienePermisoPorRuta(idUsuario, "tickets", "Autorizar"));
        }

        private bool PuedeAutorizarTickets(int idUsuario) {
            return EsRolAdministradorTickets(idUsuario)
                && _permisosService.TienePermisoPorRuta(idUsuario, "tickets", "Autorizar");
        }

        private void NotificarTicketCreado(ticketsModel ticket) {
            UsuarioCorreoInfo usuario = ObtenerUsuarioCorreo(ticket.IdUsuario);
            UsuarioCorreoInfo tecnico = ticket.IdTecnico.HasValue ? ObtenerUsuarioCorreo(ticket.IdTecnico.Value) : new UsuarioCorreoInfo();

            string destinatarios = string.Join(";", new[] { usuario.Correo, tecnico.Correo }
                .Where(correo => !string.IsNullOrWhiteSpace(correo))
                .Distinct(StringComparer.OrdinalIgnoreCase));

            if (string.IsNullOrWhiteSpace(destinatarios))
                return;

            string asunto = $"Help Desk - Ticket #{ticket.IdTicket} creado";
            string html = ConstruirHtmlTicketCreado(ticket, usuario.Nombre, tecnico.Nombre);
            CorreoResultado resultado = _correoService.EnviarCorreo(destinatarios, asunto, html);

            if (!resultado.Ok) {
                TempData["ErrorMessage"] = $"Ticket registrado correctamente, pero no fue posible enviar el correo. {resultado.Mensaje}";
            }
        }

        private void NotificarComentarioTicket(int idTicket, int idUsuarioComentario, string comentario) {
            ticketsModel? ticket = ObtenerTicketCorreo(idTicket);
            if (ticket == null)
                return;

            UsuarioCorreoInfo usuarioComenta = ObtenerUsuarioCorreo(idUsuarioComentario);
            UsuarioCorreoInfo usuarioTicket = ObtenerUsuarioCorreo(ticket.IdUsuario);
            UsuarioCorreoInfo tecnico = ticket.IdTecnico.HasValue ? ObtenerUsuarioCorreo(ticket.IdTecnico.Value) : new UsuarioCorreoInfo();

            List<string> destinatarios = new List<string>();
            if (idUsuarioComentario == ticket.IdUsuario) {
                AgregarDestinatario(destinatarios, tecnico.Correo);
            } else {
                AgregarDestinatario(destinatarios, usuarioTicket.Correo);
            }

            if (destinatarios.Count == 0)
                return;

            string asunto = $"Help Desk - Nuevo comentario en ticket #{ticket.IdTicket}";
            string html = ConstruirHtmlComentarioTicket(ticket, usuarioComenta.Nombre, comentario);
            CorreoResultado resultado = _correoService.EnviarCorreo(string.Join(";", destinatarios), asunto, html);

            if (!resultado.Ok) {
                TempData["ErrorMessage"] = $"Comentario agregado correctamente, pero no fue posible enviar el correo. {resultado.Mensaje}";
            }
        }

        private void NotificarGestionTicket(ticketsModel? ticketAnterior, int idTicket) {
            if (ticketAnterior == null)
                return;

            ticketsModel? ticketActual = ObtenerTicketCorreo(idTicket);
            if (ticketActual == null)
                return;

            bool cambioEstatus = !string.Equals(ticketAnterior.Estatus, ticketActual.Estatus, StringComparison.OrdinalIgnoreCase);
            bool cambioTecnico = ticketAnterior.IdTecnico != ticketActual.IdTecnico;
            bool cambioTipoSoporte = !string.Equals(ticketAnterior.TipoSoporte, ticketActual.TipoSoporte, StringComparison.OrdinalIgnoreCase);

            if (!cambioEstatus && !cambioTecnico && !cambioTipoSoporte)
                return;

            UsuarioCorreoInfo usuarioTicket = ObtenerUsuarioCorreo(ticketActual.IdUsuario);
            UsuarioCorreoInfo tecnicoActual = ticketActual.IdTecnico.HasValue ? ObtenerUsuarioCorreo(ticketActual.IdTecnico.Value) : new UsuarioCorreoInfo();

            List<string> destinatarios = new List<string>();
            if (cambioEstatus || cambioTipoSoporte)
                AgregarDestinatario(destinatarios, usuarioTicket.Correo);

            if (cambioTecnico || cambioTipoSoporte)
                AgregarDestinatario(destinatarios, tecnicoActual.Correo);

            if (string.Equals(ticketActual.Estatus, "Resuelto", StringComparison.OrdinalIgnoreCase))
                AgregarDestinatario(destinatarios, usuarioTicket.Correo);

            if (destinatarios.Count == 0)
                return;

            string asunto = string.Equals(ticketActual.Estatus, "Resuelto", StringComparison.OrdinalIgnoreCase)
                ? $"Help Desk - Ticket #{ticketActual.IdTicket} resuelto"
                : $"Help Desk - Ticket #{ticketActual.IdTicket} actualizado";

            string html = ConstruirHtmlGestionTicket(ticketAnterior, ticketActual, cambioEstatus, cambioTecnico, cambioTipoSoporte, tecnicoActual.Nombre);
            CorreoResultado resultado = _correoService.EnviarCorreo(string.Join(";", destinatarios), asunto, html);

            if (!resultado.Ok) {
                TempData["ErrorMessage"] = $"Gestion actualizada correctamente, pero no fue posible enviar el correo. {resultado.Mensaje}";
            }
        }

        private void NotificarEvidenciaTicket(int idTicket, int idUsuarioAlta) {
            ticketsModel? ticket = ObtenerTicketCorreo(idTicket);
            if (ticket == null)
                return;

            UsuarioCorreoInfo usuarioAlta = ObtenerUsuarioCorreo(idUsuarioAlta);
            UsuarioCorreoInfo usuarioTicket = ObtenerUsuarioCorreo(ticket.IdUsuario);
            UsuarioCorreoInfo tecnico = ticket.IdTecnico.HasValue ? ObtenerUsuarioCorreo(ticket.IdTecnico.Value) : new UsuarioCorreoInfo();

            List<string> destinatarios = new List<string>();
            if (idUsuarioAlta == ticket.IdUsuario) {
                AgregarDestinatario(destinatarios, tecnico.Correo);
            } else {
                AgregarDestinatario(destinatarios, usuarioTicket.Correo);
            }

            if (destinatarios.Count == 0)
                return;

            string asunto = $"Help Desk - Nueva evidencia en ticket #{ticket.IdTicket}";
            string html = ConstruirHtmlEvidenciaTicket(ticket, usuarioAlta.Nombre);
            CorreoResultado resultado = _correoService.EnviarCorreo(string.Join(";", destinatarios), asunto, html);

            if (!resultado.Ok) {
                TempData["ErrorMessage"] = $"Evidencia agregada correctamente, pero no fue posible enviar el correo. {resultado.Mensaje}";
            }
        }

        private void NotificarTicketCerrado(int idTicket) {
            ticketsModel? ticket = ObtenerTicketCorreo(idTicket);
            if (ticket == null)
                return;

            UsuarioCorreoInfo usuarioTicket = ObtenerUsuarioCorreo(ticket.IdUsuario);
            UsuarioCorreoInfo tecnico = ticket.IdTecnico.HasValue ? ObtenerUsuarioCorreo(ticket.IdTecnico.Value) : new UsuarioCorreoInfo();

            List<string> destinatarios = new List<string>();
            AgregarDestinatario(destinatarios, usuarioTicket.Correo);
            AgregarDestinatario(destinatarios, tecnico.Correo);

            if (destinatarios.Count == 0)
                return;

            string asunto = $"Help Desk - Ticket #{ticket.IdTicket} cerrado";
            string html = ConstruirHtmlTicketCerrado(ticket);
            CorreoResultado resultado = _correoService.EnviarCorreo(string.Join(";", destinatarios), asunto, html);

            if (!resultado.Ok) {
                TempData["ErrorMessage"] = $"Ticket cerrado correctamente, pero no fue posible enviar el correo. {resultado.Mensaje}";
            }
        }

        private ticketsModel? ObtenerTicketCorreo(int idTicket) {
            using SqlConnection? cn = _dbConector.AbrirConexion();
            if (cn == null || idTicket <= 0)
                return null;

            string query = @"
                SELECT
                    t.id_Ticket,
                    t.id_Usuario,
                    t.id_Tecnico,
                    t.asunto,
                    t.problema,
                    ISNULL(ts.descripcion, '') AS tipoSoporte,
                    ISNULL(a.nombre, '') AS areaAtiende,
                    ISNULL(e.descripcion, '') AS estatus
                FROM dbo.tblTicket t
                INNER JOIN dbo.tblTipoSoporte ts ON ts.id_TipoSoporte = t.id_TipoSoporte
                LEFT JOIN dbo.tblArea a ON a.id_Area = ts.id_Area
                LEFT JOIN dbo.tblEstatus e ON e.id_Estatus = t.id_Estatus
                WHERE t.id_Ticket = @id_Ticket
                  AND t.activo = 1";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Ticket", System.Data.SqlDbType.Int).Value = idTicket;
            using SqlDataReader dr = cmd.ExecuteReader();

            if (!dr.Read())
                return null;

            return new ticketsModel {
                IdTicket = Convert.ToInt32(dr["id_Ticket"]),
                IdUsuario = Convert.ToInt32(dr["id_Usuario"]),
                IdTecnico = dr["id_Tecnico"] == DBNull.Value ? null : Convert.ToInt32(dr["id_Tecnico"]),
                Asunto = dr["asunto"].ToString() ?? string.Empty,
                Problema = dr["problema"].ToString() ?? string.Empty,
                TipoSoporte = dr["tipoSoporte"].ToString() ?? string.Empty,
                AreaAtiende = dr["areaAtiende"].ToString() ?? string.Empty,
                Estatus = dr["estatus"].ToString() ?? string.Empty
            };
        }

        private UsuarioCorreoInfo ObtenerUsuarioCorreo(int idUsuario) {
            using SqlConnection? cn = _dbConector.AbrirConexion();
            if (cn == null || idUsuario <= 0)
                return new UsuarioCorreoInfo();

            string query = @"
                SELECT
                    LTRIM(RTRIM(ISNULL(nombre, '') + ' ' + ISNULL(app, '') + ' ' + ISNULL(apm, ''))) AS nombre,
                    ISNULL(correo, '') AS correo
                FROM dbo.tblUsuario
                WHERE id_Usuario = @id_Usuario
                  AND activo = 1";

            using SqlCommand cmd = new SqlCommand(query, cn);
            cmd.Parameters.Add("@id_Usuario", System.Data.SqlDbType.Int).Value = idUsuario;
            using SqlDataReader dr = cmd.ExecuteReader();

            if (!dr.Read())
                return new UsuarioCorreoInfo();

            return new UsuarioCorreoInfo {
                Nombre = dr["nombre"].ToString() ?? string.Empty,
                Correo = dr["correo"].ToString() ?? string.Empty
            };
        }

        private string ConstruirHtmlTicketCreado(ticketsModel ticket, string usuario, string tecnico) {
            string folio = WebUtility.HtmlEncode(ticket.IdTicket.ToString());
            string asunto = WebUtility.HtmlEncode(ticket.Asunto);
            string problema = WebUtility.HtmlEncode(ticket.Problema).Replace("\n", "<br>");
            string tipoSoporte = WebUtility.HtmlEncode(ticket.TipoSoporte);
            string area = WebUtility.HtmlEncode(ticket.AreaAtiende);
            string usuarioTicket = WebUtility.HtmlEncode(string.IsNullOrWhiteSpace(usuario) ? "Usuario" : usuario);
            string tecnicoTicket = WebUtility.HtmlEncode(string.IsNullOrWhiteSpace(tecnico) ? "Sin tecnico asignado" : tecnico);

            return $@"
                <div style=""font-family: Arial, sans-serif; color: #1f2937;"">
                    <h2 style=""margin-bottom: 8px;"">Ticket #{folio} creado</h2>
                    <p>Se registro un nuevo ticket en Help Desk.</p>
                    <table style=""border-collapse: collapse; width: 100%; max-width: 720px;"">
                        <tr><td style=""padding: 6px 0; font-weight: bold;"">Asunto:</td><td>{asunto}</td></tr>
                        <tr><td style=""padding: 6px 0; font-weight: bold;"">Usuario:</td><td>{usuarioTicket}</td></tr>
                <tr><td style=""padding: 6px 0; font-weight: bold;"">Tecnico:</td><td>{tecnicoTicket}</td></tr>
                <tr><td style=""padding: 6px 0; font-weight: bold;"">Tipo soporte:</td><td>{tipoSoporte}</td></tr>
                <tr><td style=""padding: 6px 0; font-weight: bold;"">Area:</td><td>{area}</td></tr>
                    </table>
                    <h3 style=""margin-top: 18px;"">Problema</h3>
                    <p style=""white-space: normal;"">{problema}</p>
                </div>";
        }

        private string ConstruirHtmlComentarioTicket(ticketsModel ticket, string usuarioComenta, string comentario) {
            string folio = WebUtility.HtmlEncode(ticket.IdTicket.ToString());
            string asunto = WebUtility.HtmlEncode(ticket.Asunto);
            string estatus = WebUtility.HtmlEncode(ticket.Estatus);
            string usuario = WebUtility.HtmlEncode(string.IsNullOrWhiteSpace(usuarioComenta) ? "Usuario" : usuarioComenta);
            string comentarioSeguro = WebUtility.HtmlEncode(comentario).Replace("\n", "<br>");

            return $@"
                <div style=""font-family: Arial, sans-serif; color: #1f2937;"">
                    <h2 style=""margin-bottom: 8px;"">Nuevo comentario en ticket #{folio}</h2>
                    <p>Se agrego un comentario al ticket.</p>
                    <table style=""border-collapse: collapse; width: 100%; max-width: 720px;"">
                        <tr><td style=""padding: 6px 0; font-weight: bold;"">Asunto:</td><td>{asunto}</td></tr>
                        <tr><td style=""padding: 6px 0; font-weight: bold;"">Estatus:</td><td>{estatus}</td></tr>
                        <tr><td style=""padding: 6px 0; font-weight: bold;"">Comentario de:</td><td>{usuario}</td></tr>
                    </table>
                    <h3 style=""margin-top: 18px;"">Comentario</h3>
                    <p style=""white-space: normal;"">{comentarioSeguro}</p>
                </div>";
        }

        private string ConstruirHtmlGestionTicket(ticketsModel anterior, ticketsModel actual, bool cambioEstatus, bool cambioTecnico, bool cambioTipoSoporte, string tecnicoActual) {
            string folio = WebUtility.HtmlEncode(actual.IdTicket.ToString());
            string asunto = WebUtility.HtmlEncode(actual.Asunto);
            string mensajeResuelto = string.Equals(actual.Estatus, "Resuelto", StringComparison.OrdinalIgnoreCase)
                ? "<p><strong>El ticket fue marcado como Resuelto.</strong> Favor de revisar la solucion y dar el visto bueno para cerrarlo.</p>"
                : "<p>Se actualizo la gestion del ticket.</p>";

            List<string> cambios = new List<string>();
            if (cambioEstatus)
                cambios.Add($"<li><strong>Estatus:</strong> {WebUtility.HtmlEncode(anterior.Estatus)} &rarr; {WebUtility.HtmlEncode(actual.Estatus)}</li>");
            if (cambioTecnico)
                cambios.Add($"<li><strong>Tecnico asignado:</strong> {WebUtility.HtmlEncode(string.IsNullOrWhiteSpace(tecnicoActual) ? "Sin tecnico" : tecnicoActual)}</li>");
            if (cambioTipoSoporte)
                cambios.Add($"<li><strong>Tipo soporte:</strong> {WebUtility.HtmlEncode(anterior.TipoSoporte)} &rarr; {WebUtility.HtmlEncode(actual.TipoSoporte)}</li>");

            string cambiosHtml = cambios.Count > 0 ? string.Join("", cambios) : "<li>Sin cambios relevantes.</li>";

            return $@"
                <div style=""font-family: Arial, sans-serif; color: #1f2937;"">
                    <h2 style=""margin-bottom: 8px;"">Ticket #{folio} actualizado</h2>
                    {mensajeResuelto}
                    <table style=""border-collapse: collapse; width: 100%; max-width: 720px;"">
                        <tr><td style=""padding: 6px 0; font-weight: bold;"">Asunto:</td><td>{asunto}</td></tr>
                        <tr><td style=""padding: 6px 0; font-weight: bold;"">Area:</td><td>{WebUtility.HtmlEncode(actual.AreaAtiende)}</td></tr>
                    </table>
                    <h3 style=""margin-top: 18px;"">Cambios</h3>
                    <ul>{cambiosHtml}</ul>
                </div>";
        }

        private string ConstruirHtmlEvidenciaTicket(ticketsModel ticket, string usuarioAlta) {
            string folio = WebUtility.HtmlEncode(ticket.IdTicket.ToString());
            string asunto = WebUtility.HtmlEncode(ticket.Asunto);
            string estatus = WebUtility.HtmlEncode(ticket.Estatus);
            string usuario = WebUtility.HtmlEncode(string.IsNullOrWhiteSpace(usuarioAlta) ? "Usuario" : usuarioAlta);

            return $@"
                <div style=""font-family: Arial, sans-serif; color: #1f2937;"">
                    <h2 style=""margin-bottom: 8px;"">Nueva evidencia en ticket #{folio}</h2>
                    <p>Se agrego una evidencia al ticket.</p>
                    <table style=""border-collapse: collapse; width: 100%; max-width: 720px;"">
                        <tr><td style=""padding: 6px 0; font-weight: bold;"">Asunto:</td><td>{asunto}</td></tr>
                        <tr><td style=""padding: 6px 0; font-weight: bold;"">Estatus:</td><td>{estatus}</td></tr>
                        <tr><td style=""padding: 6px 0; font-weight: bold;"">Agregado por:</td><td>{usuario}</td></tr>
                    </table>
                </div>";
        }

        private string ConstruirHtmlTicketCerrado(ticketsModel ticket) {
            string folio = WebUtility.HtmlEncode(ticket.IdTicket.ToString());
            string asunto = WebUtility.HtmlEncode(ticket.Asunto);
            string tipoSoporte = WebUtility.HtmlEncode(ticket.TipoSoporte);
            string area = WebUtility.HtmlEncode(ticket.AreaAtiende);

            return $@"
                <div style=""font-family: Arial, sans-serif; color: #1f2937;"">
                    <h2 style=""margin-bottom: 8px;"">Ticket #{folio} cerrado</h2>
                    <p>El ticket fue cerrado con visto bueno del usuario.</p>
                    <table style=""border-collapse: collapse; width: 100%; max-width: 720px;"">
                        <tr><td style=""padding: 6px 0; font-weight: bold;"">Asunto:</td><td>{asunto}</td></tr>
                        <tr><td style=""padding: 6px 0; font-weight: bold;"">Tipo soporte:</td><td>{tipoSoporte}</td></tr>
                        <tr><td style=""padding: 6px 0; font-weight: bold;"">Area:</td><td>{area}</td></tr>
                    </table>
                </div>";
        }

        private void AgregarDestinatario(List<string> destinatarios, string correo) {
            if (string.IsNullOrWhiteSpace(correo))
                return;

            if (!destinatarios.Any(item => item.Equals(correo, StringComparison.OrdinalIgnoreCase)))
                destinatarios.Add(correo);
        }

        private void MarcarTicketsCerrables(List<ticketsModel> tickets, int idUsuario) {
            foreach (ticketsModel ticket in tickets) {
                ticket.PuedeCerrarTicket = ticket.IdUsuario == idUsuario
                    && string.Equals(ticket.Estatus, "Resuelto", StringComparison.OrdinalIgnoreCase);
            }
        }

        private class UsuarioCorreoInfo {
            public string Nombre { get; set; } = string.Empty;
            public string Correo { get; set; } = string.Empty;
        }
    }
}
