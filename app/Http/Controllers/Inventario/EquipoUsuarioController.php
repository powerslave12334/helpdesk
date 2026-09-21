<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Equipo;
use App\Models\EquipoUsuario;
use App\Models\HistorialEquipoUsuario;
use App\Models\Usuario;
use App\Models\Area;
use App\Models\Oficina;
use App\Services\ActaEntregaService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class EquipoUsuarioController extends Controller
{
    public function index()
    {
        $asignaciones = EquipoUsuario::with(['equipo.tipoEquipo', 'usuario', 'area', 'equipo.oficina'])
            ->get();
        $equiposDisponibles = Equipo::where('activo', true)
            ->where('esCompartido', false)
            ->whereNotIn('id_Equipo', function ($q) {
                $q->select('id_Equipo')->from('tblEquipoUsuario')
                    ->where('activo', true)->whereNull('fechaBaja');
            })
            ->get();
        $usuarios = Usuario::where('activo', true)->get();
        $areas = Area::where('activo', true)->get();

        return view('inventario.asignacion', compact('asignaciones', 'equiposDisponibles', 'usuarios', 'areas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_Equipo' => 'required|integer',
            'id_Usuario' => 'required|integer',
            'fechaAsignacion' => 'required|date',
            'id_Area' => 'nullable|integer',
        ]);

        $asignacionActiva = EquipoUsuario::where('id_Equipo', $request->id_Equipo)
            ->where('activo', true)->whereNull('fechaBaja')->first();
        if ($asignacionActiva) {
            return back()->with('error', 'Este equipo ya tiene una asignación activa');
        }

        DB::beginTransaction();
        try {
            $equipo = Equipo::find($request->id_Equipo);
            $usuario = Usuario::find($request->id_Usuario);

            $asignacion = EquipoUsuario::create([
                'id_Equipo' => $request->id_Equipo,
                'id_Usuario' => $request->id_Usuario,
                'id_Area' => $request->id_Area,
                'fechaAsignacion' => $request->fechaAsignacion,
                'observaciones' => $request->observaciones,
                'fechaAlta' => now(),
                'activo' => true,
            ]);

            HistorialEquipoUsuario::create([
                'id_Equipo' => $request->id_Equipo,
                'id_Usuario' => $request->id_Usuario,
                'fechaAsignacion' => $request->fechaAsignacion,
                'id_Area' => $request->id_Area,
                'fechaAlta' => now(),
                'fechaAccion' => now(),
                'id_UsuarioAccion' => session('IdUsuario'),
                'accion' => 'Vincular',
                'equipoMarca' => $equipo->marca,
                'equipoModelo' => $equipo->modelo,
                'equipoNombre' => $equipo->nombreEquipo,
                'equipoNumeroSerie' => $equipo->numeroSerie,
                'equipoUbicacion' => $equipo->ubicacion,
            ]);

            DB::commit();
            return redirect()->route('inventario.asignacion.index')->with('success', 'Equipo asignado correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al asignar: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'id_Equipo' => 'required|integer',
            'id_Usuario' => 'required|integer',
            'fechaAsignacion' => 'required|date',
        ]);

        DB::beginTransaction();
        try {
            $asignacion = EquipoUsuario::findOrFail($id);
            $equipo = Equipo::find($asignacion->id_Equipo);

            HistorialEquipoUsuario::create([
                'id_EquipoUsuarioOriginal' => $id,
                'id_Equipo' => $asignacion->id_Equipo,
                'id_Usuario' => $asignacion->id_Usuario,
                'fechaAsignacion' => $asignacion->fechaAsignacion,
                'fechaBaja' => $asignacion->fechaBaja,
                'id_Area' => $asignacion->id_Area,
                'fechaAlta' => now(),
                'fechaAccion' => now(),
                'id_UsuarioAccion' => session('IdUsuario'),
                'accion' => 'Re-vincular',
                'equipoMarca' => $equipo->marca,
                'equipoModelo' => $equipo->modelo,
                'equipoNombre' => $equipo->nombreEquipo,
                'equipoNumeroSerie' => $equipo->numeroSerie,
                'equipoUbicacion' => $equipo->ubicacion,
            ]);

            $asignacion->update([
                'id_Equipo' => $request->id_Equipo,
                'id_Usuario' => $request->id_Usuario,
                'id_Area' => $request->id_Area,
                'fechaAsignacion' => $request->fechaAsignacion,
                'observaciones' => $request->observaciones,
            ]);

            DB::commit();
            return redirect()->route('inventario.asignacion.index')->with('success', 'Asignación actualizada');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function toggleActivo($id)
    {
        $asignacion = EquipoUsuario::findOrFail($id);
        if ($asignacion->activo && !$asignacion->fechaBaja) {
            $asignacion->fechaBaja = now()->toDateString();
        }
        $asignacion->activo = !$asignacion->activo;
        $asignacion->save();
        return back()->with('success', $asignacion->activo ? 'Asignación activada' : 'Asignación desactivada');
    }

    public function desvincular(Request $request)
    {
        $request->validate([
            'id_EquipoUsuario' => 'required|integer',
            'motivo' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $asignacion = EquipoUsuario::with('equipo')->findOrFail($request->id_EquipoUsuario);
            $equipo = $asignacion->equipo;

            HistorialEquipoUsuario::create([
                'id_EquipoUsuarioOriginal' => $asignacion->id_EquipoUsuario,
                'id_Equipo' => $asignacion->id_Equipo,
                'id_Usuario' => $asignacion->id_Usuario,
                'fechaAsignacion' => $asignacion->fechaAsignacion,
                'id_Area' => $asignacion->id_Area,
                'fechaAlta' => now(),
                'fechaAccion' => now(),
                'id_UsuarioAccion' => session('IdUsuario'),
                'accion' => 'Desvincular',
                'motivo' => $request->motivo,
                'equipoMarca' => $equipo->marca,
                'equipoModelo' => $equipo->modelo,
                'equipoNombre' => $equipo->nombreEquipo,
                'equipoNumeroSerie' => $equipo->numeroSerie,
                'equipoUbicacion' => $equipo->ubicacion,
            ]);

            $asignacion->delete();

            DB::commit();
            return redirect()->route('inventario.asignacion.index')->with('success', 'Equipo desvinculado correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function desvincularSeleccionados(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'motivo' => 'nullable|string',
        ]);

        $count = 0;
        foreach ($request->ids as $id) {
            try {
                $asignacion = EquipoUsuario::with('equipo')->find($id);
                if (!$asignacion) continue;

                $equipo = $asignacion->equipo;
                HistorialEquipoUsuario::create([
                    'id_EquipoUsuarioOriginal' => $id,
                    'id_Equipo' => $asignacion->id_Equipo,
                    'id_Usuario' => $asignacion->id_Usuario,
                    'fechaAsignacion' => $asignacion->fechaAsignacion,
                    'id_Area' => $asignacion->id_Area,
                    'fechaAlta' => now(),
                    'fechaAccion' => now(),
                    'id_UsuarioAccion' => session('IdUsuario'),
                    'accion' => 'Desvincular',
                    'motivo' => $request->motivo,
                    'equipoMarca' => $equipo->marca,
                    'equipoModelo' => $equipo->modelo,
                    'equipoNombre' => $equipo->nombreEquipo,
                    'equipoNumeroSerie' => $equipo->numeroSerie,
                    'equipoUbicacion' => $equipo->ubicacion,
                ]);
                $asignacion->delete();
                $count++;
            } catch (\Exception $e) {
                continue;
            }
        }
        return redirect()->route('inventario.asignacion.index')->with('success', "$count equipos desvinculados correctamente");
    }

    public function generarActa($id)
    {
        $asignacion = EquipoUsuario::with(['equipo.tipoEquipo', 'usuario.departamento', 'area'])
            ->findOrFail($id);

        $entregadoPor = session('nombreUsuario', 'Administrador del Sistema');

        $service = new ActaEntregaService();
        $html = $service->generarActa($asignacion, $entregadoPor);

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', 'inline; filename="acta-entrega-' . $id . '.html"');
    }
}
