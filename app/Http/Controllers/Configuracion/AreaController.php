<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Area;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function index()
    {
        $areas = Area::all();
        return view('configuracion.areas', compact('areas'));
    }

    public function store(Request $request)
    {
        $request->validate(['nombre' => 'required|string|max:100|unique:tblArea,nombre']);
        Area::create(['nombre' => $request->nombre, 'activo' => true]);
        return redirect()->route('configuracion.areas.index')->with('success', 'Área creada correctamente');
    }

    public function update(Request $request, $id)
    {
        $request->validate(['nombre' => 'required|string|max:100|unique:tblArea,nombre,' . $id . ',id_Area']);
        Area::findOrFail($id)->update(['nombre' => $request->nombre]);
        return redirect()->route('configuracion.areas.index')->with('success', 'Área actualizada correctamente');
    }

    public function toggleActivo($id)
    {
        $area = Area::findOrFail($id);
        $area->activo = !$area->activo;
        $area->save();
        return back()->with('success', $area->activo ? 'Área activada' : 'Área desactivada');
    }
}
