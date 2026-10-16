<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketComentario;
use App\Models\TicketEvidencia;
use App\Models\TipoSoporte;
use App\Models\Estatus;
use App\Models\Equipo;
use App\Models\TipoEquipo;
use App\Models\TipoDesarrollo;
use App\Models\Sistema;
use App\Models\ModuloSistema;
use App\Models\Usuario;
use App\Models\Vehiculo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Services\PermisosService;
use App\Services\TicketNotificacionService;

class TicketController extends Controller
{
    public function __construct(
        private PermisosService $permisosService,
        private TicketNotificacionService $notificaciones,
    ) {
    }
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
            'tipoTicket' => 'required|in:' . implode(',', array_keys(Ticket::tiposTicket())),
            'asunto' => 'required|string|max:200',
            'id_TipoSoporte' => 'required|integer',
        ];

        match ($tipoTicket) {
            'instalacion_gps' => $reglas += [
                'vehiculos' => 'required|array|min:1',
                'vehiculos.*' => 'integer|exists:tblVehiculo,id_Vehiculo',
            ],
            'revision_gps' => $reglas += [
                'vehiculo' => 'required|integer|exists:tblVehiculo,id_Vehiculo',
                'problemaGps' => 'required|in:' . implode(',', Ticket::PROBLEMAS_GPS),
                'problemaGpsOtro' => 'required_if:problemaGps,__otro|nullable|string|max:190',
                'problema' => 'required|string',
            ],
            default => $reglas += [
                'problema' => 'required|string',
            ],
        };

        $request->validate($reglas);

        $idUsuario = session('IdUsuario');

        $tipoSoporte = TipoSoporte::find($request->id_TipoSoporte);
        $estatusAbierto = Estatus::where('descripcion', 'Abierto')->where('id_Modulos', 1)->first();

        $tecnico = Usuario::where('esTecnico', true)
            ->where('id_Area', $tipoSoporte->id_Area)
            ->where('activo', true)
            ->first();

        $ticket = new Ticket();
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
            $ticket->problema = 'Instalación de GPS — unidades: ' . $ids->pluck('numeroUnidad')->implode(', ');
        } elseif ($tipoTicket === 'revision_gps') {
            $ticket->vehiculos = (string) (int) $request->vehiculo;
            $ticket->problemaGps = $request->problemaGps === '__otro'
                ? 'Otro: ' . trim($request->problemaGpsOtro)
                : $request->problemaGps;
            $ticket->problema = $request->problema;
        } else {
            $ticket->problema = $request->problema;
        }

        $ticket->save();

        if ($request->hasFile('evidencia')) {
            $file = $request->file('evidencia');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = public_path('evidencias/tickets/' . $ticket->id_Ticket);
            if (!is_dir($path)) mkdir($path, 0755, true);
            $file->move($path, $filename);

            TicketEvidencia::create([
                'id_Ticket' => $ticket->id_Ticket,
                'nombreArchivo' => $filename,
                'rutaArchivo' => 'evidencias/tickets/' . $ticket->id_Ticket . '/' . $filename,
                'extension' => $file->getClientOriginalExtension(),
                'tipoContenido' => $file->getMimeType(),
                'tamanioBytes' => $file->getSize(),
                'id_UsuarioAlta' => $idUsuario,
                'fechaAlta' => now(),
                'activo' => true,
            ]);
        }

        if ($tecnico) {
            $this->notificaciones->notificar($ticket, $tecnico, 'creado');
        }

        return redirect()->route('tickets.index')->with('success', 'Ticket #' . $ticket->id_Ticket . ' creado correctamente');
    }

    public function storeDesarrollo(Request $request)
    {
        $request->validate([
            'asunto' => 'required|string|max:100',
            'descripcionTicket' => 'required|string',
            'id_TipoSoporte' => 'required|integer',
            'id_Sistemas' => 'required|integer',
            'id_Modulo' => 'required|integer',
            'id_Tecnico' => 'required|integer',
        ]);

        $idUsuario = session('IdUsuario');
        $estatusAbierto = Estatus::where('descripcion', 'Abierto')->where('id_Modulos', 1)->first();

        $ticket = new Ticket();
        $ticket->id_Usuario = $idUsuario;
        $ticket->id_TipoSoporte = $request->id_TipoSoporte;
        $ticket->id_Tecnico = $request->id_Tecnico;
        $ticket->id_Estatus = $estatusAbierto?->id_Estatus;
        $ticket->asunto = $request->asunto;
        $ticket->problema = $request->descripcionTicket;
        $ticket->fechaAlta = now();
        $ticket->activo = true;
        $ticket->save();

        $tecnico = Usuario::find($request->id_Tecnico);
        if ($tecnico) {
            $this->notificaciones->notificar($ticket, $tecnico, 'creado', [
                'Sistema' => Sistema::find($request->id_Sistemas)?->nombre,
                'Módulo' => ModuloSistema::find($request->id_Modulo)?->nombre,
                'Tipo de desarrollo' => TipoDesarrollo::find($request->input('id_TipoDesarrollo'))?->nombre,
            ]);
        }

        return redirect()->route('tickets.index')->with('success', 'Ticket de desarrollo #' . $ticket->id_Ticket . ' creado correctamente');
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

        if (!$ticket || $ticket->estatus->descripcion == 'Cerrado') {
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
        $request->validate([
            'id_Ticket' => 'required|integer',
            'evidencia' => 'required|file|max:10240',
        ]);

        $idUsuario = session('IdUsuario');
        $file = $request->file('evidencia');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = public_path('evidencias/tickets/' . $request->id_Ticket);
        if (!is_dir($path)) mkdir($path, 0755, true);
        $file->move($path, $filename);

        TicketEvidencia::create([
            'id_Ticket' => $request->id_Ticket,
            'nombreArchivo' => $filename,
            'rutaArchivo' => 'evidencias/tickets/' . $request->id_Ticket . '/' . $filename,
            'extension' => $file->getClientOriginalExtension(),
            'tipoContenido' => $file->getMimeType(),
            'tamanioBytes' => $file->getSize(),
            'id_UsuarioAlta' => $idUsuario,
            'fechaAlta' => now(),
            'activo' => true,
        ]);

        return response()->json(['success' => true, 'message' => 'Evidencia agregada']);
    }

    public function viewEvidence($idEvidencia)
    {
        $evidencia = TicketEvidencia::find($idEvidencia);

        if (!$evidencia) {
            abort(404);
        }

        $filePath = public_path($evidencia->rutaArchivo);

        if (!File::exists($filePath)) {
            abort(404);
        }

        $mimeType = $evidencia->tipoContenido ?? File::mimeType($filePath);

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $evidencia->nombreArchivo . '"',
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

        if (!$ticket) {
            return response()->json(['error' => 'Ticket no encontrado'], 404);
        }

        $cambios = [];
        $tecnicoNuevo = null;
        if ($ticket->id_Estatus != $request->id_Estatus) {
            $estatus = Estatus::find($request->id_Estatus);
            $cambios[] = 'Estatus: ' . $estatus->descripcion;
            $ticket->id_Estatus = $request->id_Estatus;

            if ($estatus->descripcion == 'Resuelto') {
                $ticket->fechaCierre = now();
            }
        }

        if ($request->id_Tecnico && $ticket->id_Tecnico != $request->id_Tecnico) {
            $tecnicoNuevo = Usuario::find($request->id_Tecnico);
            $cambios[] = 'Técnico: ' . $tecnicoNuevo->nombre . ' ' . $tecnicoNuevo->app;
            $ticket->id_Tecnico = $request->id_Tecnico;
        }

        $ticket->save();

        if (!empty($cambios)) {
            TicketComentario::create([
                'id_Ticket' => $ticket->id_Ticket,
                'comentario' => 'Gestión actualizada: ' . implode(', ', $cambios),
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
}
