<?php

namespace App\Http\Controllers\TicketConfig;

use App\Http\Controllers\Controller;
use App\Models\Sistema;
use Illuminate\Http\Request;

class SistemasController extends Controller
{
    public function index()
    {
        $sistemas = Sistema::all();
        return view('ticketconfig.sistemas', compact('sistemas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
        ]);

        if (Sistema::where('nombre', $request->nombre)->exists()) {
            return redirect()->route('ticketconfig.sistemas.index')->with('error', 'Ya existe un sistema con ese nombre');
        }

        Sistema::create([
            'nombre' => $request->nombre,
            'enabled' => true,
        ]);

        return redirect()->route('ticketconfig.sistemas.index')->with('success', 'Sistema creado correctamente');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
        ]);

        if (Sistema::where('nombre', $request->nombre)->where('id_sistemas', '!=', $id)->exists()) {
            return redirect()->route('ticketconfig.sistemas.index')->with('error', 'Ya existe un sistema con ese nombre');
        }

        $sistema = Sistema::findOrFail($id);
        $sistema->update([
            'nombre' => $request->nombre,
        ]);

        return redirect()->route('ticketconfig.sistemas.index')->with('success', 'Sistema actualizado correctamente');
    }

    public function toggleActivo($id)
    {
        $sistema = Sistema::findOrFail($id);
        $sistema->enabled = !$sistema->enabled;
        $sistema->save();
        return back()->with('success', $sistema->enabled ? 'Sistema activado' : 'Sistema desactivado');
    }
}
