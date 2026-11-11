<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Documento FO-IT-02 (Acta de entrega): los datos que se imprimen
        // en el PDF se regeneran bajo demanda desde esta tabla.
        Schema::create('tblIsoActaEntrega', function (Blueprint $table) {
            $table->id('id_ActaEntrega');
            $table->date('fecha');
            $table->string('entregadoPor', 150);
            $table->string('recibidoPor', 150);
            // Quien lo creó → tblUsuario.id_Usuario. Sin FK explícita porque
            // en productivo el PK de tblUsuario es int y en local bigint.
            $table->unsignedBigInteger('id_Usuario');
            $table->dateTime('fechaCreacion');
            $table->boolean('activo')->default(true);

            // Firmas visuales en data-URI PNG (patrón FO-IT-01); la huella
            // SHA-256 se recalcula en el hook "saved" del modelo.
            $table->longText('firmaEntrega')->nullable();
            $table->dateTime('fechaFirmaEntrega')->nullable();
            $table->longText('firmaRecepcion')->nullable();
            $table->dateTime('fechaFirmaRecepcion')->nullable();
            $table->string('huella', 64)->nullable();

            $table->index('id_Usuario');
        });

        // Renglones de equipos del acta: filas dinámicas del formulario
        // (una fila por equipo entregado), en orden de captura.
        Schema::create('tblIsoActaEntregaEquipo', function (Blueprint $table) {
            $table->id('id_ActaEquipo');
            // Relación lógica con el acta (patrón del módulo: sin FK).
            $table->unsignedBigInteger('id_ActaEntrega');
            $table->unsignedInteger('cantidad');
            $table->string('descripcion', 255);
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('noSerie', 100)->nullable();
            // Catálogo de condiciones (ver IsoActaEntrega::CONDICIONES).
            $table->string('condiciones', 50);

            $table->index('id_ActaEntrega');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tblIsoActaEntregaEquipo');
        Schema::dropIfExists('tblIsoActaEntrega');
    }
};
