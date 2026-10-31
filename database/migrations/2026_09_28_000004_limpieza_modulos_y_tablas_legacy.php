<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $clavesMuertas = [
        'categoria-soporte', 'usuarios', 'estatus-empleados', 'rol-permiso',
        'permiso-usuarios', 'gps', 'dashboard', 'acciones',
    ];

    public function up(): void
    {
        Schema::dropIfExists('tblRolPermiso');
        Schema::dropIfExists('tblPermisoUsuarios');
        Schema::dropIfExists('tblAcciones');

        $idsSub = DB::table('tblSubModulos')->whereIn('clave', $this->clavesMuertas)->pluck('id_SubModulos')->all();

        if (!empty($idsSub)) {
            DB::table('tblRolSubModulo')->whereIn('id_SubModulos', $idsSub)->delete();
        }

        DB::table('tblSubModulos')->whereIn('clave', $this->clavesMuertas)->delete();
        DB::table('tblRolModulo')->whereIn('id_Modulos', [4, 7])->delete();
        DB::table('tblModulos')->whereIn('id_Modulos', [4, 7])->delete();
    }

    public function down(): void
    {
        Schema::create('tblAcciones', function (Blueprint $table) {
            $table->id('id_Accion');
            $table->string('nombre', 50);
            $table->dateTime('fechaAlta');
            $table->boolean('activo');
        });

        Schema::create('tblRolPermiso', function (Blueprint $table) {
            $table->id('id_RolPermiso');
            $table->foreignId('id_Rol')->constrained('tblRoles', 'id_Rol');
            $table->foreignId('id_Modulos')->constrained('tblModulos', 'id_Modulos');
            $table->foreignId('id_SubModulos')->constrained('tblSubModulos', 'id_SubModulos');
            $table->foreignId('id_Accion')->constrained('tblAcciones', 'id_Accion');
            $table->boolean('permitido');
            $table->dateTime('fechaAlta');
            $table->boolean('activo');
        });

        Schema::create('tblPermisoUsuarios', function (Blueprint $table) {
            $table->id('id_PermisoUsuarios');
            $table->foreignId('id_Usuario')->constrained('tblUsuario', 'id_Usuario');
            $table->foreignId('id_Modulos')->constrained('tblModulos', 'id_Modulos');
            $table->foreignId('id_SubModulos')->constrained('tblSubModulos', 'id_SubModulos');
            $table->foreignId('id_Accion')->constrained('tblAcciones', 'id_Accion');
            $table->boolean('permitido');
            $table->dateTime('fechaAlta');
            $table->boolean('activo');
        });

        DB::table('tblModulos')->insert([
            ['id_Modulos' => 4, 'nombre' => 'GPS', 'fechaAlta' => now(), 'activo' => true, 'orden' => 3],
            ['id_Modulos' => 7, 'nombre' => 'Nuevo', 'fechaAlta' => now(), 'activo' => true, 'orden' => 0],
        ]);

        DB::table('tblSubModulos')->insert([
            ['id_Modulos' => 4, 'nombre' => 'GPS', 'controller' => 'gps', 'clave' => 'gps', 'fechaAlta' => now(), 'activo' => true, 'orden' => 0],
            ['id_Modulos' => 5, 'nombre' => 'Permisos por Rol', 'controller' => 'rolPermiso', 'clave' => 'rol-permiso', 'fechaAlta' => now(), 'activo' => true, 'orden' => 6],
            ['id_Modulos' => 5, 'nombre' => 'Permisos por Usuario', 'controller' => 'permisoUsuarios', 'clave' => 'permiso-usuarios', 'fechaAlta' => now(), 'activo' => true, 'orden' => 7],
            ['id_Modulos' => 6, 'nombre' => 'Dashboard', 'controller' => 'dashboard', 'clave' => 'dashboard', 'fechaAlta' => now(), 'activo' => true, 'orden' => 0],
            ['id_Modulos' => 3, 'nombre' => 'Empleados', 'controller' => 'usuarios', 'clave' => 'usuarios', 'fechaAlta' => now(), 'activo' => true, 'orden' => 1],
            ['id_Modulos' => 1, 'nombre' => 'Categoria Soporte', 'controller' => 'categoriaSoporte', 'clave' => 'categoria-soporte', 'fechaAlta' => now(), 'activo' => false, 'orden' => 3],
        ]);
    }
};
