<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Documento FO-IT-03 (Recepción de equipo): los datos que se
        // imprimen en el PDF se regeneran bajo demanda desde esta tabla.
        Schema::create('tblIsoRecepcionEquipo', function (Blueprint $table) {
            $table->id('id_RecepcionEquipo');
            $table->date('fecha');
            // Selección única (catálogo IsoRecepcionEquipo::MOTIVOS).
            $table->string('motivoEntrega', 50);
            // Selección múltiple guardada como lista separada por comas
            // (catálogo TIPOS_ACTIVO).
            $table->string('tipoActivo', 500);
            // Identificación del activo (imei y número telefónico solo
            // aplican a teléfonos → opcionales).
            $table->string('marca', 100);
            $table->string('modelo', 100);
            $table->string('noSerie', 100);
            $table->string('imei', 100)->nullable();
            $table->string('numeroTelefonico', 50)->nullable();
            // Selección múltiple (catálogo ACCESORIOS).
            $table->string('accesorios', 500);
            // Texto libre; el formulario lo precarga con el valor del
            // formato oficial (IsoRecepcionEquipo::DESCRIPCION_DEFECTO).
            $table->string('descripcionEntrega', 500);
            // Selección única (catálogo APLICACION_GASTOS).
            $table->string('aplicacionGastos', 50);
            $table->decimal('gastosReparacion', 12, 2)->default(0);
            // Firmantes dinámicos: el PDF imprime debajo de cada línea el
            // rol fijo del formato (Jefe de Sistemas / Persona).
            $table->string('revisadoPor', 150);
            $table->string('entregadoPor', 150);
            // Quien lo creó → tblUsuario.id_Usuario. Sin FK explícita porque
            // en productivo el PK de tblUsuario es int y en local bigint.
            $table->unsignedBigInteger('id_Usuario');
            $table->dateTime('fechaCreacion');
            $table->boolean('activo')->default(true);

            // Firmas visuales en data-URI PNG (patrón FO-IT-01/02); la
            // huella SHA-256 se recalcula en el hook "saved" del modelo.
            $table->longText('firmaRevision')->nullable();
            $table->dateTime('fechaFirmaRevision')->nullable();
            $table->longText('firmaEntrega')->nullable();
            $table->dateTime('fechaFirmaEntrega')->nullable();
            $table->string('huella', 64)->nullable();

            $table->index('id_Usuario');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tblIsoRecepcionEquipo');
    }
};
