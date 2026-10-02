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
        Schema::table('tblSubModulos', function (Blueprint $table) {
            $table->string('clave', 100)->nullable()->unique()->after('controller');
        });

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
            if (!$existe) {
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

        Schema::create('tblRolSubModulo', function (Blueprint $table) {
            $table->id('id_RolSubModulo');
            $table->foreignId('id_Rol')->constrained('tblRoles', 'id_Rol');
            $table->foreignId('id_SubModulos')->constrained('tblSubModulos', 'id_SubModulos');
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

                if (!$existe) {
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
