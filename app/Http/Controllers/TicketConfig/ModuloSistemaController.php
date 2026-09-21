<?php

namespace App\Http\Controllers\TicketConfig;

use App\Http\Controllers\Controller;
use App\Models\ModuloSistema;
use App\Models\Sistema;
use Illuminate\Http\Request;

class ModuloSistemaController extends Controller
{
    public function index()
    {
        $modulosSistemas = ModuloSistema::all();
        $sistemas = Sistema::where('enabled', true)->get();
        return view('ticketconfig.modulossistemas', compact('modulosSistemas', 'sistemas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:150',
            'id_sistemas' => 'required|integer',
        ]);

        if (ModuloSistema::where('nombre', $request->nombre)->where('id_sistemas', $request->id_sistemas)->exists()) {
            return redirect()->route('ticketconfig.modulossistemas.index')->with('error', 'Ya existe un módulo con ese nombre para el sistema seleccionado');
        }

        ModuloSistema::create([
            'nombre' => $request->nombre,
            'id_sistemas' => $request->id_sistemas,
            'enabled' => true,
        ]);

        return redirect()->route('ticketconfig.modulossistemas.index')->with('success', 'Módulo creado correctamente');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:150',
            'id_sistemas' => 'required|integer',
        ]);

        if (ModuloSistema::where('nombre', $request->nombre)->where('id_sistemas', $request->id_sistemas)->where('id_ModulosSistemas', '!=', $id)->exists()) {
            return redirect()->route('ticketconfig.modulossistemas.index')->with('error', 'Ya existe un módulo con ese nombre para el sistema seleccionado');
        }

        $moduloSistema = ModuloSistema::findOrFail($id);
        $moduloSistema->update([
            'nombre' => $request->nombre,
            'id_sistemas' => $request->id_sistemas,
        ]);

        return redirect()->route('ticketconfig.modulossistemas.index')->with('success', 'Módulo actualizado correctamente');
    }

    public function toggleActivo($id)
    {
        $moduloSistema = ModuloSistema::findOrFail($id);
        $moduloSistema->enabled = !$moduloSistema->enabled;
        $moduloSistema->save();
        return back()->with('success', $moduloSistema->enabled ? 'Módulo activado' : 'Módulo desactivado');
    }
}
