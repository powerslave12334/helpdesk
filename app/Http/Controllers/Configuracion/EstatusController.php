<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Estatus;
use Illuminate\Http\Request;

class EstatusController extends Controller
{
    public function index()
    {
        $estatus = Estatus::with('modulo')->get();
        return view('configuracion.estatus', compact('estatus'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'descripcion' => 'required|string|max:100',
            'id_Modulos' => 'required|integer',
        ]);

        Estatus::create([
            'descripcion' => $request->descripcion,
            'id_Modulos' => $request->id_Modulos,
            'activo' => true,
        ]);

        return redirect()->route('configuracion.estatus.index')->with('success', 'Estatus creado correctamente');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'descripcion' => 'required|string|max:100',
            'id_Modulos' => 'required|integer',
        ]);

        $estatus = Estatus::findOrFail($id);
        $estatus->update([
            'descripcion' => $request->descripcion,
            'id_Modulos' => $request->id_Modulos,
        ]);

        return redirect()->route('configuracion.estatus.index')->with('success', 'Estatus actualizado correctamente');
    }

    public function toggleActivo($id)
    {
        $estatus = Estatus::findOrFail($id);
        $estatus->activo = !$estatus->activo;
        $estatus->save();
        return back()->with('success', $estatus->activo ? 'Estatus activado' : 'Estatus desactivado');
    }
}
