<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\InstalacionGPS;
use Illuminate\Http\Request;

class InstalacionGPSController extends Controller
{
    public function index()
    {
        $instalaciones = InstalacionGPS::orderBy('id_InstalacionGPS')->get();

        return view('inventario.instalacion_gps', compact('instalaciones'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'unidad' => 'required|string|max:50',
            'marca' => 'required|string|max:100',
            'placas' => 'required|string|max:20',
            'placas_federal' => 'nullable|string|max:20',
            'fecha_cambio_sim' => 'nullable|date',
            'sim_cambiado' => 'required|boolean',
            'reinstalacion' => 'required|boolean',
            'fecha_reinstalacion' => 'nullable|date',
            'marca_gps' => 'nullable|string|max:100',
            'modelo_gps' => 'nullable|string|max:100',
            'imei' => 'nullable|string|max:20',
            'numero_anterior' => 'nullable|string|max:20',
            'numero_actual' => 'required|string|max:20',
        ]);

        InstalacionGPS::create([
            'unidad' => $request->unidad,
            'marca' => $request->marca,
            'placas' => $request->placas,
            'placas_federal' => $request->placas_federal,
            'fecha_cambio_sim' => $request->fecha_cambio_sim,
            'sim_cambiado' => $request->sim_cambiado,
            'reinstalacion' => $request->reinstalacion,
            'fecha_reinstalacion' => $request->filled('fecha_reinstalacion') ? $request->fecha_reinstalacion : null,
            'marca_gps' => $request->marca_gps,
            'modelo_gps' => $request->modelo_gps,
            'imei' => $request->imei,
            'numero_anterior' => $request->numero_anterior,
            'numero_actual' => $request->numero_actual,
            'fechaAlta' => now(),
            'activo' => true,
        ]);

        return redirect()->route('inventario.instalacionGps.index')->with('success', 'Instalación GPS creada correctamente');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'unidad' => 'required|string|max:50',
            'marca' => 'required|string|max:100',
            'placas' => 'required|string|max:20',
            'placas_federal' => 'nullable|string|max:20',
            'fecha_cambio_sim' => 'nullable|date',
            'sim_cambiado' => 'required|boolean',
            'reinstalacion' => 'required|boolean',
            'fecha_reinstalacion' => 'nullable|date',
            'marca_gps' => 'nullable|string|max:100',
            'modelo_gps' => 'nullable|string|max:100',
            'imei' => 'nullable|string|max:20',
            'numero_anterior' => 'nullable|string|max:20',
            'numero_actual' => 'required|string|max:20',
        ]);

        $instalacion = InstalacionGPS::findOrFail($id);
        $instalacion->unidad = $request->unidad;
        $instalacion->marca = $request->marca;
        $instalacion->placas = $request->placas;
        $instalacion->placas_federal = $request->placas_federal;
        $instalacion->fecha_cambio_sim = $request->fecha_cambio_sim;
        $instalacion->sim_cambiado = $request->sim_cambiado;
        $instalacion->reinstalacion = $request->reinstalacion;
        $instalacion->fecha_reinstalacion = $request->filled('fecha_reinstalacion') ? $request->fecha_reinstalacion : null;
        $instalacion->marca_gps = $request->marca_gps;
        $instalacion->modelo_gps = $request->modelo_gps;
        $instalacion->imei = $request->imei;
        $instalacion->numero_anterior = $request->numero_anterior;
        $instalacion->numero_actual = $request->numero_actual;
        $instalacion->save();

        return redirect()->route('inventario.instalacionGps.index')->with('success', 'Instalación GPS actualizada correctamente');
    }

    public function toggleActivo($id)
    {
        $instalacion = InstalacionGPS::findOrFail($id);
        $instalacion->activo = ! $instalacion->activo;
        $instalacion->save();

        return back()->with('success', $instalacion->activo ? 'Instalación activada' : 'Instalación desactivada');
    }

    public function destroy($id)
    {
        $instalacion = InstalacionGPS::findOrFail($id);
        $instalacion->delete();

        return redirect()->route('inventario.instalacionGps.index')->with('success', 'Instalación GPS eliminada correctamente');
    }
}
