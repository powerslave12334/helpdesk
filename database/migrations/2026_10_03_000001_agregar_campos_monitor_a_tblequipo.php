<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tblequipo', function (Blueprint $table) {
            $table->string('tamano', 50)->nullable()->after('especificaciones');
            $table->string('puertos', 255)->nullable()->after('tamano');
            $table->decimal('costo', 12, 2)->nullable()->after('puertos');
        });
    }

    public function down(): void
    {
        Schema::table('tblequipo', function (Blueprint $table) {
            $table->dropColumn(['tamano', 'puertos', 'costo']);
        });
    }
};
