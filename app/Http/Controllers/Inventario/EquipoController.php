<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Equipo;
use App\Models\EquipoCampo;
use App\Models\EquipoDetalle;
use App\Models\TipoEquipo;
use App\Models\TipoEquipoCampo;
use App\Models\Estatus;
use App\Models\Oficina;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EquipoController extends Controller
{
    public function index()
    {
        $equipos = Equipo::with(['tipoEquipo', 'estatus', 'oficina', 'equipoDetalle.campo'])->get();
        $tiposEquipo = TipoEquipo::where('activo', true)->get();
        $estatus = Estatus::where('activo', true)->where('id_Modulos', 2)->get();
        $oficinas = Oficina::where('activo', true)->get();
        $campos = EquipoCampo::where('activo', true)->get();

        return view('inventario.equipo', compact('equipos', 'tiposEquipo', 'estatus', 'oficinas', 'campos'));
    }

    public function store(Request $request)
    {
        $request->validate([
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
        ]);

        if ($request->numeroSerie) {
            $existe = Equipo::where('activo', true)
                ->whereRaw('LOWER(numeroSerie) = ?', strtolower($request->numeroSerie))
                ->first();
            if ($existe) {
                return back()->with('error', 'El número de serie ya existe');
            }
        }

        $requiredFields = TipoEquipoCampo::where('id_TipoEquipo', $request->id_TipoEquipo)
            ->where('requerido', true)->where('activo', true)->pluck('id_EquipoCampo')->toArray();

        if ($request->campos_dinamicos) {
            foreach ($requiredFields as $rf) {
                if (empty($request->campos_dinamicos[$rf])) {
                    $campo = EquipoCampo::find($rf);
                    return back()->with('error', "El campo '{$campo->nombre}' es requerido");
                }
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
                'esCompartido' => $request->has('esCompartido') ? 1 : 0,
                'fechaAlta' => now(),
                'activo' => true,
            ]);

            $this->guardarDetalles($equipo->id_Equipo, $request->campos_dinamicos ?? []);

            DB::commit();
            return redirect()->route('inventario.equipo.index')->with('success', 'Equipo creado correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al crear equipo: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $equipo = Equipo::with(['equipoDetalle.campo', 'tipoEquipo', 'estatus', 'oficina'])->findOrFail($id);
        $tiposEquipo = TipoEquipo::where('activo', true)->get();
        $estatus = Estatus::where('activo', true)->where('id_Modulos', 2)->get();
        $oficinas = Oficina::where('activo', true)->get();

        $tipoCampos = TipoEquipoCampo::with('campo')
            ->where('id_TipoEquipo', $equipo->id_TipoEquipo)
            ->where('activo', true)->get();

        return view('inventario.equipo_edit', compact('equipo', 'tiposEquipo', 'estatus', 'oficinas', 'tipoCampos'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'marca' => 'required|string|max:100',
            'modelo' => 'required|string|max:100',
            'id_TipoEquipo' => 'required|integer',
            'id_Estatus' => 'required|integer',
            'numeroSerie' => 'nullable|string|max:100',
        ]);

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
                'esCompartido' => $request->has('esCompartido') ? 1 : 0,
            ]);

            EquipoDetalle::where('id_Equipo', $id)->update(['activo' => false]);
            $this->guardarDetalles($id, $request->campos_dinamicos ?? []);

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

    public function campos($idTipo)
    {
        $campos = TipoEquipoCampo::with('campo')
            ->where('id_TipoEquipo', $idTipo)
            ->where('activo', true)->get();
        return response()->json($campos);
    }

    private function guardarDetalles($idEquipo, array $campos)
    {
        foreach ($campos as $idCampo => $valor) {
            if (!empty($valor)) {
                EquipoDetalle::updateOrCreate(
                    ['id_Equipo' => $idEquipo, 'id_EquipoCampo' => $idCampo],
                    ['valor' => $valor, 'activo' => true, 'fechaAlta' => now()]
                );
            }
        }
    }
}
