<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tblCatTipoTeclado', function (Blueprint $table) {
            $table->unsignedBigInteger('id_TipoTeclado')->autoIncrement();
            $table->string('nombre', 100)->unique();
            $table->dateTime('fechaAlta');
            $table->boolean('activo')->default(true);
        });

        Schema::create('tblCatConectividad', function (Blueprint $table) {
            $table->unsignedBigInteger('id_Conectividad')->autoIncrement();
            $table->string('nombre', 100)->unique();
            $table->dateTime('fechaAlta');
            $table->boolean('activo')->default(true);
        });

        $ahora = now();
        foreach (['Mecánico', 'Membrana', 'Ergonómico'] as $nombre) {
            DB::table('tblCatTipoTeclado')->insert(['nombre' => $nombre, 'fechaAlta' => $ahora, 'activo' => 1]);
        }
        foreach (['USB', 'Inalámbrico', 'Bluetooth', 'PS/2'] as $nombre) {
            DB::table('tblCatConectividad')->insert(['nombre' => $nombre, 'fechaAlta' => $ahora, 'activo' => 1]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tblCatConectividad');
        Schema::dropIfExists('tblCatTipoTeclado');
    }
};
