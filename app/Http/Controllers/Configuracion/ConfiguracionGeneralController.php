<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionSistema;
use Illuminate\Http\Request;

class ConfiguracionGeneralController extends Controller
{
    public function index()
    {
        $configuraciones = ConfiguracionSistema::all();
        return view('configuracion.configuracion_general', compact('configuraciones'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'clave' => 'required|string|max:100',
            'valor' => 'required|string',
            'descripcion' => 'nullable|string|max:250',
        ]);

        $existe = ConfiguracionSistema::whereRaw('LOWER(clave) = ?', strtolower($request->clave))->first();
        if ($existe) {
            return back()->with('error', 'Esta clave ya existe');
        }

        ConfiguracionSistema::create([
            'clave' => $request->clave,
            'valor' => $request->valor,
            'descripcion' => $request->descripcion,
            'fechaAlta' => now(),
            'activo' => true,
        ]);
        return redirect()->route('configuracion.configGeneral.index')->with('success', 'Configuración creada');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'clave' => 'required|string|max:100',
            'valor' => 'required|string',
            'descripcion' => 'nullable|string|max:250',
        ]);

        $existe = ConfiguracionSistema::whereRaw('LOWER(clave) = ?', strtolower($request->clave))
            ->where('id_ConfiguracionSistema', '!=', $id)->first();
        if ($existe) {
            return back()->with('error', 'Esta clave ya existe');
        }

        ConfiguracionSistema::findOrFail($id)->update([
            'clave' => $request->clave,
            'valor' => $request->valor,
            'descripcion' => $request->descripcion,
        ]);
        return redirect()->route('configuracion.configGeneral.index')->with('success', 'Configuración actualizada');
    }

    public function toggleActivo($id)
    {
        $config = ConfiguracionSistema::findOrFail($id);
        $config->activo = !$config->activo;
        $config->save();
        return back()->with('success', $config->activo ? 'Activado' : 'Desactivado');
    }
}
