<?php

namespace App\Http\Controllers\TicketConfig;

use App\Http\Controllers\Controller;
use App\Models\TipoDesarrollo;
use Illuminate\Http\Request;

class TipoDesarrolloController extends Controller
{
    public function index()
    {
        $tiposDesarrollo = TipoDesarrollo::all();
        return view('ticketconfig.tiposdesarrollo', compact('tiposDesarrollo'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:500',
        ]);

        if (TipoDesarrollo::where('nombre', $request->nombre)->exists()) {
            return redirect()->route('ticketconfig.tiposdesarrollo.index')->with('error', 'Ya existe un tipo de desarrollo con ese nombre');
        }

        TipoDesarrollo::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'enabled' => true,
        ]);

        return redirect()->route('ticketconfig.tiposdesarrollo.index')->with('success', 'Tipo de desarrollo creado correctamente');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:500',
        ]);

        if (TipoDesarrollo::where('nombre', $request->nombre)->where('id_TipoDesarrollo', '!=', $id)->exists()) {
            return redirect()->route('ticketconfig.tiposdesarrollo.index')->with('error', 'Ya existe un tipo de desarrollo con ese nombre');
        }

        $tipoDesarrollo = TipoDesarrollo::findOrFail($id);
        $tipoDesarrollo->update([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
        ]);

        return redirect()->route('ticketconfig.tiposdesarrollo.index')->with('success', 'Tipo de desarrollo actualizado correctamente');
    }

    public function toggleActivo($id)
    {
        $tipoDesarrollo = TipoDesarrollo::findOrFail($id);
        $tipoDesarrollo->enabled = !$tipoDesarrollo->enabled;
        $tipoDesarrollo->save();
        return back()->with('success', $tipoDesarrollo->enabled ? 'Tipo de desarrollo activado' : 'Tipo de desarrollo desactivado');
    }
}
