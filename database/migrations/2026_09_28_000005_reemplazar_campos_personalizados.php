<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tblEquipo', function (Blueprint $table) {
            $table->string('serieMotherboard', 150)->nullable()->after('numeroSerie');
            $table->string('procesador', 150)->nullable()->after('serieMotherboard');
            $table->string('memoriaRam', 100)->nullable()->after('procesador');
            $table->string('almacenamiento', 150)->nullable()->after('memoriaRam');
            $table->string('sistemaOperativo', 100)->nullable()->after('almacenamiento');
            $table->string('ip', 45)->nullable()->after('sistemaOperativo');
            $table->string('mac', 50)->nullable()->after('ip');
            $table->string('estadoGarantia', 50)->nullable()->after('mac');
            $table->date('fechaFinGarantia')->nullable()->after('estadoGarantia');
            $table->text('especificaciones')->nullable()->after('fechaFinGarantia');
        });

        if (Schema::hasTable('tblEquipoCampo') && Schema::hasTable('tblEquipoDetalle')) {
            $campoId = DB::table('tblEquipoCampo')->where('nombre', 'Serie Motherboard')->value('id_EquipoCampo');

            if ($campoId) {
                $rows = DB::table('tblEquipoDetalle')
                    ->where('id_EquipoCampo', $campoId)
                    ->where('activo', true)
                    ->get();

                foreach ($rows as $r) {
                    DB::table('tblEquipo')
                        ->where('id_Equipo', $r->id_Equipo)
                        ->update(['serieMotherboard' => $r->valor]);
                }
            }
        }

        $idsSub = DB::table('tblSubModulos')->whereIn('clave', ['campos-equipo', 'campos-por-tipo'])->pluck('id_SubModulos')->all();

        if (!empty($idsSub)) {
            DB::table('tblRolSubModulo')->whereIn('id_SubModulos', $idsSub)->delete();
        }

        DB::table('tblSubModulos')->whereIn('clave', ['campos-equipo', 'campos-por-tipo'])->delete();

        Schema::dropIfExists('tblEquipoDetalle');
        Schema::dropIfExists('tblTipoEquipoCampo');
        Schema::dropIfExists('tblEquipoCampo');
    }

    public function down(): void
    {
        Schema::create('tblEquipoCampo', function (Blueprint $table) {
            $table->id('id_EquipoCampo');
            $table->string('nombre', 100);
            $table->string('descripcion', 250)->nullable();
            $table->string('tipo', 20)->nullable()->default('texto');
            $table->text('opciones')->nullable();
            $table->dateTime('fechaAlta');
            $table->boolean('activo');
        });

        Schema::create('tblTipoEquipoCampo', function (Blueprint $table) {
            $table->id('id_TipoEquipoCampo');
            $table->foreignId('id_TipoEquipo')->constrained('tblTipoEquipo', 'id_TipoEquipo');
            $table->integer('id_EquipoCampo');
            $table->boolean('requerido');
            $table->dateTime('fechaAlta');
            $table->boolean('activo');
        });

        Schema::create('tblEquipoDetalle', function (Blueprint $table) {
            $table->id('id_EquipoDetalle');
            $table->foreignId('id_Equipo')->constrained('tblEquipo', 'id_Equipo');
            $table->integer('id_EquipoCampo');
            $table->string('valor', 250)->nullable();
            $table->dateTime('fechaAlta');
            $table->boolean('activo');
        });

        Schema::table('tblEquipo', function (Blueprint $table) {
            $table->dropColumn([
                'serieMotherboard', 'procesador', 'memoriaRam', 'almacenamiento', 'sistemaOperativo',
                'ip', 'mac', 'estadoGarantia', 'fechaFinGarantia', 'especificaciones',
            ]);
        });
    }
};
