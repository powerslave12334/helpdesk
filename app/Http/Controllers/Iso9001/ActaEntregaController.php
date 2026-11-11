<?php

namespace App\Http\Controllers\Iso9001;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActaEntregaFirmarRequest;
use App\Http\Requests\ActaEntregaRequest;
use App\Models\IsoActaEntrega;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * FO-IT-02 - Acta de entrega: listado, alta, PDF y firmas.
 *
 * Las rutas genéricas de iso9001.* delegan aquí (clave "controlador" en
 * config/formatos_iso.php); los renglones de equipos del acta se guardan
 * como filas en tblIsoActaEntregaEquipo y el PDF se regenera bajo demanda
 * desde los datos guardados, sin archivar el archivo. La huella SHA-256 se
 * recalcula en el hook "saved" del modelo (ver IsoActaEntrega).
 */
class ActaEntregaController extends Controller
{
    /** Clave del formato en el catálogo. */
    public const CLAVE = 'FO-IT-02';

    /** Lista de documentos activos (patrón de tabla del proyecto). */
    public function index(): View
    {
        $documentos = IsoActaEntrega::query()
            ->with(['creador', 'equipos'])
            ->where('activo', true)
            ->orderByDesc('fechaCreacion')
            ->orderByDesc('id_ActaEntrega')
            ->get();

        return view('iso9001.formatos.acta_entrega_documentos', [
            'formato' => $this->formato(),
            'documentos' => $documentos,
        ]);
    }

    /** Formulario de nuevo documento (fecha por defecto: hoy). */
    public function create(): View
    {
        return view('iso9001.formatos.acta_entrega_form', [
            'formato' => $this->formato(),
            'fechaDefault' => now()->toDateString(),
            'condiciones' => IsoActaEntrega::CONDICIONES,
            'filasDefault' => [$this->renglonVacio()],
        ]);
    }

    /** Valida y guarda el acta con sus renglones de equipos. */
    public function store(ActaEntregaRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        $acta = DB::transaction(function () use ($datos): IsoActaEntrega {
            $acta = IsoActaEntrega::create([
                'fecha' => $datos['fecha'],
                'entregadoPor' => $datos['entregadoPor'],
                'recibidoPor' => $datos['recibidoPor'],
                'id_Usuario' => (int) auth()->id(),
                'fechaCreacion' => now(),
            ]);

            $acta->equipos()->createMany($datos['equipos']);

            return $acta;
        });

        return redirect()
            ->route('iso9001.documentos', ['clave' => self::CLAVE])
            ->with('success', "Documento {$acta->folio} guardado. Ya puedes ver o descargar su PDF.");
    }

    /** PDF bajo demanda: vista previa (stream) o descarga (?descargar=1). */
    public function pdf(Request $request, string $id)
    {
        $acta = $this->buscar($id);

        $pdf = Pdf::loadView('iso9001.formatos.acta_entrega_pdf', [
            'documento' => $acta,
            'cabeceraBase64' => $this->cabeceraBase64(),
        ])->setPaper('letter');

        $fileName = $acta->folio.'.pdf';

        return $request->boolean('descargar')
            ? $pdf->download($fileName)
            : $pdf->stream($fileName);
    }

    /**
     * Guarda las firmas capturadas (entrega y/o recepción) con su fecha; la
     * huella se recalcula automáticamente en el hook "saved" del modelo.
     */
    public function firmar(ActaEntregaFirmarRequest $request, string $id): RedirectResponse
    {
        $acta = $this->buscar($id);
        $datos = $request->validated();
        $ahora = now()->format('Y-m-d H:i:s');
        $firmas = [];

        if (! empty($datos['firmaEntrega'])) {
            $firmas['firmaEntrega'] = $datos['firmaEntrega'];
            $firmas['fechaFirmaEntrega'] = $ahora;
        }

        if (! empty($datos['firmaRecepcion'])) {
            $firmas['firmaRecepcion'] = $datos['firmaRecepcion'];
            $firmas['fechaFirmaRecepcion'] = $ahora;
        }

        $acta->update($firmas);

        return back()->with('success', "Firma(s) guardada(s) en el documento {$acta->folio}.");
    }

    /**
     * Compara la huella registrada con la recalculada desde los datos
     * actuales del documento.
     *
     * @return array{resultado: string, documento: IsoActaEntrega, resumen: array<int, array<string, string|bool>>}|null
     *                                                                                                                   null si el folio no corresponde a un documento activo.
     */
    public function verificar(string $id): ?array
    {
        $acta = IsoActaEntrega::query()
            ->with('equipos')
            ->where('activo', true)
            ->find($id);

        if (! $acta) {
            return null;
        }

        // Resguardo: un documento sin huella registrada adopta el estado
        // actual como huella inicial.
        if ($acta->huella === null) {
            $acta->updateQuietly(['huella' => $acta->calcularHuella()]);
        }

        return [
            'resultado' => $acta->huella === $acta->calcularHuella() ? 'vigente' : 'alterado',
            'documento' => $acta,
            'resumen' => $this->resumen($acta),
        ];
    }

    /** Borrado lógico: el registro se conserva para auditoría. */
    public function destroy(string $id): RedirectResponse
    {
        $acta = $this->buscar($id);

        if ($acta->estaFirmado()) {
            return back()->with('error', "El documento {$acta->folio} está firmado y no puede eliminarse.");
        }

        $acta->update(['activo' => false]);

        return back()->with('success', "Documento {$acta->folio} eliminado.");
    }

    /** Acta activa (con sus renglones) o 404. */
    private function buscar(string $id): IsoActaEntrega
    {
        return IsoActaEntrega::query()
            ->with('equipos')
            ->where('activo', true)
            ->findOrFail($id);
    }

    /**
     * Campos del documento que muestra la página de verificación.
     *
     * @return array<int, array{etiqueta: string, valor: string, ok?: bool}>
     */
    private function resumen(IsoActaEntrega $acta): array
    {
        return [
            ['etiqueta' => 'Entregado por', 'valor' => $acta->entregadoPor],
            ['etiqueta' => 'Recibido por', 'valor' => $acta->recibidoPor],
            ['etiqueta' => 'Equipos en el acta', 'valor' => (string) $acta->equipos->count()],
            [
                'etiqueta' => 'Firma de entrega',
                'valor' => $acta->fechaFirmaEntrega?->format('d/m/Y H:i') ?? 'Sin firmar',
                'ok' => $acta->fechaFirmaEntrega !== null,
            ],
            [
                'etiqueta' => 'Firma de recepción',
                'valor' => $acta->fechaFirmaRecepcion?->format('d/m/Y H:i') ?? 'Sin firmar',
                'ok' => $acta->fechaFirmaRecepcion !== null,
            ],
        ];
    }

    /** Renglón vacío con el que inicia la tabla dinámica del formulario. *
     * @return array<string, int|string>
     */
    private function renglonVacio(): array
    {
        return [
            'cantidad' => 1,
            'descripcion' => '',
            'marca' => '',
            'modelo' => '',
            'noSerie' => '',
            'condiciones' => '',
        ];
    }

    /** Datos del formato desde el catálogo (config/formatos_iso.php). */
    private function formato(): array
    {
        return collect(config('formatos_iso.formatos', []))
            ->firstWhere('clave', self::CLAVE)
            ?? ['clave' => self::CLAVE, 'nombre' => 'Acta de entrega', 'icono' => 'fas fa-hand-holding'];
    }

    /** Cabecera oficial del formato en base64 (patrón de ResponsivaErpController). */
    private function cabeceraBase64(): string
    {
        $ruta = public_path('img/iso/fo-it-02-cabecera.png');

        if (! file_exists($ruta)) {
            return '';
        }

        return 'data:image/png;base64,'.base64_encode(file_get_contents($ruta));
    }
}
