<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tblMonitoreo', function (Blueprint $table) {
            $table->decimal('cpu_porcentaje', 5, 2)->nullable()->after('estatus');
            $table->decimal('memoria_porcentaje', 5, 2)->nullable()->after('cpu_porcentaje');
            $table->decimal('memoria_usada_gb', 10, 2)->nullable()->after('memoria_porcentaje');
            $table->decimal('memoria_total_gb', 10, 2)->nullable()->after('memoria_usada_gb');
            $table->decimal('memoria_libre_gb', 10, 2)->nullable()->after('memoria_total_gb');
            $table->decimal('disco_porcentaje', 5, 2)->nullable()->after('memoria_libre_gb');
            $table->decimal('disco_usado_gb', 10, 2)->nullable()->after('disco_porcentaje');
            $table->decimal('disco_total_gb', 10, 2)->nullable()->after('disco_usado_gb');
            $table->decimal('disco_libre_gb', 10, 2)->nullable()->after('disco_total_gb');
        });
    }

    public function down(): void
    {
        Schema::table('tblMonitoreo', function (Blueprint $table) {
            $table->dropColumn([
                'cpu_porcentaje',
                'memoria_porcentaje',
                'memoria_usada_gb',
                'memoria_total_gb',
                'memoria_libre_gb',
                'disco_porcentaje',
                'disco_usado_gb',
                'disco_total_gb',
                'disco_libre_gb',
            ]);
        });
    }
};
