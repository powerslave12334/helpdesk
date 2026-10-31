<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Estatus;
use App\Support\Modulos;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EstatusController extends Controller
{
    public function index()
    {
        $estatus = Estatus::all();
        $modulos = collect(Modulos::modulos())->sortBy('orden')->values();
        $nombresModulos = $modulos->pluck('nombre', 'id');

        return view('configuracion.estatus', compact('estatus', 'modulos', 'nombresModulos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'descripcion' => 'required|string|max:100',
            'id_Modulos' => ['required', 'integer', Rule::in(Modulos::ids())],
        ]);

        Estatus::create([
            'descripcion' => $request->descripcion,
            'id_Modulos' => $request->id_Modulos,
            'fechaAlta' => now(),
            'activo' => true,
        ]);

        return redirect()->route('configuracion.estatus.index')->with('success', 'Estatus creado correctamente');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'descripcion' => 'required|string|max:100',
            'id_Modulos' => ['required', 'integer', Rule::in(Modulos::ids())],
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
