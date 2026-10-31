<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Acceso al catálogo de módulos/submódulos definido en config/modulos.php.
 * Ese archivo es la fuente única de verdad; las tablas tblModulos y
 * tblSubModulos solo almacenan permisos por rol y se sincronizan con sync().
 */
class Modulos
{
    public static function modulos(): array
    {
        return config('modulos.modulos', []);
    }

    public static function submodulos(): array
    {
        return config('modulos.submodulos', []);
    }

    public static function claves(): array
    {
        return array_column(self::submodulos(), 'clave');
    }

    public static function ids(): array
    {
        return array_map('intval', array_column(self::modulos(), 'id'));
    }

    public static function submodulosDe(int $idModulo): array
    {
        return array_values(array_filter(
            self::submodulos(),
            static fn (array $s): bool => (int) $s['id_Modulos'] === $idModulo
        ));
    }

    public static function moduloDeClave(string $clave): ?array
    {
        foreach (self::submodulos() as $s) {
            if ($s['clave'] === $clave) {
                return $s;
            }
        }

        return null;
    }

    public static function modulosSinSubmodulos(): array
    {
        return array_values(array_filter(
            self::modulos(),
            static fn (array $m): bool => empty(self::submodulosDe((int) $m['id']))
        ));
    }

    /**
     * Refleja el registro en tblModulos/tblSubModulos (upsert por id/clave).
     * Los módulos fuera del registro se desactivan (nunca se borran: tienen FK
     * desde tblEstatus/tblRolModulo) y los submódulos fuera del registro se
     * eliminan junto con sus permisos.
     */
    public static function sync(): array
    {
        $resumen = [
            'modulosCreados' => 0,
            'modulosActualizados' => 0,
            'modulosDesactivados' => 0,
            'submodulosCreados' => 0,
            'submodulosActualizados' => 0,
            'submodulosEliminados' => 0,
        ];

        foreach (self::modulos() as $m) {
            $datos = [
                'nombre' => $m['nombre'],
                'orden' => (int) $m['orden'],
                'activo' => true,
            ];

            if (DB::table('tblModulos')->where('id_Modulos', $m['id'])->exists()) {
                DB::table('tblModulos')->where('id_Modulos', $m['id'])->update($datos);
                $resumen['modulosActualizados']++;
            } else {
                DB::table('tblModulos')->insert($datos + [
                    'id_Modulos' => (int) $m['id'],
                    'fechaAlta' => now(),
                ]);
                $resumen['modulosCreados']++;
            }
        }

        $ids = self::ids();
        if ($ids !== []) {
            $fueraDeRegistro = DB::table('tblModulos')->whereNotIn('id_Modulos', $ids)->pluck('id_Modulos');
            if ($fueraDeRegistro->isNotEmpty()) {
                DB::table('tblModulos')->whereIn('id_Modulos', $fueraDeRegistro)->update(['activo' => false]);
                $resumen['modulosDesactivados'] = $fueraDeRegistro->count();
            }
        }

        foreach (self::submodulos() as $s) {
            $datos = [
                'id_Modulos' => (int) $s['id_Modulos'],
                'nombre' => $s['nombre'],
                'orden' => (int) ($s['orden'] ?? 0),
                'activo' => true,
            ];

            $idExistente = DB::table('tblSubModulos')->where('clave', $s['clave'])->value('id_SubModulos');

            if ($idExistente) {
                DB::table('tblSubModulos')->where('id_SubModulos', $idExistente)->update($datos);
                $resumen['submodulosActualizados']++;
            } else {
                DB::table('tblSubModulos')->insert($datos + [
                    'clave' => $s['clave'],
                    'fechaAlta' => now(),
                ]);
                $resumen['submodulosCreados']++;
            }
        }

        $claves = self::claves();
        if ($claves !== []) {
            $fueraDeRegistro = DB::table('tblSubModulos')->whereNotIn('clave', $claves)->pluck('id_SubModulos');
            if ($fueraDeRegistro->isNotEmpty()) {
                DB::table('tblRolSubModulo')->whereIn('id_SubModulos', $fueraDeRegistro)->delete();
                DB::table('tblSubModulos')->whereIn('id_SubModulos', $fueraDeRegistro)->delete();
                $resumen['submodulosEliminados'] = $fueraDeRegistro->count();
            }
        }

        return $resumen;
    }
}
