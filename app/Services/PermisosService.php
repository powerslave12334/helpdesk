<?php

namespace App\Services;

use App\Support\Modulos;
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

    public function tieneRolActivo(int $idUsuario): bool
    {
        return DB::table('tblRolesUsuarios')
            ->where('id_Usuario', $idUsuario)
            ->where('activo', true)
            ->exists();
    }

    /**
     * Ids de módulos que el usuario puede ver. El catálogo real es
     * config/modulos.php; la BD solo aporta los permisos del rol.
     */
    public function modulosActivosUsuarioIds(int $idUsuario): array
    {
        if ($this->esAdministrador($idUsuario)) {
            return Modulos::ids();
        }

        $idsRol = DB::table('tblRolModulo as rm')
            ->join('tblRolesUsuarios as ru', 'ru.id_Rol', '=', 'rm.id_Rol')
            ->where('ru.id_Usuario', $idUsuario)
            ->where('ru.activo', true)
            ->where('rm.activo', true)
            ->pluck('rm.id_Modulos')
            ->map(fn($v) => (int) $v)
            ->all();

        return array_values(array_intersect($idsRol, Modulos::ids()));
    }

    /**
     * Claves de submódulos (enlaces del menú y middleware permiso:<clave>)
     * que el usuario puede usar.
     */
    public function submodulosActivosUsuarioClaves(int $idUsuario): array
    {
        if ($this->esAdministrador($idUsuario)) {
            return Modulos::claves();
        }

        $clavesRol = DB::table('tblRolSubModulo as rsm')
            ->join('tblRolesUsuarios as ru', 'ru.id_Rol', '=', 'rsm.id_Rol')
            ->join('tblSubModulos as sm', 'sm.id_SubModulos', '=', 'rsm.id_SubModulos')
            ->where('ru.id_Usuario', $idUsuario)
            ->where('ru.activo', true)
            ->where('rsm.activo', true)
            ->where('sm.activo', true)
            ->whereNotNull('sm.clave')
            ->distinct()
            ->pluck('sm.clave')
            ->all();

        return array_values(array_intersect($clavesRol, Modulos::claves()));
    }

    public function submoduloActivo(int $idUsuario, string $clave): bool
    {
        return in_array($clave, $this->submodulosActivosUsuarioClaves($idUsuario), true);
    }
}
