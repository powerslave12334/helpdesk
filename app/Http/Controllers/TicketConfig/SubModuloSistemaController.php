<?php

namespace App\Http\Controllers\TicketConfig;

use App\Http\Controllers\Controller;
use App\Models\SubModuloSistema;
use App\Models\ModuloSistema;
use Illuminate\Http\Request;

class SubModuloSistemaController extends Controller
{
    public function index()
    {
        $submodulosSistemas = SubModuloSistema::with('moduloSistema')->get();
        $modulosSistemas = ModuloSistema::where('enabled', true)->get();
        return view('ticketconfig.submodulossistemas', compact('submodulosSistemas', 'modulosSistemas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:150',
            'id_ModulosSistemas' => 'required|integer',
        ]);

        if (SubModuloSistema::where('nombre', $request->nombre)->where('id_ModulosSistemas', $request->id_ModulosSistemas)->exists()) {
            return redirect()->route('ticketconfig.submodulossistemas.index')->with('error', 'Ya existe un submódulo con ese nombre en el módulo seleccionado');
        }

        SubModuloSistema::create([
            'nombre' => $request->nombre,
            'id_ModulosSistemas' => $request->id_ModulosSistemas,
            'enabled' => 1,
        ]);

        return redirect()->route('ticketconfig.submodulossistemas.index')->with('success', 'SubMódulo de sistema creado correctamente');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:150',
            'id_ModulosSistemas' => 'required|integer',
        ]);

        if (SubModuloSistema::where('nombre', $request->nombre)->where('id_ModulosSistemas', $request->id_ModulosSistemas)->where('id_SubmodulosSistemas', '!=', $id)->exists()) {
            return redirect()->route('ticketconfig.submodulossistemas.index')->with('error', 'Ya existe un submódulo con ese nombre en el módulo seleccionado');
        }

        $subModuloSistema = SubModuloSistema::findOrFail($id);
        $subModuloSistema->update([
            'nombre' => $request->nombre,
            'id_ModulosSistemas' => $request->id_ModulosSistemas,
        ]);

        return redirect()->route('ticketconfig.submodulossistemas.index')->with('success', 'SubMódulo de sistema actualizado correctamente');
    }

    public function toggleActivo($id)
    {
        $subModuloSistema = SubModuloSistema::findOrFail($id);
        $subModuloSistema->enabled = !$subModuloSistema->enabled;
        $subModuloSistema->save();
        return back()->with('success', $subModuloSistema->enabled ? 'SubMódulo de sistema activado' : 'SubMódulo de sistema desactivado');
    }
}
