<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Equipo;
use App\Models\TipoEquipo;
use App\Models\Estatus;
use App\Models\Oficina;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EquipoController extends Controller
{
    public function index()
    {
        $equipos = Equipo::with(['tipoEquipo', 'estatus', 'oficina', 'asignaciones.usuario'])
            ->get()
            ->map(function ($equipo) {
                $asignacionActiva = $equipo->asignaciones->where('activo', true)->whereNull('fechaBaja')->first();
                $equipo->asignado_a = $asignacionActiva ? $asignacionActiva->usuario->nombre . ' ' . $asignacionActiva->usuario->app : null;
                $equipo->estado_asignacion = $asignacionActiva ? 'asignado' : 'disponible';
                return $equipo;
            });
        $tiposEquipo = TipoEquipo::where('activo', true)->get();
        $estatus = Estatus::where('activo', true)->where('id_Modulos', 2)->get();
        $oficinas = Oficina::where('activo', true)->get();

        return view('inventario.equipo', compact('equipos', 'tiposEquipo', 'estatus', 'oficinas'));
    }

    public function seleccionarTipo()
    {
        $tiposEquipo = TipoEquipo::where('activo', true)->get();
        return view('inventario.equipo_seleccionar_tipo', compact('tiposEquipo'));
    }

    public function create($tipo = null)
    {
        $tiposEquipo = TipoEquipo::where('activo', true)->get();
        $estatus = Estatus::where('activo', true)->where('id_Modulos', 2)->get();
        $oficinas = Oficina::where('activo', true)->get();
        $tipoSeleccionado = $tipo ? TipoEquipo::find($tipo) : null;
        return view('inventario.equipo_create', compact('tiposEquipo', 'estatus', 'oficinas', 'tipoSeleccionado'));
    }

    public function store(Request $request)
    {
        $request->validate($this->reglasValidacion());

        if ($request->numeroSerie) {
            $existe = Equipo::where('activo', true)
                ->whereRaw('LOWER(numeroSerie) = ?', strtolower($request->numeroSerie))
                ->first();
            if ($existe) {
                return back()->with('error', 'El número de serie ya existe');
            }
        }

        DB::beginTransaction();
        try {
            $equipo = Equipo::create([
                'marca' => $request->marca,
                'modelo' => $request->modelo,
                'id_TipoEquipo' => $request->id_TipoEquipo,
                'id_Estatus' => $request->id_Estatus,
                'numeroSerie' => $request->numeroSerie,
                'nombreEquipo' => $request->nombreEquipo,
                'ubicacion' => $request->ubicacion,
                'observaciones' => $request->observaciones,
                'id_Oficinas' => $request->id_Oficinas,
                'id_PeriodoMantenimiento' => $request->id_PeriodoMantenimiento ?? 0,
                'fechaUltimoMantenimiento' => $request->fechaUltimoMantenimiento ?: null,
                'esCompartido' => $request->has('esCompartido') ? 1 : 0,
                'serieMotherboard' => $request->serieMotherboard,
                'procesador' => $request->procesador,
                'memoriaRam' => $request->memoriaRam,
                'almacenamiento' => $request->almacenamiento,
                'sistemaOperativo' => $request->sistemaOperativo,
                'ip' => $request->ip,
                'mac' => $request->mac,
                'estadoGarantia' => $request->estadoGarantia,
                'fechaFinGarantia' => $request->fechaFinGarantia ?: null,
                'especificaciones' => $request->especificaciones,
                'fechaAlta' => now(),
                'activo' => true,
            ]);

            DB::commit();
            return redirect()->route('inventario.equipo.index')->with('success', 'Equipo creado correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al crear equipo: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $equipo = Equipo::with(['tipoEquipo', 'estatus', 'oficina'])->findOrFail($id);
        $tiposEquipo = TipoEquipo::where('activo', true)->get();
        $estatus = Estatus::where('activo', true)->where('id_Modulos', 2)->get();
        $oficinas = Oficina::where('activo', true)->get();

        return view('inventario.equipo_edit', compact('equipo', 'tiposEquipo', 'estatus', 'oficinas'));
    }

    public function update(Request $request, $id)
    {
        $request->validate($this->reglasValidacion());

        if ($request->numeroSerie) {
            $existe = Equipo::where('activo', true)
                ->whereRaw('LOWER(numeroSerie) = ?', strtolower($request->numeroSerie))
                ->where('id_Equipo', '!=', $id)
                ->first();
            if ($existe) {
                return back()->with('error', 'El número de serie ya existe');
            }
        }

        DB::beginTransaction();
        try {
            $equipo = Equipo::findOrFail($id);
            $equipo->update([
                'marca' => $request->marca,
                'modelo' => $request->modelo,
                'id_TipoEquipo' => $request->id_TipoEquipo,
                'id_Estatus' => $request->id_Estatus,
                'numeroSerie' => $request->numeroSerie,
                'nombreEquipo' => $request->nombreEquipo,
                'ubicacion' => $request->ubicacion,
                'observaciones' => $request->observaciones,
                'id_Oficinas' => $request->id_Oficinas,
                'id_PeriodoMantenimiento' => $request->id_PeriodoMantenimiento ?? $equipo->id_PeriodoMantenimiento,
                'fechaUltimoMantenimiento' => $request->fechaUltimoMantenimiento ?: null,
                'esCompartido' => $request->has('esCompartido') ? 1 : 0,
                'serieMotherboard' => $request->serieMotherboard,
                'procesador' => $request->procesador,
                'memoriaRam' => $request->memoriaRam,
                'almacenamiento' => $request->almacenamiento,
                'sistemaOperativo' => $request->sistemaOperativo,
                'ip' => $request->ip,
                'mac' => $request->mac,
                'estadoGarantia' => $request->estadoGarantia,
                'fechaFinGarantia' => $request->fechaFinGarantia ?: null,
                'especificaciones' => $request->especificaciones,
            ]);

            DB::commit();
            return redirect()->route('inventario.equipo.index')->with('success', 'Equipo actualizado correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    public function toggleActivo($id)
    {
        $equipo = Equipo::findOrFail($id);
        $equipo->activo = !$equipo->activo;
        $equipo->save();
        return back()->with('success', $equipo->activo ? 'Equipo activado' : 'Equipo desactivado');
    }

    private function reglasValidacion(): array
    {
        return [
            'marca' => 'required|string|max:100',
            'modelo' => 'required|string|max:100',
            'id_TipoEquipo' => 'required|integer',
            'id_Estatus' => 'required|integer',
            'numeroSerie' => 'nullable|string|max:100',
            'nombreEquipo' => 'nullable|string|max:200',
            'ubicacion' => 'nullable|string|max:200',
            'observaciones' => 'nullable|string',
            'id_Oficinas' => 'nullable|integer',
            'esCompartido' => 'sometimes|boolean',
            'serieMotherboard' => 'nullable|string|max:150',
            'procesador' => 'nullable|string|max:150',
            'memoriaRam' => 'nullable|string|max:100',
            'almacenamiento' => 'nullable|string|max:150',
            'sistemaOperativo' => 'nullable|string|max:100',
            'ip' => 'nullable|string|max:45',
            'mac' => 'nullable|string|max:50',
            'estadoGarantia' => 'nullable|string|max:50',
            'fechaFinGarantia' => 'nullable|date',
            'especificaciones' => 'nullable|string',
        ];
    }
}
