<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Tabla FO-IT-04 (Inventario de herramienta): datos básicos de herramienta. */
    public function up(): void
    {
        Schema::create('tblIsoInventarioHerramienta', function (Blueprint $table): void {
            $table->id('id_InventarioHerramienta');
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 150);
            $table->string('marca', 100);
            $table->string('modelo', 100);
            $table->string('numeroSerie', 100)->nullable();
            $table->string('numeroInventario', 100)->nullable();
            $table->string('ubicacion', 150);
            $table->string('estado', 20)->default('bueno'); // bueno, regular, malo, baja
            $table->date('fechaAdquisicion')->nullable();
            $table->unsignedBigInteger('id_Usuario');
            $table->dateTime('fechaCreacion');
            $table->boolean('activo')->default(true);

            $table->index('id_Usuario');
            $table->index('estado');
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tblIsoInventarioHerramienta');
    }
};
