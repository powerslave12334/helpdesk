<?php

namespace App\Http\Controllers\Iso9001;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResponsivaErpFirmarRequest;
use App\Http\Requests\ResponsivaErpRequest;
use App\Models\IsoResponsivaErp;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * FO-IT-01 - Responsiva ERP: listado, alta, PDF y firmas.
 *
 * Las rutas genéricas de iso9001.* delegan aquí (clave "controlador" en
 * config/formatos_iso.php); el PDF se regenera bajo demanda desde los datos
 * guardados, sin archivar el archivo. La huella SHA-256 se recalcula en el
 * hook "saved" del modelo (ver IsoResponsivaErp).
 */
class ResponsivaErpController extends Controller
{
    /** Clave del formato en el catálogo. */
    public const CLAVE = 'FO-IT-01';

    /** Lista de documentos activos (patrón de tabla del proyecto). */
    public function index(): View
    {
        $documentos = IsoResponsivaErp::query()
            ->with('creador')
            ->where('activo', true)
            ->orderByDesc('fechaCreacion')
            ->orderByDesc('id_ResponsivaErp')
            ->get();

        return view('iso9001.formatos.responsiva_erp_documentos', [
            'formato' => $this->formato(),
            'documentos' => $documentos,
        ]);
    }

    /** Formulario de nuevo documento (fecha por defecto: hoy). */
    public function create(): View
    {
        return view('iso9001.formatos.responsiva_erp_form', [
            'formato' => $this->formato(),
            'fechaDefault' => now()->toDateString(),
        ]);
    }

    /** Valida y guarda el documento. */
    public function store(ResponsivaErpRequest $request): RedirectResponse
    {
        $documento = IsoResponsivaErp::create([
            ...$request->validated(),
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

        $pdf = Pdf::loadView('iso9001.formatos.responsiva_erp_pdf', [
            'documento' => $documento,
            'cabeceraBase64' => $this->cabeceraBase64(),
        ])->setPaper('letter');

        $fileName = $documento->folio.'.pdf';

        return $request->boolean('descargar')
            ? $pdf->download($fileName)
            : $pdf->stream($fileName);
    }

    /**
     * Guarda las firmas capturadas (empleado y/o TI) con su fecha; la huella
     * se recalcula automáticamente en el hook "saved" del modelo.
     */
    public function firmar(ResponsivaErpFirmarRequest $request, string $id): RedirectResponse
    {
        $documento = $this->buscar($id);
        $datos = $request->validated();
        $ahora = now()->format('Y-m-d H:i:s');
        $firmas = [];

        if (! empty($datos['firmaEmpleado'])) {
            $firmas['firmaEmpleado'] = $datos['firmaEmpleado'];
            $firmas['fechaFirmaEmpleado'] = $ahora;
        }

        if (! empty($datos['firmaTi'])) {
            $firmas['firmaTi'] = $datos['firmaTi'];
            $firmas['fechaFirmaTi'] = $ahora;
        }

        $documento->update($firmas);

        return back()->with('success', "Firma(s) guardada(s) en el documento {$documento->folio}.");
    }

    /**
     * Compara la huella registrada con la recalculada desde los datos
     * actuales del documento.
     *
     * @return array{resultado: string, documento: IsoResponsivaErp}|null
     *                                                                    null si el folio no corresponde a un documento activo.
     */
    public function verificar(string $id): ?array
    {
        $documento = IsoResponsivaErp::query()
            ->where('activo', true)
            ->find($id);

        if (! $documento) {
            return null;
        }

        // Documentos anteriores a la función de firmas no tienen huella
        // registrada: se adopta el estado actual como huella inicial.
        if ($documento->huella === null) {
            $documento->updateQuietly(['huella' => $documento->calcularHuella()]);
        }

        return [
            'resultado' => $documento->huella === $documento->calcularHuella() ? 'vigente' : 'alterado',
            'documento' => $documento,
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
    private function buscar(string $id): IsoResponsivaErp
    {
        return IsoResponsivaErp::query()
            ->where('activo', true)
            ->findOrFail($id);
    }

    /** Datos del formato desde el catálogo (config/formatos_iso.php). */
    private function formato(): array
    {
        return collect(config('formatos_iso.formatos', []))
            ->firstWhere('clave', self::CLAVE)
            ?? ['clave' => self::CLAVE, 'nombre' => 'Responsiva ERP', 'icono' => 'fas fa-file-signature'];
    }

    /** Cabecera oficial del formato en base64 (patrón de MantenimientoController). */
    private function cabeceraBase64(): string
    {
        $ruta = public_path('img/iso/fo-it-01-cabecera.png');

        if (! file_exists($ruta)) {
            return '';
        }

        return 'data:image/png;base64,'.base64_encode(file_get_contents($ruta));
    }
}
