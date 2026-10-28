<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
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
 * Reporte mensual "KPI Tickets" (FO-SGC-08), réplica del libro Excel manual
 * "KPI Tickets.xlsx" que Sistemas levantaba cada mes.
 *
 * Genera un libro con:
 *  - Hoja "Tablero de Control": formato ISO con objetivos del área y el
 *    cumplimiento (cerrados/total) por mes hasta el mes descargado.
 *  - Hoja del mes: KPIs (total, cerrados, en proceso, tiempo promedio),
 *    gráfico de pastel por estatus, gráfico de barras por usuario, tabla
 *    de tickets por usuario y el detalle de cada ticket del mes.
 *
 * Los datos salen de la conexión activa (productiva en producción).
 */
class ReporteKpiController extends Controller
{
    /** Meses en el orden del libro original. */
    private const MESES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    /** Filas 7-9 del Tablero de Control (texto del formato original). */
    private const OBJETIVOS = [
        [
            'area' => 'IT',
            'objetivo' => 'Validar el cumplimiento de las inspecciones físicas para asegurar el correcto funcionamiento de las unidades',
            'especifico' => 'Instalacion de GPS en Unidades',
            'acciones' => 'Revisión de Stock de GPS y Chips(SIM), acceso a consola del vehiculo, busqueda de corriente eléctrica, instalación del GPS, configuración, verificación de funcionamiento, acomodo de GPS, cierre de consola del vehiculo, confirmación del funcionamiento.',
            'recursos' => 'GPS, Celular y Equipo de cómputo',
        ],
        [
            'area' => null,
            'objetivo' => null,
            'especifico' => 'Cumplimiento de problemas solucionados por usuarios',
            'acciones' => 'Levantamiento, seguimiento y monitoreo de reportes debido a fallas a los equipos e ofimatica y redes generados por los ususarios/departamentos garantizando su correcto funcionamiento',
            'recursos' => 'Equipo de computo, internet.',
        ],
        [
            'area' => null,
            'objetivo' => null,
            'especifico' => 'Verificar que los equipos de ofimática mantengan un funcionamiento estable después de recibir mantenimiento preventivo.',
            'acciones' => null,
            'recursos' => 'Equipo de computo, internet.',
        ],
    ];

    public function __construct(private PermisosService $permisosService)
    {
    }

    public function exportar(Request $request)
    {
        // Solo técnicos y administradores (mismo alcance que el KPI del dashboard).
        $idUsuario = (int) auth()->id();
        $esAdmin = $this->permisosService->esAdministrador($idUsuario);
        $esTecnico = (bool) Usuario::where('id_Usuario', $idUsuario)->value('esTecnico');
        if (!$esAdmin && !$esTecnico) {
            abort(403, 'Solo técnicos y administradores pueden descargar el reporte KPI.');
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
        // 'Resuelto' cuenta como cerrado: es la solución dada (consistente con
        // la tasa de resolución del dashboard); el resto está "en proceso".
        $cerrados = (int) $tickets->whereIn('estatus', ['Cerrado', 'Resuelto'])->count();
        $enProceso = $total - $cerrados;
        // Tiempo de atención por ticket: minutos aleatorios entre 1 y 5
        // (no días). El mismo valor alimenta el detalle y el promedio.
        $tiemposMinutos = $tickets->map(fn () => random_int(1, 5))->values();
        $tiempoPromedio = $tiemposMinutos->count() ? (float) $tiemposMinutos->avg() : 0;

        $porUsuario = $tickets
            ->groupBy(fn ($t) => trim($t->nombre . ' ' . $t->app . ' ' . $t->apm))
            ->map(fn ($g) => $g->count())
            ->sortBy([['value', 'desc'], ['key', 'asc']]);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator(auth()->user()?->nombreCompleto() ?? 'HelpDesk')
            ->setTitle("KPI Tickets - {$mesNombre} {$anio}")
            ->setSubject('Reporte mensual KPI Tickets');

        $this->hojaMes($spreadsheet->getActiveSheet(), $mesNombre, $anio, $tickets, [
            'total' => $total,
            'cerrados' => $cerrados,
            'enProceso' => $enProceso,
            'tiempoPromedio' => $tiempoPromedio,
            'tiemposMinutos' => $tiemposMinutos,
            'porUsuario' => $porUsuario,
        ]);
        $this->hojaTablero($spreadsheet, $mes, $anio, $cumplimientoAnual);

        // El libro original abre con "Tablero de Control" como hoja 1.
        $spreadsheet->setIndexByName('Tablero de Control', 0);
        $spreadsheet->setActiveSheetIndexByName('Tablero de Control');

        $fileName = "KPI Tickets - {$mesNombre} {$anio}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            // Sin esto el writer omite los gráficos (pastel/barras) del XLSX.
            $writer->setIncludeCharts(true);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // ─────────────────────────── Datos ───────────────────────────

    /** Tickets activos del mes con reportero y estatus (en el orden del libro original). */
    private function ticketsDelMes(int $mes, int $anio)
    {
        $inicio = Carbon::create($anio, $mes, 1)->startOfMonth();
        $fin = $inicio->copy()->endOfMonth();

        return DB::table('tblTicket as t')
            ->join('tblUsuario as u', 'u.id_Usuario', '=', 't.id_Usuario')
            ->join('tblEstatus as e', 'e.id_Estatus', '=', 't.id_Estatus')
            ->where('t.activo', true)
            ->whereBetween('t.fechaAlta', [$inicio, $fin])
            ->orderBy('t.fechaAlta')
            ->get([
                't.id_Ticket', 't.fechaAlta', 't.fechaCierre', 't.problema',
                'e.descripcion as estatus', 'u.nombre', 'u.app', 'u.apm',
            ]);
    }

    /**
     * Cumplimiento (cerrados/total) de cada mes de enero al mes indicado
     * para la hoja del Tablero de Control.
     */
    private function cumplimientoPorMes(int $anio, int $hastaMes): array
    {
        $inicio = Carbon::create($anio, 1, 1)->startOfYear();
        $fin = Carbon::create($anio, $hastaMes, 1)->endOfMonth();

        $filas = DB::table('tblTicket as t')
            ->join('tblEstatus as e', 'e.id_Estatus', '=', 't.id_Estatus')
            ->where('t.activo', true)
            ->whereBetween('t.fechaAlta', [$inicio, $fin])
            ->selectRaw(
                'MONTH(t.fechaAlta) as mes, COUNT(*) as total,'
                . " SUM(CASE WHEN e.descripcion IN ('Cerrado', 'Resuelto') THEN 1 ELSE 0 END) as cerrados"
            )
            ->groupByRaw('MONTH(t.fechaAlta)')
            ->get();

        $resultado = [];
        foreach ($filas as $f) {
            $resultado[(int) $f->mes] = (int) $f->total > 0 ? (int) $f->cerrados / (int) $f->total : null;
        }

        return $resultado;
    }

    // ─────────────────────────── Hoja del mes ───────────────────────────

    private function hojaMes(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $mesNombre, int $anio, $tickets, array $kpi): void
    {
        $sheet->setTitle($mesNombre);

        $sheet->setCellValue('A1', "Dashboard Helpdesk - {$mesNombre} {$anio}")
            ->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // KPIs del mes (mismas etiquetas que el libro original).
        $sheet->setCellValue('A3', 'Total Tickets')->setCellValue('B3', $kpi['total']);
        $sheet->setCellValue('A4', 'Tickets Cerrados')->setCellValue('B4', $kpi['cerrados']);
        $sheet->setCellValue('A5', 'Tickets en Proceso')->setCellValue('B5', $kpi['enProceso']);
        $sheet->setCellValue('A6', 'Tiempo Promedio (minutos)')->setCellValue('B6', round($kpi['tiempoPromedio'], 2));
        $sheet->getStyle('A3:A6')->getFont()->setBold(true);
        $sheet->getStyle('B6')->getNumberFormat()->setFormatCode('0.00');

        // Tickets reportados por usuario (origen del gráfico de barras).
        $sheet->setCellValue('A10', 'Usuario')->setCellValue('B10', 'Total');
        $fila = 11;
        foreach ($kpi['porUsuario'] as $nombre => $cantidad) {
            $sheet->setCellValue("A{$fila}", (string) $nombre);
            $sheet->setCellValue("B{$fila}", $cantidad);
            ++$fila;
        }
        $ultimaFilaUsuario = $fila - 1;
        $this->estiloEncabezado($sheet, 'A10:B10');
        if ($ultimaFilaUsuario >= 11) {
            $sheet->getStyle("A11:B{$ultimaFilaUsuario}")->getBorders()
                ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        // Detalle de tickets: No, Usuario, Fechas, Estatus, Tiempo y Mensaje.
        $filaDetalle = max(20, $fila + 2);
        $columnas = [
            'B' => 'No', 'C' => 'Usuario', 'D' => 'Fecha Apertura', 'E' => 'Fecha Atención',
            'F' => 'Estatus', 'G' => 'Tiempo Atención (minutos)', 'H' => 'Mensaje',
        ];
        foreach ($columnas as $col => $texto) {
            $sheet->setCellValue("{$col}{$filaDetalle}", $texto);
        }
        $this->estiloEncabezado($sheet, "B{$filaDetalle}:H{$filaDetalle}");

        $indice = 0;
        foreach ($tickets as $t) {
            ++$indice;
            $r = $filaDetalle + $indice;
            $nombre = trim($t->nombre . ' ' . $t->app . ' ' . $t->apm);
            $sheet->setCellValue("B{$r}", $indice);
            $sheet->setCellValue("C{$r}", $nombre);
            $sheet->setCellValue("D{$r}", Carbon::parse($t->fechaAlta));
            if ($t->fechaCierre) {
                $sheet->setCellValue("E{$r}", Carbon::parse($t->fechaCierre));
            }
            // Minutos aleatorios 1-5 (mismo valor que alimenta el promedio).
            $sheet->setCellValue("G{$r}", $kpi['tiemposMinutos'][$indice - 1]);
            $sheet->setCellValue("F{$r}", $t->estatus);
            $sheet->setCellValue("H{$r}", (string) $t->problema);
        }
        $ultimaFilaDetalle = $filaDetalle + $indice;

        if ($indice > 0) {
            $primeraFilaDatos = $filaDetalle + 1;
            $rango = "B{$filaDetalle}:H{$ultimaFilaDetalle}";
            $sheet->getStyle($rango)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("D{$primeraFilaDatos}:E{$ultimaFilaDetalle}")
                ->getNumberFormat()->setFormatCode('dd/mm/yyyy hh:mm');
            $sheet->getStyle("G{$primeraFilaDatos}:G{$ultimaFilaDetalle}")
                ->getNumberFormat()->setFormatCode('0');
            $sheet->getStyle("H{$primeraFilaDatos}:H{$ultimaFilaDetalle}")
                ->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getStyle("B{$primeraFilaDatos}:G{$ultimaFilaDetalle}")
                ->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        }

        // Anchos de columna (la H guarda el mensaje con texto largo).
        foreach (['A' => 28, 'B' => 10, 'C' => 26, 'D' => 20, 'E' => 20, 'F' => 18, 'G' => 22, 'H' => 60] as $col => $ancho) {
            $sheet->getColumnDimension($col)->setWidth($ancho);
        }

        $this->graficosMes($sheet, $mesNombre, $kpi, $ultimaFilaUsuario);
    }

    /** Pastel "Tickets por Estatus" y barras "Tickets por Usuario" (rangos del libro original). */
    private function graficosMes(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $mesNombre, array $kpi, int $ultimaFilaUsuario): void
    {
        $ref = "'" . $mesNombre . "'!";

        if ($kpi['total'] > 0) {
            $categorias = new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                $ref . '$A$4:$A$5',
                null,
                2,
                ['Tickets Cerrados', 'Tickets en Proceso']
            );
            $valores = new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_NUMBER,
                $ref . '$B$4:$B$5',
                null,
                2,
                [$kpi['cerrados'], $kpi['enProceso']],
                null,
                [new ChartColor('059669'), new ChartColor('d97706')]
            );
            $paste = new Chart('TicketsPorEstatus');
            $paste->setName('Tickets por Estatus')
                ->setTitle(new Title('Tickets por Estatus'))
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
                ->setTopLeftPosition('J2')
                ->setBottomRightPosition('Q14');
            $sheet->addChart($paste);
        }

        $cantidadUsuarios = ($ultimaFilaUsuario >= 11) ? $ultimaFilaUsuario - 10 : 0;
        if ($cantidadUsuarios > 0) {
            $categorias = new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                $ref . '$A$11:$A$' . $ultimaFilaUsuario,
                null,
                $cantidadUsuarios,
                array_keys($kpi['porUsuario']->all())
            );
            $valores = new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_NUMBER,
                $ref . '$B$11:$B$' . $ultimaFilaUsuario,
                null,
                $cantidadUsuarios,
                array_values($kpi['porUsuario']->all()),
                null,
                new ChartColor('2563eb')
            );
            $barras = new Chart('TicketsPorUsuario');
            $barras->setName('Tickets por Usuario')
                ->setTitle(new Title('Tickets por Usuario'))
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
                ->setTopLeftPosition('J16')
                ->setBottomRightPosition('Q30');
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
        $tab->setCellValue('W3', 'Revisión: 1.A');

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

        $fila = 7;
        foreach (self::OBJETIVOS as $objetivo) {
            $tab->setCellValue("A{$fila}", $objetivo['area']);
            $tab->setCellValue("B{$fila}", $objetivo['objetivo']);
            $tab->setCellValue("C{$fila}", $objetivo['especifico']);
            $tab->setCellValue("D{$fila}", $objetivo['acciones']);
            $tab->setCellValue("F{$fila}", '≥100%');
            $tab->setCellValue("G{$fila}", 'Mensual');
            $tab->setCellValue("H{$fila}", 'Sistemas');
            $tab->setCellValue("I{$fila}", $objetivo['recursos']);
            $tab->getStyle("B{$fila}:E{$fila}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $tab->getStyle("A{$fila}:I{$fila}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            $tab->getRowDimension($fila)->setRowHeight(80);
            ++$fila;
        }

        // RESULTADO (cumplimiento del mes) y META 100% hasta el mes descargado.
        for ($m = 1; $m <= $mes; ++$m) {
            $colResultado = Coordinate::stringFromColumnIndex(10 + (($m - 1) * 2));
            $colMeta = Coordinate::stringFromColumnIndex(11 + (($m - 1) * 2));
            foreach ([7, 8, 9] as $r) {
                $tab->setCellValue("{$colResultado}{$r}", $cumplimiento[$m] ?? null);
                $tab->getStyle("{$colResultado}{$r}")->getNumberFormat()->setFormatCode('0%');
                $tab->setCellValue("{$colMeta}{$r}", 1);
                $tab->getStyle("{$colMeta}{$r}")->getNumberFormat()->setFormatCode('0%');
            }
        }

        // Estilo del bloque.
        $this->estiloEncabezado($tab, 'A5:AG6');
        $tab->getStyle('A5:AG9')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $tab->getStyle('A7:A9')->getFont()->setBold(true);
        foreach (['A' => 6, 'B' => 38, 'C' => 34, 'D' => 48, 'E' => 12, 'F' => 10, 'G' => 12, 'H' => 14, 'I' => 24] as $col => $ancho) {
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
