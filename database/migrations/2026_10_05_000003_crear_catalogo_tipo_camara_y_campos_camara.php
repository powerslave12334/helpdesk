<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tblCatTipoCamara', function (Blueprint $table) {
            $table->unsignedBigInteger('id_TipoCamara')->autoIncrement();
            $table->string('nombre', 100)->unique();
            $table->dateTime('fechaAlta');
            $table->boolean('activo')->default(true);
        });

        $ahora = now();
        foreach (['Domo', 'Bala', 'PTZ', 'Cónica'] as $nombre) {
            DB::table('tblCatTipoCamara')->insert(['nombre' => $nombre, 'fechaAlta' => $ahora, 'activo' => 1]);
        }

        Schema::table('tblEquipo', function (Blueprint $table) {
            $table->string('tipoCamara', 50)->nullable()->after('funciones');
            $table->string('resolucion', 10)->nullable()->after('tipoCamara');
            $table->string('conexion', 30)->nullable()->after('resolucion');
        });
    }

    public function down(): void
    {
        Schema::table('tblEquipo', function (Blueprint $table) {
            $table->dropColumn(['tipoCamara', 'resolucion', 'conexion']);
        });
        Schema::dropIfExists('tblCatTipoCamara');
    }
};
