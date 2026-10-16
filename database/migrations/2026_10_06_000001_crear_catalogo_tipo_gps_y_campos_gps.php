<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tblCatTipoGps', function (Blueprint $table) {
            $table->unsignedBigInteger('id_TipoGps')->autoIncrement();
            $table->string('nombre', 100)->unique();
            $table->dateTime('fechaAlta');
            $table->boolean('activo')->default(true);
        });

        $ahora = now();
        foreach (['Vehicular', 'Motocicleta', 'Personal', 'Patrimonial'] as $nombre) {
            DB::table('tblCatTipoGps')->insert(['nombre' => $nombre, 'fechaAlta' => $ahora, 'activo' => 1]);
        }

        Schema::table('tblEquipo', function (Blueprint $table) {
            $table->string('tipoGps', 50)->nullable()->after('conexion');
            $table->string('imei', 20)->nullable()->after('tipoGps');
            $table->string('operador', 50)->nullable()->after('imei');
            $table->string('lineaSim', 20)->nullable()->after('operador');
        });
    }

    public function down(): void
    {
        Schema::table('tblEquipo', function (Blueprint $table) {
            $table->dropColumn(['tipoGps', 'imei', 'operador', 'lineaSim']);
        });
        Schema::dropIfExists('tblCatTipoGps');
    }
};
