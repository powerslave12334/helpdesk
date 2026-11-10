<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tblTipoEquipoCampoConfig')) {
            Schema::create('tblTipoEquipoCampoConfig', function (Blueprint $table) {
                $table->id('id_TipoEquipoCampoConfig');
                $table->integer('id_TipoEquipo');
                $table->foreign('id_TipoEquipo')->references('id_TipoEquipo')->on('tblTipoEquipo')->onDelete('cascade');
                $table->string('campo', 100);
                $table->boolean('es_requerido')->default(false);
                $table->integer('orden')->default(0);
                $table->boolean('activo')->default(true);
                $table->dateTime('fechaAlta');

                $table->unique(['id_TipoEquipo', 'campo'], 'uk_tipo_equipo_campo');
            });
        }

        // Sembrar configuración inicial a partir de los perfiles vigentes
        $perfiles = config('equipo_perfiles');
        $camposCatalogo = config('equipo_campos_catalogo.secciones');
        $todosLosCampos = [];
        foreach ($camposCatalogo as $sec) {
            foreach (array_keys($sec['campos']) as $c) {
                $todosLosCampos[] = $c;
            }
        }

        $tiposExistentes = DB::table('tblTipoEquipo')->get();
        $now = now();

        foreach ($tiposExistentes as $tipo) {
            $idTipo = (int) $tipo->id_TipoEquipo;
            $camposHabilitados = [];

            // Campos base que siempre están en casi todo equipo
            $base = ['marca', 'numeroSerie', 'nombreEquipo', 'ubicacion', 'id_Oficinas', 'observaciones'];

            // Modelo
            if (! in_array($idTipo, $perfiles['sin_modelo'] ?? [], true)) {
                $base[] = 'modelo';
            }

            // Período y último mantenimiento (para los que no están en sin_textos)
            if (! in_array($idTipo, $perfiles['sin_textos'] ?? [], true)) {
                $base[] = 'id_PeriodoMantenimiento';
                $base[] = 'fechaUltimoMantenimiento';
            }

            // Periféricos / Adquisición / Costos
            if (in_array($idTipo, $perfiles['campos']['perifericos'] ?? [], true)) {
                $base[] = 'fechaAdquisicion';
                $base[] = 'costo';
            }

            // PC / Laptops (IDs 1 y 6)
            if (in_array($idTipo, $perfiles['campos']['pc'] ?? [], true)) {
                $base = array_merge($base, [
                    'serieMotherboard', 'procesador', 'memoriaRam', 'almacenamiento',
                    'tamanoAlmacenamiento', 'sistemaOperativo', 'ip', 'mac',
                    'estadoGarantia', 'fechaFinGarantia', 'especificaciones', 'esCompartido',
                    'fechaAdquisicion', 'costo',
                ]);
            }

            // Monitor (ID 2)
            if (in_array($idTipo, $perfiles['campos']['monitor'] ?? [], true)) {
                $base = array_merge($base, ['tamano', 'puertos', 'estadoGarantia', 'fechaFinGarantia']);
            }

            // Teclado (ID 3)
            if (in_array($idTipo, $perfiles['campos']['tipo_teclado'] ?? [], true)) {
                $base = array_merge($base, ['tipoTeclado', 'conectividad']);
            }

            // Mouse (ID 4)
            if (in_array($idTipo, $perfiles['campos']['conectividad'] ?? [], true) && $idTipo !== 3) {
                $base = array_merge($base, ['conectividad']);
            }

            // Regulador / UPS (ID 5, 13)
            if (in_array($idTipo, $perfiles['campos']['regulador'] ?? [], true) || $idTipo === 13) {
                $base = array_merge($base, ['capacidad', 'voltajeEntrada', 'voltajeSalida', 'contactos']);
            }

            // Impresora (ID 7)
            if (in_array($idTipo, $perfiles['campos']['impresora'] ?? [], true)) {
                $base = array_merge($base, [
                    'tipoImpresora', 'colorImpresion', 'multifuncion', 'funciones',
                    'ip', 'mac', 'estadoGarantia', 'fechaFinGarantia',
                ]);
            }

            // Cámara (ID 8)
            if (in_array($idTipo, $perfiles['campos']['camara'] ?? [], true)) {
                $base = array_merge($base, [
                    'tipoCamara', 'resolucion', 'conexion',
                    'ip', 'mac', 'estadoGarantia', 'fechaFinGarantia',
                ]);
            }

            // GPS (ID 9)
            if (in_array($idTipo, $perfiles['campos']['gps'] ?? [], true)) {
                $base = array_merge($base, ['tipoGps', 'imei', 'operador', 'lineaSim', 'estadoGarantia', 'fechaFinGarantia']);
            }

            // Si es un tipo nuevo que no encaja en lo anterior, habilitamos por defecto identificación y compras
            if (empty($base)) {
                $base = ['marca', 'modelo', 'numeroSerie', 'nombreEquipo', 'ubicacion', 'id_Oficinas', 'fechaAdquisicion', 'costo', 'observaciones'];
            }

            $camposUnicos = array_values(array_unique($base));
            $insertData = [];
            foreach ($camposUnicos as $idx => $campo) {
                $esReq = in_array($campo, ['marca'], true);
                if ($campo === 'modelo' && ! in_array($idTipo, $perfiles['sin_modelo'] ?? [], true)) {
                    $esReq = true;
                }
                if ($campo === 'tipoGps' && $idTipo === 9) {
                    $esReq = true;
                }

                $insertData[] = [
                    'id_TipoEquipo' => $idTipo,
                    'campo' => $campo,
                    'es_requerido' => $esReq,
                    'orden' => $idx + 1,
                    'activo' => true,
                    'fechaAlta' => $now,
                ];
            }

            if (! empty($insertData)) {
                // Verificar si ya existen registros para este tipo
                $existentes = DB::table('tblTipoEquipoCampoConfig')
                    ->where('id_TipoEquipo', $idTipo)
                    ->count();
                if ($existentes === 0) {
                    DB::table('tblTipoEquipoCampoConfig')->insert($insertData);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tblTipoEquipoCampoConfig');
    }
};
