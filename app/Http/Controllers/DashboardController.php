<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Equipo;
use App\Models\Usuario;
use App\Services\PermisosService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(private PermisosService $permisosService)
    {
    }

    public function index()
    {
        $idUsuario = session('IdUsuario');
        $esAdmin = $this->permisosService->esAdministrador($idUsuario);
        $esTecnico = Usuario::where('id_Usuario', $idUsuario)->value('esTecnico');

        $estatusAbierto = DB::table('tblEstatus')->where('descripcion', 'Abierto')->where('id_Modulos', 1)->value('id_Estatus');
        $estatusResuelto = DB::table('tblEstatus')->where('descripcion', 'Resuelto')->where('id_Modulos', 1)->value('id_Estatus');
        $estatusCerrado = DB::table('tblEstatus')->where('descripcion', 'Cerrado')->where('id_Modulos', 1)->value('id_Estatus');

        $stats = [
            'mis_tickets' => Ticket::where('id_Usuario', $idUsuario)->where('activo', true)->count(),
            'asignados' => Ticket::where('id_Tecnico', $idUsuario)->where('activo', true)->count(),
            'abiertos' => $esAdmin ? Ticket::where('activo', true)->where('id_Estatus', $estatusAbierto)->count() : 0,
            'resueltos' => $esAdmin ? Ticket::where('activo', true)->where('id_Estatus', $estatusResuelto)->count() : 0,
            'cerrados' => $esAdmin ? Ticket::where('activo', true)->where('id_Estatus', $estatusCerrado)->count() : 0,
            'total_tickets' => $esAdmin ? Ticket::where('activo', true)->count() : 0,
            'total_equipos' => $esAdmin ? Equipo::where('activo', true)->count() : 0,
            'total_usuarios' => $esAdmin ? Usuario::where('activo', true)->count() : 0,
        ];

        $recentTickets = Ticket::with(['usuario', 'tecnico', 'tipoSoporte', 'estatus'])
            ->where('activo', true)
            ->orderBy('id_Ticket', 'desc')
            ->limit(10)
            ->get();

        if (!$esAdmin) {
            $recentTickets = $recentTickets->filter(function ($t) use ($idUsuario, $esTecnico) {
                return $t->id_Usuario == $idUsuario || ($esTecnico && $t->id_Tecnico == $idUsuario);
            });
        }

        $usuario = auth()->user();
        return view('dashboard', compact('usuario', 'stats', 'recentTickets', 'esAdmin', 'esTecnico'));
    }
}
