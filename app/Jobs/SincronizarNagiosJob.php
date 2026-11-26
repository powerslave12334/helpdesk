<?php

namespace App\Jobs;

use App\Models\Monitoreo;
use App\Services\NagiosService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SincronizarNagiosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = 60;

    public function handle(): void
    {
        $nagios = app(NagiosService::class);
        $resultado = $nagios->obtenerEstado();

        if (! $resultado['success']) {
            Log::error('SincronizarNagiosJob falló: '.$resultado['error']);

            return;
        }

        $data = $resultado['data'];
        $servicios = $nagios->normalizarServicios($data);

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
                    // Si no existe en tblMonitoreo, crear un registro básico vinculado a Nagios
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

            Log::info('SincronizarNagiosJob completado: '.$sincronizados.' servicios sincronizados.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SincronizarNagiosJob error: '.$e->getMessage());

            throw $e;
        }
    }
}
