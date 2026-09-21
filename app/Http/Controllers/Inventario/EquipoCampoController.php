<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\EquipoCampo;
use Illuminate\Http\Request;

class EquipoCampoController extends Controller
{
    public function index()
    {
        $campos = EquipoCampo::all();
        return view('inventario.campo', compact('campos'));
    }

    public function store(Request $request)
    {
        $request->validate(['nombre' => 'required|string|max:100|unique:tblEquipoCampo,nombre']);
        EquipoCampo::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'fechaAlta' => now(),
            'activo' => true,
        ]);
        return redirect()->route('inventario.campo.index')->with('success', 'Campo creado correctamente');
    }

    public function update(Request $request, $id)
    {
        $request->validate(['nombre' => 'required|string|max:100|unique:tblEquipoCampo,nombre,' . $id . ',id_EquipoCampo']);
        EquipoCampo::findOrFail($id)->update([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
        ]);
        return redirect()->route('inventario.campo.index')->with('success', 'Campo actualizado correctamente');
    }

    public function toggleActivo($id)
    {
        $campo = EquipoCampo::findOrFail($id);
        $campo->activo = !$campo->activo;
        $campo->save();
        return back()->with('success', $campo->activo ? 'Campo activado' : 'Campo desactivado');
    }
}
