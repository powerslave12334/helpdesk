<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tblVehiculo', function (Blueprint $table) {
            $table->id('id_Vehiculo');
            $table->string('numeroUnidad', 50);
            $table->string('tipoVehiculo', 30);
            $table->dateTime('fechaAlta');
            $table->boolean('activo');
            $table->unique('numeroUnidad');
        });

        Schema::table('tblTicket', function (Blueprint $table) {
            $table->string('tipoTicket', 20)->default('falla_tecnica')->after('id_TipoSoporte');
            $table->string('vehiculos', 255)->nullable()->after('id_Equipo');
            $table->string('problemaGps', 200)->nullable()->after('vehiculos');
        });
    }

    public function down(): void
    {
        Schema::table('tblTicket', function (Blueprint $table) {
            $table->dropColumn(['tipoTicket', 'vehiculos', 'problemaGps']);
        });
        Schema::dropIfExists('tblVehiculo');
    }
};
