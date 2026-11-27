<?php

namespace App\Http\Controllers\Monitoreo;

use App\Http\Controllers\Controller;
use App\Models\Monitoreo;
use App\Services\NagiosService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonitoreoController extends Controller
{
    public function index(Request $request)
    {
        return view('monitoreo.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:150',
            'tipo' => 'required|string|in:servidor,servicio,sitio_web,equipo',
            'host_o_url' => 'required|string|max:255',
            'puerto' => 'nullable|integer|min:1|max:65535',
            'intervalo_minutos' => 'required|integer|min:1|max:1440',
        ], [], [
            'nombre' => 'Nombre',
            'tipo' => 'Tipo',
            'host_o_url' => 'Host o URL',
            'puerto' => 'Puerto',
            'intervalo_minutos' => 'Intervalo (minutos)',
        ]);

        DB::beginTransaction();
        try {
            Monitoreo::create([
                'nombre' => $request->nombre,
                'tipo' => $request->tipo,
                'host_o_url' => $request->host_o_url,
                'puerto' => $request->puerto,
                'intervalo_minutos' => $request->intervalo_minutos,
                'estatus' => 'desconocido',
                'tiempo_respuesta_ms' => null,
                'ultimo_check' => null,
                'mensaje_ultimo_check' => null,
                'activo' => true,
                'fechaAlta' => now(),
            ]);
            DB::commit();

            return back()->with('success', 'Objeto de monitoreo creado correctamente');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error al crear objeto de monitoreo: '.$e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $monitoreo = Monitoreo::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:150',
            'tipo' => 'required|string|in:servidor,servicio,sitio_web,equipo',
            'host_o_url' => 'required|string|max:255',
            'puerto' => 'nullable|integer|min:1|max:65535',
            'intervalo_minutos' => 'required|integer|min:1|max:1440',
        ], [], [
            'nombre' => 'Nombre',
            'tipo' => 'Tipo',
            'host_o_url' => 'Host o URL',
            'puerto' => 'Puerto',
            'intervalo_minutos' => 'Intervalo (minutos)',
        ]);

        DB::beginTransaction();
        try {
            $monitoreo->update([
                'nombre' => $request->nombre,
                'tipo' => $request->tipo,
                'host_o_url' => $request->host_o_url,
                'puerto' => $request->puerto,
                'intervalo_minutos' => $request->intervalo_minutos,
            ]);
            DB::commit();

            return back()->with('success', 'Objeto de monitoreo actualizado correctamente');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error al actualizar objeto de monitoreo: '.$e->getMessage());
        }
    }

    public function destroy($id)
    {
        $monitoreo = Monitoreo::findOrFail($id);

        try {
            $monitoreo->delete();

            return back()->with('success', 'Objeto de monitoreo eliminado correctamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar objeto de monitoreo: '.$e->getMessage());
        }
    }

    public function toggle($id)
    {
        $monitoreo = Monitoreo::findOrFail($id);
        $monitoreo->activo = ! $monitoreo->activo;
        $monitoreo->save();

        return back()->with('success', $monitoreo->activo ? 'Monitoreo activado' : 'Monitoreo desactivado');
    }

    /**
     * Sincroniza el estado de Nagios Core con tblMonitoreo.
     */
    public function sincronizarNagios(NagiosService $nagios)
    {
        $resultado = $nagios->obtenerEstado();

        if (! $resultado['success']) {
            return back()->with('error', 'Error al conectar con Nagios: '.$resultado['error']);
        }

        $servicios = $nagios->normalizarServicios($resultado['data']);

        $sincronizados = 0;
        $errores = 0;

        DB::beginTransaction();
        try {
            foreach ($servicios as $svc) {
                $monitoreo = Monitoreo::where('nagios_host_name', $svc['host'])
                    ->where('nagios_service_description', $svc['service'])
                    ->first();

                $estadoMapeado = $nagios->mapearEstado($svc['estado']);
                $ultimoCheck = $svc['last_check'] ?? now();

                $datosMetricas = [
                    'cpu_porcentaje' => $svc['cpu_porcentaje'] ?? null,
                    'memoria_porcentaje' => $svc['memoria_porcentaje'] ?? null,
                    'memoria_usada_gb' => $svc['memoria_usada_gb'] ?? null,
                    'memoria_total_gb' => $svc['memoria_total_gb'] ?? null,
                    'memoria_libre_gb' => $svc['memoria_libre_gb'] ?? null,
                    'disco_porcentaje' => $svc['disco_porcentaje'] ?? null,
                    'disco_usado_gb' => $svc['disco_usado_gb'] ?? null,
                    'disco_total_gb' => $svc['disco_total_gb'] ?? null,
                    'disco_libre_gb' => $svc['disco_libre_gb'] ?? null,
                ];

                if ($monitoreo) {
                    $monitoreo->update(array_merge([
                        'estatus' => $estadoMapeado,
                        'tiempo_respuesta_ms' => $svc['latency'],
                        'mensaje_ultimo_check' => $svc['output'],
                        'ultimo_check' => $ultimoCheck,
                        'ultimo_sincronizado_nagios' => now(),
                    ], $datosMetricas));
                } else {
                    Monitoreo::create(array_merge([
                        'nombre' => $svc['host'].' - '.$svc['service'],
                        'tipo' => 'servicio',
                        'host_o_url' => $svc['host'],
                        'puerto' => null,
                        'intervalo_minutos' => 5,
                        'estatus' => $estadoMapeado,
                        'tiempo_respuesta_ms' => $svc['latency'],
                        'ultimo_check' => $ultimoCheck,
                        'mensaje_ultimo_check' => $svc['output'],
                        'activo' => true,
                        'fechaAlta' => now(),
                        'nagios_host_name' => $svc['host'],
                        'nagios_service_description' => $svc['service'],
                        'ultimo_sincronizado_nagios' => now(),
                    ], $datosMetricas));
                }

                $sincronizados++;
            }

            DB::commit();

            return back()->with('success', 'Sincronización con Nagios completada: '.$sincronizados.' servicios actualizados.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error al sincronizar con Nagios: '.$e->getMessage());
        }
    }
}
