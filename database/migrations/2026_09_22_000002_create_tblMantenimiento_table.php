<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tblMantenimiento', function (Blueprint $table) {
            $table->id('id_Mantenimiento');
            $table->foreignId('id_Equipo')->constrained('tblEquipo', 'id_Equipo');
            $table->foreignId('id_UsuarioTecnico')->constrained('tblUsuario', 'id_Usuario');
            $table->dateTime('fechaProgramada');
            $table->dateTime('fechaEjecucion')->nullable();
            $table->foreignId('id_Estatus')->constrained('tblEstatus', 'id_Estatus')->nullable();
            $table->string('tipoMantenimiento', 50)->default('Preventivo');
            $table->text('observaciones')->nullable();
            $table->foreignId('id_Ticket')->nullable()->constrained('tblTicket', 'id_Ticket');
            $table->dateTime('fechaAlta')->useCurrent();
            $table->boolean('activo')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tblMantenimiento');
    }
};
