<?php

namespace App\Http\Controllers;

use App\Services\PermisosService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\ChartColor;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Reporte mensual "KPI Desarrollo" (FO-SGC-08), réplica del libro Excel
 * manual "KPI-desarrollo.xlsx" del área de Desarrollo de Software.
 *
 * Genera un libro con:
 *  - Hoja "Tablero de Control": los 3 indicadores del área (cumplir
 *    requerimientos / corregir errores / entregar en tiempo) con el
 *    cumplimiento mensual hasta el mes descargado.
 *  - Hoja del mes: 3 bloques (solicitudes / corrección de errores /
 *    tiempo de entrega) con tareas agrupadas por módulo, panel resumen
 *    con fórmulas y gráficas de pastel y barras.
 *
 * Los datos salen de la conexión activa (productiva en producción).
 */
class ReporteKpiDesarrolloController extends Controller
{
    /** Meses en el orden del libro original. */
    private const MESES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    /**
     * Filas 7-9 del Tablero de Control (textos exactos de la plantilla
     * "KPI-desarrollo.xlsx"). El área (A) y el objetivo general (B) van
     * fusionados en las tres filas, igual que el original.
     */
    private const INDICADORES = [
        [
            'especifico' => 'Cumplir con la funcionalidad de la solicitud de desarrollo de software requerida',
            'acciones' => "*Realizar un analisis del desarrollo solicitado\n"
                . "*Realizar el desarrollo solicitado\n"
                . "*Realizar pruebas del desarrollo solicitado\n"
                . "*Implementar el desarrollo solicitado\n"
                . "*Capacitar al usuario",
        ],
        [
            'especifico' => 'Corregir errores de los desarrollos de software solicitados',
            'acciones' => "*Revisar con usuario el error reportado\n"
                . "*Analizar el error reportado\n"
                . "*Corregir el error reportado\n"
                . "*Implementar la solicion del error reportado\n"
                . "*Notificar al usuario la correccion del error",
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
        // Reporte exclusivo del área de Desarrollo (config/kpi.php).
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

        $tickets = $this->ticketsDelMes($mes, $anio);
        $cumplimientoAnual = $this->cumplimientoPorMes($anio, $mes);

        $total = $tickets->count();
        // 'Resuelto' cuenta como terminado (consistente con el KPI de Soporte).
        $terminados = (int) $tickets->whereIn('estatus', ['Cerrado', 'Resuelto'])->count();
        $pendientes = $total - $terminados;

        // Bloques 2 y 3: clasificación que la plantilla hace a mano.
        $correcciones = $tickets
            ->filter(fn ($t) => str_contains(strtolower((string) $t->tipoDesarrollo), 'error'))
            ->values();
        $aTiempo = $tickets
            ->filter(fn ($t) => $t->fechaEstimadaTermino
                && $t->fechaCierre
                && Carbon::parse($t->fechaCierre) <= Carbon::parse($t->fechaEstimadaTermino))
            ->values();

        $porModulo = $tickets
            ->groupBy(fn ($t) => trim((string) ($t->modulo ?: 'Sin módulo')))
            ->map(fn ($g) => $g->count())
            ->sortBy([['value', 'desc'], ['key', 'asc']]);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator(auth()->user()?->nombreCompleto() ?? 'HelpDesk')
            ->setTitle("KPI Desarrollo - {$mesNombre} {$anio}")
            ->setSubject('Reporte mensual KPI Desarrollo');

        $this->hojaMes($spreadsheet->getActiveSheet(), $mesNombre, $anio, $tickets, [
            'correcciones' => $correcciones,
            'aTiempo' => $aTiempo,
            'total' => $total,
            'terminados' => $terminados,
            'pendientes' => $pendientes,
            'porModulo' => $porModulo,
        ]);
        $this->hojaTablero($spreadsheet, $mes, $anio, $cumplimientoAnual);

        // El libro abre con el Tablero como hoja 1.
        $spreadsheet->setIndexByName('Tablero de Control', 0);
        $spreadsheet->setActiveSheetIndexByName('Tablero de Control');

        $fileName = "KPI Desarrollo - {$mesNombre} {$anio}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            // Sin esto el writer omite las gráficas del XLSX.
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
     * desarrollo (módulo/submódulo/fecha estimada) cuando exista.
     */
    private function ticketsDelMes(int $mes, int $anio)
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

    /**
     * Por mes (enero → mes descargado): cumplimiento general, de
     * correcciones de errores y de entrega en tiempo para el Tablero.
     * Los dos últimos quedan null cuando no hay datos que medir.
     */
    private function cumplimientoPorMes(int $anio, int $hastaMes): array
    {
        $inicio = Carbon::create($anio, 1, 1)->startOfYear();
        $fin = Carbon::create($anio, $hastaMes, 1)->endOfMonth();
        $areas = config('kpi.areas_desarrollo', ['Desarrollo de Software']);
        $cerrado = "e.descripcion IN ('Cerrado', 'Resuelto')";

        $filas = DB::table('tblTicket as t')
            ->join('tblTipoSoporte as ts', 'ts.id_TipoSoporte', '=', 't.id_TipoSoporte')
            ->join('tblArea as a', 'a.id_Area', '=', 'ts.id_Area')
            ->leftJoin('tblEstatus as e', 'e.id_Estatus', '=', 't.id_Estatus')
            ->leftJoin('tblTicketDesarrolloDetalle as d', 'd.id_Ticket', '=', 't.id_Ticket')
            ->leftJoin('tblTipoDesarrollo as td', 'td.id_TipoDesarrollo', '=', 'd.id_TipoDesarrollo')
            ->where('t.activo', true)
            ->whereIn('a.nombre', $areas)
            ->whereBetween('t.fechaAlta', [$inicio, $fin])
            ->selectRaw(
                'MONTH(t.fechaAlta) as mes, COUNT(t.id_Ticket) as total,'
                . " SUM(CASE WHEN {$cerrado} THEN 1 ELSE 0 END) as cerrados,"
                . " SUM(CASE WHEN LOWER(td.nombre) LIKE '%error%' THEN 1 ELSE 0 END) as err_total,"
                . " SUM(CASE WHEN LOWER(td.nombre) LIKE '%error%' AND {$cerrado} THEN 1 ELSE 0 END) as err_cerrados,"
                . ' SUM(CASE WHEN d.fechaEstimadaTermino IS NOT NULL THEN 1 ELSE 0 END) as tiempo_total,'
                . " SUM(CASE WHEN d.fechaEstimadaTermino IS NOT NULL AND {$cerrado}"
                . ' AND t.fechaCierre <= d.fechaEstimadaTermino THEN 1 ELSE 0 END) as tiempo_ok'
            )
            ->groupByRaw('MONTH(t.fechaAlta)')
            ->get();

        $resultado = [];
        foreach ($filas as $f) {
            $total = (int) $f->total;
            $resultado[(int) $f->mes] = [
                'general' => $total > 0 ? (int) $f->cerrados / $total : null,
                // Sin tickets de ese tipo en el mes => null (celda vacía).
                'errores' => (int) $f->err_total > 0 ? (int) $f->err_cerrados / (int) $f->err_total : null,
                'tiempo' => (int) $f->tiempo_total > 0 ? (int) $f->tiempo_ok / (int) $f->tiempo_total : null,
            ];
        }

        return $resultado;
    }

    // ─────────────────────────── Hoja del mes ───────────────────────────

    private function hojaMes(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $mesNombre, int $anio, $tickets, array $kpi): void
    {
        $sheet->setTitle($mesNombre);

        $sheet->setCellValue('A1', "KPI Desarrollo - {$mesNombre} {$anio}")
            ->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Encabezados de columna (una sola vez, como la plantilla).
        $columnas = [
            'A' => 'No', 'B' => 'Tarea', 'C' => 'Modulo', 'D' => 'Submodulo',
            'E' => 'Fecha inicio', 'F' => 'Fecha Termino',
        ];
        foreach ($columnas as $col => $texto) {
            $sheet->setCellValue("{$col}3", $texto);
        }
        $this->estiloEncabezado($sheet, 'A3:F3');

        $fila = 4;
        $contador = 0;

        // Los 3 bloques de la plantilla, en el mismo orden.
        $fila = $this->bloque($sheet, $fila, $contador, 'Indicadores de solicitud de desarrollo de software', $tickets);
        $fila = $this->bloque($sheet, $fila, $contador, 'Indicadores de correccion de errores de desarrollo de software', $kpi['correcciones']);
        $fila = $this->bloque($sheet, $fila, $contador, 'Indicadores de tiempo de entrega de desarrollo de software', $kpi['aTiempo']);

        $this->panel($sheet, $mesNombre, $kpi);
        $this->tablaModulos($sheet, $kpi['porModulo']);

        // Anchos de columna (B guarda la tarea con texto largo).
        foreach (['A' => 6, 'B' => 62, 'C' => 24, 'D' => 22, 'E' => 14, 'F' => 14, 'G' => 3, 'H' => 40, 'I' => 12] as $col => $ancho) {
            $sheet->getColumnDimension($col)->setWidth($ancho);
        }

        $this->graficosMes($sheet, $mesNombre, $kpi);
    }

    /**
     * Escribe un bloque: título fusionado A:F, luego una sección por módulo
     * con sus tickets. Devuelve la fila siguiente. La numeración "No" es
     * continua a lo largo de los 3 bloques (como la plantilla).
     */
    private function bloque(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $fila, int &$contador, string $titulo, $tickets): int
    {
        $sheet->mergeCells("A{$fila}:F{$fila}");
        $sheet->setCellValue("A{$fila}", $titulo);
        $this->estiloEncabezado($sheet, "A{$fila}:F{$fila}");
        ++$fila;

        if ($tickets->isEmpty()) {
            return $fila;
        }

        // Secciones por módulo; "SIN MÓDULO" al final (tickets históricos
        // creados antes de guardar el detalle).
        $grupos = $tickets
            ->groupBy(fn ($t) => trim((string) ($t->modulo ?: 'Sin módulo')))
            ->sortBy(fn ($g, $nombre) => $nombre === 'Sin módulo' ? PHP_INT_MAX : mb_strtolower($nombre));

        foreach ($grupos as $nombre => $grupo) {
            $sheet->mergeCells("A{$fila}:F{$fila}");
            $sheet->setCellValue("A{$fila}", mb_strtoupper((string) $nombre));
            $sheet->getStyle("A{$fila}:F{$fila}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F2F2']],
            ]);
            ++$fila;

            foreach ($grupo as $t) {
                ++$contador;
                $sheet->setCellValue("A{$fila}", $contador);
                $sheet->setCellValue("B{$fila}", (string) $t->asunto);
                $sheet->setCellValue("C{$fila}", (string) ($t->modulo ?? ''));
                $sheet->setCellValue("D{$fila}", (string) ($t->submodulo ?? ''));
                $sheet->setCellValue("E{$fila}", Carbon::parse($t->fechaAlta));
                if ($t->fechaCierre) {
                    $sheet->setCellValue("F{$fila}", Carbon::parse($t->fechaCierre));
                }
                ++$fila;
            }
        }

        // Bordes y formatos de las filas de datos recién escritas
        // (secciones + tickets; $fila quedó justo después de la última).
        if ($tickets->isNotEmpty()) {
            $desde = $fila - $this->filasDelBloque($tickets);
            $hasta = $fila - 1;
            $sheet->getStyle("A{$desde}:F{$hasta}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("E{$desde}:F{$hasta}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
            $sheet->getStyle("B{$desde}:B{$hasta}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getStyle("A{$desde}:F{$hasta}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        }

        return $fila;
    }

    /** Filas de datos que ocupa un conjunto de tickets (sección + tickets). */
    private function filasDelBloque($tickets): int
    {
        return $tickets->count() + $tickets->groupBy(fn ($t) => trim((string) ($t->modulo ?: 'Sin módulo')))->count();
    }

    /** Panel derecho H/I con las fórmulas de la plantilla. */
    private function panel(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $mesNombre, array $kpi): void
    {
        $sheet->mergeCells('H2:H3');
        $sheet->setCellValue('H2', "Indicadores de solicitud de desarrollo de software del mes de {$mesNombre}");
        $sheet->mergeCells('I2:I3');
        $sheet->setCellValue('I2', 'Cantidad');
        $this->estiloEncabezado($sheet, 'H2:I3');

        $sheet->setCellValue('H4', 'Solicitudes de desarrollo de software:');
        $sheet->setCellValue('I4', $kpi['total']);
        $sheet->setCellValue('H5', 'Tareas terminadas:');
        // Mismas fórmulas que la plantilla (recalculan al abrir).
        $sheet->setCellValue('I5', '=I4-I6');
        $sheet->setCellValue('H6', 'Tareas pendientes:');
        $sheet->setCellValue('I6', $kpi['pendientes']);
        $sheet->setCellValue('H7', 'Porcentaje de cumplimiento:');
        // Guard contra #DIV/0! cuando el mes no tiene solicitudes.
        $sheet->setCellValue('I7', $kpi['total'] > 0 ? '=I5/I4' : 0);

        $sheet->getStyle('H4:H7')->getFont()->setBold(true);
        $sheet->getStyle('I7')->getNumberFormat()->setFormatCode('0%');
        $sheet->getStyle('H4:I7')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    /** Tabla de apoyo "Tickets por módulo" (origen del gráfico de barras). */
    private function tablaModulos(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, $porModulo): void
    {
        if ($porModulo->isEmpty()) {
            return;
        }

        $sheet->setCellValue('H10', 'Módulo')->setCellValue('I10', 'Tickets');
        $this->estiloEncabezado($sheet, 'H10:I10');

        $fila = 11;
        foreach ($porModulo as $nombre => $cantidad) {
            $sheet->setCellValue("H{$fila}", (string) $nombre);
            $sheet->setCellValue("I{$fila}", $cantidad);
            ++$fila;
        }
        $sheet->getStyle("H11:I" . ($fila - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    /** Pastel terminadas/pendientes y barras por módulo (visibles al abrir). */
    private function graficosMes(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $mesNombre, array $kpi): void
    {
        $ref = "'" . $mesNombre . "'!";

        if ($kpi['total'] > 0) {
            $categorias = new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                $ref . '$H$5:$H$6',
                null,
                2,
                ['Tareas terminadas', 'Tareas pendientes']
            );
            $valores = new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_NUMBER,
                $ref . '$I$5:$I$6',
                null,
                2,
                [$kpi['terminados'], $kpi['pendientes']],
                null,
                [new ChartColor('059669'), new ChartColor('d97706')]
            );
            $paste = new Chart('TareasPorEstado');
            $paste->setName('Tareas por Estado')
                ->setTitle(new Title('Tareas por Estado'))
                ->setLegend(new Legend(Legend::POSITION_RIGHT))
                ->setPlotArea(new PlotArea(null, [new DataSeries(
                    DataSeries::TYPE_PIECHART,
                    // CT_PieChart no admite <c:grouping>: si se escribe, Excel
                    // rechaza el archivo y elimina los gráficos al repararlo.
                    null,
                    [1],
                    [],
                    [$categorias],
                    [$valores]
                )]))
                ->setTopLeftPosition('K2')
                ->setBottomRightPosition('R14');
            $sheet->addChart($paste);
        }

        $cantidadModulos = $kpi['porModulo']->count();
        if ($cantidadModulos > 0) {
            $ultimaFila = 10 + $cantidadModulos;
            $categorias = new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                $ref . '$H$11:$H$' . $ultimaFila,
                null,
                $cantidadModulos,
                array_keys($kpi['porModulo']->all())
            );
            $valores = new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_NUMBER,
                $ref . '$I$11:$I$' . $ultimaFila,
                null,
                $cantidadModulos,
                array_values($kpi['porModulo']->all()),
                null,
                new ChartColor('2563eb')
            );
            $barras = new Chart('TareasPorModulo');
            $barras->setName('Tareas por Módulo')
                ->setTitle(new Title('Tareas por Módulo'))
                ->setLegend(new Legend(Legend::POSITION_BOTTOM))
                ->setPlotArea(new PlotArea(null, [new DataSeries(
                    DataSeries::TYPE_BARCHART,
                    DataSeries::GROUPING_CLUSTERED,
                    [1],
                    [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, null, null, 1, ['Total'])],
                    [$categorias],
                    [$valores],
                    DataSeries::DIRECTION_COL
                )]))
                ->setTopLeftPosition('K16')
                ->setBottomRightPosition('R30');
            $sheet->addChart($barras);
        }
    }

    // ─────────────────────────── Tablero de Control ───────────────────────────

    private function hojaTablero(Spreadsheet $spreadsheet, int $mes, int $anio, array $cumplimiento): void
    {
        $tab = $spreadsheet->createSheet();
        $tab->setTitle('Tablero de Control');

        $tab->setCellValue('B1', 'FO-SGC-08 TABLERO DE CONTROL')->getStyle('B1')->getFont()->setBold(true)->setSize(12);
        $tab->setCellValue('W1', 'FO-SGC-08');
        $tab->setCellValue('W2', now()->format('d/m/Y'));
        $tab->setCellValue('W3', 'Revisión: 00');

        $tab->mergeCells('A4:AG4');
        $tab->setCellValue('A4', 'TABLERO DE CONTROL')
            ->getStyle('A4')->getFont()->setBold(true)->setSize(14);
        $tab->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Encabezados fijos (A-I) sobre los meses (pares J-K = Enero ... AF-AG = Diciembre).
        $cabeceras = [
            'A' => 'AREA', 'B' => 'OBJETIVO', 'C' => 'OBJETIVO ESPECIFICO', 'D' => 'ACCIONES',
            'E' => 'INDICADOR', 'F' => 'META', 'G' => 'FRECUENCIA', 'H' => 'RESPONSABLE', 'I' => 'RECURSOS',
        ];
        foreach ($cabeceras as $col => $texto) {
            $tab->mergeCells("{$col}5:{$col}6");
            $tab->setCellValue("{$col}5", $texto);
        }

        for ($m = 1; $m <= 12; ++$m) {
            $colResultado = Coordinate::stringFromColumnIndex(10 + (($m - 1) * 2));
            $colMeta = Coordinate::stringFromColumnIndex(11 + (($m - 1) * 2));
            $tab->mergeCells("{$colResultado}5:{$colMeta}5");
            $tab->setCellValue("{$colResultado}5", strtoupper(self::MESES[$m]));
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
            $tab->setCellValue("F{$fila}", '≥100%');
            $tab->setCellValue("G{$fila}", 'Mensual');
            $tab->setCellValue("H{$fila}", 'Jefe de TI');
            $tab->setCellValue("I{$fila}", 'Equipo de computo, internet, IDE, lenguajes de programacion');
            $tab->getStyle("B{$fila}:E{$fila}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $tab->getStyle("A{$fila}:I{$fila}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            $tab->getRowDimension($fila)->setRowHeight(80);
            ++$fila;
        }

        // RESULTADO/META hasta el mes descargado. Fila 7 = cumplimiento
        // general; fila 8 = correcciones de error; fila 9 = entrega en
        // tiempo (null => celda vacía cuando no hay datos que medir).
        $claves = [7 => 'general', 8 => 'errores', 9 => 'tiempo'];
        for ($m = 1; $m <= $mes; ++$m) {
            $colResultado = Coordinate::stringFromColumnIndex(10 + (($m - 1) * 2));
            $colMeta = Coordinate::stringFromColumnIndex(11 + (($m - 1) * 2));
            foreach ($claves as $r => $clave) {
                $valor = $cumplimiento[$m][$clave] ?? null;
                $tab->setCellValue("{$colResultado}{$r}", $valor);
                $tab->getStyle("{$colResultado}{$r}")->getNumberFormat()->setFormatCode('0%');
                $tab->setCellValue("{$colMeta}{$r}", 1);
                $tab->getStyle("{$colMeta}{$r}")->getNumberFormat()->setFormatCode('0%');
            }
        }

        // Estilo del bloque.
        $this->estiloEncabezado($tab, 'A5:AG6');
        $tab->getStyle('A5:AG9')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $tab->getStyle('A7:A9')->getFont()->setBold(true);
        foreach (['A' => 24, 'B' => 38, 'C' => 34, 'D' => 48, 'E' => 12, 'F' => 10, 'G' => 12, 'H' => 14, 'I' => 30] as $col => $ancho) {
            $tab->getColumnDimension($col)->setWidth($ancho);
        }
        for ($i = 10; $i <= 33; ++$i) {
            $tab->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(9);
        }
    }

    private function estiloEncabezado(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $rango): void
    {
        $sheet->getStyle($rango)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DDEBF7']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
    }
}
