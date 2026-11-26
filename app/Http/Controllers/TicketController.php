<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Estatus;
use App\Models\ModuloSistema;
use App\Models\Sistema;
use App\Models\Ticket;
use App\Models\TicketComentario;
use App\Models\TicketDesarrolloDetalle;
use App\Models\TicketEvidencia;
use App\Models\TipoDesarrollo;
use App\Models\TipoEquipo;
use App\Models\TipoSoporte;
use App\Models\Usuario;
use App\Models\Vehiculo;
use App\Services\PermisosService;
use App\Services\TicketNotificacionService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class TicketController extends Controller
{
    public function __construct(
        private PermisosService $permisosService,
        private TicketNotificacionService $notificaciones,
    ) {}

    public function index()
    {
        $idUsuario = session('IdUsuario');
        $esAdmin = $this->esAdmin($idUsuario);

        $tickets = Ticket::with(['usuario', 'tecnico', 'tipoSoporte', 'estatus', 'equipo'])
            ->where('activo', true)
            ->orderBy('id_Ticket', 'desc')
            ->get();

        $misTickets = $tickets->where('id_Usuario', $idUsuario);
        $asignados = $esAdmin ? $tickets->where('id_Tecnico', $idUsuario) : collect();
        $todos = $esAdmin ? $tickets : collect();

        $tiposSoporte = TipoSoporte::where('activo', true)->get();
        $estatus = Estatus::where('activo', true)->where('id_Modulos', 1)->get();
        $equipos = $this->equiposPermitidos($idUsuario)->get();
        $tecnicos = Usuario::where('esTecnico', true)->where('activo', true)->get();
        $tiposDesarrollo = TipoDesarrollo::where('enabled', true)->get();
        $sistemas = Sistema::where('enabled', true)->get();
        $vehiculos = Vehiculo::where('activo', true)->orderBy('numeroUnidad')->get();

        return view('tickets.index', compact(
            'misTickets', 'asignados', 'todos', 'esAdmin',
            'tiposSoporte', 'estatus', 'equipos', 'tecnicos',
            'tiposDesarrollo', 'sistemas', 'vehiculos'
        ));
    }

    public function store(Request $request)
    {
        $tipoTicket = $request->input('tipoTicket', 'falla_tecnica');

        $reglas = [
            'tipoTicket' => 'required|in:'.implode(',', array_keys(Ticket::tiposTicket())),
            'asunto' => 'required|string|max:200',
            'id_TipoSoporte' => 'required|integer',
            'evidencia' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx|max:10240',
        ];

        match ($tipoTicket) {
            'instalacion_gps' => $reglas += [
                'vehiculos' => 'required|array|min:1',
                'vehiculos.*' => 'integer|exists:tblVehiculo,id_Vehiculo',
            ],
            'revision_gps' => $reglas += [
                'vehiculo' => 'required|integer|exists:tblVehiculo,id_Vehiculo',
                'problemaGps' => 'required|in:'.implode(',', Ticket::PROBLEMAS_GPS),
                'problemaGpsOtro' => 'required_if:problemaGps,__otro|nullable|string|max:190',
                'problema' => 'required|string',
            ],
            default => $reglas += [
                'problema' => 'required|string',
            ],
        };

        // PHP rechaza el archivo por los límites de php.ini antes de que
        // Laravel lo valide: se responde con el detalle (código y límites
        // que PHP está leyendo) en lugar del genérico "failed to upload".
        $archivo = $request->file('evidencia');
        if ($archivo instanceof UploadedFile && ! $archivo->isValid()) {
            return back()->withErrors([
                'evidencia' => $this->mensajeDeSubidaFallida($archivo->getError()),
            ]);
        }

        $request->validate($reglas, $this->mensajesDeEvidencia());

        $idUsuario = session('IdUsuario');

        $tipoSoporte = TipoSoporte::find($request->id_TipoSoporte);
        $estatusAbierto = Estatus::where('descripcion', 'Abierto')->where('id_Modulos', 1)->first();

        $tecnico = Usuario::where('esTecnico', true)
            ->where('id_Area', $tipoSoporte->id_Area)
            ->where('activo', true)
            ->first();

        $ticket = new Ticket;
        $ticket->id_Usuario = $idUsuario;
        $ticket->id_TipoSoporte = $request->id_TipoSoporte;
        $ticket->tipoTicket = $tipoTicket;
        $ticket->id_Tecnico = $tecnico?->id_Usuario;
        $ticket->id_Estatus = $estatusAbierto?->id_Estatus;
        $ticket->id_Equipo = $this->equipoPermitidoParaUsuario($request->input('id_Equipo'), $idUsuario);
        $ticket->asunto = $request->asunto;
        $ticket->fechaAlta = now();
        $ticket->activo = true;

        if ($tipoTicket === 'instalacion_gps') {
            $ids = Vehiculo::whereIn('id_Vehiculo', $request->input('vehiculos', []))
                ->where('activo', true)
                ->orderBy('numeroUnidad')
                ->get();
            $ticket->vehiculos = $ids->pluck('id_Vehiculo')->implode(',');
            $ticket->problema = 'Instalación de GPS — unidades: '.$ids->pluck('numeroUnidad')->implode(', ');
        } elseif ($tipoTicket === 'revision_gps') {
            $ticket->vehiculos = (string) (int) $request->vehiculo;
            $ticket->problemaGps = $request->problemaGps === '__otro'
                ? 'Otro: '.trim($request->problemaGpsOtro)
                : $request->problemaGps;
            $ticket->problema = $request->problema;
        } else {
            $ticket->problema = $request->problema;
        }

        $ticket->save();

        $errorEvidencia = null;
        if ($request->hasFile('evidencia')) {
            try {
                $this->registrarEvidencia(
                    $request->file('evidencia'),
                    (int) $ticket->id_Ticket,
                    (int) $idUsuario,
                );
            } catch (RuntimeException $e) {
                // El ticket ya quedó guardado: se conserva y se avisa que la
                // evidencia no se guardó (el alta no se revierte).
                $errorEvidencia = 'El ticket fue creado, pero la evidencia no se pudo guardar: '.$e->getMessage();
            }
        }

        if ($tecnico) {
            $this->notificaciones->notificar($ticket, $tecnico, 'creado');
        }

        if ($errorEvidencia !== null) {
            return redirect()->route('tickets.index')->with('error', $errorEvidencia);
        }

        return redirect()->route('tickets.index')->with('success', 'Ticket #'.$ticket->id_Ticket.' creado correctamente');
    }

    public function storeDesarrollo(Request $request)
    {
        $request->validate([
            'asunto' => 'required|string|max:100',
            'descripcionTicket' => 'required|string',
            'id_TipoSoporte' => 'required|integer',
            'id_TipoDesarrollo' => 'required|integer',
            'id_Sistemas' => 'required|integer',
            'id_Modulo' => 'required|integer',
            // Opcional: no todos los módulos tienen submódulos.
            'id_Submodulo' => 'nullable|integer',
            'fechaEstimadaTermino' => 'nullable|date',
            'id_Tecnico' => 'required|integer',
        ]);

        $idUsuario = session('IdUsuario');
        $estatusAbierto = Estatus::where('descripcion', 'Abierto')->where('id_Modulos', 1)->first();

        $ticket = new Ticket;
        $ticket->id_Usuario = $idUsuario;
        $ticket->id_TipoSoporte = $request->id_TipoSoporte;
        $ticket->id_Tecnico = $request->id_Tecnico;
        $ticket->id_Estatus = $estatusAbierto?->id_Estatus;
        $ticket->asunto = $request->asunto;
        $ticket->problema = $request->descripcionTicket;
        $ticket->tipoTicket = 'desarrollo';
        $ticket->fechaAlta = now();
        $ticket->activo = true;
        $ticket->save();

        // Detalle de desarrollo: alimenta el reporte KPI Desarrollo
        // (módulo/submódulo/tipo). Sin esto tblTicketDesarrolloDetalle
        // queda vacía y el reporte sale sin clasificar.
        TicketDesarrolloDetalle::create([
            'id_Ticket' => $ticket->id_Ticket,
            'id_TipoDesarrollo' => $request->id_TipoDesarrollo,
            'id_Sistemas' => $request->id_Sistemas,
            'id_Modulo' => $request->id_Modulo,
            'id_Submodulo' => $request->filled('id_Submodulo') ? $request->id_Submodulo : null,
            'fechaEstimadaTermino' => $request->filled('fechaEstimadaTermino') ? $request->fechaEstimadaTermino : null,
        ]);

        $tecnico = Usuario::find($request->id_Tecnico);
        if ($tecnico) {
            $this->notificaciones->notificar($ticket, $tecnico, 'creado', [
                'Sistema' => Sistema::find($request->id_Sistemas)?->nombre,
                'Módulo' => ModuloSistema::find($request->id_Modulo)?->nombre,
                'Tipo de desarrollo' => TipoDesarrollo::find($request->input('id_TipoDesarrollo'))?->nombre,
            ]);
        }

        return redirect()->route('tickets.index')->with('success', 'Ticket de desarrollo #'.$ticket->id_Ticket.' creado correctamente');
    }

    public function show($id)
    {
        $ticket = Ticket::with(['usuario', 'tecnico', 'tipoSoporte', 'estatus', 'equipo',
            'comentarios.usuario', 'comentarios.estatus', 'evidencias.usuario'])
            ->findOrFail($id);

        $estatus = Estatus::where('activo', true)->where('id_Modulos', 1)->get();
        $tecnicos = Usuario::where('esTecnico', true)->where('activo', true)->get();
        $idUsuario = session('IdUsuario');
        $esAdmin = $this->esAdmin($idUsuario);

        return response()->json([
            'ticket' => $ticket,
            'vehiculos' => $ticket->vehiculosDelTicket(),
            'tipoTicketLabel' => $ticket->etiquetaTipoTicket(),
            'estatus' => $estatus,
            'tecnicos' => $tecnicos,
            'esAdmin' => $esAdmin,
            'esPropietario' => $ticket->id_Usuario == $idUsuario,
        ]);
    }

    public function addComment(Request $request)
    {
        $request->validate([
            'id_Ticket' => 'required|integer',
            'comentario' => 'required|string',
        ]);

        $idUsuario = session('IdUsuario');
        $ticket = Ticket::find($request->id_Ticket);

        if (! $ticket || $ticket->estatus->descripcion == 'Cerrado') {
            return response()->json(['error' => 'Ticket cerrado o no encontrado'], 400);
        }

        TicketComentario::create([
            'id_Ticket' => $request->id_Ticket,
            'comentario' => $request->comentario,
            'fechaComentario' => now(),
            'id_Usuario' => $idUsuario,
            'id_Estatus' => $ticket->id_Estatus,
            'fechaAlta' => now(),
            'activo' => true,
        ]);

        return response()->json(['success' => true, 'message' => 'Comentario agregado']);
    }

    public function addEvidence(Request $request)
    {
        // PHP rechaza el archivo por los límites de php.ini antes de que
        // Laravel lo valide: sin este aviso el usuario solo ve el genérico
        // "The evidencia failed to upload." (HTTP 422).
        $archivo = $request->file('evidencia');
        if ($archivo instanceof UploadedFile && ! $archivo->isValid()) {
            return response()->json([
                'success' => false,
                'errors' => ['evidencia' => [$this->mensajeDeSubidaFallida($archivo->getError())]],
            ], 422);
        }

        $request->validate([
            'id_Ticket' => 'required|integer',
            'evidencia' => 'required|file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx|max:10240',
        ], $this->mensajesDeEvidencia());

        $idUsuario = session('IdUsuario');

        try {
            $this->registrarEvidencia($archivo, (int) $request->id_Ticket, (int) $idUsuario);
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'message' => 'Evidencia agregada']);
    }

    public function viewEvidence($idEvidencia)
    {
        $evidencia = TicketEvidencia::find($idEvidencia);

        if (! $evidencia) {
            abort(404);
        }

        // Registros que quedaron con fileId (época de Drive) se redirigen a
        // su enlace; el resto se sirve en local desde public/.
        if ($evidencia->driveFileId) {
            return redirect()->away($evidencia->url);
        }

        $filePath = public_path($evidencia->rutaArchivo);

        if (! File::exists($filePath)) {
            abort(404);
        }

        $mimeType = $evidencia->tipoContenido ?? File::mimeType($filePath);

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.$evidencia->nombreArchivo.'"',
        ]);
    }

    public function updateManagement(Request $request)
    {
        $request->validate([
            'id_Ticket' => 'required|integer',
            'id_Estatus' => 'required|integer',
            'id_Tecnico' => 'nullable|integer',
        ]);

        $idUsuario = session('IdUsuario');
        $ticket = Ticket::find($request->id_Ticket);

        if (! $ticket) {
            return response()->json(['error' => 'Ticket no encontrado'], 404);
        }

        $cambios = [];
        $tecnicoNuevo = null;
        if ($ticket->id_Estatus != $request->id_Estatus) {
            $estatus = Estatus::find($request->id_Estatus);
            $cambios[] = 'Estatus: '.$estatus->descripcion;
            $ticket->id_Estatus = $request->id_Estatus;

            if ($estatus->descripcion == 'Resuelto') {
                $ticket->fechaCierre = now();
            }
        }

        if ($request->id_Tecnico && $ticket->id_Tecnico != $request->id_Tecnico) {
            $tecnicoNuevo = Usuario::find($request->id_Tecnico);
            $cambios[] = 'Técnico: '.$tecnicoNuevo->nombre.' '.$tecnicoNuevo->app;
            $ticket->id_Tecnico = $request->id_Tecnico;
        }

        $ticket->save();

        if (! empty($cambios)) {
            TicketComentario::create([
                'id_Ticket' => $ticket->id_Ticket,
                'comentario' => 'Gestión actualizada: '.implode(', ', $cambios),
                'fechaComentario' => now(),
                'id_Usuario' => $idUsuario,
                'id_Estatus' => $ticket->id_Estatus,
                'fechaAlta' => now(),
                'activo' => true,
            ]);
        }

        if ($tecnicoNuevo) {
            $this->notificaciones->notificar($ticket, $tecnicoNuevo, 'reasignado');
        }

        return response()->json(['success' => true, 'message' => 'Gestión actualizada']);
    }

    public function closeWithApproval($id)
    {
        $idUsuario = session('IdUsuario');
        $ticket = Ticket::with('estatus')->findOrFail($id);

        if ($ticket->id_Usuario != $idUsuario) {
            return response()->json(['error' => 'Solo el propietario puede cerrar el ticket'], 403);
        }

        if ($ticket->estatus->descripcion != 'Resuelto') {
            return response()->json(['error' => 'El ticket debe estar resuelto para cerrarlo'], 400);
        }

        $estatusCerrado = Estatus::where('descripcion', 'Cerrado')->where('id_Modulos', 1)->first();
        $ticket->id_Estatus = $estatusCerrado->id_Estatus;
        $ticket->fechaCierre = now();
        $ticket->save();

        TicketComentario::create([
            'id_Ticket' => $ticket->id_Ticket,
            'comentario' => 'Ticket cerrado con visto bueno por el usuario',
            'fechaComentario' => now(),
            'id_Usuario' => $idUsuario,
            'id_Estatus' => $estatusCerrado->id_Estatus,
            'fechaAlta' => now(),
            'activo' => true,
        ]);

        return response()->json(['success' => true, 'message' => 'Ticket cerrado correctamente']);
    }

    public function obtenerModulos($idSistema)
    {
        $modulos = DB::table('tblModulosSistemas')
            ->where('id_sistemas', $idSistema)
            ->where('enabled', true)
            ->get();

        return response()->json($modulos);
    }

    public function obtenerSubmodulos($idModulo)
    {
        $submodulos = DB::table('tblSubmodulosSistemas')
            ->where('id_ModulosSistemas', $idModulo)
            ->where('enabled', true)
            ->get();

        return response()->json($submodulos);
    }

    /**
     * Equipos que el usuario puede vincular a un ticket de soporte:
     * impresoras activas (cualquiera) o equipos con asignación vigente
     * al usuario (activo y sin fechaBaja).
     */
    private function equiposPermitidos(int $idUsuario)
    {
        $idImpresora = TipoEquipo::where('descripcion', 'Impresora')->value('id_TipoEquipo');

        return Equipo::where('activo', true)
            ->where(function ($q) use ($idUsuario, $idImpresora) {
                if ($idImpresora !== null) {
                    $q->where('id_TipoEquipo', $idImpresora);
                }
                $q->orWhereHas('asignaciones', function ($a) use ($idUsuario) {
                    $a->where('id_Usuario', $idUsuario)
                        ->where('activo', true)
                        ->whereNull('fechaBaja');
                });
            });
    }

    /**
     * Devuelve el id_Equipo solo si está en la lista permitida;
     * en cualquier otro caso null ("Sin equipo"). No interrumpe el alta.
     */
    private function equipoPermitidoParaUsuario(?string $idEquipo, int $idUsuario): ?int
    {
        if ($idEquipo === null || $idEquipo === '') {
            return null;
        }

        $permitido = $this->equiposPermitidos($idUsuario)
            ->where('id_Equipo', $idEquipo)
            ->exists();

        return $permitido ? (int) $idEquipo : null;
    }

    private function esAdmin($idUsuario)
    {
        return $this->permisosService->esAdministrador($idUsuario);
    }

    /**
     * Guarda la evidencia en public/evidencias/tickets/{id}/ y la registra
     * en tblTicketEvidencia con la ruta relativa (sin driveFileId).
     *
     * @throws RuntimeException si la carpeta no se puede crear o escribir
     *                          (permisos del sitio web en public/evidencias).
     */
    private function registrarEvidencia(UploadedFile $archivo, int $idTicket, int $idUsuario): TicketEvidencia
    {
        // MIME y tamaño se leen ANTES de mover: el UploadedFile no actualiza
        // su ruta temporal al moverse, así que getMimeType()/getSize() después
        // del movimiento lanzan "file does not exist" (HTTP 500).
        $mime = $archivo->getMimeType();
        $tamanio = $archivo->getSize();
        $extension = $archivo->getClientOriginalExtension();
        $base = Str::slug(pathinfo($archivo->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'evidencia';
        $filename = time().'_'.$base.'.'.$extension;

        $rutaDestino = public_path('evidencias/tickets/'.$idTicket);
        if (! is_dir($rutaDestino) && ! @mkdir($rutaDestino, 0755, true) && ! is_dir($rutaDestino)) {
            throw new RuntimeException('no se pudo crear la carpeta public/evidencias/tickets/'.$idTicket.'. Revisa los permisos de la carpeta public/evidencias/ en el servidor.');
        }

        try {
            $archivo->move($rutaDestino, $filename);
        } catch (\Throwable $e) {
            throw new RuntimeException('no se pudo escribir el archivo en public/evidencias/. Revisa los permisos de la carpeta en el servidor.', 0, $e);
        }

        return TicketEvidencia::create([
            'id_Ticket' => $idTicket,
            'nombreArchivo' => $filename,
            'rutaArchivo' => 'evidencias/tickets/'.$idTicket.'/'.$filename,
            'extension' => $extension,
            'tipoContenido' => $mime,
            'tamanioBytes' => $tamanio,
            'id_UsuarioAlta' => $idUsuario,
            'fechaAlta' => now(),
            'activo' => true,
        ]);
    }

    /**
     * Traduce un error de subida de PHP (UPLOAD_ERR_*) a un mensaje
     * accionable: PHP rechaza el archivo antes de que Laravel lo valide.
     * Para el caso más común se incluyen los límites reales que el sitio
     * web está leyendo, así se distingue de inmediato un php.ini mal
     * apuntado o sin reiniciar de un archivo que simplemente es muy grande.
     */
    private function mensajeDeSubidaFallida(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => sprintf(
                'El archivo supera el límite de PHP (error %d): upload_max_filesize = %s y post_max_size = %s en el php.ini que usa el sitio web. Edita ese php.ini (verifícalo con phpinfo), pon 10M / 12M y reinicia el servicio web.',
                $error,
                ini_get('upload_max_filesize') ?: 'sin valor',
                ini_get('post_max_size') ?: 'sin valor',
            ),
            UPLOAD_ERR_PARTIAL => 'La subida se interrumpió a mitad de archivo: vuelve a intentarlo.',
            UPLOAD_ERR_NO_TMP_DIR => 'El servidor no tiene directorio temporal (tmp) configurado en php.ini.',
            UPLOAD_ERR_CANT_WRITE => 'El servidor no pudo escribir el archivo temporal: revisa permisos y espacio en disco.',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP bloqueó la subida del archivo.',
            default => 'No se pudo subir el archivo (error de PHP '.$error.'): vuelve a intentarlo.',
        };
    }

    /**
     * Mensajes en español de las reglas de evidencia (modal SweetAlert y
     * banner de errores del alta de ticket).
     *
     * @return array<string, string>
     */
    private function mensajesDeEvidencia(): array
    {
        return [
            'evidencia.required' => 'Selecciona un archivo de evidencia.',
            'evidencia.uploaded' => 'El archivo fue rechazado por el servidor: revisa upload_max_filesize / post_max_size en php.ini.',
            'evidencia.mimes' => 'Formato no permitido: usa JPG, JPEG, PNG, GIF, WEBP, PDF, DOC o DOCX.',
            'evidencia.max' => 'La evidencia no puede pesar más de 10 MB.',
        ];
    }
}
