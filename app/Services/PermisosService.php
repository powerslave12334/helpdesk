<?php

namespace App\Services;

use App\Models\Usuario;
use App\Models\Modulo;
use App\Models\SubModulo;
use App\Models\Accion;
use Illuminate\Support\Facades\DB;

class PermisosService
{
    public function esAdministrador(int $idUsuario): bool
    {
        return DB::table('tblRolesUsuarios as ru')
            ->join('tblRoles as r', 'r.id_Rol', '=', 'ru.id_Rol')
            ->where('ru.id_Usuario', $idUsuario)
            ->where('ru.activo', true)
            ->where('r.activo', true)
            ->where(function ($q) {
                $q->whereRaw("LOWER(r.nombre) LIKE '%admin%'")
                  ->orWhereRaw("LOWER(r.nombre) = 'root'");
            })
            ->exists();
    }

    public function tienePermiso(int $idUsuario, int $idModulo, int $idSubModulo, int $idAccion): bool
    {
        if ($this->esAdministrador($idUsuario)) {
            return true;
        }

        $permisoUsuario = DB::table('tblPermisoUsuarios')
            ->where('id_Usuario', $idUsuario)
            ->where('id_Modulos', $idModulo)
            ->where('id_SubModulos', $idSubModulo)
            ->where('id_Accion', $idAccion)
            ->where('activo', true)
            ->orderByDesc('id_PermisoUsuarios')
            ->first();

        if ($permisoUsuario) {
            return (bool) $permisoUsuario->permitido;
        }

        $permisoRol = DB::table('tblRolesUsuarios as ru')
            ->join('tblRolPermiso as rp', 'rp.id_Rol', '=', 'ru.id_Rol')
            ->where('ru.id_Usuario', $idUsuario)
            ->where('ru.activo', true)
            ->where('rp.id_Modulos', $idModulo)
            ->where('rp.id_SubModulos', $idSubModulo)
            ->where('rp.id_Accion', $idAccion)
            ->where('rp.activo', true)
            ->select(DB::raw('SUM(CASE WHEN rp.permitido = 1 THEN 1 ELSE 0 END) as total_permisos'))
            ->havingRaw('COUNT(1) > 0')
            ->first();

        if ($permisoRol && $permisoRol->total_permisos > 0) {
            return true;
        }

        return false;
    }

    public function tienePermisoPorRuta(int $idUsuario, string $controller, string $accion): bool
    {
        if ($this->esAdministrador($idUsuario)) {
            return true;
        }

        $subModulo = DB::table('tblSubModulos')
            ->whereRaw('LOWER(controller) = ?', [strtolower($controller)])
            ->where('activo', true)
            ->first();

        if (!$subModulo) {
            return false;
        }

        $idsAcciones = $this->obtenerIdsAccion($accion);

        foreach ($idsAcciones as $idAccion) {
            if ($this->tienePermiso($idUsuario, $subModulo->id_Modulos, $subModulo->id_SubModulos, $idAccion)) {
                return true;
            }
        }

        return false;
    }

    private function obtenerIdsAccion(string $accion): array
    {
        $aliasMap = [
            'consultar' => ['Consultar', 'Consulta', 'Leer', 'Lectura', 'Read'],
            'leer'      => ['Consultar', 'Consulta', 'Leer', 'Lectura', 'Read'],
            'lectura'   => ['Consultar', 'Consulta', 'Leer', 'Lectura', 'Read'],
            'crear'     => ['Crear', 'Alta', 'Agregar', 'Escritura', 'Create'],
            'actualizar' => ['Actualizar', 'Editar', 'Cambios', 'Modificar', 'Update'],
            'editar'    => ['Actualizar', 'Editar', 'Cambios', 'Modificar', 'Update'],
            'eliminar'  => ['Eliminar', 'Baja', 'Delete'],
        ];

        $nombres = $aliasMap[strtolower($accion)] ?? [$accion];

        return DB::table('tblAcciones')
            ->where('activo', true)
            ->whereIn('nombre', $nombres)
            ->pluck('id_Accion')
            ->toArray();
    }

    public function obtenerPermisosUsuario(int $idUsuario): array
    {
        if ($this->esAdministrador($idUsuario)) {
            return ['admin' => true];
        }

        $permisosUsuario = DB::table('tblPermisoUsuarios as pu')
            ->join('tblSubModulos as sm', 'sm.id_SubModulos', '=', 'pu.id_SubModulos')
            ->where('pu.id_Usuario', $idUsuario)
            ->where('pu.activo', true)
            ->select('sm.controller', 'pu.id_Accion', 'pu.permitido')
            ->get();

        $permisosRol = DB::table('tblRolesUsuarios as ru')
            ->join('tblRolPermiso as rp', 'rp.id_Rol', '=', 'ru.id_Rol')
            ->join('tblSubModulos as sm', 'sm.id_SubModulos', '=', 'rp.id_SubModulos')
            ->where('ru.id_Usuario', $idUsuario)
            ->where('ru.activo', true)
            ->where('rp.activo', true)
            ->select('sm.controller', 'rp.id_Accion', 'rp.permitido')
            ->get();

        return [
            'usuario' => $permisosUsuario->toArray(),
            'rol' => $permisosRol->toArray(),
        ];
    }
}
