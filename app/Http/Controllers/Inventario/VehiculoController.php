<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Vehiculo;
use Illuminate\Http\Request;

class VehiculoController extends Controller
{
    public function index()
    {
        $vehiculos = Vehiculo::orderBy('id_Vehiculo')->get();
        return view('inventario.vehiculo', compact('vehiculos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'numeroUnidad' => 'required|string|max:50|unique:tblVehiculo,numeroUnidad',
            'tipoVehiculo' => 'required|in:' . implode(',', Vehiculo::tipos()),
        ]);

        Vehiculo::create([
            'numeroUnidad' => $request->numeroUnidad,
            'tipoVehiculo' => $request->tipoVehiculo,
            'fechaAlta' => now(),
            'activo' => true,
        ]);

        return redirect()->route('inventario.vehiculo.index')->with('success', 'Vehículo creado correctamente');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'numeroUnidad' => 'required|string|max:50|unique:tblVehiculo,numeroUnidad,' . $id . ',id_Vehiculo',
            'tipoVehiculo' => 'required|in:' . implode(',', Vehiculo::tipos()),
        ]);

        Vehiculo::findOrFail($id)->update([
            'numeroUnidad' => $request->numeroUnidad,
            'tipoVehiculo' => $request->tipoVehiculo,
        ]);

        return redirect()->route('inventario.vehiculo.index')->with('success', 'Vehículo actualizado correctamente');
    }

    public function toggleActivo($id)
    {
        $vehiculo = Vehiculo::findOrFail($id);
        $vehiculo->activo = !$vehiculo->activo;
        $vehiculo->save();

        return back()->with('success', $vehiculo->activo ? 'Vehículo activado' : 'Vehículo desactivado');
    }
}
