<?php

namespace App\Http\Controllers\TicketConfig;

use App\Http\Controllers\Controller;
use App\Models\TipoEquipoTicket;
use Illuminate\Http\Request;

class TipoEquipoTicketController extends Controller
{
    public function index()
    {
        $tiposEquipoTicket = TipoEquipoTicket::all();
        return view('ticketconfig.tiposequipoticket', compact('tiposEquipoTicket'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'clave' => 'required|string|max:20',
            'descripcion' => 'required|string|max:100',
        ]);

        if (TipoEquipoTicket::where('clave', $request->clave)->exists()) {
            return redirect()->route('ticketconfig.tiposequipoticket.index')->with('error', 'Ya existe un tipo de equipo con esa clave');
        }

        TipoEquipoTicket::create([
            'clave' => strtoupper($request->clave),
            'descripcion' => $request->descripcion,
            'fechaAlta' => now(),
            'activo' => true,
        ]);

        return redirect()->route('ticketconfig.tiposequipoticket.index')->with('success', 'Tipo de equipo creado correctamente');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'clave' => 'required|string|max:20',
            'descripcion' => 'required|string|max:100',
        ]);

        if (TipoEquipoTicket::where('clave', $request->clave)->where('id_TipoEquipoTicket', '!=', $id)->exists()) {
            return redirect()->route('ticketconfig.tiposequipoticket.index')->with('error', 'Ya existe un tipo de equipo con esa clave');
        }

        $tipoEquipo = TipoEquipoTicket::findOrFail($id);
        $tipoEquipo->update([
            'clave' => strtoupper($request->clave),
            'descripcion' => $request->descripcion,
        ]);

        return redirect()->route('ticketconfig.tiposequipoticket.index')->with('success', 'Tipo de equipo actualizado correctamente');
    }

    public function toggleActivo($id)
    {
        $tipoEquipo = TipoEquipoTicket::findOrFail($id);
        $tipoEquipo->activo = !$tipoEquipo->activo;
        $tipoEquipo->save();
        return back()->with('success', $tipoEquipo->activo ? 'Tipo de equipo activado' : 'Tipo de equipo desactivado');
    }
}
