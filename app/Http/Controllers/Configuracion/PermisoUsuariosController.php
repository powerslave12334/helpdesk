<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\PermisoUsuario;
use App\Models\Usuario;
use App\Models\Modulo;
use App\Models\SubModulo;
use App\Models\Accion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermisoUsuariosController extends Controller
{
    public function index(Request $request)
    {
        $idUsuario = $request->get('idUsuario', 0);
        $usuarios = Usuario::where('activo', true)->get();
        $modulos = Modulo::where('activo', true)->get();
        $submodulos = SubModulo::where('activo', true)->get();
        $acciones = Accion::where('activo', true)->get();

        $permisos = PermisoUsuario::where('activo', true)
            ->when($idUsuario, fn($q) => $q->where('id_Usuario', $idUsuario))
            ->get();

        return view('configuracion.permiso_usuarios', compact('usuarios', 'modulos', 'submodulos', 'acciones', 'permisos', 'idUsuario'));
    }

    public function guardarMatriz(Request $request)
    {
        $request->validate(['id_Usuario' => 'required|integer']);

        $seleccionados = $request->get('PermisosSeleccionados', []);
        $permisosMap = [];
        foreach ($seleccionados as $key) {
            $parts = explode('|', $key);
            if (count($parts) == 3) {
                $permisosMap["{$parts[0]}_{$parts[1]}_{$parts[2]}"] = true;
            }
        }

        $modulos = Modulo::where('activo', true)->get();
        $submodulos = SubModulo::where('activo', true)->get();
        $acciones = Accion::where('activo', true)->get();

        DB::beginTransaction();
        try {
            foreach ($modulos as $m) {
                foreach ($submodulos->where('id_Modulos', $m->id_Modulos) as $sm) {
                    foreach ($acciones as $a) {
                        $key = "{$m->id_Modulos}_{$sm->id_SubModulos}_{$a->id_Accion}";
                        $existe = PermisoUsuario::where('id_Usuario', $request->id_Usuario)
                            ->where('id_Modulos', $m->id_Modulos)
                            ->where('id_SubModulos', $sm->id_SubModulos)
                            ->where('id_Accion', $a->id_Accion)
                            ->first();

                        if ($existe) {
                            $existe->update(['permitido' => isset($permisosMap[$key]) ? 1 : 0, 'activo' => 1]);
                        } elseif (isset($permisosMap[$key])) {
                            PermisoUsuario::create([
                                'id_Usuario' => $request->id_Usuario,
                                'id_Modulos' => $m->id_Modulos,
                                'id_SubModulos' => $sm->id_SubModulos,
                                'id_Accion' => $a->id_Accion,
                                'permitido' => 1,
                                'fechaAlta' => now(),
                                'activo' => true,
                            ]);
                        }
                    }
                }
            }
            DB::commit();
            return redirect()->route('configuracion.permisoUsuarios.index', ['idUsuario' => $request->id_Usuario])->with('success', 'Permisos guardados correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al guardar: ' . $e->getMessage());
        }
    }
}
