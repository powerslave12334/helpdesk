<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tblMantenimiento', function (Blueprint $table) {
            $table->string('estadoInicial', 50)->nullable()->after('observaciones');
            $table->char('hw_encendido', 2)->nullable()->after('estadoInicial');
            $table->char('hw_pantalla', 2)->nullable()->after('hw_encendido');
            $table->char('hw_ventilacion', 2)->nullable()->after('hw_pantalla');
            $table->char('hw_dispositivos', 2)->nullable()->after('hw_ventilacion');
            $table->char('sw_so', 2)->nullable()->after('hw_dispositivos');
            $table->char('sw_seguridad', 2)->nullable()->after('sw_so');
            $table->char('sw_antivirus', 2)->nullable()->after('sw_seguridad');
            $table->char('sw_rendimiento', 2)->nullable()->after('sw_antivirus');
            $table->boolean('act_limpieza')->default(false)->after('sw_rendimiento');
            $table->boolean('act_revision_hw')->default(false)->after('act_limpieza');
            $table->boolean('act_actualizacion_so')->default(false)->after('act_revision_hw');
            $table->boolean('act_instalacion_sw')->default(false)->after('act_actualizacion_so');
            $table->boolean('act_parches')->default(false)->after('act_instalacion_sw');
            $table->boolean('act_eliminacion')->default(false)->after('act_parches');
            $table->boolean('act_diagnostico')->default(false)->after('act_eliminacion');
            $table->boolean('act_otro')->default(false)->after('act_diagnostico');
            $table->text('act_otro_detalle')->nullable()->after('act_otro');
            $table->string('conclusion', 100)->nullable()->after('act_otro_detalle');
        });
    }

    public function down(): void
    {
        Schema::table('tblMantenimiento', function (Blueprint $table) {
            $table->dropColumn([
                'estadoInicial', 'hw_encendido', 'hw_pantalla', 'hw_ventilacion', 'hw_dispositivos',
                'sw_so', 'sw_seguridad', 'sw_antivirus', 'sw_rendimiento',
                'act_limpieza', 'act_revision_hw', 'act_actualizacion_so', 'act_instalacion_sw',
                'act_parches', 'act_eliminacion', 'act_diagnostico', 'act_otro', 'act_otro_detalle',
                'conclusion',
            ]);
        });
    }
};
