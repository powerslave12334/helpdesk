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
        $idUsuario = (int) auth()->id();
        $esAdmin = $this->permisosService->esAdministrador($idUsuario);
        $esTecnico = (bool) Usuario::where('id_Usuario', $idUsuario)->value('esTecnico');
        $tab = in_array($request->get('tab'), ['tickets', 'equipos', 'mantenimientos'], true)
            ? $request->get('tab')
            : 'tickets';

        // El dashboard del admin se acota SIEMPRE al dueño de la sesión:
        // técnico => tickets asignados a él; no técnico => los que él creó.
        // Se anula el filtro 'tecnico' de la URL porque no se puede abrir otro técnico.
        $filtros = $esAdmin ? $this->filtros($request) : $this->filtrosVacios();
        if ($esAdmin) {
            $filtros['tecnico'] = null;
            $filtros['idTecnicoPropio'] = $esTecnico ? $idUsuario : null;
            $filtros['idCreadorPropio'] = $esTecnico ? null : $idUsuario;
        }

        $kpis = $esAdmin
            ? $this->kpisAdmin($filtros)
            : $this->kpisPersonal($idUsuario, $esTecnico, $filtros);

        $recentTickets = $this->recentTickets($idUsuario, $esAdmin, $esTecnico, $filtros);

        $catalogos = $this->catalogos();

        // Para habilitar el botón de asignación rápida en la pestaña Equipos.
        $puedeAsignarEquipos = $this->permisosService->submoduloActivo($idUsuario, 'asignacion-equipo');

        // Descarga de reportes mensuales KPI (áreas habilitadas, ver config/kpi.php).
        $puedeDescargarKpi = $this->permisosService->puedeDescargarKpi($idUsuario);
        $puedeDescargarKpiDesarrollo = $this->permisosService->puedeDescargarKpiDesarrollo($idUsuario);
        $puedeDescargarKpiGps = $this->permisosService->puedeDescargarKpiGps($idUsuario);

        $usuario = auth()->user();
        return view('dashboard', compact(
            'usuario', 'kpis', 'recentTickets', 'esAdmin', 'esTecnico', 'tab', 'filtros', 'catalogos', 'puedeAsignarEquipos', 'puedeDescargarKpi', 'puedeDescargarKpiDesarrollo', 'puedeDescargarKpiGps'
        ));
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
            // Se inyectan en index() cuando el admin está en modo personal.
            'idTecnicoPropio' => null,
            'idCreadorPropio' => null,
        ];
    }

    private function filtrosVacios(): array
    {
        return [
            'preset' => 'todo', 'desde' => null, 'hasta' => null,
            'tipo' => null, 'estatus' => null, 'tecnico' => null, 'depto' => null, 'area' => null,
            'idTecnicoPropio' => null, 'idCreadorPropio' => null,
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
        if (!empty($f['idTecnicoPropio'])) $q->where('t.id_Tecnico', $f['idTecnicoPropio']);
        if (!empty($f['idCreadorPropio'])) $q->where('t.id_Usuario', $f['idCreadorPropio']);
        if ($f['depto']) {
            $q->whereIn('t.id_Usuario', DB::table('tblUsuario')->where('id_Depto', $f['depto'])->pluck('id_Usuario'));
        }
        if ($f['area']) {
            $q->whereIn('t.id_Usuario', DB::table('tblUsuario')->where('id_Area', $f['area'])->pluck('id_Usuario'));
        }

        return $q;
    }

    /** Consulta base de mantenimientos activos con joins a equipo, tipo, técnico y estatus. */
    private function mantenimientosBase()
    {
        return DB::table('tblMantenimiento as m')
            ->where('m.activo', true)
            ->join('tblEquipo as me', 'me.id_Equipo', '=', 'm.id_Equipo')
            ->join('tblTipoEquipo as mt', 'mt.id_TipoEquipo', '=', 'me.id_TipoEquipo')
            ->join('tblUsuario as mu', 'mu.id_Usuario', '=', 'm.id_UsuarioTecnico')
            // Join por FK directa: no se filtra por id_Modulos para no excluir el estatus real.
            ->leftJoin('tblEstatus as mse', 'mse.id_Estatus', '=', 'm.id_Estatus');
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
            ->select(DB::raw('AVG(' . $this->diffHoras('t.fechaAlta', 't.fechaCierre') . ') as horas'))
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

        // Detalle de los equipos sin asignar (para la tabla de la pestaña Equipos).
        $equiposSinAsignarLista = (clone $eq)
            ->join('tblTipoEquipo as te', 'te.id_TipoEquipo', '=', 'e.id_TipoEquipo')
            ->leftJoin('tblEstatus as st', function ($j) {
                $j->on('st.id_Estatus', '=', 'e.id_Estatus')->where('st.id_Modulos', 2);
            })
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))->from('tblEquipoUsuario as eu')
                    ->whereColumn('eu.id_Equipo', 'e.id_Equipo')
                    ->where('eu.activo', true)->whereNull('eu.fechaBaja');
            })
            ->orderBy('te.descripcion')
            ->orderBy('e.marca')
            ->orderBy('e.modelo')
            ->get([
                'e.id_Equipo', 'e.nombreEquipo', 'e.marca', 'e.modelo', 'e.numeroSerie',
                'e.ubicacion', 'e.sistemaOperativo', 'e.ip', 'e.fechaFinGarantia', 'e.fechaAlta',
                'te.descripcion as tipoEquipo', 'st.descripcion as estatusEquipo',
            ]);

        // Detalle de los equipos asignados (con usuario y área de la asignación activa).
        $equiposAsignadosLista = (clone $eq)
            ->join('tblEquipoUsuario as eu', function ($j) {
                $j->on('eu.id_Equipo', '=', 'e.id_Equipo')->where('eu.activo', true)->whereNull('eu.fechaBaja');
            })
            ->join('tblTipoEquipo as te', 'te.id_TipoEquipo', '=', 'e.id_TipoEquipo')
            ->join('tblUsuario as u', 'u.id_Usuario', '=', 'eu.id_Usuario')
            ->leftJoin('tblArea as a', 'a.id_Area', '=', 'eu.id_Area')
            ->leftJoin('tblEstatus as st', function ($j) {
                $j->on('st.id_Estatus', '=', 'e.id_Estatus')->where('st.id_Modulos', 2);
            })
            ->orderBy('u.nombre')
            ->orderBy('te.descripcion')
            ->get([
                'e.id_Equipo', 'e.nombreEquipo', 'e.marca', 'e.modelo', 'e.numeroSerie',
                'e.ubicacion', 'e.sistemaOperativo', 'e.fechaFinGarantia',
                'te.descripcion as tipoEquipo', 'st.descripcion as estatusEquipo',
                'u.nombre as usuarioNombre', 'u.app as usuarioApp', 'u.apm as usuarioApm',
                'a.nombre as areaAsignada', 'eu.fechaAsignacion as fechaAsignacion',
            ]);

        $equiposAsignados = (clone $eq)
            ->whereExists(function ($sub) {
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

        // Conteos por estado para los sub-tabs (misma lógica que MantenimientoController::api).
        $mantPendientes = (clone $mBase)->whereNull('fechaEjecucion')->where('fechaProgramada', '>=', now())->count();
        $mantCompletados = (clone $mBase)->whereNotNull('fechaEjecucion')->count();

        // Helper para las listas de mantenimiento con sus joins.
        $mantColumnas = [
            'm.id_Mantenimiento', 'm.tipoMantenimiento', 'm.fechaProgramada', 'm.fechaEjecucion',
            'm.reporteCerrado', 'm.observaciones',
            'me.id_Equipo', 'me.nombreEquipo', 'me.marca', 'me.modelo', 'me.numeroSerie', 'me.ubicacion',
            'mt.descripcion as tipoEquipo',
            'mu.nombre as tecnicoNombre', 'mu.app as tecnicoApp', 'mu.apm as tecnicoApm',
            'mse.descripcion as estatusMantenimiento',
        ];

        // Pendientes: sin ejecutar y con fecha programada futura (o de hoy).
        $mantPendientesLista = $this->mantenimientosBase()
            ->whereNull('m.fechaEjecucion')
            ->where('m.fechaProgramada', '>=', now())
            ->orderBy('m.fechaProgramada')
            ->get($mantColumnas);

        // Vencidos: sin ejecutar y con fecha programada ya pasada.
        $mantVencidosLista = $this->mantenimientosBase()
            ->whereNull('m.fechaEjecucion')
            ->where('m.fechaProgramada', '<', now())
            ->orderBy('m.fechaProgramada')
            ->get($mantColumnas);

        // Completados: ya tienen fecha de ejecución.
        $mantCompletadosLista = $this->mantenimientosBase()
            ->whereNotNull('m.fechaEjecucion')
            ->orderByDesc('m.fechaEjecucion')
            ->get($mantColumnas);

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
                'sin_asignar_lista' => $equiposSinAsignarLista,
                'asignados' => $equiposAsignados,
                'asignados_lista' => $equiposAsignadosLista,
                'garantias_vencidas' => $garantiasVencidas,
                'garantias_por_vencer' => $garantiasPorVencer,
            ],
            'mantenimiento' => [
                'proximos' => $mantProximos,
                'vencidos' => $mantVencidos,
                'pct_reporte_cerrado' => $pctReporteCerrado,
                'total' => $mantTotal,
                'pendientes' => $mantPendientes,
                'completados' => $mantCompletados,
                'pendientes_lista' => $mantPendientesLista,
                'vencidos_lista' => $mantVencidosLista,
                'completados_lista' => $mantCompletadosLista,
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
            ->select(DB::raw('AVG(' . $this->diffHoras('t.fechaAlta', 't.fechaCierre') . ') as horas'))->value('horas');

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

    private function diffHoras(string $desde, string $hasta): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlsrv' => "DATEDIFF(HOUR, {$desde}, {$hasta})",
            default => "TIMESTAMPDIFF(HOUR, {$desde}, {$hasta})",
        };
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
        $periodoSql = match (DB::connection()->getDriverName()) {
            'sqlsrv' => $porMes
                ? 'CONVERT(varchar(7), t.fechaAlta, 126)'
                : 'CONVERT(varchar(10), t.fechaAlta, 126)',
            default => "DATE_FORMAT(t.fechaAlta, '{$formato}')",
        };
        $rows = $q->select(DB::raw("{$periodoSql} as periodo"), DB::raw('COUNT(*) as total'))
            ->groupBy(DB::raw($periodoSql))->pluck('total', 'periodo');

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
        if (!empty($f['idTecnicoPropio'])) $q->where('id_Tecnico', $f['idTecnicoPropio']);
        if (!empty($f['idCreadorPropio'])) $q->where('id_Usuario', $f['idCreadorPropio']);
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
            // Para el modal de asignación rápida de equipos
            'usuariosAsignacion' => DB::table('tblUsuario')->where('activo', true)->orderBy('nombre')->get(['id_Usuario', 'nombre', 'app', 'apm']),
        ];
    }
}
