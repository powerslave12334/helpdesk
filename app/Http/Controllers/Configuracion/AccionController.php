<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Accion;
use Illuminate\Http\Request;

class AccionController extends Controller
{
    public function index()
    {
        $acciones = Accion::all();
        return view('configuracion.acciones', compact('acciones'));
    }

    public function store(Request $request)
    {
        $request->validate(['nombre' => 'required|string|max:50|unique:tblAcciones,nombre']);
        Accion::create(['nombre' => $request->nombre, 'fechaAlta' => now(), 'activo' => true]);
        return redirect()->route('configuracion.acciones.index')->with('success', 'Acción creada');
    }

    public function update(Request $request, $id)
    {
        $request->validate(['nombre' => 'required|string|max:50|unique:tblAcciones,nombre,' . $id . ',id_Accion']);
        Accion::findOrFail($id)->update(['nombre' => $request->nombre]);
        return redirect()->route('configuracion.acciones.index')->with('success', 'Acción actualizada');
    }

    public function toggleActivo($id)
    {
        $accion = Accion::findOrFail($id);
        $accion->activo = !$accion->activo;
        $accion->save();
        return back()->with('success', $accion->activo ? 'Activado' : 'Desactivado');
    }
}
