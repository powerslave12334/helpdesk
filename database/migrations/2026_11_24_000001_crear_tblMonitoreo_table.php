<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tblMonitoreo', function (Blueprint $table) {
            $table->id('id_Monitoreo');
            $table->string('nombre', 150);
            $table->string('tipo', 50)->comment('servidor, servicio, sitio_web, equipo');
            $table->string('host_o_url', 255);
            $table->unsignedSmallInteger('puerto')->nullable();
            $table->unsignedSmallInteger('intervalo_minutos')->default(5)->comment('Cada cuántos minutos se verifica');
            $table->string('estatus', 20)->default('desconocido')->comment('online, offline, degradado, desconocido');
            $table->unsignedInteger('tiempo_respuesta_ms')->nullable()->comment('Último tiempo de respuesta en ms');
            $table->dateTime('ultimo_check')->nullable();
            $table->text('mensaje_ultimo_check')->nullable();
            $table->boolean('activo')->default(true);
            $table->dateTime('fechaAlta')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tblMonitoreo');
    }
};
