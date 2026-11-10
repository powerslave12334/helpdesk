<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Vehiculo;
use Illuminate\Http\Request;

class VehiculoController extends Controller
{
    public function index(Request $request)
    {
        $query = Vehiculo::where('activo', 1);

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numeroUnidad', 'like', "%{$search}%")
                    ->orWhere('marca', 'like', "%{$search}%")
                    ->orWhere('modelo', 'like', "%{$search}%")
                    ->orWhere('placa_Estatal', 'like', "%{$search}%")
                    ->orWhere('placa_Federal', 'like', "%{$search}%")
                    ->orWhere('placa_Puesta', 'like', "%{$search}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $vehiculos = $query->orderBy('numeroUnidad', 'asc')->paginate(15);

        return view('inventario.vehiculos', compact('vehiculos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'numeroUnidad' => 'required|string|max:50',
            'marca' => 'required|string|max:100',
            'modelo' => 'required|string|max:100',
            'estado' => 'nullable|string|max:100',
            'placa_Estatal' => 'nullable|string|max:50',
            'placa_Federal' => 'nullable|string|max:50',
            'placa_Puesta' => 'nullable|string|max:50',
        ]);

        Vehiculo::create(array_merge($request->all(), ['activo' => 1]));

        return response()->json(['message' => 'Vehículo registrado correctamente.']);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'numeroUnidad' => 'required|string|max:50',
            'marca' => 'required|string|max:100',
            'modelo' => 'required|string|max:100',
            'estado' => 'nullable|string|max:100',
            'placa_Estatal' => 'nullable|string|max:50',
            'placa_Federal' => 'nullable|string|max:50',
            'placa_Puesta' => 'nullable|string|max:50',
        ]);

        $vehiculo = Vehiculo::findOrFail($id);
        $vehiculo->update($request->all());

        return response()->json(['message' => 'Vehículo actualizado correctamente.']);
    }

    public function destroy($id)
    {
        $vehiculo = Vehiculo::findOrFail($id);
        $vehiculo->update(['activo' => 0]);

        return response()->json(['message' => 'Vehículo desactivado correctamente.']);
    }
}
