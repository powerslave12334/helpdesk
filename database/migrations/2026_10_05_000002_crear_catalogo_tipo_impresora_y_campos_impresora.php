<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tblCatTipoImpresora', function (Blueprint $table) {
            $table->unsignedBigInteger('id_TipoImpresora')->autoIncrement();
            $table->string('nombre', 100)->unique();
            $table->dateTime('fechaAlta');
            $table->boolean('activo')->default(true);
        });

        $ahora = now();
        foreach (['Láser', 'Inyección de tinta', 'Matricial', 'Térmica'] as $nombre) {
            DB::table('tblCatTipoImpresora')->insert(['nombre' => $nombre, 'fechaAlta' => $ahora, 'activo' => 1]);
        }

        Schema::table('tblEquipo', function (Blueprint $table) {
            $table->string('tipoImpresora', 50)->nullable()->after('conectividad');
            $table->string('colorImpresion', 20)->nullable()->after('tipoImpresora');
            $table->boolean('multifuncion')->nullable()->after('colorImpresion');
            $table->string('funciones', 150)->nullable()->after('multifuncion');
        });
    }

    public function down(): void
    {
        Schema::table('tblEquipo', function (Blueprint $table) {
            $table->dropColumn(['tipoImpresora', 'colorImpresion', 'multifuncion', 'funciones']);
        });
        Schema::dropIfExists('tblCatTipoImpresora');
    }
};
