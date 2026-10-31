<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Equipo;
use App\Models\EquipoRelacion;
use App\Models\TipoRelacionEquipo;
use Illuminate\Http\Request;

class EquipoRelacionController extends Controller
{
    public function index()
    {
        $relaciones = EquipoRelacion::with(['equipoPadre', 'equipoHijo', 'tipoRelacion'])->get();
        $equipos = Equipo::where('activo', true)->get();
        $tiposRelacion = TipoRelacionEquipo::where('activo', true)->get();
        return view('inventario.relacion', compact('relaciones', 'equipos', 'tiposRelacion'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_EquipoPadre' => 'required|integer',
            'id_EquipoHijo' => 'required|integer',
            'id_TipoRelacionEquipo' => 'required|integer',
            'fechaAsignacion' => 'required|date',
        ]);

        if ($request->id_EquipoPadre == $request->id_EquipoHijo) {
            return back()->with('error', 'El equipo padre e hijo deben ser diferentes');
        }

        $existe = EquipoRelacion::where('id_EquipoPadre', $request->id_EquipoPadre)
            ->where('id_EquipoHijo', $request->id_EquipoHijo)
            ->where('id_TipoRelacionEquipo', $request->id_TipoRelacionEquipo)
            ->where('activo', true)->first();
        if ($existe) {
            return back()->with('error', 'Ya existe una relación activa entre estos equipos');
        }

        $hijoOcupado = EquipoRelacion::where('id_EquipoHijo', $request->id_EquipoHijo)
            ->where('id_TipoRelacionEquipo', $request->id_TipoRelacionEquipo)
            ->where('activo', true)->first();
        if ($hijoOcupado) {
            return back()->with('error', 'Este equipo hijo ya tiene una relación activa de este tipo');
        }

        EquipoRelacion::create([
            'id_EquipoPadre' => $request->id_EquipoPadre,
            'id_EquipoHijo' => $request->id_EquipoHijo,
            'id_TipoRelacionEquipo' => $request->id_TipoRelacionEquipo,
            'fechaAsignacion' => $request->fechaAsignacion,
            'observaciones' => $request->observaciones,
            'fechaAlta' => now(),
            'activo' => true,
        ]);
        return redirect()->route('inventario.relacion.index')->with('success', 'Relación creada correctamente');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'id_EquipoPadre' => 'required|integer',
            'id_EquipoHijo' => 'required|integer',
            'id_TipoRelacionEquipo' => 'required|integer',
            'fechaAsignacion' => 'required|date',
        ]);

        if ($request->id_EquipoPadre == $request->id_EquipoHijo) {
            return back()->with('error', 'El equipo padre e hijo deben ser diferentes');
        }

        $relacion = EquipoRelacion::findOrFail($id);
        $relacion->update([
            'id_EquipoPadre' => $request->id_EquipoPadre,
            'id_EquipoHijo' => $request->id_EquipoHijo,
            'id_TipoRelacionEquipo' => $request->id_TipoRelacionEquipo,
            'fechaAsignacion' => $request->fechaAsignacion,
            'observaciones' => $request->observaciones,
        ]);
        return redirect()->route('inventario.relacion.index')->with('success', 'Relación actualizada');
    }

    public function toggleActivo($id)
    {
        $relacion = EquipoRelacion::findOrFail($id);
        if ($relacion->activo && !$relacion->fechaBaja) {
            $relacion->fechaBaja = now()->toDateString();
        }
        $relacion->activo = !$relacion->activo;
        $relacion->save();
        return back()->with('success', $relacion->activo ? 'Relación activada' : 'Relación desactivada');
    }
}
