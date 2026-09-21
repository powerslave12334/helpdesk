<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\EquipoDetalle;
use App\Models\EquipoCampoDefinicion;
use App\Models\TipoEquipo;
use App\Models\TipoEquipoCampo;
use App\Models\Estatus;
use App\Models\Oficina;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventarioController extends Controller
{
    public function index()
    {
        $equipos = Equipo::with(['tipoEquipo', 'estatus', 'oficina', 'equipoDetalle.campo'])->get();
        $tiposEquipo = TipoEquipo::where('activo', true)->get();
        $estatus = Estatus::where('activo', true)->where('id_Modulos', 2)->get();
        $oficinas = Oficina::where('activo', true)->get();
        $campos = EquipoCampoDefinicion::where('activo', true)->get();

        return view('inventario.index', compact('equipos', 'tiposEquipo', 'estatus', 'oficinas', 'campos'));
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
        ]);

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
            $equipo = new Equipo();
            $equipo->marca = $request->marca;
            $equipo->modelo = $request->modelo;
            $equipo->id_TipoEquipo = $request->id_TipoEquipo;
            $equipo->id_Estatus = $request->id_Estatus;
            $equipo->numeroSerie = $request->numeroSerie;
            $equipo->nombreEquipo = $request->nombreEquipo;
            $equipo->ubicacion = $request->ubicacion;
            $equipo->observaciones = $request->observaciones;
            $equipo->id_Oficinas = $request->id_Oficinas;
            $equipo->fechaAlta = now();
            $equipo->activo = true;
            $equipo->save();

            if ($request->campos_dinamicos) {
                foreach ($request->campos_dinamicos as $idCampo => $valor) {
                    if (!empty($valor)) {
                        EquipoDetalle::create([
                            'id_Equipo' => $equipo->id_Equipo,
                            'id_EquipoCampo' => $idCampo,
                            'valor' => $valor,
                            'fechaAlta' => now(),
                            'activo' => true,
                        ]);
                    }
                }
            }

            DB::commit();
            return redirect()->route('inventario.index')->with('success', 'Equipo creado correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al crear equipo: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
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
            ]);

            EquipoDetalle::where('id_Equipo', $id)->update(['activo' => false]);

            if ($request->campos_dinamicos) {
                foreach ($request->campos_dinamicos as $idCampo => $valor) {
                    if (!empty($valor)) {
                        EquipoDetalle::updateOrCreate(
                            ['id_Equipo' => $id, 'id_EquipoCampo' => $idCampo],
                            ['valor' => $valor, 'activo' => true, 'fechaAlta' => now()]
                        );
                    }
                }
            }

            DB::commit();
            return redirect()->route('inventario.index')->with('success', 'Equipo actualizado correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar equipo: ' . $e->getMessage());
        }
    }

    public function toggleActivo($id)
    {
        $equipo = Equipo::findOrFail($id);
        $equipo->activo = !$equipo->activo;
        $equipo->save();
        return back()->with('success', $equipo->activo ? 'Equipo activado' : 'Equipo desactivado');
    }

    public function obtenerCampos($idTipo)
    {
        $campos = TipoEquipoCampo::with('campo')
            ->where('id_TipoEquipo', $idTipo)
            ->where('activo', true)
            ->get();
        return response()->json($campos);
    }
}
