<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\HistorialEquipoUsuario;

class EquipoHistorialController extends Controller
{
    public function index()
    {
        $historial = HistorialEquipoUsuario::with(['usuario', 'usuarioAccion', 'area'])
            ->orderBy('fechaAccion', 'desc')
            ->get();

        return view('inventario.historial', compact('historial'));
    }
}
