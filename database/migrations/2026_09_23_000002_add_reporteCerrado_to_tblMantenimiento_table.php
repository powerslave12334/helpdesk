<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tblMantenimiento', function (Blueprint $table) {
            $table->boolean('reporteCerrado')->default(false)->after('conclusion');
        });
    }

    public function down(): void
    {
        Schema::table('tblMantenimiento', function (Blueprint $table) {
            $table->dropColumn('reporteCerrado');
        });
    }
};
