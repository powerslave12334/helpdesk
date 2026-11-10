<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('tblEquipo', 'attributes')) {
            return;
        }

        Schema::table('tblEquipo', function (Blueprint $table) {
            $table->json('attributes')->nullable()->after('id_TipoEquipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('tblEquipo', 'attributes')) {
            return;
        }

        Schema::table('tblEquipo', function (Blueprint $table) {
            $table->dropColumn('attributes');
        });
    }
};
