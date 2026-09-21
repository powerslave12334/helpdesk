<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\RolPermiso;
use App\Models\Rol;
use App\Models\Modulo;
use App\Models\SubModulo;
use App\Models\Accion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RolPermisoController extends Controller
{
    public function index(Request $request)
    {
        $idRol = $request->get('idRol', 0);
        $roles = Rol::where('activo', true)->get();
        $modulos = Modulo::where('activo', true)->get();
        $submodulos = SubModulo::where('activo', true)->get();
        $acciones = Accion::where('activo', true)->get();

        $permisos = RolPermiso::where('activo', true)
            ->when($idRol, fn($q) => $q->where('id_Rol', $idRol))
            ->get();

        return view('configuracion.rol_permisos', compact('roles', 'modulos', 'submodulos', 'acciones', 'permisos', 'idRol'));
    }

    public function guardarMatriz(Request $request)
    {
        $request->validate(['id_Rol' => 'required|integer']);

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
                        $existe = RolPermiso::where('id_Rol', $request->id_Rol)
                            ->where('id_Modulos', $m->id_Modulos)
                            ->where('id_SubModulos', $sm->id_SubModulos)
                            ->where('id_Accion', $a->id_Accion)
                            ->first();

                        if ($existe) {
                            $existe->update(['permitido' => isset($permisosMap[$key]) ? 1 : 0, 'activo' => 1]);
                        } elseif (isset($permisosMap[$key])) {
                            RolPermiso::create([
                                'id_Rol' => $request->id_Rol,
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
            return redirect()->route('configuracion.rolPermiso.index', ['idRol' => $request->id_Rol])->with('success', 'Permisos guardados correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al guardar permisos: ' . $e->getMessage());
        }
    }
}
