<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Departamento;
use Illuminate\Http\Request;

class DeptoController extends Controller
{
    public function index()
    {
        $deptos = Departamento::all();
        return view('configuracion.deptos', compact('deptos'));
    }

    public function store(Request $request)
    {
        $request->validate(['nombre' => 'required|string|max:100|unique:tblDepto,nombre']);
        Departamento::create(['nombre' => $request->nombre, 'activo' => true]);
        return redirect()->route('configuracion.deptos.index')->with('success', 'Departamento creado correctamente');
    }

    public function update(Request $request, $id)
    {
        $request->validate(['nombre' => 'required|string|max:100|unique:tblDepto,nombre,' . $id . ',id_Depto']);
        Departamento::findOrFail($id)->update(['nombre' => $request->nombre]);
        return redirect()->route('configuracion.deptos.index')->with('success', 'Departamento actualizado correctamente');
    }

    public function toggleActivo($id)
    {
        $depto = Departamento::findOrFail($id);
        $depto->activo = !$depto->activo;
        $depto->save();
        return back()->with('success', $depto->activo ? 'Departamento activado' : 'Departamento desactivado');
    }
}
