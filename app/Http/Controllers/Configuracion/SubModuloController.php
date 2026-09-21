<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\SubModulo;
use App\Models\Modulo;
use Illuminate\Http\Request;

class SubModuloController extends Controller
{
    public function index()
    {
        $submodulos = SubModulo::with('modulo')->get();
        $modulos = Modulo::where('activo', true)->get();
        return view('configuracion.submodulos', compact('submodulos', 'modulos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_Modulos' => 'required|integer',
            'nombre' => 'required|string|max:50',
            'controller' => 'required|string|max:100',
            'action' => 'nullable|string|max:50',
        ]);

        SubModulo::create([
            'id_Modulos' => $request->id_Modulos,
            'nombre' => $request->nombre,
            'controller' => $request->controller,
            'action' => $request->action,
            'orden' => $request->orden ?? 0,
            'fechaAlta' => now(),
            'activo' => true,
        ]);
        return redirect()->route('configuracion.submodulos.index')->with('success', 'SubMódulo creado');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'id_Modulos' => 'required|integer',
            'nombre' => 'required|string|max:50',
            'controller' => 'required|string|max:100',
            'action' => 'nullable|string|max:50',
        ]);

        SubModulo::findOrFail($id)->update([
            'id_Modulos' => $request->id_Modulos,
            'nombre' => $request->nombre,
            'controller' => $request->controller,
            'action' => $request->action,
            'orden' => $request->orden ?? 0,
        ]);
        return redirect()->route('configuracion.submodulos.index')->with('success', 'SubMódulo actualizado');
    }

    public function toggleActivo($id)
    {
        $sub = SubModulo::findOrFail($id);
        $sub->activo = !$sub->activo;
        $sub->save();
        return back()->with('success', $sub->activo ? 'Activado' : 'Desactivado');
    }
}
