<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La evidencia de los tickets se sube a Google Drive: aquí se guarda el
 * fileId con el que se arman los enlaces de vista y de contenido.
 * Es nullable porque los registros anteriores siguen siendo archivos
 * locales en public/evidencias.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tblTicketEvidencia', function (Blueprint $table) {
            $table->string('driveFileId', 100)->nullable()->after('rutaArchivo');
        });
    }

    public function down(): void
    {
        Schema::table('tblTicketEvidencia', function (Blueprint $table) {
            $table->dropColumn('driveFileId');
        });
    }
};
