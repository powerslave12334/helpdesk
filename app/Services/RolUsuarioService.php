<?php

namespace App\Services;

use App\Models\Rol;
use App\Models\RolesUsuario;
use Illuminate\Support\Facades\DB;

class RolUsuarioService
{
    public function asignarRolSiNoTiene(int $idUsuario, string $nombreRol = 'Empleado'): bool
    {
        $yaTieneRol = DB::table('tblRolesUsuarios')
            ->where('id_Usuario', $idUsuario)
            ->where('activo', true)
            ->exists();

        if ($yaTieneRol) {
            return false;
        }

        $rol = Rol::where('nombre', $nombreRol)->where('activo', true)->first();

        if (!$rol) {
            return false;
        }

        $parExistente = RolesUsuario::where('id_Usuario', $idUsuario)
            ->where('id_Rol', $rol->id_Rol)
            ->first();

        if ($parExistente) {
            $parExistente->update(['activo' => true]);
            return true;
        }

        RolesUsuario::create([
            'id_Rol' => $rol->id_Rol,
            'id_Usuario' => $idUsuario,
            'fechaAlta' => now(),
            'activo' => true,
        ]);

        return true;
    }

    public function asignarRol(int $idUsuario, int $idRol): void
    {
        DB::transaction(function () use ($idUsuario, $idRol) {
            RolesUsuario::where('id_Usuario', $idUsuario)
                ->where('activo', true)
                ->where('id_Rol', '!=', $idRol)
                ->update(['activo' => false]);

            $parExistente = RolesUsuario::where('id_Usuario', $idUsuario)
                ->where('id_Rol', $idRol)
                ->first();

            if ($parExistente) {
                $parExistente->update(['activo' => true]);
                return;
            }

            RolesUsuario::create([
                'id_Rol' => $idRol,
                'id_Usuario' => $idUsuario,
                'fechaAlta' => now(),
                'activo' => true,
            ]);
        });
    }
}
