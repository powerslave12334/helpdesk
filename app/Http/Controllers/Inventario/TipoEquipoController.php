<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\TipoEquipo;
use Illuminate\Http\Request;

class TipoEquipoController extends Controller
{
    public function index()
    {
        $tipos = TipoEquipo::all();
        return view('inventario.tipo_equipo', compact('tipos'));
    }

    public function store(Request $request)
    {
        $request->validate(['descripcion' => 'required|string|max:100|unique:tblTipoEquipo,descripcion']);
        TipoEquipo::create([
            'descripcion' => $request->descripcion,
            'fechaAlta' => now(),
            'activo' => true,
        ]);
        return redirect()->route('inventario.tipoEquipo.index')->with('success', 'Tipo de equipo creado correctamente');
    }

    public function update(Request $request, $id)
    {
        $request->validate(['descripcion' => 'required|string|max:100|unique:tblTipoEquipo,descripcion,' . $id . ',id_TipoEquipo']);
        TipoEquipo::findOrFail($id)->update(['descripcion' => $request->descripcion]);
        return redirect()->route('inventario.tipoEquipo.index')->with('success', 'Tipo de equipo actualizado correctamente');
    }

    public function toggleActivo($id)
    {
        $tipo = TipoEquipo::findOrFail($id);
        $tipo->activo = !$tipo->activo;
        $tipo->save();
        return back()->with('success', $tipo->activo ? 'Tipo activado' : 'Tipo desactivado');
    }
}
