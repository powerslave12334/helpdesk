<?php

namespace App\Http\Controllers\Iso9001;

use App\Http\Controllers\Controller;
use App\Http\Requests\InventarioHerramientaRequest;
use App\Models\IsoInventarioHerramienta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InventarioHerramientaController extends Controller
{
    public const CLAVE = 'FO-IT-04';

    public function index(): View
    {
        $request = request();
        $query = IsoInventarioHerramienta::query()->where('activo', true);

        if ($request->filled('codigo')) {
            $query->where('codigo', 'like', '%'.$request->codigo.'%');
        }
        if ($request->filled('nombre')) {
            $query->where('nombre', 'like', '%'.$request->nombre.'%');
        }
        if ($request->filled('marca')) {
            $query->where('marca', 'like', '%'.$request->marca.'%');
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $herramientas = $query->with('creador')
            ->orderByDesc('fechaCreacion')
            ->orderByDesc('id_InventarioHerramienta')
            ->paginate(15)
            ->appends($request->query());

        return view('iso9001.formatos.inventario_herramienta_documentos', [
            'formato' => $this->formato(),
            'herramientas' => $herramientas,
            'filtros' => $request->only(['codigo', 'nombre', 'marca', 'estado']),
        ]);
    }

    public function create(): View
    {
        return view('iso9001.formatos.inventario_herramienta_form', [
            'formato' => $this->formato(),
            'estados' => IsoInventarioHerramienta::ESTADOS,
        ]);
    }

    public function store(InventarioHerramientaRequest $request): RedirectResponse
    {
        $herramienta = IsoInventarioHerramienta::create([
            ...$request->validated(),
            'id_Usuario' => (int) auth()->id(),
            'fechaCreacion' => now(),
        ]);

        return redirect()
            ->route('iso9001.documentos', ['clave' => self::CLAVE])
            ->with('success', "Herramienta {$herramienta->folio} registrada.");
    }

    /** PDF: reporte consolidado si id='reporte', o hoja individual de una herramienta. */
    public function pdf(Request $request, string $id)
    {
        if ($id === 'reporte') {
            $herramientas = IsoInventarioHerramienta::query()
                ->where('activo', true)
                ->with('creador')
                ->orderBy('codigo')
                ->get();

            $pdf = Pdf::loadView('iso9001.formatos.inventario_herramienta_reporte', [
                'formato' => $this->formato(),
                'herramientas' => $herramientas,
            ])->setPaper('letter', 'landscape');

            return $request->boolean('descargar')
                ? $pdf->download('inventario-herramienta.pdf')
                : $pdf->stream('inventario-herramienta.pdf');
        }

        $herramienta = IsoInventarioHerramienta::query()
            ->where('activo', true)
            ->findOrFail($id);

        $pdf = Pdf::loadView('iso9001.formatos.inventario_herramienta_pdf', [
            'formato' => $this->formato(),
            'herramienta' => $herramienta,
        ])->setPaper('letter');

        return $request->boolean('descargar')
            ? $pdf->download($herramienta->folio.'.pdf')
            : $pdf->stream($herramienta->folio.'.pdf');
    }

    public function formato(): array
    {
        return collect(config('formatos_iso.formatos', []))
            ->firstWhere('clave', self::CLAVE)
            ?? ['clave' => self::CLAVE, 'nombre' => 'Inventario de herramienta', 'icono' => 'fas fa-tools'];
    }
}
