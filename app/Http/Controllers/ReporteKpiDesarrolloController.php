<?php

namespace App\Http\Controllers;

use App\Services\PermisosService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Layout;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Reporte mensual "KPI Desarrollo" (FO-SGC-08).
 *
 * Réplica fiel del libro manual "KPI-desarrollo.xlsx" del área de Desarrollo
 * de Software:
 *
 *  - Hoja "TABLERO DE CONTROL " (con espacio final, como el original):
 *    área, objetivo, 3 objetivos específicos con sus acciones, meta,
 *    frecuencia, responsable y recursos; columnas por mes (DICIEMBRE del año
 *    anterior y ENERO..mes descargado) en pares RESULTADO/META, META siempre
 *    100%, y el logo de la empresa anclado en A1:A3.
 *  - Hoja "{Mes} {Año}" con los 3 bloques del original:
 *      1. Indicadores de solicitud de desarrollo de software
 *      2. Indicadores de correccion de errores de desarrollo de software
 *      3. Indicadores de tiempo de entrega de desarrollo de software
 *    Cada bloque agrupa por módulo con numeración =ROW()-filaEncabezado,
 *    lleva su panel de resumen en H/I con las mismas fórmulas y su gráfica
 *    de pastel 3D (Solicitud de desarrollo / Correccion de errores / Tiempo
 *    de entrega) anclada en G:J bajo el panel, con porcentajes.
 *
 * Colores, fuentes, anchos, bordes y textos son los del archivo original.
 * El logo vive en resources/images/kpi-desarrollo-logo.png.
 */
class ReporteKpiDesarrolloController extends Controller
{
    /** Meses en el orden del libro original. */
    private const MESES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    /** Los 3 objetivos específicos del Tablero, con el texto exacto del original. */
    private const INDICADORES = [
        [
            'especifico' => 'Cumplir con la funcionalidad de la solicitud de desarrollo de software requerida',
            'acciones' => "*Realizar un analisis del desarrollo solicitado\n"
                . "*Realizar el desarrollo solicitado\n"
                . "*Realizar pruebas del desarrollo solicitado\n"
                . "*Implementar el desarrollo solicitado\n"
                . "*Capacitar al usuario ",
        ],
        [
            'especifico' => 'Corregir errores de los desarrollos de software solicitados',
            'acciones' => "*Revisar con usuario el error reportado\n"
                . "*Analizar el error reportado\n"
                . "*Corregir el error reportado\n"
                . "* Implementar la solicion del error reportado\n"
                . "* Notificar al usuario la correccion del error",
        ],
        [
            'especifico' => 'Entregar en tiempo que se asigna una solicitud de desarrollo de software',
            'acciones' => '* Realizar el desarrollo solicitado en el tiempo estimado de entrega',
        ],
    ];

    public function __construct(private PermisosService $permisosService)
    {
    }

    public function exportar(Request $request)
    {
        $idUsuario = (int) auth()->id();
        if (!$this->permisosService->puedeDescargarKpiDesarrollo($idUsuario)) {
            $areas = implode(' / ', (array) config('kpi.areas_desarrollo', ['Desarrollo de Software']));
            abort(403, "El reporte KPI Desarrollo es exclusivo del área: {$areas}.");
        }

        $datos = $request->validate([
            'mes' => ['nullable', 'integer', 'between:1,12'],
            'anio' => ['nullable', 'integer', 'between:2020,' . now()->year],
        ]);
        $mes = (int) ($datos['mes'] ?? now()->month);
        $anio = (int) ($datos['anio'] ?? now()->year);
        $mesNombre = self::MESES[$mes];

        $tickets = $this->ticketsDelMes($anio, $mes);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator(auth()->user()?->nombreCompleto() ?? 'HelpDesk')
            ->setTitle("KPI Desarrollo - {$mesNombre} {$anio}")
            ->setSubject('Reporte mensual KPI Desarrollo');

        // Hoja 0: Tablero (como el original).
        $this->hojaTablero($spreadsheet->getActiveSheet(), $anio, $mes, $mesNombre);
        // Hoja 1: el mes descargado.
        $this->hojaMes($spreadsheet->createSheet(), $mesNombre, $anio, $tickets);

        $spreadsheet->setActiveSheetIndex(0);

        $fileName = "KPI Desarrollo - {$mesNombre} {$anio}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->setIncludeCharts(true);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // ─────────────────────────── Datos ───────────────────────────

    /**
     * Tickets de desarrollo del mes: los cuyo tipo de soporte pertenece al
     * área de desarrollo (config kpi.areas_desarrollo, contra tblArea.nombre
     * vía tblTipoSoporte.id_Area — sin IDs hardcodeados), con su detalle de
     * desarrollo (módulo/submódulo/tipo) cuando exista.
     */
    private function ticketsDelMes(int $anio, int $mes)
    {
        $inicio = Carbon::create($anio, $mes, 1)->startOfMonth();
        $fin = $inicio->copy()->endOfMonth();
        $areas = config('kpi.areas_desarrollo', ['Desarrollo de Software']);

        return DB::table('tblTicket as t')
            ->join('tblTipoSoporte as ts', 'ts.id_TipoSoporte', '=', 't.id_TipoSoporte')
            ->join('tblArea as a', 'a.id_Area', '=', 'ts.id_Area')
            ->leftJoin('tblEstatus as e', 'e.id_Estatus', '=', 't.id_Estatus')
            ->leftJoin('tblTicketDesarrolloDetalle as d', 'd.id_Ticket', '=', 't.id_Ticket')
            ->leftJoin('tblModulosSistemas as ms', 'ms.id_ModulosSistemas', '=', 'd.id_Modulo')
            ->leftJoin('tblSubmodulosSistemas as sms', 'sms.id_SubmodulosSistemas', '=', 'd.id_Submodulo')
            ->leftJoin('tblTipoDesarrollo as td', 'td.id_TipoDesarrollo', '=', 'd.id_TipoDesarrollo')
            ->where('t.activo', true)
            ->whereIn('a.nombre', $areas)
            ->whereBetween('t.fechaAlta', [$inicio, $fin])
            ->orderBy('t.fechaAlta')
            ->get([
                't.id_Ticket', 't.asunto', 't.fechaAlta', 't.fechaCierre',
                'e.descripcion as estatus', 'd.fechaEstimadaTermino',
                'ms.nombre as modulo', 'sms.nombre as submodulo',
                'td.nombre as tipoDesarrollo',
            ]);
    }

    /** Resumen de un mes para el Tablero (solicitudes y correcciones). */
    private function resumenMes(int $anio, int $mes): array
    {
        $inicio = Carbon::create($anio, $mes, 1)->startOfMonth();
        $fin = $inicio->copy()->endOfMonth();
        $areas = config('kpi.areas_desarrollo', ['Desarrollo de Software']);

        $filas = DB::table('tblTicket as t')
            ->join('tblTipoSoporte as ts', 'ts.id_TipoSoporte', '=', 't.id_TipoSoporte')
            ->join('tblArea as a', 'a.id_Area', '=', 'ts.id_Area')
            ->leftJoin('tblEstatus as e', 'e.id_Estatus', '=', 't.id_Estatus')
            ->leftJoin('tblTicketDesarrolloDetalle as d', 'd.id_Ticket', '=', 't.id_Ticket')
            ->leftJoin('tblTipoDesarrollo as td', 'td.id_TipoDesarrollo', '=', 'd.id_TipoDesarrollo')
            ->where('t.activo', true)
            ->whereIn('a.nombre', $areas)
            ->whereBetween('t.fechaAlta', [$inicio, $fin])
            ->get(['e.descripcion as estatus', 'td.nombre as tipo']);

        $esCorreccion = fn ($t) => str_contains(mb_strtolower((string) $t->tipo), 'error');
        $esCerrado = fn ($t) => in_array((string) $t->estatus, ['Cerrado', 'Resuelto'], true);

        $solicitudes = $filas->reject($esCorreccion);
        $errores = $filas->filter($esCorreccion);

        $solTotal = $solicitudes->count();
        $solPend = $solicitudes->reject($esCerrado)->count();
        $errTotal = $errores->count();
        $errPend = $errores->reject($esCerrado)->count();

        return [
            'tiene' => $filas->isNotEmpty(),
            'sol_total' => $solTotal,
            'sol_pend' => $solPend,
            'err_total' => $errTotal,
            'err_pend' => $errPend,
        ];
    }

    /**
     * Meses del Tablero, en el orden del original: DICIEMBRE del año anterior
     * y luego ENERO..mes descargado (el archivo real lo llena así). Cuando el
     * mes descargado es DICIEMBRE se usa ENERO..DICIEMBRE del año.
     */
    private function mesesTablero(int $anio, int $mes): array
    {
        $meses = [];
        if ($mes === 12) {
            for ($m = 1; $m <= 12; ++$m) {
                $meses[] = ['anio' => $anio, 'mes' => $m];
            }
            return $meses;
        }

        $meses[] = ['anio' => $anio - 1, 'mes' => 12];
        for ($m = 1; $m <= $mes; ++$m) {
            $meses[] = ['anio' => $anio, 'mes' => $m];
        }

        return $meses;
    }

    // ─────────────────────────── Hoja del mes ───────────────────────────

    private function hojaMes(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $mesNombre, int $anio, $tickets): void
    {
        $sheet->setTitle("{$mesNombre} {$anio}");
        $sheet->getSheetView()->setZoomScale(100);
        $sheet->setShowGridlines(true);

        $esCorreccion = fn ($t) => str_contains(mb_strtolower((string) $t->tipoDesarrollo), 'error');
        $solicitudes = $tickets->reject($esCorreccion)->values();
        $errores = $tickets->filter($esCorreccion)->values();

        // ── Bloque 1: solicitudes de desarrollo ──
        $fila = 2;
        $this->tituloBloque($sheet, $fila, 'Indicadores de solicitud de desarrollo de software');
        $this->encabezadosColumnas($sheet, ++$fila);

        $panel1 = $this->panelSolicitudes($sheet, $solicitudes, $mesNombre);
        $this->grafica($sheet, 1, 'Solicitud de desarrollo', $panel1);

        $fila = $this->secciones($sheet, ++$fila, $solicitudes);

        // ── Bloque 2: corrección de errores ──
        // El panel va 6 filas bajo el título del bloque (como el original).
        $inicioBloque2 = max($fila + 1, $this->finGrafica($panel1) + 2);
        $this->tituloBloque($sheet, $inicioBloque2, 'Indicadores de correccion de errores de desarrollo de software');
        $this->encabezadosColumnas($sheet, $inicioBloque2 + 1);

        $panel2 = $this->panelErrores($sheet, $errores, $mesNombre, $inicioBloque2 + 6);
        $this->grafica($sheet, 2, 'Correccion de errores', $panel2);

        $fila = $this->secciones($sheet, $inicioBloque2 + 2, $errores);

        // ── Bloque 3: tiempo de entrega (todas las tareas) ──
        $inicioBloque3 = max($fila + 1, $this->finGrafica($panel2) + 2);
        $this->tituloBloque($sheet, $inicioBloque3, 'Indicadores de tiempo de entrega de desarrollo de software');
        $this->encabezadosColumnas($sheet, $inicioBloque3 + 1);

        $panel3 = $this->panelTiempo(
            $sheet,
            $panel1,
            $panel2,
            $mesNombre,
            max($inicioBloque3 + 5, $this->finGrafica($panel2) + 2)
        );
        $this->grafica($sheet, 3, 'Tiempo de entrega', $panel3);

        $this->secciones($sheet, $inicioBloque3 + 2, $tickets->values());

        // ── Anchos y altura de la fila 1 (como el original) ──
        foreach (['A' => 3.57, 'B' => 58.29, 'C' => 16.43, 'D' => 16.14, 'E' => 11.43, 'F' => 14, 'H' => 56.29, 'J' => 53] as $col => $ancho) {
            $sheet->getColumnDimension($col)->setWidth($ancho);
        }
        $sheet->getRowDimension(1)->setRowHeight(15.75);
    }

    /** Título de bloque: franja naranja fusionada A:F. */
    private function tituloBloque(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $fila, string $texto): void
    {
        $sheet->mergeCells("A{$fila}:F{$fila}");
        $sheet->setCellValue("A{$fila}", $texto);
        $this->aplicarEstiloTitulo($sheet, "A{$fila}:F{$fila}");
    }

    private function aplicarEstiloTitulo(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $rango): void
    {
        $sheet->getStyle($rango)->applyFromArray([
            'font' => ['name' => 'Calibri', 'size' => 11, 'bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFF9933']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_BOTTOM,
            ],
        ]);
    }

    /** Fila de encabezados No / Tarea / Modulo / Submodulo / Fecha inicio / Fecha Termino. */
    private function encabezadosColumnas(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $fila): void
    {
        $cabeceras = [
            'A' => 'No', 'B' => 'Tarea', 'C' => 'Modulo', 'D' => 'Submodulo',
            'E' => 'Fecha inicio', 'F' => 'Fecha Termino',
        ];
        foreach ($cabeceras as $col => $texto) {
            $sheet->setCellValue("{$col}{$fila}", $texto);
        }
        $sheet->getStyle("A{$fila}:F{$fila}")->applyFromArray([
            'font' => ['name' => 'Calibri', 'size' => 11, 'bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0B5394']],
            'alignment' => ['vertical' => Alignment::VERTICAL_BOTTOM],
        ]);
        $sheet->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    /**
     * Escribe las secciones por módulo (encabezado fusionado A:F, azul claro)
     * y sus tareas. Devuelve la fila siguiente a la última escrita.
     */
    private function secciones(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $fila, $tickets): int
    {
        if ($tickets->isEmpty()) {
            return $fila;
        }

        $grupos = $tickets
            ->groupBy(fn ($t) => trim((string) ($t->modulo ?: 'Sin módulo')))
            ->sortBy(fn ($g, $nombre) => $nombre === 'Sin módulo' ? PHP_INT_MAX : mb_strtolower($nombre));

        foreach ($grupos as $nombre => $grupo) {
            // Encabezado de sección (nombre del módulo en mayúsculas).
            $sheet->mergeCells("A{$fila}:F{$fila}");
            $sheet->setCellValue("A{$fila}", mb_strtoupper((string) $nombre));
            $sheet->getStyle("A{$fila}:F{$fila}")->applyFromArray([
                'font' => ['name' => 'Calibri', 'size' => 11, 'bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFC8E3FB']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);

            $filaEncabezado = $fila;
            ++$fila;

            foreach ($grupo as $t) {
                // Numeración por fórmula, como el original (=ROW()-encabezado).
                $sheet->setCellValue("A{$fila}", '=ROW()-' . $filaEncabezado);
                $sheet->setCellValue("B{$fila}", (string) $t->asunto);
                $sheet->setCellValue("C{$fila}", (string) ($t->modulo ?? ''));
                $sheet->setCellValue("D{$fila}", (string) ($t->submodulo ?? ''));
                $sheet->setCellValue("E{$fila}", ExcelDate::PHPToExcel(Carbon::parse($t->fechaAlta)->startOfDay()));
                // Igual que el original: "----------" cuando no hay fecha de término.
                if ($t->fechaCierre) {
                    $sheet->setCellValue("F{$fila}", ExcelDate::PHPToExcel(Carbon::parse($t->fechaCierre)->startOfDay()));
                } else {
                    $sheet->setCellValue("F{$fila}", '----------');
                }

                // Estilo de la fila de datos (Calibri 8; fechas m/d/yyyy).
                $sheet->getStyle("A{$fila}:F{$fila}")->applyFromArray([
                    'font' => ['name' => 'Calibri', 'size' => 8],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C{$fila}:F{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("E{$fila}:F{$fila}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("E{$fila}:F{$fila}")->getNumberFormat()->setFormatCode('m/d/yyyy');

                ++$fila;
            }
        }

        return $fila;
    }

    // ─────────────────────────── Paneles H/I ───────────────────────────

    /** Encabezado azul de panel (H:I en dos filas). Devuelve la fila del cuadro. */
    private function encabezadoPanel(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $fila, string $texto): int
    {
        $sheet->mergeCells("H{$fila}:H" . ($fila + 1));
        $sheet->mergeCells("I{$fila}:I" . ($fila + 1));
        $sheet->setCellValue("H{$fila}", $texto);
        $sheet->setCellValue("I{$fila}", 'Cantidad');
        $sheet->getStyle("H{$fila}:I" . ($fila + 1))->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 8, 'bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF90C6F7']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getStyle("H{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("I{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $this->bordePanel($sheet, "H{$fila}:I" . ($fila + 1));

        return $fila + 2;
    }

    /** Cuadro de 4 filas (etiqueta / valor) con bordes y fuente Arial 8. */
    private function cuadroPanel(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $fila, array $filas): void
    {
        foreach ($filas as $i => [$etiqueta, $valor, $formato]) {
            $r = $fila + $i;
            $sheet->setCellValue("H{$r}", $etiqueta);
            $sheet->setCellValue("I{$r}", $valor);
            if ($formato !== null) {
                $sheet->getStyle("I{$r}")->getNumberFormat()->setFormatCode($formato);
            }
        }

        $rango = "H{$fila}:I" . ($fila + count($filas) - 1);
        $sheet->getStyle($rango)->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 8],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("H{$fila}:H" . ($fila + count($filas) - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("I{$fila}:I" . ($fila + count($filas) - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $this->bordePanel($sheet, $rango);
    }

    /** Bordes finos con contorno medio, como el original. */
    private function bordePanel(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $rango): void
    {
        $sheet->getStyle($rango)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        [$ini, $fin] = explode(':', $rango);
        $colIni = preg_replace('/\d+/', '', $ini);
        $colFin = preg_replace('/\d+/', '', $fin);
        $filaIni = (int) preg_replace('/\D+/', '', $ini);
        $filaFin = (int) preg_replace('/\D+/', '', $fin);

        $sheet->getStyle("{$colIni}{$filaIni}:{$colFin}{$filaIni}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle("{$colIni}{$filaFin}:{$colFin}{$filaFin}")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle("{$colIni}{$filaIni}:{$colIni}{$filaFin}")->getBorders()->getLeft()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle("{$colFin}{$filaIni}:{$colFin}{$filaFin}")->getBorders()->getRight()->setBorderStyle(Border::BORDER_MEDIUM);
    }

    /** Panel 1 (solicitudes). Devuelve filas de sus celdas para el panel 3. */
    private function panelSolicitudes(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, $solicitudes, string $mesNombre): array
    {
        $total = $solicitudes->count();
        $pendientes = $solicitudes->reject(
            fn ($t) => in_array((string) $t->estatus, ['Cerrado', 'Resuelto'], true)
        )->count();

        $this->encabezadoPanel($sheet, 2, "Indicadores de solicitud de desarrollo de software del mes de {$mesNombre}");

        $this->cuadroPanel($sheet, 4, [
            ['Solicitudes de desarrollo de software:', $total, null],
            ['Tareas terminadas:', '=I4-I6', null],
            ['Tareas pendientes:', $pendientes, null],
            ['Porcentaje de cumplimiento:', $total > 0 ? '=I5/I4' : 0, '0%'],
        ]);

        return ['inicio' => 2, 'total' => 4, 'terminadas' => 5, 'pendientes' => 6, 'porcentaje' => 7, 'total_valor' => $total];
    }

    /** Panel 2 (errores). Se coloca 6 filas después del título del bloque. */
    private function panelErrores(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, $errores, string $mesNombre, int $filaEncabezado): array
    {
        $total = $errores->count();
        $pendientes = $errores->reject(
            fn ($t) => in_array((string) $t->estatus, ['Cerrado', 'Resuelto'], true)
        )->count();

        $cuadro = $this->encabezadoPanel($sheet, $filaEncabezado, "Indicadores de errores reportados del mes de {$mesNombre}");

        $this->cuadroPanel($sheet, $cuadro, [
            ['Errores reportados', $total, null],
            ['Errores terminadas:', '=I' . $cuadro . '-I' . ($cuadro + 2), null],
            ['Errores pendientes:', $pendientes, null],
            ['Porcentaje de cumplimiento:', $total > 0 ? '=I' . ($cuadro + 1) . '/I' . $cuadro : 0, '0%'],
        ]);

        return [
            'inicio' => $filaEncabezado,
            'total' => $cuadro,
            'terminadas' => $cuadro + 1,
            'pendientes' => $cuadro + 2,
            'porcentaje' => $cuadro + 3,
            'total_valor' => $total,
            'fin' => $cuadro + 3,
        ];
    }

    /** Panel 3 (tiempo de entrega): suma de los paneles 1 y 2, como el original. */
    private function panelTiempo(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $p1, array $p2, string $mesNombre, int $filaEncabezado): array
    {
        $total = $p1['total_valor'] + $p2['total_valor'];

        $filaEncabezado = max($filaEncabezado, $p2['porcentaje'] + 1);
        $cuadro = $this->encabezadoPanel($sheet, $filaEncabezado, "Indicadores de entrega en tiempo de desarrollo de software del mes de {$mesNombre}");

        $totalFila = $cuadro;
        $aTiempoFila = $cuadro + 1;
        $tardeFila = $cuadro + 2;
        $pctFila = $cuadro + 3;

        $this->cuadroPanel($sheet, $cuadro, [
            ['Tiempo de entrega total', '=I' . $p1['total'] . '+I' . $p2['total'], null],
            ['Entregado a tiempo', '=I' . $p1['terminadas'] . '+I' . $p2['terminadas'], null],
            ['No entregado  a tiempo', '=I' . $p1['pendientes'] . '+I' . $p2['pendientes'], null],
            ['Porcentaje de cumplimiento:', $total > 0 ? '=I' . $aTiempoFila . '/I' . $totalFila : 0, '0%'],
        ]);

        return [
            'inicio' => $filaEncabezado,
            'total' => $cuadro,
            'terminadas' => $aTiempoFila,
            'pendientes' => $tardeFila,
            'porcentaje' => $pctFila,
            'total_valor' => $total,
            'fin' => $pctFila,
        ];
    }

    // ─────────────────────────── Gráficas ───────────────────────────

    /**
     * Gráfica de pastel 3D del panel, como el original: ocupa G:J, empieza
     * 8 filas debajo del encabezado del panel y mide 12 filas de alto.
     * Las series son las mismas celdas del panel (terminadas / pendientes).
     */
    private function grafica(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $indice, string $titulo, array $panel): void
    {
        $hoja = $sheet->getTitle();
        $etiquetas = "'{$hoja}'!\$H\${$panel['terminadas']}:\$H\${$panel['pendientes']}";
        $valores = "'{$hoja}'!\$I\${$panel['terminadas']}:\$I\${$panel['pendientes']}";

        // Porcentaje dentro de cada rebanada, como el original.
        $etiquetasDatos = new Layout();
        $etiquetasDatos->setShowPercent(true);
        $etiquetasDatos->setShowVal(false);
        $etiquetasDatos->setShowCatName(false);
        $etiquetasDatos->setShowSerName(false);
        $etiquetasDatos->setShowLegendKey(false);
        $etiquetasDatos->setShowBubbleSize(false);
        $etiquetasDatos->setShowLeaderLines(true);

        $serieValores = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, $valores, null, 2);
        $serieValores->setLabelLayout($etiquetasDatos);

        $series = new DataSeries(
            DataSeries::TYPE_PIECHART_3D,
            // Los pasteles no llevan <c:grouping>; con '' el writer lo omite.
            '',
            [0],
            [],
            [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, $etiquetas, null, 2)],
            [$serieValores]
        );

        $chart = new Chart(
            "grafico{$indice}",
            new Title($titulo),
            new Legend(Legend::POSITION_RIGHT, null, false),
            new PlotArea(null, [$series])
        );

        $top = $panel['inicio'] + 8;
        $chart->setTopLeftPosition("G{$top}");
        $chart->setBottomRightPosition('J' . ($top + 11));

        $sheet->addChart($chart);
    }

    /** Última fila que ocupa la gráfica del panel (para no encimarla). */
    private function finGrafica(array $panel): int
    {
        return $panel['inicio'] + 19;
    }

    // ─────────────────────────── Tablero de Control ───────────────────────────

    private function hojaTablero(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $tab, int $anio, int $mes, string $mesNombre): void
    {
        $tab->setTitle('TABLERO DE CONTROL ');
        $tab->setShowGridlines(false);
        $tab->getSheetView()->setZoomScale(70);

        // Encabezado FO-SGC-08.
        $tab->setCellValue('B1', 'FO-SGC-08 TABLERO DE CONTROL');
        $tab->getStyle('B1')->getFont()->setName('Arial Black')->setSize(72)->setBold(true);
        $tab->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_BOTTOM);
        $tab->mergeCells('B1:I3');

        $tab->setCellValue('J1', 'FO-SGC-08');
        $tab->setCellValue('J2', ExcelDate::PHPToExcel(now()));
        $tab->setCellValue('J3', 'Revisión: 00');
        $tab->mergeCells('J1:K1');
        $tab->mergeCells('J2:K2');
        $tab->mergeCells('J3:K3');
        $tab->getStyle('J1:K3')->applyFromArray([
            'font' => ['name' => 'Calibri', 'size' => 14, 'bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM], 'bottom' => ['borderStyle' => Border::BORDER_MEDIUM], 'left' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);
        $tab->getStyle('J2')->getNumberFormat()->setFormatCode('m/d/yyyy');

        $tab->mergeCells('A1:A3');

        // Logo de la empresa, como el original: anclado de A1 al inicio de la
        // fila 3 (misma caja que el dibujo del archivo de referencia).
        $rutaLogo = resource_path('images/kpi-desarrollo-logo.png');
        if (is_file($rutaLogo)) {
            $logo = new Drawing();
            $logo->setName('Logo');
            $logo->setDescription('Logo');
            $logo->setPath($rutaLogo);
            $logo->setCoordinates('A1');
            $logo->setWidthAndHeight(309, 72);
            $logo->setWorksheet($tab);
        }

        // Banda del título.
        $tab->mergeCells('A4:K4');
        $tab->setCellValue('A4', 'TABLERO DE CONTROL ');
        $tab->getStyle('A4')->applyFromArray([
            'font' => ['name' => 'Calibri', 'size' => 18, 'bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF7E9732']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_BOTTOM],
        ]);
        $tab->getStyle('A4')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

        // Cabeceras fijas (A-I) sobre dos filas.
        $cabeceras = [
            'A' => 'AREA ', 'B' => 'OBJETIVO ', 'C' => 'OBJETIVO ESPECIFICO', 'D' => 'ACCIONES',
            'E' => 'INDICADOR', 'F' => 'META', 'G' => 'FRECUENCIA', 'H' => 'RESPONSABLE', 'I' => 'RECURSOS',
        ];
        foreach ($cabeceras as $col => $texto) {
            // El original solo fusiona A, E, F, G, H e I (B, C y D van en la fila 5).
            if (in_array($col, ['A', 'E', 'F', 'G', 'H', 'I'], true)) {
                $tab->mergeCells("{$col}5:{$col}6");
            }
            $tab->setCellValue("{$col}5", $texto);
        }

        // Meses: DICIEMBRE (año anterior) y ENERO..mes descargado.
        $meses = $this->mesesTablero($anio, $mes);
        foreach ($meses as $i => $m) {
            $colResultado = Coordinate::stringFromColumnIndex(10 + (($i) * 2));
            $colMeta = Coordinate::stringFromColumnIndex(11 + (($i) * 2));
            $tab->mergeCells("{$colResultado}5:{$colMeta}5");
            $tab->setCellValue("{$colResultado}5", mb_strtoupper(self::MESES[$m['mes']]));
            $tab->setCellValue("{$colResultado}6", 'RESULTADO');
            $tab->setCellValue("{$colMeta}6", 'META');
        }

        // Textos del área (fusionados A7:A9 y B7:B9, como la plantilla).
        $tab->mergeCells('A7:A9');
        $tab->mergeCells('B7:B9');
        $tab->setCellValue('A7', 'DESARROLLO DE SOFTWARE');
        $tab->setCellValue('B7', 'Cumplir con los requerimientos de desarrollo de software');

        $fila = 7;
        foreach (self::INDICADORES as $indicador) {
            $tab->setCellValue("C{$fila}", $indicador['especifico']);
            $tab->setCellValue("D{$fila}", $indicador['acciones']);
            $tab->setCellValue("G{$fila}", 'Mensual ');
            $tab->setCellValue("H{$fila}", 'Jefe de TI');
            $tab->setCellValue("I{$fila}", 'Equipo de computo, internet, IDE, lenguajes de programacion');
            ++$fila;
        }

        // Resultados y metas por mes.
        foreach ($meses as $i => $m) {
            $colResultado = Coordinate::stringFromColumnIndex(10 + ($i * 2));
            $colMeta = Coordinate::stringFromColumnIndex(11 + ($i * 2));
            $r = $this->resumenMes((int) $m['anio'], (int) $m['mes']);

            [$v7, $v8, $v9] = $this->porcentajesTablero($r);

            foreach (['7' => $v7, '8' => $v8, '9' => $v9] as $f => $valor) {
                if ($valor !== null) {
                    $tab->setCellValue("{$colResultado}{$f}", $valor);
                }
                $tab->getStyle("{$colResultado}{$f}")->getNumberFormat()->setFormatCode('0%');
            }

            // La META del tablero es 100% en los tres indicadores, siempre
            // (así está en el archivo original, sin depender de los datos).
            foreach (['7', '8', '9'] as $f) {
                $tab->setCellValue("{$colMeta}{$f}", 1);
                $tab->getStyle("{$colMeta}{$f}")->getNumberFormat()->setFormatCode('0%');
            }
        }

        // Estilo del bloque de datos (A7 hasta el último mes).
        $ultimaCol = Coordinate::stringFromColumnIndex(11 + ((count($meses) - 1) * 2));
        $tab->getStyle("A5:{$ultimaCol}9")->applyFromArray([
            'font' => ['name' => 'Calibri', 'size' => 14],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $tab->getStyle("A5:{$ultimaCol}6")->applyFromArray([
            'font' => ['name' => 'Calibri', 'size' => 14, 'bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF113052']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $tab->getStyle("A7:{$ultimaCol}9")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFC0D8F2']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $tab->getStyle('A7:A9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $tab->getStyle('A7:A9')->getFont()->setBold(true);
        $tab->getStyle('C7:D9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $tab->getStyle('F7:F9')->getNumberFormat()->setFormatCode('0%');
        $tab->getStyle('F7:F9')->getFont()->setBold(true);
        $tab->getStyle('G7:I9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Resultados y metas: negrita y centrado, como el original.
        $tab->getStyle("J7:{$ultimaCol}9")->getFont()->setBold(true);
        $tab->getStyle("J7:{$ultimaCol}9")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $tab->getStyle("J5:{$ultimaCol}6")->getAlignment()->setVertical(Alignment::VERTICAL_BOTTOM);

        // Anchos de columna.
        $anchos = ['A' => 43.43, 'B' => 30.43, 'C' => 56.29, 'D' => 81, 'E' => 114.43, 'F' => 14.43, 'G' => 20.43, 'H' => 25.43, 'I' => 59.86];
        foreach ($anchos as $col => $ancho) {
            $tab->getColumnDimension($col)->setWidth($ancho);
        }
        foreach ($meses as $i => $m) {
            $tab->getColumnDimension(Coordinate::stringFromColumnIndex(10 + ($i * 2)))->setWidth(14.86);
        }

        // Alturas de fila.
        foreach ([1 => 36, 2 => 36, 3 => 52.5, 4 => 23.25, 5 => 21, 6 => 21, 7 => 99.75, 8 => 99.75, 9 => 71.45] as $r => $alto) {
            $tab->getRowDimension($r)->setRowHeight($alto);
        }

        // Impresión vertical, como el original.
        $tab->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
    }

    /**
     * Porcentajes del Tablero: fila 7 solicitudes, fila 8 correcciones,
     * fila 9 tiempo de entrega (suma de ambas, como el original).
     */
    private function porcentajesTablero(array $r): array
    {
        $solTotal = $r['sol_total'];
        $errTotal = $r['err_total'];
        $total = $solTotal + $errTotal;

        $v7 = !$r['tiene'] || $solTotal === 0 ? null : ($solTotal - $r['sol_pend']) / $solTotal;
        $v8 = !$r['tiene'] ? null : ($errTotal === 0 ? 0 : ($errTotal - $r['err_pend']) / $errTotal);
        $v9 = $total === 0 ? null : (($solTotal - $r['sol_pend']) + ($errTotal - $r['err_pend'])) / $total;

        return [$v7, $v8, $v9];
    }
}
