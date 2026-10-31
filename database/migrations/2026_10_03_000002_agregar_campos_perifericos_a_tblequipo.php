<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tblequipo', function (Blueprint $table) {
            $table->string('tipoTeclado', 100)->nullable()->after('especificaciones');
            $table->string('conectividad', 100)->nullable()->after('tipoTeclado');
            $table->string('capacidad', 50)->nullable()->after('conectividad');
            $table->string('voltajeEntrada', 20)->nullable()->after('capacidad');
            $table->string('voltajeSalida', 20)->nullable()->after('voltajeEntrada');
            $table->unsignedSmallInteger('contactos')->nullable()->after('voltajeSalida');
        });
    }

    public function down(): void
    {
        Schema::table('tblequipo', function (Blueprint $table) {
            $table->dropColumn(['tipoTeclado', 'conectividad', 'capacidad', 'voltajeEntrada', 'voltajeSalida', 'contactos']);
        });
    }
};
