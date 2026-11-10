<?php

namespace App\Http\Controllers;

use App\Services\PermisosService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReporteKpiGpsController extends Controller
{
    /** Nombres de los meses (1..12) para la hoja y el nombre del archivo. */
    private const MESES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    /** El logo GPS va en resources/images/kpi-gps-logo.png */
    private const LOGO_PATH = __DIR__.'/../../../resources/images/kpi-gps-logo.png';

    /** Nombres de las columnas de la tabla por hoja (15 columnas, A..O). */
    private const COLUMNAS_TABLA = [
        'Marca GPS',
        'Modelo GPS',
        'IMEI',
        'SN',
        'No Telefonico',
        'Fecha de instalacion',
        'Unidad',
        'Permisionario',
        'Tipo de requerimiento',
        'Status',
        'Marca Anterior',
        'Modelo GPS Anterior',
        'IMEI GPS Anterior',
        'SN GPS Anterior',
        'No Telefonico Anterior',
    ];

    public function __construct()
    {
        //
    }

    public function exportar(Request $request)
    {
        $idUsuario = (int) auth()->id();

        if (! app(PermisosService::class)->puedeDescargarKpiGps($idUsuario)) {
            abort(403, 'El reporte KPI GPS es exclusivo de los roles Administrador y Root.');
        }

        $datos = $request->validate([
            'mes' => ['nullable', 'integer', 'between:1,12'],
            'anio' => ['nullable', 'integer', 'between:2020,'.now()->year],
        ]);
        $mes = (int) ($datos['mes'] ?? now()->month);
        $anio = (int) ($datos['anio'] ?? now()->year);
        $mesNombre = self::MESES[$mes];

        /**
         * Solo el mes solicitado: datos de tblInstalacionesGPS con fechaAlta
         * dentro del rango exacto del mes. Si el mes no tiene registros, la
         * hoja sale vacía (solo encabezados y estadísticas).
         */
        $fechaInicio = Carbon::create($anio, $mes, 1)->startOfMonth();
        $fechaFin = Carbon::create($anio, $mes, 1)->endOfMonth();

        $instalaciones = DB::table('tblInstalacionesGPS')
            ->whereBetween('fechaAlta', [$fechaInicio, $fechaFin])
            ->get([
                'unidad',
                'fechaAlta',
                'activo',
                'fecha_cambio_sim',
                'fecha_reinstalacion',
                'marca_gps',
                'modelo_gps',
                'imei',
                'numero_anterior',
                'numero_actual',
            ]);

        /** Crear Spreadsheet. */
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator(auth()->user()?->nombreCompleto() ?? 'HelpDesk')
            ->setTitle("KPI GPS - {$mesNombre} {$anio}")
            ->setSubject('Reporte mensual KPI GPS');

        /** ---- Hoja 0: Indicadores / Tablero de Control ---- */
        $hoja0 = $spreadsheet->getActiveSheet();
        $hoja0->setTitle('Indicadores');
        $this->hojaIndicadores($hoja0);

        /** ---- Hoja única: el mes solicitado ---- */
        $hoja = $spreadsheet->createSheet();
        $hoja->setTitle($mesNombre);
        $this->hojaMes($hoja, $mes, $instalaciones);

        $spreadsheet->setActiveSheetIndex(0);

        $fileName = "KPI GPS - {$mesNombre} {$anio}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * ---- Hoja 0: Indicadores / Tablero de Control ----
     * Reproduce la estructura Y el formato del archivo KPI-GPS.xlsx original:
     * título FO-SGC-08 (Arial Black 48), sello, banda verde, encabezados en
     * azul marino con texto blanco, los 3 indicadores del área IT y las
     * columnas de meses (pareadas RESULTADO/META) de ENERO a DICIEMBRE.
     */
    private function hojaIndicadores($sheet): void
    {
        // ---- Anchos de columna (idénticos al original) ----
        $anchos = [
            'A' => 58, 'B' => 27.86, 'C' => 26.71, 'D' => 57.29, 'E' => 115.86,
            'F' => 10.86, 'G' => 21.14, 'H' => 22.29, 'I' => 19.14,
            'J' => 20.71, 'K' => 10.29, 'L' => 20.71, 'M' => 11.43, 'N' => 20.71,
            'O' => 11.43, 'P' => 20.71, 'Q' => 11.43, 'R' => 20.71, 'S' => 11.43,
            'T' => 20.71, 'U' => 11.43, 'V' => 20.71, 'W' => 11.43, 'X' => 20.71,
            'Y' => 11.43, 'Z' => 20.71, 'AA' => 11.43, 'AB' => 28.57, 'AC' => 11.43,
            'AD' => 20.71, 'AE' => 11.43, 'AF' => 20.71, 'AG' => 10.29,
        ];
        foreach ($anchos as $c => $w) {
            $sheet->getColumnDimension($c)->setWidth($w);
        }

        // ---- Alturas de fila (idénticas al original) ----
        foreach ([1 => 18.75, 2 => 18.75, 3 => 18.75, 4 => 22.5, 5 => 36, 6 => 20.25, 7 => 112.5, 8 => 93.75, 9 => 131.25] as $fila => $h) {
            $sheet->getRowDimension($fila)->setRowHeight($h);
        }

        // ---- Sello superior derecho (W1:AG3) ----
        $sheet->mergeCells('W1:AG1');
        $sheet->setCellValue('W1', 'FO-SGC-08');
        $sheet->mergeCells('W2:AG2');
        $sheet->mergeCells('W3:AG3');
        $sheet->setCellValue('W3', 'Revisión: 1.A');
        $sello = [
            'font' => ['name' => 'Century Gothic', 'size' => 14, 'bold' => true, 'color' => ['argb' => 'FF000000']],
            'borders' => [
                'left' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF000000']],
                'right' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF000000']],
                'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF000000']],
                'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF000000']],
                'horizontal' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF000000']],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ];
        $sello['alignment']['vertical'] = Alignment::VERTICAL_CENTER;
        $sheet->getStyle('W1:AG2')->applyFromArray($sello);
        $sello['alignment']['vertical'] = Alignment::VERTICAL_BOTTOM;
        $sheet->getStyle('W3:AG3')->applyFromArray($sello);

        // ---- Título principal (B1:V3). A1:A3 queda como área del logo ----
        $sheet->mergeCells('A1:A3');
        $sheet->mergeCells('B1:V3');
        $sheet->setCellValue('B1', 'FO-SGC-08 TABLERO DE CONTROL');
        $sheet->getStyle('A1:A3')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle('B1')->applyFromArray([
            'font' => ['name' => 'Arial Black', 'size' => 48, 'bold' => true, 'color' => ['argb' => 'FF000000']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_BOTTOM, 'wrapText' => true],
        ]);

        // Logo GPS (anclado en A1, como el original)
        if (file_exists(self::LOGO_PATH)) {
            $logo = new Drawing;
            $logo->setName('Logo GPS');
            $logo->setDescription('Logo GPS');
            $logo->setPath(self::LOGO_PATH);
            $logo->setCoordinates('A1');
            $logo->setWidthAndHeight(309, 72);
            $logo->setWorksheet($sheet);
        }

        // ---- Banda verde del título (A4:AG4) ----
        // Texto con los mismos espacios de relleno del original (centrado).
        $sheet->mergeCells('A4:AG4');
        $sheet->setCellValue('A4', str_repeat(' ', 396).'TABLERO DE CONTROL ');
        $sheet->getStyle('A4:AG4')->applyFromArray([
            'font' => ['name' => 'Century Gothic', 'size' => 18, 'bold' => true, 'color' => ['argb' => 'FF000000']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF7D9632']],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);

        // ---- Encabezados estáticos (fila 5), texto exacto del original ----
        $cabeceras = [
            'A5' => 'AREA ',
            'B5' => 'OBJETIVO ',
            'C5' => 'OBJETIVO ESPECIFICO',
            'D5' => str_repeat(' ', 54).'ACCIONES',
            'E5' => 'INDICADOR',
            'F5' => 'META',
            'G5' => 'FRECUENCIA',
            'H5' => 'RESPONSABLE',
            'I5' => 'RECURSOS',
        ];
        foreach ($cabeceras as $addr => $txt) {
            $sheet->setCellValue($addr, $txt);
        }

        // ---- Columnas de meses: 12 pares (RESULTADO/META) de ENERO a DICIEMBRE
        // Los nombres conservan los espacios finales del original.
        $meses = [
            ['ENERO ', 10], ['FEBRERO', 12], ['MARZO', 14], ['ABRIL ', 16],
            ['MAYO', 18], ['JUNIO ', 20], ['JULIO', 22], ['AGOSTO', 24],
            ['SEPTIEMBRE', 26], ['OCTUBRE ', 28], ['NOVIEMBRE', 30], ['DICIEMBRE', 32],
        ];
        foreach ($meses as [$nombre, $col]) {
            $c1 = Coordinate::stringFromColumnIndex($col);
            $c2 = Coordinate::stringFromColumnIndex($col + 1);
            $sheet->mergeCells("{$c1}5:{$c2}5");
            $sheet->setCellValue("{$c1}5", $nombre);
            // ENERO..SEPTIEMBRE con espacio final; OCTUBRE..DICIEMBRE sin él.
            $sheet->setCellValue("{$c1}6", $col >= 28 ? 'RESULTADO' : 'RESULTADO ');
            $sheet->setCellValue("{$c2}6", 'META');
        }

        // Estilo de los encabezados: azul marino con texto blanco (filas 5 y 6),
        // sin línea horizontal entre ellas (igual que el original).
        $sheet->getStyle('A5:AG6')->applyFromArray([
            'font' => ['name' => 'Century Gothic', 'size' => 14, 'bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF113051']],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']],
                'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']],
                'right' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        // Borde izquierdo externo solo en la fila 6 (la fila 5 no lo lleva).
        $sheet->getStyle('A6')->applyFromArray([
            'borders' => ['left' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]],
        ]);
        // Separadores verticales internos de A..I (todas las fronteras).
        $sheet->getStyle('A5:I6')->applyFromArray([
            'borders' => ['vertical' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]],
        ]);
        // Frontera entre RECURSOS (I) y el primer mes (J).
        $sheet->getStyle('I5:I6')->applyFromArray([
            'borders' => ['right' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]],
        ]);
        // Fronteras entre pares de meses: sin línea dentro del par combinado.
        foreach ([11, 13, 15, 17, 19, 21, 23, 25, 27, 29, 31] as $c) {
            $L = Coordinate::stringFromColumnIndex($c);
            $sheet->getStyle("{$L}5:{$L}6")->applyFromArray([
                'borders' => ['right' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]],
            ]);
        }
        // Divisor de fila bajo cada mes combinado (fila 6, borde superior).
        $sheet->getStyle('J6:AG6')->applyFromArray([
            'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]],
        ]);
        // D5 (ACCIONES) va alineado a la izquierda, como en el original.
        $sheet->getStyle('D5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_GENERAL);

        // ---- 3 filas de indicadores del área IT (texto fiel al original) ----
        $sheet->mergeCells('A7:A9');
        $sheet->mergeCells('B7:B9');
        $sheet->setCellValue('A7', 'IT');
        $sheet->setCellValue('B7', ' Validar el cumplimiento de las inspecciones físicas para asegurar el correcto funcionamiento de las unidades ');

        // Columnas: C = OBJETIVO ESPECIFICO, D = ACCIONES, F = META,
        // G = FRECUENCIA, H = RESPONSABLE, I = RECURSOS (texto exacto).
        $indicadores = [
            7 => [
                'especifico' => 'Instalacion de GPS en Unidades ',
                'acciones' => 'Revisión de Stock de GPS y Chips(SIM), acceso a consola del vehiculo, busqueda de corriente eléctrica, instalación del GPS, configuración, verificación de funcionamiento, acomodo de GPS, cierre de consola del vehiculo, confirmación del funcionamiento.',
                'recursos' => 'GPS, Celular y Equipo de cómputo',
            ],
            8 => [
                'especifico' => 'Cumplimiento de problemas solucionados por usuarios ',
                'acciones' => 'Levantamiento, seguimiento y monitoreo de reportes debido a fallas a los equipos e ofimatica y redes generados por los ususarios/departamentos garantizando su correcto funcionamiento ',
                'recursos' => 'Equipo de computo, internet.',
            ],
            9 => [
                'especifico' => 'Verificar que los equipos de ofimática mantengan un funcionamiento estable después de recibir mantenimiento preventivo.',
                'acciones' => '',
                'recursos' => 'Equipo de computo, internet.',
            ],
        ];

        // Estilo base de la tabla de indicadores: borde fino, fondo blanco.
        $sheet->getStyle('A7:AG9')->applyFromArray([
            'font' => ['name' => 'Calibri', 'size' => 14, 'color' => ['argb' => 'FF000000']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getStyle('A7')->getFont()->setBold(true);
        $sheet->getStyle('F7:F9')->getFont()->setBold(true);
        $sheet->getStyle('F7:F9')->getNumberFormat()->setFormatCode('0%');

        foreach ($indicadores as $fila => $datos) {
            $sheet->setCellValue("C{$fila}", $datos['especifico']);
            if ($datos['acciones'] !== '') {
                $sheet->setCellValue("D{$fila}", $datos['acciones']);
            }
            $sheet->setCellValue("F{$fila}", '≥100%'); // META
            $sheet->setCellValue("G{$fila}", 'Mensual ');
            $sheet->setCellValue("H{$fila}", 'Sistemas ');
            $sheet->setCellValue("I{$fila}", $datos['recursos']);

            // Valores mensuales: RESULTADO/META de ENERO..JULIO (como el original).
            // MARZO = 80% (0.8); el resto = 100%. AGOSTO..DICIEMBRE quedan vacíos.
            foreach ($meses as [$nombre, $col]) {
                if ($col > 22) {
                    continue; // AGOSTO..DICIEMBRE sin datos (igual que el original)
                }
                $c1 = Coordinate::stringFromColumnIndex($col);
                $c2 = Coordinate::stringFromColumnIndex($col + 1);

                $sheet->setCellValue("{$c2}{$fila}", 1); // META = 100%

                $resultado = ($nombre === 'MARZO') ? 0.8 : 1;
                $sheet->setCellValue("{$c1}{$fila}", $resultado); // RESULTADO
            }
        }

        // ---- Estilo exacto de las celdas de meses (filas 7..9) ----
        // [columna] => [negrita, relleno]; reproducido celda por celda del
        // original (RESULTADO verde 92D050, MARZO amarillo, META blanco y las
        // columnas vacías de AGOSTO..DICIEMBRE con su gris EEECE1).
        $estiloMeses = [
            10 => [false, 'FF92D050'], 11 => [true, 'FFFFFFFF'], // ENERO
            12 => [true, 'FF92D050'],  13 => [true, 'FFFFFFFF'], // FEBRERO
            14 => [true, 'FFFFFF00'],  15 => [true, 'FFFFFFFF'], // MARZO
            16 => [false, 'FF92D050'], 17 => [true, 'FFFFFFFF'], // ABRIL
            18 => [false, 'FF92D050'], 19 => [true, 'FFFFFFFF'], // MAYO
            20 => [false, 'FF92D050'], 21 => [true, 'FFFFFFFF'], // JUNIO
            22 => [false, 'FF92D050'], 23 => [true, 'FFFFFFFF'], // JULIO
            24 => [true, 'FFFFFFFF'],  25 => [true, 'FFEEECE1'], // AGOSTO
            26 => [true, 'FFFFFFFF'],  27 => [true, 'FFEEECE1'], // SEPTIEMBRE
            28 => [true, 'FFFFFFFF'],  29 => [true, 'FFEEECE1'], // OCTUBRE
            30 => [true, 'FFFFFFFF'],  31 => [true, 'FFEEECE1'], // NOVIEMBRE
            32 => [true, 'FFFFFFFF'],  33 => [true, 'FFEEECE1'], // DICIEMBRE
        ];
        foreach ($estiloMeses as $col => [$negrita, $color]) {
            // El original deja General en los RESULTADO vacíos (AGOSTO..DICIEMBRE).
            $nf = in_array($col, [24, 26, 28, 30, 32], true) ? 'General' : '0%';
            for ($fila = 7; $fila <= 9; $fila++) {
                $sheet->getStyle(Coordinate::stringFromColumnIndex($col).$fila)->applyFromArray([
                    'font' => ['bold' => $negrita],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $color]],
                    'numberFormat' => ['formatCode' => $nf],
                ]);
            }
        }
    }

    /**
     * ---- Hoja del mes solicitado ----
     * Reproduce la hoja "Dashboard GPS" del original: título con fondo morado,
     * bloque de estadísticas vertical (columna A = etiqueta, columna B = valor)
     * y la tabla de datos de 15 columnas (A..O) a partir de la fila 22, con
     * encabezado azul y filas en azul claro.
     */
    private function hojaMes($sheet, int $mesN, $instalaciones): void
    {
        // ---- Anchos de columna (idénticos al original) ----
        $sheet->getColumnDimension('A')->setWidth(15.57);
        $sheet->getColumnDimension('B')->setWidth(16.57);
        $sheet->getColumnDimension('C')->setWidth(21);
        for ($i = 4; $i <= count(self::COLUMNAS_TABLA); $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(18);
        }

        // ---- Título Dashboard GPS (fondo morado, texto blanco) ----
        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', 'Dashboard GPS');
        $sheet->getRowDimension(1)->setRowHeight(18.75);
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => ['name' => 'Calibri', 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF8064A2']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_BOTTOM],
            'numberFormat' => ['formatCode' => '_-* #,##0.00_-;\\-* #,##0.00_-;_-* "-"??_-;_-@_-'],
        ]);

        // ---- Estadísticas: Tipo de requerimiento (columna I) ----
        $sheet->setCellValue('A4', 'Instalacion');
        $sheet->setCellValue('B4', '=COUNTIF(I23:I100, "Instalacion")');

        $sheet->setCellValue('A5', 'Cambio de SIM');
        $sheet->setCellValue('B5', '=COUNTIF(I23:I100, "Cambio de SIM")');

        $sheet->setCellValue('A6', 'Reinstalacion');
        $sheet->setCellValue('B6', '=COUNTIF(I23:I100, "Reinstalacion")');

        $sheet->setCellValue('A7', 'Total de GPS');
        $sheet->setCellValue('B7', '=SUM(B4:B6)');

        // ---- Estadísticas: Status (columna J) ----
        $sheet->setCellValue('A9', 'Terminado');
        $sheet->setCellValue('B9', '=COUNTIF(J23:J100, "Terminado")');

        $sheet->setCellValue('A10', 'No Terminado');
        $sheet->setCellValue('B10', '=COUNTIF(J23:J100, "No terminado")');

        // ---- Estadísticas: TIPO DE UNIDAD (columna G) ----
        $sheet->setCellValue('A12', 'TIPO DE UNIDAD');
        $sheet->setCellValue('A13', 'PE');
        $sheet->setCellValue('B13', '=COUNTIF(G23:G100,"PE")');
        $sheet->setCellValue('A14', 'CASA');
        $sheet->setCellValue('B14', '=COUNTIF(G23:G100, "T-*")');

        // Formato del bloque de estadísticas (Calibri 11; A12 en negrita),
        // sin formato numérico: idéntico al original.
        $sheet->getStyle('A4:B14')->getFont()->setName('Calibri')->setSize(11);
        $sheet->getStyle('A12')->getFont()->setBold(true);

        // ---- Encabezados de la tabla de datos (fila 22, azul con texto blanco) ----
        foreach (self::COLUMNAS_TABLA as $idx => $titulo) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($idx + 1).'22', $titulo);
        }
        $sheet->getRowDimension(22)->setRowHeight(15.75);
        $sheet->getStyle('A22:O22')->applyFromArray([
            'font' => ['name' => 'Calibri', 'size' => 11, 'bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F4E78']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_BOTTOM],
        ]);
        // Separadores verticales blancos entre columnas del encabezado (B..O).
        $sheet->getStyle('B22:O22')->applyFromArray([
            'borders' => [
                'left' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']],
                'vertical' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']],
            ],
        ]);

        // ---- Llenar datos (filas 23 en adelante) ----
        $fila = 23;
        foreach ($instalaciones as $i) {
            if ((int) Carbon::parse($i->fechaAlta)->month !== $mesN) {
                continue;
            }

            $status = $i->activo ? 'Terminado' : 'No Terminado';
            $tipo = $this->tipoRequerimiento($i);

            // Campos que no existen en tblInstalacionesGPS -> N/A (según indicación).
            $na = 'N/A';

            $valores = [
                $i->marca_gps ?? $na,                                  // A Marca GPS
                $i->modelo_gps ?? $na,                                 // B Modelo GPS
                $i->imei ?? $na,                                       // C IMEI
                $na,                                                   // D SN (no existe)
                $i->numero_actual ?? $na,                              // E No Telefonico
                Carbon::parse($i->fechaAlta)->format('d/m/Y'),         // F Fecha de instalacion
                $i->unidad ?? $na,                                     // G Unidad
                $na,                                                   // H Permisionario (no existe)
                $tipo,                                                 // I Tipo de requerimiento
                $status,                                               // J Status
                $na,                                                   // K Marca Anterior (no existe)
                $na,                                                   // L Modelo GPS Anterior (no existe)
                $na,                                                   // M IMEI GPS Anterior (no existe)
                $na,                                                   // N SN GPS Anterior (no existe)
                $i->numero_anterior ?? $na,                            // O No Telefonico Anterior
            ];

            foreach ($valores as $idx => $valor) {
                // Se guarda como texto, igual que el archivo original.
                $sheet->setCellValueExplicit(
                    Coordinate::stringFromColumnIndex($idx + 1).$fila,
                    (string) $valor,
                    DataType::TYPE_STRING
                );
            }
            $fila++;
        }

        // ---- Formato de las filas de datos, fiel al original ----
        // Relleno alterno B8CCE4 / DBE5F2, separadores blancos, primera fila
        // con borde superior grueso y la columna A sin borde izquierdo.
        $ultima = $fila - 1;
        if ($ultima >= 23) {
            $sheet->getRowDimension(23)->setRowHeight(15.75);
            for ($r = 23; $r <= $ultima; $r++) {
                $color = (($r - 23) % 2 === 0) ? 'FFB8CCE4' : 'FFDBE5F2';
                $arriba = ($r === 23) ? Border::BORDER_THICK : Border::BORDER_THIN;

                $sheet->getStyle("A{$r}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $color]],
                    'borders' => ['top' => ['borderStyle' => $arriba, 'color' => ['argb' => 'FFFFFFFF']]],
                ]);
                $sheet->getStyle("B{$r}:O{$r}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $color]],
                    'borders' => [
                        'top' => ['borderStyle' => $arriba, 'color' => ['argb' => 'FFFFFFFF']],
                        'left' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']],
                        'vertical' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']],
                    ],
                ]);
            }
            // IMEI y SN con formato 0.00, como el original.
            $sheet->getStyle("C23:D{$ultima}")->getNumberFormat()->setFormatCode('0.00');
        }
    }

    /**
     * Tipo de requerimiento derivado de las fechas de la tabla:
     * - fecha_reinstalacion con valor -> Reinstalacion
     * - fecha_cambio_sim con valor    -> Cambio de SIM
     * - en otro caso                  -> Instalacion
     */
    private function tipoRequerimiento(object $i): string
    {
        if (! empty($i->fecha_reinstalacion)) {
            return 'Reinstalacion';
        }
        if (! empty($i->fecha_cambio_sim)) {
            return 'Cambio de SIM';
        }

        return 'Instalacion';
    }
}
