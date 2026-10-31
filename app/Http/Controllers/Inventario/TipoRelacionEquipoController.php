<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\TipoRelacionEquipo;
use Illuminate\Http\Request;

class TipoRelacionEquipoController extends Controller
{
    public function index()
    {
        $tipos = TipoRelacionEquipo::all();
        return view('inventario.tipo_relacion', compact('tipos'));
    }

    public function store(Request $request)
    {
        $request->validate(['descripcion' => 'required|string|max:100|unique:tblTipoRelacionEquipo,descripcion']);
        TipoRelacionEquipo::create([
            'descripcion' => $request->descripcion,
            'fechaAlta' => now(),
            'activo' => true,
        ]);
        return redirect()->route('inventario.tipoRelacion.index')->with('success', 'Tipo de relación creado correctamente');
    }

    public function update(Request $request, $id)
    {
        $request->validate(['descripcion' => 'required|string|max:100|unique:tblTipoRelacionEquipo,descripcion,' . $id . ',id_TipoRelacionEquipo']);
        TipoRelacionEquipo::findOrFail($id)->update(['descripcion' => $request->descripcion]);
        return redirect()->route('inventario.tipoRelacion.index')->with('success', 'Tipo de relación actualizado');
    }

    public function toggleActivo($id)
    {
        $tipo = TipoRelacionEquipo::findOrFail($id);
        $tipo->activo = !$tipo->activo;
        $tipo->save();
        return back()->with('success', $tipo->activo ? 'Activado' : 'Desactivado');
    }
}
