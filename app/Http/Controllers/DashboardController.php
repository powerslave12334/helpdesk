<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Equipo;
use App\Models\Usuario;
use App\Services\PermisosService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(private PermisosService $permisosService)
    {
    }

    public function index(Request $request)
    {
        $idUsuario = session('IdUsuario');
        $esAdmin = $this->permisosService->esAdministrador($idUsuario);
        $esTecnico = (bool) Usuario::where('id_Usuario', $idUsuario)->value('esTecnico');
        $tab = in_array($request->get('tab'), ['tickets', 'equipos', 'mantenimientos'], true)
            ? $request->get('tab')
            : 'tickets';

        $filtros = $esAdmin ? $this->filtros($request) : $this->filtrosVacios();

        $kpis = $esAdmin
            ? $this->kpisAdmin($filtros)
            : $this->kpisPersonal($idUsuario, $esTecnico, $filtros);

        $recentTickets = $this->recentTickets($idUsuario, $esAdmin, $esTecnico, $filtros);

        $catalogos = $this->catalogos();

        $usuario = auth()->user();
        return view('dashboard', compact('usuario', 'kpis', 'recentTickets', 'esAdmin', 'esTecnico', 'tab', 'filtros', 'catalogos'));
    }

    // ─────────────────────────── Filtros ───────────────────────────

    private function filtros(Request $request): array
    {
        $preset = in_array($request->get('rango'), ['hoy', '7d', '30d', 'mes', 'anio', 'todo', 'custom'], true)
            ? $request->get('rango')
            : 'mes';

        $now = now();
        $desde = null;
        $hasta = null;

        switch ($preset) {
            case 'hoy': $desde = $now->copy()->startOfDay(); $hasta = $now->copy()->endOfDay(); break;
            case '7d': $desde = $now->copy()->subDays(6)->startOfDay(); $hasta = $now->copy()->endOfDay(); break;
            case '30d': $desde = $now->copy()->subDays(29)->startOfDay(); $hasta = $now->copy()->endOfDay(); break;
            case 'mes': $desde = $now->copy()->startOfMonth(); $hasta = $now->copy()->endOfMonth(); break;
            case 'anio': $desde = $now->copy()->startOfYear(); $hasta = $now->copy()->endOfYear(); break;
            case 'todo': $desde = null; $hasta = null; break;
            case 'custom':
                $desde = $request->filled('desde') ? Carbon::parse($request->get('desde'))->startOfDay() : null;
                $hasta = $request->filled('hasta') ? Carbon::parse($request->get('hasta'))->endOfDay() : null;
                break;
        }

        return [
            'preset' => $preset,
            'desde' => $desde,
            'hasta' => $hasta,
            'tipo' => $request->integer('tipo') ?: null,
            'estatus' => $request->integer('estatus') ?: null,
            'tecnico' => $request->integer('tecnico') ?: null,
            'depto' => $request->integer('depto') ?: null,
            'area' => $request->integer('area') ?: null,
        ];
    }

    private function filtrosVacios(): array
    {
        return [
            'preset' => 'todo', 'desde' => null, 'hasta' => null,
            'tipo' => null, 'estatus' => null, 'tecnico' => null, 'depto' => null, 'area' => null,
        ];
    }

    private function baseTickets(array $f, bool $conRango = true)
    {
        $q = DB::table('tblTicket as t')->where('t.activo', true);

        if ($conRango) {
            if ($f['desde']) $q->where('t.fechaAlta', '>=', $f['desde']);
            if ($f['hasta']) $q->where('t.fechaAlta', '<=', $f['hasta']);
        }
        if ($f['tipo']) $q->where('t.id_TipoSoporte', $f['tipo']);
        if ($f['estatus']) $q->where('t.id_Estatus', $f['estatus']);
        if ($f['tecnico']) $q->where('t.id_Tecnico', $f['tecnico']);
        if ($f['depto']) {
            $q->whereIn('t.id_Usuario', DB::table('tblUsuario')->where('id_Depto', $f['depto'])->pluck('id_Usuario'));
        }
        if ($f['area']) {
            $q->whereIn('t.id_Usuario', DB::table('tblUsuario')->where('id_Area', $f['area'])->pluck('id_Usuario'));
        }

        return $q;
    }

    // ─────────────────────────── KPIs ───────────────────────────

    private function kpisAdmin(array $f): array
    {
        $porEstatus = $this->baseTickets($f)
            ->join('tblEstatus as e', 'e.id_Estatus', '=', 't.id_Estatus')
            ->select('e.descripcion', DB::raw('COUNT(*) as total'))
            ->groupBy('e.descripcion')
            ->pluck('total', 'descripcion');

        $count = fn($nombre) => (int) ($porEstatus[$nombre] ?? 0);
        $totalTickets = (int) $porEstatus->sum();
        $resueltos = $count('Resuelto');
        $cerrados = $count('Cerrado');

        $porTipo = $this->baseTickets($f)
            ->join('tblTipoSoporte as ts', 'ts.id_TipoSoporte', '=', 't.id_TipoSoporte')
            ->select('ts.descripcion', DB::raw('COUNT(*) as total'))
            ->groupBy('ts.descripcion')
            ->pluck('total', 'descripcion');

        $sinAsignar = (clone $this->baseTickets($f))->whereNull('t.id_Tecnico')->count();

        $estatusFinales = DB::table('tblEstatus')->where('id_Modulos', 1)->whereIn('descripcion', ['Resuelto', 'Cerrado'])->pluck('id_Estatus');
        $vencidos = (clone $this->baseTickets($f))
            ->whereNotIn('t.id_Estatus', $estatusFinales)
            ->where('t.fechaAlta', '<', now()->subDays(7))
            ->count();

        $tasaResolucion = $totalTickets > 0 ? round((($resueltos + $cerrados) / $totalTickets) * 100, 1) : 0;

        $tiempoPromedioHoras = (clone $this->baseTickets($f))
            ->whereNotNull('t.fechaCierre')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, t.fechaAlta, t.fechaCierre)) as horas'))
            ->value('horas');

        // Actividad reciente (ignora el rango de fechas, respeta los demás filtros)
        $fSinRango = $f;
        $fSinRango['desde'] = null;
        $fSinRango['hasta'] = null;
        $creadosHoy = (clone $this->baseTickets($fSinRango, false))->whereDate('t.fechaAlta', today())->count();
        $creadosSemana = (clone $this->baseTickets($fSinRango, false))->where('t.fechaAlta', '>=', now()->startOfWeek())->count();
        $creadosMes = (clone $this->baseTickets($fSinRango, false))->whereYear('t.fechaAlta', now()->year)->whereMonth('t.fechaAlta', now()->month)->count();

        [$tendenciaLabels, $tendenciaData] = $this->tendencia($f);

        // Equipos (sin filtros: los filtros son solo para tickets)
        $eq = DB::table('tblEquipo as e')->where('e.activo', true);

        $totalEquipos = (clone $eq)->count();

        $equiposPorTipo = (clone $eq)
            ->join('tblTipoEquipo as te', 'te.id_TipoEquipo', '=', 'e.id_TipoEquipo')
            ->select('te.descripcion', DB::raw('COUNT(*) as total'))
            ->groupBy('te.descripcion')
            ->orderByDesc('total')
            ->pluck('total', 'descripcion');

        $equiposPorEstatus = (clone $eq)
            ->join('tblEstatus as s', 's.id_Estatus', '=', 'e.id_Estatus')
            ->where('s.id_Modulos', 2)
            ->select('s.descripcion', DB::raw('COUNT(*) as total'))
            ->groupBy('s.descripcion')
            ->pluck('total', 'descripcion');

        $equiposSinAsignar = (clone $eq)
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))->from('tblEquipoUsuario as eu')
                    ->whereColumn('eu.id_Equipo', 'e.id_Equipo')
                    ->where('eu.activo', true)->whereNull('eu.fechaBaja');
            })->count();

        $garantiasVencidas = (clone $eq)->whereNotNull('e.fechaFinGarantia')->whereDate('e.fechaFinGarantia', '<', today())->count();
        $garantiasPorVencer = (clone $eq)->whereNotNull('e.fechaFinGarantia')
            ->whereDate('e.fechaFinGarantia', '>=', today())
            ->whereDate('e.fechaFinGarantia', '<=', today()->addDays(30))->count();

        // Mantenimiento (sin filtros)
        $mBase = DB::table('tblMantenimiento')->where('activo', true);
        $mantProximos = (clone $mBase)->whereNull('fechaEjecucion')->whereBetween('fechaProgramada', [now()->startOfDay(), now()->addDays(30)])->count();
        $mantVencidos = (clone $mBase)->whereNull('fechaEjecucion')->where('fechaProgramada', '<', now()->startOfDay())->count();
        $mantTotal = (clone $mBase)->count();
        $mantCerrados = (clone $mBase)->where('reporteCerrado', true)->count();
        $pctReporteCerrado = $mantTotal > 0 ? round(($mantCerrados / $mantTotal) * 100, 1) : 0;

        return [
            'tickets' => [
                'total' => $totalTickets,
                'por_estatus' => $porEstatus,
                'por_tipo' => $porTipo,
                'sin_asignar' => $sinAsignar,
                'vencidos' => $vencidos,
                'tasa_resolucion' => $tasaResolucion,
                'tiempo_promedio_horas' => $tiempoPromedioHoras,
                'creados_hoy' => $creadosHoy,
                'creados_semana' => $creadosSemana,
                'creados_mes' => $creadosMes,
            ],
            'tendencia' => ['labels' => $tendenciaLabels, 'data' => $tendenciaData],
            'inventario' => [
                'total' => $totalEquipos,
                'por_tipo' => $equiposPorTipo,
                'por_estatus' => $equiposPorEstatus,
                'sin_asignar' => $equiposSinAsignar,
                'garantias_vencidas' => $garantiasVencidas,
                'garantias_por_vencer' => $garantiasPorVencer,
            ],
            'mantenimiento' => [
                'proximos' => $mantProximos,
                'vencidos' => $mantVencidos,
                'pct_reporte_cerrado' => $pctReporteCerrado,
                'total' => $mantTotal,
            ],
        ];
    }

    private function kpisPersonal(int $idUsuario, bool $esTecnico, array $f): array
    {
        $q = $this->baseTickets($f)->where('t.id_Usuario', $idUsuario);

        $porEstatus = (clone $q)
            ->join('tblEstatus as e', 'e.id_Estatus', '=', 't.id_Estatus')
            ->select('e.descripcion', DB::raw('COUNT(*) as total'))
            ->groupBy('e.descripcion')
            ->pluck('total', 'descripcion');

        $resueltoId = DB::table('tblEstatus')->where('id_Modulos', 1)->where('descripcion', 'Resuelto')->value('id_Estatus');

        $pendientesVistoBueno = (clone $q)->where('t.id_Estatus', $resueltoId)->count();

        $tiempoPromedioHoras = (clone $q)->whereNotNull('t.fechaCierre')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, t.fechaAlta, t.fechaCierre)) as horas'))->value('horas');

        $asignados = 0;
        if ($esTecnico) {
            $asignados = (clone $this->baseTickets($f))->where('t.id_Tecnico', $idUsuario)->count();
        }

        return [
            'total' => (int) $porEstatus->sum(),
            'por_estatus' => $porEstatus,
            'asignados' => $asignados,
            'pendientes_visto_bueno' => $pendientesVistoBueno,
            'tiempo_promedio_horas' => $tiempoPromedioHoras,
        ];
    }

    private function tendencia(array $f): array
    {
        if ($f['desde']) {
            $desde = $f['desde'];
        } else {
            $min = $this->baseTickets($f, false)->min('t.fechaAlta');
            $desde = $min ? Carbon::parse($min)->startOfMonth() : now()->startOfMonth();
        }
        $hasta = $f['hasta'] ?? now()->endOfDay();
        $porMes = $desde->diffInDays($hasta) > 31;

        $q = $this->baseTickets($f)->where('t.fechaAlta', '>=', $desde)->where('t.fechaAlta', '<=', $hasta);

        $formato = $porMes ? '%Y-%m' : '%Y-%m-%d';
        $rows = $q->select(DB::raw("DATE_FORMAT(t.fechaAlta, '{$formato}') as periodo"), DB::raw('COUNT(*) as total'))
            ->groupBy('periodo')->pluck('total', 'periodo');

        $labels = [];
        $data = [];
        if ($porMes) {
            $cursor = $desde->copy()->startOfMonth();
            while ($cursor <= $hasta) {
                $labels[] = $cursor->format('m/Y');
                $data[] = (int) ($rows[$cursor->format('Y-m')] ?? 0);
                $cursor->addMonth();
            }
        } else {
            $cursor = $desde->copy()->startOfDay();
            while ($cursor <= $hasta) {
                $labels[] = $cursor->format('d/m');
                $data[] = (int) ($rows[$cursor->format('Y-m-d')] ?? 0);
                $cursor->addDay();
            }
        }

        return [$labels, $data];
    }

    private function recentTickets(int $idUsuario, bool $esAdmin, bool $esTecnico, array $f)
    {
        $q = Ticket::with(['usuario', 'tecnico', 'tipoSoporte', 'estatus'])->where('activo', true);

        if ($f['desde']) $q->where('fechaAlta', '>=', $f['desde']);
        if ($f['hasta']) $q->where('fechaAlta', '<=', $f['hasta']);
        if ($f['tipo']) $q->where('id_TipoSoporte', $f['tipo']);
        if ($f['estatus']) $q->where('id_Estatus', $f['estatus']);
        if ($f['tecnico']) $q->where('id_Tecnico', $f['tecnico']);
        if ($f['depto']) $q->whereIn('id_Usuario', DB::table('tblUsuario')->where('id_Depto', $f['depto'])->pluck('id_Usuario'));
        if ($f['area']) $q->whereIn('id_Usuario', DB::table('tblUsuario')->where('id_Area', $f['area'])->pluck('id_Usuario'));

        if (!$esAdmin) {
            $q->where(function ($w) use ($idUsuario, $esTecnico) {
                $w->where('id_Usuario', $idUsuario);
                if ($esTecnico) $w->orWhere('id_Tecnico', $idUsuario);
            });
        }

        return $q->orderBy('id_Ticket', 'desc')->limit(10)->get();
    }

    private function catalogos(): array
    {
        return [
            'tiposSoporte' => DB::table('tblTipoSoporte')->where('activo', true)->get(['id_TipoSoporte', 'descripcion']),
            'estatusTicket' => DB::table('tblEstatus')->where('id_Modulos', 1)->get(['id_Estatus', 'descripcion']),
            'tecnicos' => DB::table('tblUsuario')->where('esTecnico', true)->where('activo', true)->get(['id_Usuario', 'nombre', 'app']),
            'deptos' => DB::table('tblDepto')->where('activo', true)->orderBy('nombre')->get(['id_Depto', 'nombre']),
            'areas' => DB::table('tblArea')->where('activo', true)->orderBy('nombre')->get(['id_Area', 'nombre']),
        ];
    }
}
