<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use Illuminate\Http\Request;

class RolController extends Controller
{
    public function index()
    {
        $roles = Rol::all();
        return view('configuracion.roles', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:tblRoles,nombre',
            'descipcion' => 'nullable|string|max:255',
        ]);

        Rol::create([
            'nombre' => $request->nombre,
            'descipcion' => $request->descipcion,
            'fechaAlta' => now(),
            'activo' => true,
        ]);

        return redirect()->route('configuracion.roles.index')->with('success', 'Rol creado correctamente');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:tblRoles,nombre,' . $id . ',id_Rol',
            'descipcion' => 'nullable|string|max:255',
        ]);

        $rol = Rol::findOrFail($id);
        $rol->update([
            'nombre' => $request->nombre,
            'descipcion' => $request->descipcion,
        ]);

        return redirect()->route('configuracion.roles.index')->with('success', 'Rol actualizado correctamente');
    }

    public function toggleActivo($id)
    {
        $rol = Rol::findOrFail($id);
        $rol->activo = !$rol->activo;
        $rol->save();
        return back()->with('success', $rol->activo ? 'Rol activado' : 'Rol desactivado');
    }
}
