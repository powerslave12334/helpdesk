<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // En productivo las tablas legacy usan int como PK; en MySQL local, bigint.
        $tipoLlave = DB::connection()->getDriverName() === 'sqlsrv' ? 'integer' : 'bigInteger';

        Schema::create('tblMantenimiento', function (Blueprint $table) use ($tipoLlave) {
            $table->id('id_Mantenimiento');
            $table->{$tipoLlave}('id_Equipo')->unsigned()->constrained('tblEquipo', 'id_Equipo');
            $table->{$tipoLlave}('id_UsuarioTecnico')->unsigned()->constrained('tblUsuario', 'id_Usuario');
            $table->dateTime('fechaProgramada');
            $table->dateTime('fechaEjecucion')->nullable();
            $table->{$tipoLlave}('id_Estatus')->nullable()->unsigned()->constrained('tblEstatus', 'id_Estatus');
            $table->string('tipoMantenimiento', 50)->default('Preventivo');
            $table->text('observaciones')->nullable();
            $table->{$tipoLlave}('id_Ticket')->nullable()->unsigned()->constrained('tblTicket', 'id_Ticket');
            $table->dateTime('fechaAlta')->useCurrent();
            $table->boolean('activo')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tblMantenimiento');
    }
};
