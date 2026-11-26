<?php

namespace App\Http\Controllers\Iso9001;

use App\Http\Controllers\Controller;
use App\Http\Requests\InventarioTiRequest;
use App\Models\Equipo;
use App\Models\Estatus;
use App\Models\TipoEquipo;
use App\Models\Usuario;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventarioTiController extends Controller
{
    public const CLAVE = 'FO-IT-05';

    public function index(): View
    {
        $request = request();
        $query = Equipo::query()
            ->where('activo', true)
            ->with(['tipoEquipo', 'estatus']);

        if ($request->filled('usuario')) {
            $query->whereHas('usuarios', function ($q) use ($request) {
                $q->where('tblUsuario.nombre', 'like', '%'.$request->usuario.'%')
                    ->orWhere('tblUsuario.app', 'like', '%'.$request->usuario.'%')
                    ->orWhere('tblUsuario.apm', 'like', '%'.$request->usuario.'%');
            });
        }
        if ($request->filled('departamento')) {
            $query->whereHas('usuarios.depto', function ($q) use ($request) {
                $q->where('nombre', 'like', '%'.$request->departamento.'%');
            });
        }
        if ($request->filled('marca')) {
            $query->where('marca', 'like', '%'.$request->marca.'%');
        }
        if ($request->filled('condicion')) {
            $query->where('id_Estatus', $request->condicion);
        }

        $equipos = $query->with(['tipoEquipo', 'estatus', 'usuarios'])
            ->orderByDesc('fechaAlta')
            ->orderByDesc('id_Equipo')
            ->paginate(15)
            ->appends($request->query());

        $estatus = Estatus::where('activo', true)->orderBy('descripcion')->get();

        return view('iso9001.formatos.inventario_ti_documentos', [
            'formato' => $this->formato(),
            'equipos' => $equipos,
            'estatus' => $estatus,
            'filtros' => $request->only(['usuario', 'departamento', 'marca', 'condicion']),
        ]);
    }

    public function create(): View
    {
        $usuarios = Usuario::where('activo', true)
            ->with('depto')
            ->orderBy('nombre')
            ->get();

        $tiposEquipo = TipoEquipo::where('activo', true)
            ->orderBy('descripcion')
            ->get();

        $estatus = Estatus::where('activo', true)
            ->orderBy('descripcion')
            ->get();

        return view('iso9001.formatos.inventario_ti_form', [
            'formato' => $this->formato(),
            'usuarios' => $usuarios,
            'tiposEquipo' => $tiposEquipo,
            'estatus' => $estatus,
        ]);
    }

    public function store(InventarioTiRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        $usuario = Usuario::find($datos['usuario_id']);
        $departamento = $datos['departamento'] ?? ($usuario?->depto?->nombre ?? '');

        $equipo = Equipo::create([
            'nombreEquipo' => $datos['marca'].' '.$datos['modelo'].' - '.$datos['noSerie'],
            'marca' => $datos['marca'],
            'modelo' => $datos['modelo'],
            'id_TipoEquipo' => $datos['tipoEquipo'],
            'numeroSerie' => $datos['noSerie'],
            'ubicacion' => $datos['ubicacion'],
            'id_Estatus' => $datos['condicion'],
            'fechaAlta' => now(),
            'activo' => true,
            'id_Oficinas' => $usuario?->id_Oficinas ?? null,
        ]);

        // Asignar usuario al equipo (pivot tblEquipoUsuario)
        $equipo->usuarios()->syncWithoutDetaching([$datos['usuario_id'] => [
            'activo' => true,
            'fechaAlta' => now(),
        ]]);

        return redirect()
            ->route('iso9001.documentos', ['clave' => self::CLAVE])
            ->with('success', 'Activo FO-IT-05-'.$equipo->id_Equipo.' registrado.');
    }

    /** PDF: reporte consolidado si id='reporte', o hoja individual de un activo. */
    public function pdf(Request $request, string $id)
    {
        if ($id === 'reporte') {
            $equipos = Equipo::query()
                ->where('activo', true)
                ->with(['tipoEquipo', 'estatus', 'usuarios'])
                ->orderBy('nombreEquipo')
                ->get();

            $pdf = Pdf::loadView('iso9001.formatos.inventario_ti_reporte', [
                'formato' => $this->formato(),
                'equipos' => $equipos,
            ])->setPaper('letter', 'landscape');

            return $request->boolean('descargar')
                ? $pdf->download('inventario-ti.pdf')
                : $pdf->stream('inventario-ti.pdf');
        }

        $equipo = Equipo::query()
            ->where('activo', true)
            ->with(['tipoEquipo', 'estatus', 'usuarios'])
            ->findOrFail($id);

        $pdf = Pdf::loadView('iso9001.formatos.inventario_ti_pdf', [
            'formato' => $this->formato(),
            'equipo' => $equipo,
        ])->setPaper('letter');

        return $request->boolean('descargar')
            ? $pdf->download('FO-IT-05-'.$equipo->id_Equipo.'.pdf')
            : $pdf->stream('FO-IT-05-'.$equipo->id_Equipo.'.pdf');
    }

    public function destroy(string $id): RedirectResponse
    {
        $equipo = Equipo::query()->where('activo', true)->findOrFail($id);
        $equipo->update(['activo' => false]);

        return redirect()
            ->route('iso9001.documentos', ['clave' => self::CLAVE])
            ->with('success', 'Activo FO-IT-05-'.$equipo->id_Equipo.' dado de baja.');
    }

    public function formato(): array
    {
        return collect(config('formatos_iso.formatos', []))
            ->firstWhere('clave', self::CLAVE)
            ?? ['clave' => self::CLAVE, 'nombre' => 'Inventario de TI', 'icono' => 'fas fa-laptop'];
    }
}
