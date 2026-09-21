<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
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

        $stats = [
            'mis_tickets' => Ticket::where('id_Usuario', $idUsuario)->where('activo', true)->count(),
            'asignados' => Ticket::where('id_Tecnico', $idUsuario)->where('activo', true)->count(),
            'abiertos' => Ticket::where('activo', true)->where('id_Estatus', DB::table('tblEstatus')->where('descripcion', 'Abierto')->where('id_Modulos', 1)->value('id_Estatus'))->count(),
            'cerrados' => Ticket::where('activo', true)->where('id_Estatus', DB::table('tblEstatus')->where('descripcion', 'Cerrado')->where('id_Modulos', 1)->value('id_Estatus'))->count(),
        ];

        $recentTickets = Ticket::with(['tipoSoporte', 'estatus'])
            ->where('activo', true)
            ->orderBy('id_Ticket', 'desc')
            ->limit(10)
            ->get();

        $usuario = auth()->user();
        return view('dashboard', compact('usuario', 'stats', 'recentTickets'));
    }
}
