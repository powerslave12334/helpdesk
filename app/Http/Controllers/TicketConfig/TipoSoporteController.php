<?php

namespace App\Http\Controllers\TicketConfig;

use App\Http\Controllers\Controller;
use App\Models\TipoSoporte;
use App\Models\Area;
use Illuminate\Http\Request;

class TipoSoporteController extends Controller
{
    public function index()
    {
        $tiposSoporte = TipoSoporte::all();
        $areas = Area::where('activo', true)->get();
        return view('ticketconfig.tipossoporte', compact('tiposSoporte', 'areas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'descripcion' => 'required|string|max:100',
            'id_Area' => 'required|integer',
        ]);

        if (TipoSoporte::where('descripcion', $request->descripcion)->exists()) {
            return redirect()->route('ticketconfig.tipossoporte.index')->with('error', 'Ya existe un tipo de soporte con esa descripción');
        }

        TipoSoporte::create([
            'descripcion' => $request->descripcion,
            'fechaAlta' => now(),
            'activo' => true,
            'id_Area' => $request->id_Area,
        ]);

        return redirect()->route('ticketconfig.tipossoporte.index')->with('success', 'Tipo de soporte creado correctamente');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'descripcion' => 'required|string|max:100',
            'id_Area' => 'required|integer',
        ]);

        if (TipoSoporte::where('descripcion', $request->descripcion)->where('id_TipoSoporte', '!=', $id)->exists()) {
            return redirect()->route('ticketconfig.tipossoporte.index')->with('error', 'Ya existe un tipo de soporte con esa descripción');
        }

        $tipoSoporte = TipoSoporte::findOrFail($id);
        $tipoSoporte->update([
            'descripcion' => $request->descripcion,
            'id_Area' => $request->id_Area,
        ]);

        return redirect()->route('ticketconfig.tipossoporte.index')->with('success', 'Tipo de soporte actualizado correctamente');
    }

    public function toggleActivo($id)
    {
        $tipoSoporte = TipoSoporte::findOrFail($id);
        $tipoSoporte->activo = !$tipoSoporte->activo;
        $tipoSoporte->save();
        return back()->with('success', $tipoSoporte->activo ? 'Tipo de soporte activado' : 'Tipo de soporte desactivado');
    }
}
