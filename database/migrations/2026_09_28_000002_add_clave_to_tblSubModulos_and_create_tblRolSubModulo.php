<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tblSubModulos', 'clave')) {
            Schema::table('tblSubModulos', function (Blueprint $table) {
                $table->string('clave', 100)->nullable()->after('controller');
            });
        }

        // SQL Server no permite múltiples NULL en un índice UNIQUE (a diferencia
        // de MySQL); se usa un índice filtrado. Idempotente para re-ejecución.
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlsrv') {
            $existe = DB::select(
                "SELECT 1 AS x FROM sys.indexes WHERE name = 'tblsubmodulos_clave_unique' AND object_id = OBJECT_ID('dbo.tblSubModulos')"
            );
            if (empty($existe)) {
                DB::statement('CREATE UNIQUE INDEX tblsubmodulos_clave_unique ON dbo.tblSubModulos (clave) WHERE clave IS NOT NULL');
            }
        } elseif ($driver === 'sqlite') {
            // SQLite no soporta SHOW INDEX; el índice se crea directamente.
            $tieneIndice = DB::select(
                "SELECT 1 AS x FROM sqlite_master WHERE type = 'index' AND name = 'tblsubmodulos_clave_unique'"
            );
            if (empty($tieneIndice)) {
                Schema::table('tblSubModulos', function (Blueprint $table) {
                    $table->unique('clave');
                });
            }
        } else {
            $tieneIndice = DB::select("SHOW INDEX FROM tblSubModulos WHERE Key_name = 'tblsubmodulos_clave_unique'");
            if (empty($tieneIndice)) {
                Schema::table('tblSubModulos', function (Blueprint $table) {
                    $table->unique('clave');
                });
            }
        }

        $map = [
            'modulos' => 'modulos',
            'subModulos' => 'submodulos',
            'roles' => 'roles',
            'acciones' => 'acciones',
            'rolesUsuarios' => 'roles-usuarios',
            'rolPermiso' => 'rol-permiso',
            'permisoUsuarios' => 'permiso-usuarios',
            'tickets' => 'tickets',
            'tipoDesarrollo' => 'tipo-desarrollo',
            'usuarios' => 'usuarios',
            'verEmpleados' => 'ver-empleados',
            'departamentos' => 'departamentos',
            'areas' => 'areas',
            'tipoEmpleado' => 'tipo-empleado',
            'tipoSoporte' => 'tipo-soporte',
            'tipoEquipo' => 'tipo-equipo',
            'equipo' => 'equipo',
            'equipoUsuario' => 'asignacion-equipo',
            'oficinas' => 'oficinas',
            'equipoCampo' => 'campos-equipo',
            'tipoEquipoCampo' => 'campos-por-tipo',
            'tipoRelacionEquipo' => 'tipo-relacion-equipo',
            'equipoRelacion' => 'relacion-equipo',
            'configuracionGeneral' => 'config-general',
            'categoriaSoporte' => 'categoria-soporte',
            'tipoEquipoTicket' => 'tipo-equipo-soporte',
            'sistemas' => 'tipo-sistema',
            'modulosSistemas' => 'modulos-sistemas',
            'submodulosSistemas' => 'submodulos-sistemas',
            'equipoHistorial' => 'historial-asignacion',
        ];

        foreach (DB::table('tblSubModulos')->get() as $sm) {
            if ($sm->controller === 'estatus') {
                $clave = ((int) $sm->id_Modulos === 3) ? 'estatus-empleados' : 'estatus';
            } else {
                $clave = $map[$sm->controller] ?? Str::slug($sm->controller ?: $sm->nombre);
            }

            DB::table('tblSubModulos')
                ->where('id_SubModulos', $sm->id_SubModulos)
                ->update(['clave' => $clave]);
        }

        $nuevos = [
            ['id_Modulos' => 2, 'clave' => 'mantenimientos', 'nombre' => 'Mantenimientos', 'controller' => 'mantenimientos'],
            ['id_Modulos' => 5, 'clave' => 'roles-modulos', 'nombre' => 'Módulos por Rol', 'controller' => 'rolesModulos'],
            ['id_Modulos' => 4, 'clave' => 'gps', 'nombre' => 'GPS', 'controller' => 'gps'],
            ['id_Modulos' => 6, 'clave' => 'dashboard', 'nombre' => 'Dashboard', 'controller' => 'dashboard'],
        ];

        foreach ($nuevos as $n) {
            $existe = DB::table('tblSubModulos')->where('clave', $n['clave'])->exists();
            if (! $existe) {
                // En una BD nueva el módulo padre puede no existir aún (en las
                // BD existentes ya estaba poblado); se crea para no violar la FK.
                $existeModulo = DB::table('tblModulos')->where('id_Modulos', $n['id_Modulos'])->exists();
                if (! $existeModulo) {
                    DB::table('tblModulos')->insert([
                        'id_Modulos' => $n['id_Modulos'],
                        'nombre' => 'Módulo '.$n['id_Modulos'],
                        'fechaAlta' => now(),
                        'activo' => true,
                        'orden' => 0,
                    ]);
                }

                DB::table('tblSubModulos')->insert([
                    'id_Modulos' => $n['id_Modulos'],
                    'nombre' => $n['nombre'],
                    'controller' => $n['controller'],
                    'clave' => $n['clave'],
                    'fechaAlta' => now(),
                    'activo' => true,
                    'orden' => 0,
                ]);
            }
        }

        // En productivo las tablas legacy usan int como PK; en MySQL local, bigint.
        $tipoLlave = DB::connection()->getDriverName() === 'sqlsrv' ? 'integer' : 'bigInteger';

        Schema::create('tblRolSubModulo', function (Blueprint $table) use ($tipoLlave) {
            $table->id('id_RolSubModulo');
            $table->{$tipoLlave}('id_Rol')->unsigned()->constrained('tblRoles', 'id_Rol');
            $table->{$tipoLlave}('id_SubModulos')->unsigned()->constrained('tblSubModulos', 'id_SubModulos');
            $table->dateTime('fechaAlta');
            $table->boolean('activo');
            $table->unique(['id_Rol', 'id_SubModulos']);
        });

        $rolModulos = DB::table('tblRolModulo')->where('activo', true)->get();
        foreach ($rolModulos as $rm) {
            $submodulos = DB::table('tblSubModulos')
                ->where('id_Modulos', $rm->id_Modulos)
                ->where('activo', true)
                ->pluck('id_SubModulos');

            foreach ($submodulos as $idSub) {
                $existe = DB::table('tblRolSubModulo')
                    ->where('id_Rol', $rm->id_Rol)
                    ->where('id_SubModulos', $idSub)
                    ->exists();

                if (! $existe) {
                    DB::table('tblRolSubModulo')->insert([
                        'id_Rol' => $rm->id_Rol,
                        'id_SubModulos' => $idSub,
                        'fechaAlta' => now(),
                        'activo' => true,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tblRolSubModulo');

        DB::table('tblSubModulos')
            ->whereIn('clave', ['mantenimientos', 'roles-modulos', 'gps', 'dashboard'])
            ->delete();

        Schema::table('tblSubModulos', function (Blueprint $table) {
            $table->dropUnique(['clave']);
            $table->dropColumn('clave');
        });
    }
};
