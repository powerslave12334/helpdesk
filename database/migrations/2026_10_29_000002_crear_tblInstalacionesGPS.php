<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tblInstalacionesGPS', function (Blueprint $table) {
            $table->unsignedBigInteger('id_InstalacionGPS')->autoIncrement();
            $table->string('unidad', 50);
            $table->string('marca', 100);
            $table->string('placas', 20);
            $table->string('placas_federal', 20)->nullable();
            $table->date('fecha_cambio_sim')->nullable();
            $table->boolean('sim_cambiado')->default(false);
            $table->boolean('reinstalacion')->default(false);
            $table->date('fecha_reinstalacion')->nullable();
            $table->string('marca_gps', 100)->nullable();
            $table->string('modelo_gps', 100)->nullable();
            $table->string('imei', 20)->nullable();
            $table->string('numero_anterior', 20)->nullable();
            $table->string('numero_actual', 20)->nullable();
            $table->dateTime('fechaAlta');
            $table->boolean('activo')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tblInstalacionesGPS');
    }
};