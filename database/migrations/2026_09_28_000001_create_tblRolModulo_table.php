<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tblRolModulo', function (Blueprint $table) {
            $table->id('id_RolModulo');
            $table->foreignId('id_Rol')->constrained('tblRoles', 'id_Rol');
            $table->foreignId('id_Modulos')->constrained('tblModulos', 'id_Modulos');
            $table->dateTime('fechaAlta');
            $table->boolean('activo');
            $table->unique(['id_Rol', 'id_Modulos']);
        });

        if (Schema::hasTable('tblRolPermiso')) {
            $permisos = DB::table('tblRolPermiso')
                ->where('activo', true)
                ->where('permitido', true)
                ->select('id_Rol', 'id_Modulos')
                ->distinct()
                ->get();

            foreach ($permisos as $p) {
                $existe = DB::table('tblRolModulo')
                    ->where('id_Rol', $p->id_Rol)
                    ->where('id_Modulos', $p->id_Modulos)
                    ->exists();

                if (!$existe) {
                    DB::table('tblRolModulo')->insert([
                        'id_Rol' => $p->id_Rol,
                        'id_Modulos' => $p->id_Modulos,
                        'fechaAlta' => now(),
                        'activo' => true,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tblRolModulo');
    }
};
