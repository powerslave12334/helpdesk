<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tblEquipo', function (Blueprint $table) {
            $table->dateTime('fechaUltimoMantenimiento')->nullable()->after('id_PeriodoMantenimiento');
        });
    }

    public function down(): void
    {
        Schema::table('tblEquipo', function (Blueprint $table) {
            $table->dropColumn('fechaUltimoMantenimiento');
        });
    }
};
