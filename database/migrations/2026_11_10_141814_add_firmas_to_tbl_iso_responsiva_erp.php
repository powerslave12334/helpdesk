<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tblIsoResponsivaErp', function (Blueprint $table) {
            // Firmas visuales en data-URI PNG (longText: MySQL LONGTEXT /
            // SQL Server nvarchar(max) vía el script database/sqlsrv/).
            $table->longText('firmaEmpleado')->nullable();
            $table->dateTime('fechaFirmaEmpleado')->nullable();
            $table->longText('firmaTi')->nullable();
            $table->dateTime('fechaFirmaTi')->nullable();
            // SHA-256 en hex de la información del documento + firmas.
            $table->string('huella', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tblIsoResponsivaErp', function (Blueprint $table) {
            $table->dropColumn([
                'firmaEmpleado', 'fechaFirmaEmpleado',
                'firmaTi', 'fechaFirmaTi', 'huella',
            ]);
        });
    }
};
