<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tblVehiculo', function (Blueprint $table) {
            $table->string('estado', 100)->nullable();
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('placa_Estatal', 50)->nullable();
            $table->string('placa_Federal', 50)->nullable();
            $table->string('placa_Puesta', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tblVehiculo', function (Blueprint $table) {
            $table->dropColumn(['estado', 'marca', 'modelo', 'placa_Estatal', 'placa_Federal', 'placa_Puesta']);
        });
    }
};
