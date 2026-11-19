<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Agrega la FK al equipo asignado (tblEquipo) a la recepción FO-IT-03. */
    public function up(): void
    {
        Schema::table('tblIsoRecepcionEquipo', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_Equipo')->nullable()->after('id_RecepcionEquipo');
            $table->index('id_Equipo');
        });
    }

    public function down(): void
    {
        Schema::table('tblIsoRecepcionEquipo', function (Blueprint $table): void {
            $table->dropIndex(['id_Equipo']);
            $table->dropColumn('id_Equipo');
        });
    }
};
