<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tblIsoResponsivaErp', function (Blueprint $table) {
            $table->id('id_ResponsivaErp');
            $table->date('fecha');
            $table->string('nombreEmpleado', 150);
            $table->string('puesto', 100);
            $table->string('correo', 150);
            $table->string('usuarioErp', 100);
            // Contraseña ERP cifrada (cast "encrypted" del modelo); se guarda
            // para poder regenerar el PDF bajo demanda.
            $table->string('contrasenaErp', 500);
            // Quien lo creó → tblUsuario.id_Usuario. Sin FK explícita porque
            // en productivo el PK de tblUsuario es int y en local bigint.
            $table->unsignedBigInteger('id_Usuario');
            $table->dateTime('fechaCreacion');
            $table->boolean('activo')->default(true);

            $table->index('id_Usuario');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tblIsoResponsivaErp');
    }
};
