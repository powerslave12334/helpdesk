<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Modulo;
use Illuminate\Http\Request;

class ModuloController extends Controller
{
    public function index()
    {
        $modulos = Modulo::all();
        return view('configuracion.modulos', compact('modulos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_Modulos' => 'required|integer|unique:tblModulos,id_Modulos',
        ]);

        Modulo::create(['id_Modulos' => $request->id_Modulos]);
        return redirect()->route('configuracion.modulos.index')->with('success', 'Módulo creado correctamente');
    }

    public function update(Request $request, $id)
    {
        // Modulo table has only id_Modulos as PK, no other fields to update
        return redirect()->route('configuracion.modulos.index');
    }
}
