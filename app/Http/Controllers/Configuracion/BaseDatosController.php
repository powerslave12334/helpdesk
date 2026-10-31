<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Support\ConexionActiva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BaseDatosController extends Controller
{
    /**
     * Conmuta la base de datos activa. Primero valida que la conexión destino
     * responda; si falla, no se guarda el cambio.
     */
    public function cambiar(Request $request)
    {
        $request->validate([
            'conexion' => 'required|in:' . implode(',', array_keys(ConexionActiva::OPCIONES)),
        ]);

        $nueva = $request->input('conexion');

        if ($nueva === ConexionActiva::actual()) {
            return back()->with('success', 'La conexión seleccionada ya está activa.');
        }

        $error = $this->probarConexion($nueva);
        if ($error) {
            return back()->with('error', 'No se pudo conectar con ' . ConexionActiva::etiqueta($nueva) . ': ' . $error);
        }

        ConexionActiva::guardar($nueva);

        return redirect()
            ->route('configuracion.configGeneral.index')
            ->with('success', 'Base de datos cambiada a: ' . ConexionActiva::etiqueta($nueva) . '. El cambio aplica en la siguiente petición.');
    }

    /**
     * Prueba la conexión seleccionada sin conmutar.
     */
    public function probar(Request $request)
    {
        $request->validate([
            'conexion' => 'required|in:' . implode(',', array_keys(ConexionActiva::OPCIONES)),
        ]);

        $nueva = $request->input('conexion');
        $error = $this->probarConexion($nueva);

        if ($error) {
            return back()->with('error', 'Fallo de conexión con ' . ConexionActiva::etiqueta($nueva) . ': ' . $error);
        }

        return back()->with('success', 'Conexión exitosa con ' . ConexionActiva::etiqueta($nueva) . '.');
    }

    private function probarConexion(string $conexion): ?string
    {
        try {
            DB::connection($conexion)->getPdo();

            return null;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }
}
