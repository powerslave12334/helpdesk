<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use App\Models\RolModulo;
use App\Models\RolSubModulo;
use App\Support\Modulos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RolModuloController extends Controller
{
    public function index()
    {
        $roles = Rol::where('activo', true)->get();

        $modulos = collect(Modulos::modulos())
            ->sortBy([['orden', 'asc'], ['nombre', 'asc']])
            ->values();

        $submodulosPorModulo = collect(Modulos::submodulos())
            ->groupBy('id_Modulos');

        $modulosPorRol = RolModulo::where('activo', true)
            ->get()
            ->groupBy('id_Rol')
            ->map(fn($items) => $items->pluck('id_Modulos')->map(fn($v) => (int) $v)->all())
            ->map(fn($ids) => array_values(array_intersect($ids, Modulos::ids())));

        $submodulosPorRol = DB::table('tblRolSubModulo as rsm')
            ->join('tblSubModulos as sm', 'sm.id_SubModulos', '=', 'rsm.id_SubModulos')
            ->where('rsm.activo', true)
            ->get(['rsm.id_Rol', 'sm.clave'])
            ->groupBy('id_Rol')
            ->map(fn($items) => $items->pluck('clave')->all())
            ->map(fn($claves) => array_values(array_intersect($claves, Modulos::claves())));

        $esAdminPorRol = $roles->mapWithKeys(fn($r) => [
            $r->id_Rol => $this->esRolAdmin($r->nombre),
        ]);

        return view('configuracion.roles_modulos', compact(
            'roles', 'modulos', 'submodulosPorModulo',
            'modulosPorRol', 'submodulosPorRol', 'esAdminPorRol'
        ));
    }

    public function guardar(Request $request)
    {
        $request->validate(['id_Rol' => 'required|integer']);

        $rol = Rol::findOrFail($request->id_Rol);

        if ($this->esRolAdmin($rol->nombre)) {
            return back()->with('success', 'Los roles administradores siempre tienen todos los módulos activos.');
        }

        $clavesSeleccionadas = array_values(array_intersect(
            array_map('strval', (array) $request->get('SubModulosSeleccionados', [])),
            Modulos::claves()
        ));

        $modulosSinSubmodulos = collect(Modulos::modulosSinSubmodulos())
            ->map(fn($m) => (int) $m['id'])
            ->all();
        $modulosSeleccionados = array_values(array_intersect(
            array_map('intval', (array) $request->get('ModulosSeleccionados', [])),
            $modulosSinSubmodulos
        ));

        $idsPorClave = DB::table('tblSubModulos')
            ->whereIn('clave', $clavesSeleccionadas)
            ->pluck('id_SubModulos', 'clave');

        $faltantes = array_diff($clavesSeleccionadas, $idsPorClave->keys()->all());
        if (count($faltantes) > 0) {
            return back()->with(
                'error',
                'Catálogo desincronizado (falta: ' . implode(', ', $faltantes) . '). Ejecuta: php artisan modulos:sync'
            );
        }

        $idsSeleccionados = $idsPorClave->values()->all();

        $modulosDerivados = collect($clavesSeleccionadas)
            ->map(fn($clave) => (int) Modulos::moduloDeClave($clave)['id_Modulos'])
            ->merge($modulosSeleccionados)
            ->unique()
            ->values()
            ->all();

        DB::beginTransaction();
        try {
            RolSubModulo::where('id_Rol', $rol->id_Rol)->delete();
            RolModulo::where('id_Rol', $rol->id_Rol)->delete();

            foreach ($idsSeleccionados as $idSubmodulo) {
                RolSubModulo::create([
                    'id_Rol' => $rol->id_Rol,
                    'id_SubModulos' => $idSubmodulo,
                    'fechaAlta' => now(),
                    'activo' => true,
                ]);
            }

            foreach ($modulosDerivados as $idModulo) {
                RolModulo::create([
                    'id_Rol' => $rol->id_Rol,
                    'id_Modulos' => $idModulo,
                    'fechaAlta' => now(),
                    'activo' => true,
                ]);
            }

            DB::commit();
            return redirect()->route('configuracion.rolModulo.index')->with('success', 'Permisos del rol "' . $rol->nombre . '" guardados correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al guardar los permisos: ' . $e->getMessage());
        }
    }

    private function esRolAdmin(string $nombre): bool
    {
        $nombre = strtolower($nombre);
        return str_contains($nombre, 'admin') || $nombre === 'root';
    }
}
