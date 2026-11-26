<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tblMonitoreo', function (Blueprint $table) {
            $table->string('nagios_host_name', 150)->nullable()->after('host_o_url');
            $table->string('nagios_service_description', 150)->nullable()->after('nagios_host_name');
            $table->dateTime('ultimo_sincronizado_nagios')->nullable()->after('nagios_service_description');
        });
    }

    public function down(): void
    {
        Schema::table('tblMonitoreo', function (Blueprint $table) {
            $table->dropColumn([
                'nagios_host_name',
                'nagios_service_description',
                'ultimo_sincronizado_nagios',
            ]);
        });
    }
};
