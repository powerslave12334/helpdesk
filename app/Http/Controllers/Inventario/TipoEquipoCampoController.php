<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\TipoEquipoCampo;
use App\Models\TipoEquipo;
use App\Models\EquipoCampo;
use Illuminate\Http\Request;

class TipoEquipoCampoController extends Controller
{
    public function index()
    {
        $relaciones = TipoEquipoCampo::with(['tipoEquipo', 'campo'])->get();
        $tiposEquipo = TipoEquipo::where('activo', true)->get();
        $campos = EquipoCampo::where('activo', true)->get();
        return view('inventario.tipo_equipo_campo', compact('relaciones', 'tiposEquipo', 'campos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_TipoEquipo' => 'required|integer',
            'id_EquipoCampo' => 'required|integer',
        ]);

        $existe = TipoEquipoCampo::where('id_TipoEquipo', $request->id_TipoEquipo)
            ->where('id_EquipoCampo', $request->id_EquipoCampo)->first();
        if ($existe) {
            return back()->with('error', 'Esta relación ya existe');
        }

        TipoEquipoCampo::create([
            'id_TipoEquipo' => $request->id_TipoEquipo,
            'id_EquipoCampo' => $request->id_EquipoCampo,
            'requerido' => $request->has('requerido'),
            'fechaAlta' => now(),
            'activo' => true,
        ]);
        return redirect()->route('inventario.tipoEquipoCampo.index')->with('success', 'Relación creada correctamente');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'id_TipoEquipo' => 'required|integer',
            'id_EquipoCampo' => 'required|integer',
        ]);

        $relacion = TipoEquipoCampo::findOrFail($id);
        $existe = TipoEquipoCampo::where('id_TipoEquipo', $request->id_TipoEquipo)
            ->where('id_EquipoCampo', $request->id_EquipoCampo)
            ->where('id_TipoEquipoCampo', '!=', $id)->first();
        if ($existe) {
            return back()->with('error', 'Esta relación ya existe');
        }

        $relacion->update([
            'id_TipoEquipo' => $request->id_TipoEquipo,
            'id_EquipoCampo' => $request->id_EquipoCampo,
            'requerido' => $request->has('requerido'),
        ]);
        return redirect()->route('inventario.tipoEquipoCampo.index')->with('success', 'Relación actualizada');
    }

    public function toggleActivo($id)
    {
        $relacion = TipoEquipoCampo::findOrFail($id);
        $relacion->activo = !$relacion->activo;
        $relacion->save();
        return back()->with('success', $relacion->activo ? 'Activado' : 'Desactivado');
    }
}
