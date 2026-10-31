<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\TicketDesarrolloDetalle;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportarHistoricoKpiDesarrollo extends Command
{
    protected $signature = 'kpi:importar-historico
        {archivo? : Ruta del libro KPI-desarrollo.xlsx (por defecto, la raíz del proyecto)}
        {--dry-run : Analiza el libro y muestra el resumen sin escribir en la base}
        {--connection= : Conexión de BD a usar (p. ej. sqlsrv_productivo)}
        {--force : Importa aunque los conteos no coincidan con los paneles del libro}';

    protected $description = 'Importa a tblTicket/tblTicketDesarrolloDetalle el histórico de desarrollo tomado del libro KPI-desarrollo.xlsx';

    /** Conteos oficiales del libro (paneles de cada hoja): mes => [solicitudes, correcciones]. */
    private const ESPERADO = [
        '2026-01' => [3, 1],
        '2026-02' => [2, 22],
        '2026-03' => [10, 14],
        '2026-04' => [71, 51],
        '2026-05' => [53, 44],
        // El panel de junio dice 38, pero la hoja tiene 36 filas y su bloque de
        // tiempo suma 52 (36 + 16), así que el 38 es un conteo manual desfasado.
        '2026-06' => [36, 16],
        '2026-07' => [17, 3],
        '2026-08' => [8, 10],
        '2026-09' => [11, 13],
    ];

    /** Nombre de mes (normalizado) => número. */
    private const MESES = [
        'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
        'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
        'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12,
    ];

    /** Nombres del libro que no coinciden literalmente con el catálogo. */
    private const ALIAS_MODULOS = [
        'gastos' => 'gastos cargos',
        'cxc' => 'cuentas por cobrar',
        'ticket' => 'tickets',
    ];

    private const ALIAS_SUBMODULOS = [
        'traspasos' => 'traspaso',
        'ticket' => 'tickets',
        'anqueles' => 'catalogos anaquel',
    ];

    private array $ids = [];
    private array $modulos = [];
    private array $submodulos = [];
    private array $creados = ['modulos' => [], 'submodulos' => []];
    private array $faltantes = ['modulos' => [], 'submodulos' => []];
    private bool $dryRun = false;

    public function handle(): int
    {
        $archivo = $this->argument('archivo') ?: base_path('KPI-desarrollo.xlsx');

        if (!is_file($archivo)) {
            $this->error("No se encontró el libro: {$archivo}");

            return self::FAILURE;
        }

        if ($conexion = $this->option('connection')) {
            config(['database.default' => $conexion]);
            DB::purge($conexion);
        }

        $this->dryRun = (bool) $this->option('dry-run');

        if (!$this->resolverCatalogos()) {
            return self::FAILURE;
        }

        $this->line('Libro: ' . $archivo);
        $this->line('Conexión: ' . config('database.default'));
        $this->line($this->dryRun ? 'Modo: DRY-RUN (no se escribe en la base)' : 'Modo: CARGA REAL');
        $this->newLine();

        $libro = IOFactory::createReaderForFile($archivo)->load($archivo);
        $registros = [];

        foreach ($libro->getSheetNames() as $indice => $nombreHoja) {
            if ($indice === 0 || str_starts_with(mb_strtoupper(trim($nombreHoja)), 'TABLERO')) {
                continue;
            }

            $periodo = $this->periodoDeHoja($nombreHoja);

            if (!$periodo) {
                $this->warn("Hoja ignorada (nombre no reconocido): {$nombreHoja}");
                continue;
            }

            [$anio, $mes] = $periodo;
            $registros = array_merge(
                $registros,
                $this->leerHoja($libro->getSheetByName($nombreHoja), $anio, $mes, $nombreHoja)
            );
        }

        if (!$registros) {
            $this->error('El libro no produjo registros.');

            return self::FAILURE;
        }

        [$coincide, $tabla] = $this->compararConPaneles($registros);
        $this->table(['Hoja', 'Solicitudes', 'Correcciones', 'Esperado (sol/err)', 'Fechas ajustadas'], $tabla);
        $this->newLine();

        if (!$coincide) {
            $this->warn('Los conteos NO coinciden con los paneles del libro.');

            if (!$this->dryRun && !$this->option('force')) {
                $this->error('Carga cancelada. Corrige el parser o usa --force para importar de todas formas.');

                return self::FAILURE;
            }
        }

        if ($this->dryRun) {
            foreach ($registros as $registro) {
                $modulo = $this->resolverModulo($registro['modulo']);
                $this->resolverSubmodulo($modulo['id'], $registro['submodulo']);
            }

            $this->reportarCatalogos();
            $this->info('Dry-run terminado: no se escribió nada.');

            return self::SUCCESS;
        }

        $insertados = 0;
        $omitidos = [];

        foreach ($this->agruparPorMes($registros) as $periodo => $filasMes) {
            $prefix = sprintf('[HIST-KPI %s]', $periodo);

            if (DB::table('tblTicket')->whereRaw('LEFT(problema, ?) = ?', [mb_strlen($prefix), $prefix])->exists()) {
                $omitidos[] = $periodo;
                continue;
            }

            [$anio, $mes] = array_map('intval', explode('-', $periodo));

            DB::transaction(function () use ($filasMes, $anio, $mes, $prefix, &$insertados) {
                $secuencia = [];

                foreach ($filasMes as $registro) {
                    $secuencia[$registro['bloque']] = ($secuencia[$registro['bloque']] ?? 0) + 1;
                    $marca = sprintf('%s B%d #%02d', $prefix, $registro['bloque'], $secuencia[$registro['bloque']]);

                    $fechaAlta = ($registro['inicio'] ?? Carbon::create($anio, $mes, 1))->copy()->setTime(9, 0);
                    $fechaFin = $registro['fin'];
                    $cerrado = $registro['cerrado'];

                    $fechaCierre = null;
                    if ($cerrado) {
                        $fechaCierre = ($fechaFin ?? Carbon::create($anio, $mes, 1)->endOfMonth())->copy()->setTime(18, 0);
                    }

                    $ticket = Ticket::create([
                        'id_Usuario' => $this->ids['usuario'],
                        'id_TipoSoporte' => $this->ids['tipoSoporte'],
                        'id_Tecnico' => $this->ids['usuario'],
                        'asunto' => mb_substr($registro['tarea'], 0, 200),
                        'problema' => $marca . ' ' . $registro['tarea'],
                        'fechaAlta' => $fechaAlta,
                        'activo' => 1,
                        'id_Estatus' => $cerrado ? $this->ids['cerrado'] : $this->ids['enProceso'],
                        'fechaCierre' => $fechaCierre,
                        'tipoTicket' => 'desarrollo',
                    ]);

                    $modulo = $this->resolverModulo($registro['modulo']);
                    $submodulo = $this->resolverSubmodulo($modulo['id'], $registro['submodulo']);

                    TicketDesarrolloDetalle::create([
                        'id_Ticket' => $ticket->id_Ticket,
                        'id_TipoDesarrollo' => $registro['bloque'] === 2
                            ? $this->ids['tipoError']
                            : $this->ids['tipoSolicitud'],
                        'id_Sistemas' => $modulo['sistema'] ?? 1,
                        'id_Modulo' => $modulo['id'],
                        'id_Submodulo' => $submodulo,
                        'fechaEstimadaTermino' => $fechaFin,
                    ]);

                    $insertados++;
                }
            });
        }

        $this->newLine();
        $this->info("Tickets insertados: {$insertados}");

        if ($omitidos) {
            $this->warn('Meses ya importados (omitidos): ' . implode(', ', $omitidos));
        }

        $this->reportarCatalogos();

        return self::SUCCESS;
    }

    /** Carga los ids fijos del catálogo y los mapas de módulo/submódulo. */
    private function resolverCatalogos(): bool
    {
        $this->ids = [
            'usuario' => (int) (DB::table('tblUsuario')
                ->where('nombre', 'like', 'Juan Edgardo Vidal%')
                ->where('activo', 1)
                ->value('id_Usuario') ?: 1),
            'tipoSoporte' => (int) DB::table('tblTipoSoporte')
                ->where('descripcion', 'Desarrollo de software')
                ->value('id_TipoSoporte'),
            'cerrado' => (int) DB::table('tblEstatus')
                ->where('descripcion', 'Cerrado')
                ->value('id_Estatus'),
            'enProceso' => (int) DB::table('tblEstatus')
                ->where('descripcion', 'En proceso')
                ->value('id_Estatus'),
            'tipoError' => (int) DB::table('tblTipoDesarrollo')
                ->where('nombre', 'Correccion de error')
                ->value('id_TipoDesarrollo'),
            'tipoSolicitud' => (int) DB::table('tblTipoDesarrollo')
                ->where('nombre', 'Mejora u Optimizacion')
                ->value('id_TipoDesarrollo'),
        ];

        foreach ($this->ids as $clave => $valor) {
            if ($valor <= 0) {
                $this->error("Catálogo incompleto: no se encontró el id de '{$clave}'.");

                return false;
            }
        }

        // El primer módulo de cada nombre gana: ERP-Alcomex (id_sistemas=1)
        // se carga antes que HelpDesk, así 'Inventario' resuelve al del ERP.
        foreach (DB::table('tblModulosSistemas')->orderBy('id_ModulosSistemas')->get() as $modulo) {
            $clave = $this->normalizar($modulo->nombre);

            if (!isset($this->modulos[$clave])) {
                $this->modulos[$clave] = [
                    'id' => (int) $modulo->id_ModulosSistemas,
                    'sistema' => (int) $modulo->id_sistemas,
                ];
            }
        }

        foreach (DB::table('tblSubmodulosSistemas')->get() as $submodulo) {
            $clave = $submodulo->id_ModulosSistemas . '|' . $this->normalizar($submodulo->nombre);
            $this->submodulos[$clave] = (int) $submodulo->id_SubmodulosSistemas;
        }

        return true;
    }

    /** Lee una hoja mensual y devuelve los tickets que contiene. */
    private function leerHoja($hoja, int $anio, int $mes, string $nombreHoja): array
    {
        $maxFila = $hoja->getHighestRow();
        $periodo = sprintf('%04d-%02d', $anio, $mes);
        $layout = 'nuevo';
        $encabezado = null;

        // El layout viejo (ene-may) trae Estatus/Realizado por en C/D; el nuevo
        // (jun-sep) trae Modulo/Submodulo y fechas. Junio no trae módculo en C.
        for ($fila = 1; $fila <= min($maxFila, 20); $fila++) {
            if (mb_strtoupper(trim((string) $hoja->getCell("A{$fila}")->getValue())) === 'NO') {
                $encabezado = $fila;
                $colC = mb_strtoupper(trim((string) $hoja->getCell("C{$fila}")->getValue()));
                if ($colC === 'ESTATUS') {
                    $layout = 'viejo';
                }
                break;
            }
        }

        $bloque = 0;
        $seccion = null;
        $registros = [];

        // Se recorre desde la fila 1: el primer título del bloque ("Indicadores
        // de solicitud...") está antes del encabezado y marca el inicio del
        // bloque 1. Las filas previas quedan fuera porque $bloque sigue en 0.
        for ($fila = 1; $fila <= $maxFila; $fila++) {
            $a = trim((string) $hoja->getCell("A{$fila}")->getValue());
            $b = trim((string) $hoja->getCell("B{$fila}")->getValue());
            $aMayus = mb_strtoupper($a);

            if ($aMayus !== '' && str_starts_with($aMayus, 'INDICADORES')) {
                if (str_contains($aMayus, 'TIEMPO') || str_contains($aMayus, 'TIMEPO')) {
                    // Bloque de tiempo de entrega: repite los dos anteriores.
                    $bloque = 0;
                } elseif (str_contains($aMayus, 'CORRECCION')) {
                    $bloque = 2;
                    $seccion = null;
                } else {
                    // En el layout viejo los bloques se titulan igual
                    // ("Indicadores de solicitud..."): el segundo son errores y
                    // el tercero es el resumen de tiempo (se ignora).
                    if ($layout === 'viejo' && $bloque >= 2) {
                        $bloque = 0;
                    } else {
                        $bloque = $bloque === 0 ? 1 : 2;
                    }
                    $seccion = null;
                }
                continue;
            }

            if ($b === '') {
                if ($bloque !== 0 && $layout === 'nuevo' && $a !== '' && !is_numeric($a) && !str_starts_with($a, '=')) {
                    $seccion = $a;
                }
                continue;
            }

            if (mb_strtolower($b) === 'tarea') {
                continue;
            }

            if ($bloque === 0) {
                continue;
            }

            $tarea = preg_replace('/\s+/u', ' ', $b);
            $inicio = null;
            $fin = null;
            $moduloTexto = null;
            $submoduloTexto = null;
            $cerrado = false;
            $ajustada = false;

            if ($layout === 'viejo') {
                $estatusTexto = $this->normalizar((string) $hoja->getCell("C{$fila}")->getValue());
                $cerrado = in_array($estatusTexto, ['terminado', 'terminada', 'cerrado'], true);
                $inicio = Carbon::create($anio, $mes, 1, 9, 0);
            } else {
                $moduloTexto = trim((string) $hoja->getCell("C{$fila}")->getValue()) ?: $seccion;
                $submoduloTexto = trim((string) $hoja->getCell("D{$fila}")->getValue()) ?: null;

                $inicio = $this->normalizarFecha(
                    $this->fechaDeCelda($hoja->getCell("E{$fila}")->getValue()),
                    $anio,
                    $mes,
                    $ajustada
                );
                $fin = $this->normalizarFecha(
                    $this->fechaDeCelda($hoja->getCell("F{$fila}")->getValue()),
                    $anio,
                    $mes,
                    $ajustada
                );
                $cerrado = $fin !== null;
            }

            $registros[] = [
                'hoja' => $nombreHoja,
                'periodo' => $periodo,
                'fila' => $fila,
                'bloque' => $bloque,
                'tarea' => $tarea,
                'modulo' => $moduloTexto,
                'submodulo' => $submoduloTexto,
                'inicio' => $inicio,
                'fin' => $fin,
                'cerrado' => $cerrado,
                'ajustada' => $ajustada,
            ];
        }

        return $registros;
    }

    /** Devuelve [anio, mes] a partir del nombre de la hoja (p. ej. "Junio 2026"). */
    private function periodoDeHoja(string $nombre): ?array
    {
        $partes = preg_split('/\s+/', mb_strtolower(trim($nombre)));

        if (count($partes) < 2) {
            return null;
        }

        $mes = self::MESES[$this->normalizar($partes[0])] ?? null;
        $anio = (int) end($partes);

        if (!$mes || $anio < 2020 || $anio > 2100) {
            return null;
        }

        return [$anio, $mes];
    }

    /** Compara lo leído con los paneles del libro. */
    private function compararConPaneles(array $registros): array
    {
        $coincide = true;
        $tabla = [];

        foreach ($this->agruparPorMes($registros) as $periodo => $filas) {
            $solicitudes = count(array_filter($filas, fn ($r) => $r['bloque'] === 1));
            $correcciones = count(array_filter($filas, fn ($r) => $r['bloque'] === 2));
            $esperado = self::ESPERADO[$periodo] ?? null;
            $cumple = $esperado === null || ($esperado[0] === $solicitudes && $esperado[1] === $correcciones);
            $coincide = $coincide && $cumple;

            $tabla[] = [
                $filas[0]['hoja'],
                $solicitudes,
                $correcciones,
                $esperado ? "{$esperado[0]} / {$esperado[1]}" : 'n/d',
                count(array_filter($filas, fn ($r) => $r['ajustada'])),
            ];
        }

        return [$coincide, $tabla];
    }

    private function agruparPorMes(array $registros): array
    {
        $porMes = [];

        foreach ($registros as $registro) {
            $porMes[$registro['periodo']][] = $registro;
        }

        ksort($porMes);

        return $porMes;
    }

    private function resolverModulo(?string $texto): array
    {
        $texto = $texto !== null ? trim($texto) : '';

        if ($texto === '') {
            return ['id' => null, 'sistema' => 1];
        }

        $clave = $this->normalizar($texto);
        $clave = self::ALIAS_MODULOS[$clave] ?? $clave;

        if (isset($this->modulos[$clave])) {
            return $this->modulos[$clave];
        }

        $this->faltantes['modulos'][$texto] = true;

        if ($this->dryRun) {
            return ['id' => null, 'sistema' => 1];
        }

        $nombre = $this->titulo($texto);
        $id = DB::table('tblModulosSistemas')->insertGetId([
            'nombre' => $nombre,
            'id_sistemas' => 1,
            'enabled' => 1,
        ], 'id_ModulosSistemas');

        $this->modulos[$clave] = ['id' => (int) $id, 'sistema' => 1];
        $this->creados['modulos'][] = $nombre;

        return $this->modulos[$clave];
    }

    private function resolverSubmodulo(?int $idModulo, ?string $texto): ?int
    {
        $texto = $texto !== null ? trim($texto) : '';

        if ($texto === '') {
            return null;
        }

        if (!$idModulo) {
            // Solo en dry-run: el módulo aún no existe, pero el submódulo se reporta.
            $this->faltantes['submodulos'][$texto] = true;

            return null;
        }

        $normalizado = $this->normalizar($texto);
        $normalizado = self::ALIAS_SUBMODULOS[$normalizado] ?? $normalizado;
        $clave = $idModulo . '|' . $normalizado;

        if (isset($this->submodulos[$clave])) {
            return $this->submodulos[$clave];
        }

        $this->faltantes['submodulos'][$texto] = true;

        if ($this->dryRun) {
            return null;
        }

        $nombre = $this->titulo($texto);
        $id = DB::table('tblSubmodulosSistemas')->insertGetId([
            'nombre' => $nombre,
            'id_ModulosSistemas' => $idModulo,
            'enabled' => 1,
        ], 'id_SubmodulosSistemas');

        $this->submodulos[$clave] = (int) $id;
        $this->creados['submodulos'][] = "{$nombre} (módulo {$idModulo})";

        return (int) $id;
    }

    /** Fechas del libro: seriales de Excel, texto d/m/Y o '----------' (sin término). */
    private function fechaDeCelda($valor): ?Carbon
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_numeric($valor)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $valor));
            } catch (\Throwable $e) {
                return null;
            }
        }

        $texto = trim((string) $valor);

        if ($texto === '' || str_starts_with($texto, '---')) {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $formato) {
            try {
                $fecha = Carbon::createFromFormat($formato, $texto);

                if ($fecha) {
                    return $fecha->startOfDay();
                }
            } catch (\Throwable $e) {
                // Prueba el siguiente formato.
            }
        }

        try {
            return Carbon::parse($texto);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Ancla la fecha al mes de la hoja: el libro tiene capturas con año/mes
     * equivocados (p. ej. 22/07/2022 o 29/09/2026 en la hoja de junio) y el
     * KPI agrupa por mes de fechaAlta.
     */
    private function normalizarFecha(?Carbon $fecha, int $anio, int $mes, bool &$ajustada): ?Carbon
    {
        if (!$fecha) {
            return null;
        }

        if ((int) $fecha->year === $anio && (int) $fecha->month === $mes) {
            return $fecha;
        }

        $ajustada = true;
        $ultimoDia = Carbon::create($anio, $mes, 1)->endOfMonth()->day;
        $dia = min((int) $fecha->day, $ultimoDia);

        return Carbon::create($anio, $mes, $dia, (int) $fecha->hour, (int) $fecha->minute);
    }

    private function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim($texto));
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n',
        ]);
        $texto = preg_replace('/[^a-z0-9]+/', ' ', $texto);

        return trim($texto);
    }

    private function titulo(string $texto): string
    {
        $texto = mb_convert_case(trim($texto), MB_CASE_TITLE, 'UTF-8');

        // Conectores en minúscula salvo al inicio: "Inventario de computo".
        return preg_replace_callback('/\s(De|Del|La|Las|Los|El|Y)\b/u', function ($m) {
            return ' ' . mb_strtolower($m[1]);
        }, $texto);
    }

    private function reportarCatalogos(): void
    {
        if ($this->creados['modulos'] || $this->creados['submodulos']) {
            $this->info('Catálogo creado:');
            foreach ($this->creados['modulos'] as $nombre) {
                $this->line("  · Módulo: {$nombre}");
            }
            foreach ($this->creados['submodulos'] as $nombre) {
                $this->line("  · Submódulo: {$nombre}");
            }
        }

        if ($this->dryRun && ($this->faltantes['modulos'] || $this->faltantes['submodulos'])) {
            $pendientes = array_merge(
                array_keys($this->faltantes['modulos']),
                array_keys($this->faltantes['submodulos'])
            );
            $this->line('Se crearían en catálogo: ' . implode(', ', array_unique($pendientes)));
        }
    }
}
