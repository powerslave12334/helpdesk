<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\RolesUsuario;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\Request;

class RolesUsuariosController extends Controller
{
    public function index()
    {
        $asignaciones = RolesUsuario::with(['rol', 'usuario'])->get();
        $roles = Rol::where('activo', true)->get();
        $usuarios = Usuario::where('activo', true)->get();
        return view('configuracion.roles_usuarios', compact('asignaciones', 'roles', 'usuarios'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_Rol' => 'required|integer',
            'id_Usuario' => 'required|integer',
        ]);

        $existeActivo = RolesUsuario::where('id_Usuario', $request->id_Usuario)
            ->where('activo', true)->first();
        if ($existeActivo) {
            return back()->with('error', 'Este usuario ya tiene un rol activo. Desactive el actual primero.');
        }

        $parExistente = RolesUsuario::where('id_Usuario', $request->id_Usuario)
            ->where('id_Rol', $request->id_Rol)->first();

        if ($parExistente) {
            $parExistente->update(['activo' => true]);
        } else {
            RolesUsuario::create([
                'id_Rol' => $request->id_Rol,
                'id_Usuario' => $request->id_Usuario,
                'fechaAlta' => now(),
                'activo' => true,
            ]);
        }

        return redirect()->route('configuracion.rolesUsuarios.index')->with('success', 'Rol asignado correctamente');
    }

    public function toggleActivo($id)
    {
        $asignacion = RolesUsuario::findOrFail($id);
        $asignacion->activo = !$asignacion->activo;
        $asignacion->save();
        return back()->with('success', $asignacion->activo ? 'Rol activado' : 'Rol desactivado');
    }
}
