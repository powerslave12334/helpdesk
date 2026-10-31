<?php

namespace App\Http\Controllers;

use App\Models\TipoEmpleado;
use Illuminate\Http\Request;

class TipoEmpleadoController extends Controller
{
    public function index()
    {
        $tiposEmpleado = TipoEmpleado::all();
        return view('empleados.tiposempleados', compact('tiposEmpleado'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
        ]);

        if (TipoEmpleado::where('nombre', $request->nombre)->exists()) {
            return redirect()->route('empleados.tipoEmpleado.index')->with('error', 'Ya existe un tipo de empleado con ese nombre');
        }

        TipoEmpleado::create([
            'nombre' => $request->nombre,
            'fechaAlta' => now(),
            'activo' => 1,
        ]);

        return redirect()->route('empleados.tipoEmpleado.index')->with('success', 'Tipo de empleado creado correctamente');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
        ]);

        if (TipoEmpleado::where('nombre', $request->nombre)->where('id_TipoEmpleado', '!=', $id)->exists()) {
            return redirect()->route('empleados.tipoEmpleado.index')->with('error', 'Ya existe un tipo de empleado con ese nombre');
        }

        $tipoEmpleado = TipoEmpleado::findOrFail($id);
        $tipoEmpleado->update([
            'nombre' => $request->nombre,
        ]);

        return redirect()->route('empleados.tipoEmpleado.index')->with('success', 'Tipo de empleado actualizado correctamente');
    }

    public function toggleActivo($id)
    {
        $tipoEmpleado = TipoEmpleado::findOrFail($id);
        $tipoEmpleado->activo = !$tipoEmpleado->activo;
        $tipoEmpleado->save();
        return back()->with('success', $tipoEmpleado->activo ? 'Tipo de empleado activado' : 'Tipo de empleado desactivado');
    }
}
