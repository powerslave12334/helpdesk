<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Mantenimiento;
use App\Models\Equipo;
use App\Models\EquipoUsuario;
use App\Models\Usuario;
use App\Models\Estatus;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class MantenimientoController extends Controller
{
    public function index()
    {
        $tecnicos = Usuario::where('esTecnico', true)->where('activo', true)->get();
        $estatus = Estatus::where('activo', true)->get();

        return view('inventario.mantenimiento', compact('tecnicos', 'estatus'));
    }

    public function api(Request $request)
    {
        $start = $request->input('start');
        $end = $request->input('end');
        $tecnicoId = $request->input('tecnico');
        $equipoId = $request->input('equipo');
        $tipo = $request->input('tipo');
        $estado = $request->input('estado');

        $query = Mantenimiento::with(['equipo', 'tecnico', 'estatus'])
            ->where('activo', true);

        if ($start && $end) {
            $query->whereBetween('fechaProgramada', [$start, $end]);
        }

        if ($tecnicoId) {
            $query->where('id_UsuarioTecnico', $tecnicoId);
        }

        if ($equipoId) {
            $query->where('id_Equipo', $equipoId);
        }

        if ($tipo) {
            $query->where('tipoMantenimiento', $tipo);
        }

        $mantenimientos = $query->get();

        $events = $mantenimientos->map(function ($m) {
            $estado = $m->fechaEjecucion ? 'Completado' : ($m->fechaProgramada->isPast() ? 'Vencido' : 'Pendiente');

            $color = match($estado) {
                'Completado' => '#059669',
                'Vencido' => '#dc2626',
                'Pendiente' => '#d97706',
                default => '#6b7280',
            };

            $asignacion = EquipoUsuario::where('id_Equipo', $m->id_Equipo)
                ->where('activo', true)
                ->with('usuario')
                ->first();

            $usuarioAsignado = $asignacion && $asignacion->usuario
                ? $asignacion->usuario->nombre . ' ' . $asignacion->usuario->app
                : 'Sin usuario';

            $reportePendiente = $estado === 'Completado' && empty($m->estadoInicial);

            return [
                'id' => $m->id_Mantenimiento,
                'title' => ($m->equipo->nombreEquipo ?? $m->equipo->marca ?? 'Equipo #' . $m->id_Equipo) . ' - ' . $usuarioAsignado,
                'start' => $m->fechaProgramada->format('Y-m-d\TH:i:s'),
                'end' => $m->fechaProgramada->copy()->addHours(2)->format('Y-m-d\TH:i:s'),
                'color' => $color,
                'extendedProps' => [
                    'equipo' => $m->equipo->nombreEquipo ?? $m->equipo->marca ?? 'N/A',
                    'usuarioAsignado' => $usuarioAsignado,
                    'tecnico' => $m->tecnico ? $m->tecnico->nombre . ' ' . $m->tecnico->app : 'Sin asignar',
                    'tipo' => $m->tipoMantenimiento,
                    'estado' => $estado,
                    'observaciones' => $m->observaciones ?? '',
                    'id_Equipo' => $m->id_Equipo,
                    'fechaEjecucion' => $m->fechaEjecucion?->format('d/m/Y H:i'),
                    'id_Ticket' => $m->id_Ticket,
                    'reportePendiente' => $reportePendiente,
                    'reporteCerrado' => $m->reporteCerrado,
                ],
            ];
        });

        if ($estado) {
            $events = $events->filter(function ($event) use ($estado) {
                return $event['extendedProps']['estado'] === $estado;
            })->values();
        }

        return response()->json($events);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_Equipo' => 'required|exists:tblEquipo,id_Equipo',
            'id_UsuarioTecnico' => 'required|exists:tblUsuario,id_Usuario',
            'fechaProgramada' => 'required|date',
            'tipoMantenimiento' => 'required|string|max:50',
            'observaciones' => 'nullable|string',
        ]);

        $estatusPendiente = Estatus::where('descripcion', 'Pendiente')->first();

        Mantenimiento::create([
            'id_Equipo' => $request->id_Equipo,
            'id_UsuarioTecnico' => $request->id_UsuarioTecnico,
            'fechaProgramada' => $request->fechaProgramada,
            'id_Estatus' => $estatusPendiente?->id_Estatus,
            'tipoMantenimiento' => $request->tipoMantenimiento,
            'observaciones' => $request->observaciones,
            'fechaAlta' => now(),
            'activo' => true,
        ]);

        $equipo = Equipo::find($request->id_Equipo);
        if ($equipo && $equipo->id_PeriodoMantenimiento) {
            $periodo = \App\Models\PeriodoMantenimiento::find($equipo->id_PeriodoMantenimiento);
            if ($periodo && $periodo->meses > 0) {
                $fechaBase = Carbon::parse($request->fechaProgramada);
                $limite = $fechaBase->copy()->addYears(2);
                $fechaComparacion = $fechaBase->copy();

                while (true) {
                    $siguienteFecha = $fechaComparacion->copy()->addMonths($periodo->meses);
                    if ($siguienteFecha->gt($limite)) break;

                    $existe = Mantenimiento::where('id_Equipo', $equipo->id_Equipo)
                        ->whereDate('fechaProgramada', $siguienteFecha->format('Y-m-d'))
                        ->where('activo', true)
                        ->exists();

                    if (!$existe) {
                        Mantenimiento::create([
                            'id_Equipo' => $equipo->id_Equipo,
                            'id_UsuarioTecnico' => $request->id_UsuarioTecnico,
                            'fechaProgramada' => $siguienteFecha,
                            'id_Estatus' => $estatusPendiente?->id_Estatus,
                            'tipoMantenimiento' => 'Preventivo',
                            'observaciones' => "Mantenimiento {$periodo->descripcion} automático",
                            'fechaAlta' => now(),
                            'activo' => true,
                        ]);
                    }

                    $fechaComparacion = $siguienteFecha;
                }
            }
        }

        return redirect()->route('inventario.mantenimientos.index')->with('success', 'Mantenimiento programado correctamente.');
    }

    public function completar(Request $request, $id)
    {
        $mantenimiento = Mantenimiento::findOrFail($id);

        $estatusCompletado = Estatus::where('descripcion', 'Resuelto')->first();

        $mantenimiento->update([
            'fechaEjecucion' => now(),
            'id_Estatus' => $estatusCompletado?->id_Estatus,
        ]);

        return response()->json(['success' => true, 'message' => 'Mantenimiento completado.']);
    }

    public function destroy($id)
    {
        $mantenimiento = Mantenimiento::findOrFail($id);
        $mantenimiento->update(['activo' => false]);

        return response()->json(['success' => true]);
    }

    public function cambiarFecha(Request $request, $id)
    {
        $mantenimiento = Mantenimiento::findOrFail($id);

        $request->validate([
            'nueva_fecha' => 'required|date',
        ]);

        $nuevaFecha = Carbon::parse($request->nueva_fecha);
        $fechaAnterior = $mantenimiento->fechaProgramada->copy();
        $diferenciaDias = $fechaAnterior->diffInDays($nuevaFecha);

        $mantenimiento->update(['fechaProgramada' => $nuevaFecha]);

        $actualizados = 0;

        if ($request->propagar) {
            $futuros = Mantenimiento::where('id_Equipo', $mantenimiento->id_Equipo)
                ->where('activo', true)
                ->where('id_Mantenimiento', '!=', $id)
                ->where('fechaProgramada', '>', $fechaAnterior)
                ->orderBy('fechaProgramada')
                ->get();

            foreach ($futuros as $futuro) {
                $nuevaFechaFuturo = $futuro->fechaProgramada->addDays($diferenciaDias);
                $futuro->update(['fechaProgramada' => $nuevaFechaFuturo]);
                $actualizados++;
            }
        }

        $equipo = $mantenimiento->equipo->nombreEquipo ?? $mantenimiento->equipo->marca ?? 'Equipo';
        $mensaje = "Fecha de mantenimiento de $equipo actualizada a {$nuevaFecha->format('d/m/Y')}";
        if ($actualizados > 0) {
            $mensaje .= " ($actualizados mantenimientos futuros actualizados)";
        }

        return response()->json(['success' => true, 'message' => $mensaje]);
    }

    public function buscar(Request $request)
    {
        $termino = $request->input('q', '');

        if (strlen($termino) < 2) {
            return response()->json([]);
        }

        $equipoIds = EquipoUsuario::where('activo', true)
            ->whereHas('usuario', function ($q) use ($termino) {
                $q->where('nombre', 'LIKE', "%{$termino}%")
                  ->orWhere('app', 'LIKE', "%{$termino}%");
            })
            ->pluck('id_Equipo')
            ->toArray();

        $mantenimientos = Mantenimiento::with(['equipo', 'tecnico'])
            ->where('activo', true)
            ->where('fechaProgramada', '>=', now()->subMonths(6))
            ->where(function ($q) use ($termino, $equipoIds) {
                $q->whereHas('equipo', function ($q2) use ($termino) {
                    $q2->where('nombreEquipo', 'LIKE', "%{$termino}%")
                       ->orWhere('marca', 'LIKE', "%{$termino}%")
                       ->orWhere('modelo', 'LIKE', "%{$termino}%");
                })
                ->orWhereHas('tecnico', function ($q2) use ($termino) {
                    $q2->where('nombre', 'LIKE', "%{$termino}%")
                       ->orWhere('app', 'LIKE', "%{$termino}%");
                })
                ->orWhereIn('id_Equipo', $equipoIds);
            })
            ->orderBy('fechaProgramada')
            ->limit(10)
            ->get();

        $results = $mantenimientos->map(function ($m) {
            $nombreEquipo = $m->equipo->nombreEquipo ?? $m->equipo->marca ?? 'Equipo #' . $m->id_Equipo;
            $tecnicoNombre = $m->tecnico ? $m->tecnico->nombre . ' ' . $m->tecnico->app : 'Sin técnico';

            return [
                'id' => $m->id_Mantenimiento,
                'equipo' => $nombreEquipo,
                'tecnico' => $tecnicoNombre,
                'fecha' => $m->fechaProgramada->format('d/m/Y'),
                'fecha_iso' => $m->fechaProgramada->format('Y-m-d'),
                'tipo' => $m->tipoMantenimiento,
            ];
        });

        return response()->json($results);
    }

    public function buscarEquipo(Request $request)
    {
        $termino = $request->input('q', '');

        if (strlen($termino) < 2) {
            return response()->json([]);
        }

        $equipos = Equipo::where('activo', true)
            ->where(function ($q) use ($termino) {
                $q->where('nombreEquipo', 'LIKE', "%{$termino}%")
                   ->orWhere('marca', 'LIKE', "%{$termino}%")
                   ->orWhere('modelo', 'LIKE', "%{$termino}%")
                   ->orWhereHas('asignaciones', function ($q2) use ($termino) {
                       $q2->where('activo', true)
                          ->whereHas('usuario', function ($q3) use ($termino) {
                              $q3->where('nombre', 'LIKE', "%{$termino}%")
                                 ->orWhere('app', 'LIKE', "%{$termino}%");
                          })
                          ->orWhereHas('area', function ($q3) use ($termino) {
                              $q3->where('nombre', 'LIKE', "%{$termino}%");
                          });
                   });
            })
            ->with(['asignaciones.usuario', 'asignaciones.area', 'oficina'])
            ->limit(15)
            ->get();

        $results = $equipos->map(function ($eq) {
            $asignacion = $eq->asignaciones->where('activo', true)->first();
            $usuario = $asignacion && $asignacion->usuario ? $asignacion->usuario->nombre . ' ' . $asignacion->usuario->app : 'Sin usuario';
            $area = $asignacion && $asignacion->area ? $asignacion->area->nombre : 'Sin área';

            return [
                'id' => $eq->id_Equipo,
                'nombre' => $eq->nombreEquipo ?? $eq->marca,
                'marca_modelo' => $eq->marca . ' ' . $eq->modelo,
                'usuario' => $usuario,
                'area' => $area,
                'oficina' => $eq->oficina ? $eq->oficina->nombre : '',
            ];
        });

        return response()->json($results);
    }

    public function editarReporte($id)
    {
        $mantenimiento = Mantenimiento::with(['equipo', 'tecnico'])->findOrFail($id);
        $equipo = $mantenimiento->equipo;

        $asignacion = EquipoUsuario::where('id_Equipo', $equipo->id_Equipo)
            ->where('activo', true)
            ->with('usuario.area')
            ->first();

        $usuarioAsignado = $asignacion && $asignacion->usuario
            ? $asignacion->usuario->nombre . ' ' . $asignacion->usuario->app
            : 'Sin usuario asignado';

        $tecnico = $mantenimiento->tecnico;
        $tecnicos = Usuario::where('esTecnico', true)->where('activo', true)->orderBy('nombre')->get();

        return view('inventario.editar_reporte', compact(
            'mantenimiento', 'equipo', 'usuarioAsignado', 'tecnico', 'tecnicos'
        ));
    }

    public function guardarReporte(Request $request, $id)
    {
        $mantenimiento = Mantenimiento::findOrFail($id);

        $mantenimiento->update([
            'id_UsuarioTecnico' => $request->id_UsuarioTecnico,
            'tipoMantenimiento' => $request->tipoMantenimiento,
            'estadoInicial' => $request->estadoInicial,
            'hw_encendido' => $request->hw_encendido,
            'hw_pantalla' => $request->hw_pantalla,
            'hw_ventilacion' => $request->hw_ventilacion,
            'hw_dispositivos' => $request->hw_dispositivos,
            'sw_so' => $request->sw_so,
            'sw_seguridad' => $request->sw_seguridad,
            'sw_antivirus' => $request->sw_antivirus,
            'sw_rendimiento' => $request->sw_rendimiento,
            'act_limpieza' => $request->has('act_limpieza'),
            'act_revision_hw' => $request->has('act_revision_hw'),
            'act_actualizacion_so' => $request->has('act_actualizacion_so'),
            'act_instalacion_sw' => $request->has('act_instalacion_sw'),
            'act_parches' => $request->has('act_parches'),
            'act_eliminacion' => $request->has('act_eliminacion'),
            'act_diagnostico' => $request->has('act_diagnostico'),
            'act_otro' => $request->has('act_otro'),
            'act_otro_detalle' => $request->act_otro_detalle,
            'conclusion' => $request->conclusion,
            'observaciones' => $request->observaciones,
        ]);

        return redirect()->route('inventario.mantenimientos.reporte', ['id' => $id, 'preview' => $request->boolean('preview')]);
    }

    public function cerrarReporte(Request $request, $id)
    {
        $mantenimiento = Mantenimiento::findOrFail($id);

        $mantenimiento->update([
            'id_UsuarioTecnico' => $request->id_UsuarioTecnico,
            'tipoMantenimiento' => $request->tipoMantenimiento,
            'estadoInicial' => $request->estadoInicial,
            'hw_encendido' => $request->hw_encendido,
            'hw_pantalla' => $request->hw_pantalla,
            'hw_ventilacion' => $request->hw_ventilacion,
            'hw_dispositivos' => $request->hw_dispositivos,
            'sw_so' => $request->sw_so,
            'sw_seguridad' => $request->sw_seguridad,
            'sw_antivirus' => $request->sw_antivirus,
            'sw_rendimiento' => $request->sw_rendimiento,
            'act_limpieza' => $request->has('act_limpieza'),
            'act_revision_hw' => $request->has('act_revision_hw'),
            'act_actualizacion_so' => $request->has('act_actualizacion_so'),
            'act_instalacion_sw' => $request->has('act_instalacion_sw'),
            'act_parches' => $request->has('act_parches'),
            'act_eliminacion' => $request->has('act_eliminacion'),
            'act_diagnostico' => $request->has('act_diagnostico'),
            'act_otro' => $request->has('act_otro'),
            'act_otro_detalle' => $request->act_otro_detalle,
            'conclusion' => $request->conclusion,
            'observaciones' => $request->observaciones,
            'reporteCerrado' => true,
        ]);

        return response()->json(['success' => true, 'message' => 'Reporte guardado y cerrado correctamente.']);
    }

    public function generarReporte(Request $request, $id)
    {
        $mantenimiento = Mantenimiento::with(['equipo', 'tecnico'])->findOrFail($id);
        $equipo = $mantenimiento->equipo;

        $asignacion = EquipoUsuario::where('id_Equipo', $equipo->id_Equipo)
            ->where('activo', true)
            ->with('usuario.area')
            ->first();

        $usuarioAsignado = $asignacion && $asignacion->usuario
            ? $asignacion->usuario->nombre . ' ' . $asignacion->usuario->app
            : 'Sin usuario asignado';

        $area = $asignacion && $asignacion->area ? $asignacion->area->nombre : null;

        $tecnico = $mantenimiento->tecnico;

        $numMant = Mantenimiento::where('id_Equipo', $equipo->id_Equipo)
            ->where('fechaProgramada', '<=', $mantenimiento->fechaProgramada)
            ->where('activo', true)
            ->count();

        $oficina = $equipo->oficina ? $equipo->oficina->nombre : 'SIN';
        $ubicacion3 = strtoupper(substr($equipo->ubicacion ?? 'SIN', 0, 3));
        $nombreEquipo = $equipo->nombreEquipo ?? 'SIN';

        $numeroReporte = str_pad($numMant, 2, '0', STR_PAD_LEFT) . '-' . $oficina . '-' . $ubicacion3 . '-' . $nombreEquipo;

        $logoPath = public_path('img/logo.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }

        $pdf = Pdf::loadView('inventario.reporte_mantenimiento', compact(
            'mantenimiento', 'equipo', 'usuarioAsignado', 'area', 'tecnico', 'numeroReporte', 'logoBase64'
        ))->setPaper('letter');

        $fileName = 'Reporte_' . $numeroReporte . '.pdf';

        if ($request->boolean('preview')) {
            return $pdf->stream($fileName);
        }

        return $pdf->download($fileName);
    }
}
