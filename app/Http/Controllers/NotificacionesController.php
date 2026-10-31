<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\Request;

class NotificacionesController extends Controller
{
    public function obtener()
    {
        $idUsuario = session('IdUsuario');

        $notificaciones = Notificacion::where('id_UsuarioDestino', $idUsuario)
            ->orderBy('FechaCreacion', 'desc')
            ->get();

        $noLeidas = Notificacion::where('id_UsuarioDestino', $idUsuario)
            ->where('Leido', false)
            ->count();

        return response()->json([
            'notificaciones' => $notificaciones,
            'noLeidas' => $noLeidas,
        ]);
    }

    public function marcarLeido(Request $request)
    {
        $request->validate([
            'id_Notificacion' => 'required|integer',
        ]);

        $idUsuario = session('IdUsuario');

        Notificacion::where('id_Notificacion', $request->id_Notificacion)
            ->where('id_UsuarioDestino', $idUsuario)
            ->update(['Leido' => true]);

        $noLeidas = Notificacion::where('id_UsuarioDestino', $idUsuario)
            ->where('Leido', false)
            ->count();

        return response()->json([
            'success' => true,
            'noLeidas' => $noLeidas,
        ]);
    }

    public function marcarTodasLeido()
    {
        $idUsuario = session('IdUsuario');

        Notificacion::where('id_UsuarioDestino', $idUsuario)
            ->where('Leido', false)
            ->update(['Leido' => true]);

        return response()->json([
            'success' => true,
            'noLeidas' => 0,
        ]);
    }
}
