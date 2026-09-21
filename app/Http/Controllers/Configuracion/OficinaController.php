<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Oficina;
use Illuminate\Http\Request;

class OficinaController extends Controller
{
    public function index()
    {
        $oficinas = Oficina::all();
        return view('configuracion.oficinas', compact('oficinas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:5|unique:tblOficinas,nombre',
            'descripcion' => 'nullable|string|max:250',
        ]);
        Oficina::create([
            'nombre' => strtoupper($request->nombre),
            'descripcion' => $request->descripcion,
            'fechaAlta' => now(),
            'activo' => true,
        ]);
        return redirect()->route('configuracion.oficinas.index')->with('success', 'Oficina creada');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:5|unique:tblOficinas,nombre,' . $id . ',id_Oficinas',
            'descripcion' => 'nullable|string|max:250',
        ]);
        Oficina::findOrFail($id)->update([
            'nombre' => strtoupper($request->nombre),
            'descripcion' => $request->descripcion,
        ]);
        return redirect()->route('configuracion.oficinas.index')->with('success', 'Oficina actualizada');
    }

    public function toggleActivo($id)
    {
        $oficina = Oficina::findOrFail($id);
        $oficina->activo = !$oficina->activo;
        $oficina->save();
        return back()->with('success', $oficina->activo ? 'Activada' : 'Desactivada');
    }
}
