<?php

namespace App\Http\Controllers\Iso9001;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecepcionEquipoFirmarRequest;
use App\Http\Requests\RecepcionEquipoRequest;
use App\Models\Equipo;
use App\Models\IsoRecepcionEquipo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * FO-IT-03 - Recepción de equipo: listado, alta, PDF y firmas.
 *
 * Las rutas genéricas de iso9001.* delegan aquí (clave "controlador" en
 * config/formatos_iso.php); los grupos de checkboxes se guardan como texto
 * (selección única directa y selección múltiple como lista por comas) y el
 * PDF se regenera bajo demanda desde los datos guardados, sin archivar el
 * archivo. La huella SHA-256 se recalcula en el hook "saved" del modelo
 * (ver IsoRecepcionEquipo).
 */
class RecepcionEquipoController extends Controller
{
    /** Clave del formato en el catálogo. */
    public const CLAVE = 'FO-IT-03';

    /** Lista de documentos activos (patrón de tabla del proyecto). */
    public function index(): View
    {
        $documentos = IsoRecepcionEquipo::query()
            ->with('creador')
            ->where('activo', true)
            ->orderByDesc('fechaCreacion')
            ->orderByDesc('id_RecepcionEquipo')
            ->get();

        return view('iso9001.formatos.recepcion_equipo_documentos', [
            'formato' => $this->formato(),
            'documentos' => $documentos,
        ]);
    }

    /** Formulario de nuevo documento (fecha por defecto: hoy). */
    public function create(): View
    {
        $equipos = $this->equiposAsignados();

        return view('iso9001.formatos.recepcion_equipo_form', [
            'formato' => $this->formato(),
            'fechaDefault' => now()->toDateString(),
            'motivos' => IsoRecepcionEquipo::MOTIVOS,
            'tiposActivo' => IsoRecepcionEquipo::TIPOS_ACTIVO,
            'accesorios' => IsoRecepcionEquipo::ACCESORIOS,
            'aplicacionesGastos' => IsoRecepcionEquipo::APLICACION_GASTOS,
            'descripcionDefault' => IsoRecepcionEquipo::DESCRIPCION_DEFECTO,
            'equipos' => $equipos,
        ]);
    }

    /**
     * Equipos con asignación activa en tblEquipoUsuario, para autocompletar
     * la identificación del activo en el formulario FO-IT-03.
     *
     * @return array<int, array{id_Equipo: int, nombreEquipo: string, marca: string, modelo: string, numeroSerie: string, imei: string|null, lineaSim: string|null, usuario: string}>
     */
    private function equiposAsignados(): array
    {
        $filas = DB::table('tblEquipo as e')
            ->join('tblEquipoUsuario as eu', 'eu.id_Equipo', '=', 'e.id_Equipo')
            ->join('tblUsuario as u', 'u.id_Usuario', '=', 'eu.id_Usuario')
            ->where('e.activo', 1)
            ->where('eu.activo', 1)
            ->whereNull('eu.fechaBaja')
            ->select(
                'e.id_Equipo',
                'e.nombreEquipo',
                'e.marca',
                'e.modelo',
                'e.numeroSerie',
                'e.imei',
                'e.lineaSim',
                'u.nombre',
                'u.app',
                'u.apm',
            )
            ->distinct()
            ->orderBy('e.marca')
            ->orderBy('e.modelo')
            ->get();

        return $filas->map(function ($fila): array {
            return [
                'id_Equipo' => (int) $fila->id_Equipo,
                'nombreEquipo' => (string) $fila->nombreEquipo,
                'marca' => (string) $fila->marca,
                'modelo' => (string) $fila->modelo,
                'numeroSerie' => (string) $fila->numeroSerie,
                'imei' => $fila->imei,
                'lineaSim' => $fila->lineaSim,
                'usuario' => trim("{$fila->nombre} {$fila->app} {$fila->apm}"),
            ];
        })->toArray();
    }

    /** Valida y guarda el documento. */
    public function store(RecepcionEquipoRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        // Si se seleccionó un equipo asignado, se copian sus datos de
        // identificación y se guarda la FK. Si no, se usa texto libre.
        $idEquipo = $datos['id_equipo'] ?? null;
        unset($datos['id_equipo']);

        if ($idEquipo) {
            $equipo = Equipo::find($idEquipo);
            if ($equipo) {
                $datos['marca'] = $equipo->marca;
                $datos['modelo'] = $equipo->modelo;
                $datos['noSerie'] = $equipo->numeroSerie;
                $datos['imei'] = $equipo->imei;
                $datos['numeroTelefonico'] = $equipo->lineaSim;
            }
            $datos['id_Equipo'] = $idEquipo;
        }

        $documento = IsoRecepcionEquipo::create([
            'id_Equipo' => $datos['id_Equipo'] ?? null,
            'fecha' => $datos['fecha'],
            'motivoEntrega' => $datos['motivoEntrega'],
            'tipoActivo' => implode(', ', $datos['tipoActivo']),
            'marca' => $datos['marca'],
            'modelo' => $datos['modelo'],
            'noSerie' => $datos['noSerie'],
            'imei' => $this->opcional($datos['imei'] ?? null),
            'numeroTelefonico' => $this->opcional($datos['numeroTelefonico'] ?? null),
            'accesorios' => implode(', ', $datos['accesorios']),
            'descripcionEntrega' => $datos['descripcionEntrega'],
            'aplicacionGastos' => $datos['aplicacionGastos'],
            'gastosReparacion' => $datos['gastosReparacion'],
            'revisadoPor' => $datos['revisadoPor'],
            'entregadoPor' => $datos['entregadoPor'],
            'id_Usuario' => (int) auth()->id(),
            'fechaCreacion' => now(),
        ]);

        return redirect()
            ->route('iso9001.documentos', ['clave' => self::CLAVE])
            ->with('success', "Documento {$documento->folio} guardado. Ya puedes ver o descargar su PDF.");
    }

    /** PDF bajo demanda: vista previa (stream) o descarga (?descargar=1). */
    public function pdf(Request $request, string $id)
    {
        $documento = $this->buscar($id);

        $pdf = Pdf::loadView('iso9001.formatos.recepcion_equipo_pdf', [
            'documento' => $documento,
            'cabeceraBase64' => $this->cabeceraBase64(),
        ])->setPaper('letter');

        $fileName = $documento->folio.'.pdf';

        return $request->boolean('descargar')
            ? $pdf->download($fileName)
            : $pdf->stream($fileName);
    }

    /**
     * Guarda las firmas capturadas (revisión y/o entrega) con su fecha; la
     * huella se recalcula automáticamente en el hook "saved" del modelo.
     */
    public function firmar(RecepcionEquipoFirmarRequest $request, string $id): RedirectResponse
    {
        $documento = $this->buscar($id);
        $datos = $request->validated();
        $ahora = now()->format('Y-m-d H:i:s');
        $firmas = [];

        if (! empty($datos['firmaRevision'])) {
            $firmas['firmaRevision'] = $datos['firmaRevision'];
            $firmas['fechaFirmaRevision'] = $ahora;
        }

        if (! empty($datos['firmaEntrega'])) {
            $firmas['firmaEntrega'] = $datos['firmaEntrega'];
            $firmas['fechaFirmaEntrega'] = $ahora;
        }

        $documento->update($firmas);

        return back()->with('success', "Firma(s) guardada(s) en el documento {$documento->folio}.");
    }

    /**
     * Compara la huella registrada con la recalculada desde los datos
     * actuales del documento.
     *
     * @return array{resultado: string, documento: IsoRecepcionEquipo, resumen: array<int, array<string, string|bool>>}|null
     *                                                                                                                       null si el folio no corresponde a un documento activo.
     */
    public function verificar(string $id): ?array
    {
        $documento = IsoRecepcionEquipo::query()
            ->where('activo', true)
            ->find($id);

        if (! $documento) {
            return null;
        }

        // Resguardo: un documento sin huella registrada adopta el estado
        // actual como huella inicial.
        if ($documento->huella === null) {
            $documento->updateQuietly(['huella' => $documento->calcularHuella()]);
        }

        return [
            'resultado' => $documento->huella === $documento->calcularHuella() ? 'vigente' : 'alterado',
            'documento' => $documento,
            'resumen' => $this->resumen($documento),
        ];
    }

    /** Borrado lógico: el registro se conserva para auditoría. */
    public function destroy(string $id): RedirectResponse
    {
        $documento = $this->buscar($id);

        if ($documento->estaFirmado()) {
            return back()->with('error', "El documento {$documento->folio} está firmado y no puede eliminarse.");
        }

        $documento->update(['activo' => false]);

        return back()->with('success', "Documento {$documento->folio} eliminado.");
    }

    /** Documento activo o 404. */
    private function buscar(string $id): IsoRecepcionEquipo
    {
        return IsoRecepcionEquipo::query()
            ->where('activo', true)
            ->findOrFail($id);
    }

    /**
     * Campos del documento que muestra la página de verificación.
     *
     * @return array<int, array{etiqueta: string, valor: string, ok?: bool}>
     */
    private function resumen(IsoRecepcionEquipo $documento): array
    {
        return [
            ['etiqueta' => 'Motivo de la entrega', 'valor' => $documento->motivoEntrega],
            ['etiqueta' => 'Tipo de activo', 'valor' => $documento->tipoActivo],
            ['etiqueta' => 'No. de serie', 'valor' => $documento->noSerie],
            [
                'etiqueta' => 'Firma de revisión',
                'valor' => $documento->fechaFirmaRevision?->format('d/m/Y H:i') ?? 'Sin firmar',
                'ok' => $documento->fechaFirmaRevision !== null,
            ],
            [
                'etiqueta' => 'Firma de entrega',
                'valor' => $documento->fechaFirmaEntrega?->format('d/m/Y H:i') ?? 'Sin firmar',
                'ok' => $documento->fechaFirmaEntrega !== null,
            ],
        ];
    }

    /** Texto opcional: cadena vacía → null (columnas nullable). */
    private function opcional(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor === '' ? null : $valor;
    }

    /** Datos del formato desde el catálogo (config/formatos_iso.php). */
    private function formato(): array
    {
        return collect(config('formatos_iso.formatos', []))
            ->firstWhere('clave', self::CLAVE)
            ?? ['clave' => self::CLAVE, 'nombre' => 'Recepción de equipo', 'icono' => 'fas fa-clipboard-check'];
    }

    /** Cabecera oficial del formato en base64 (patrón de ResponsivaErpController). */
    private function cabeceraBase64(): string
    {
        $ruta = public_path('img/iso/fo-it-03-cabecera.png');

        if (! file_exists($ruta)) {
            return '';
        }

        return 'data:image/png;base64,'.base64_encode(file_get_contents($ruta));
    }
}
