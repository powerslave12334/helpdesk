<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProgramadorCierreDiario
{
    public const HORA = '06:00';

    /**
     * Ejecuta el cierre de tickets inactivos si ya pasó la hora programada
     * y aún no se completa hoy. Diseñado para correr desde una petición web
     * (no requiere cron del sistema operativo).
     */
    public function ejecutarSiCorresponde(): bool
    {
        if (now()->format('H:i') < self::HORA) {
            return false;
        }

        $hecho = 'helpdesk:cierre_diario:hecho:' . now()->toDateString();
        $candado = 'helpdesk:cierre_diario:lock';

        if (Cache::get($hecho)) {
            return false;
        }
        // Candado corto: evita ejecuciones concurrentes. Si el proceso muere
        // a mitad (fatal), expira y el siguiente reintenta el mismo día.
        if (!Cache::add($candado, 1, 10)) {
            return false;
        }

        try {
            // El cierre puede tardar varios minutos en la primera ejecución
            // (varios tickets + correos SMTP). No debe morir a los 30 s ni
            // abortar si el navegador se desconecta.
            @set_time_limit(300);
            ignore_user_abort(true);

            $codigo = Artisan::call('tickets:cerrar-inactivos', ['--dias' => 4]);
            $salida = Artisan::output();
            Log::info("Cierre automático diario (código {$codigo}): {$salida}");

            if ($codigo === 0) {
                // Solo queda marcado como hecho si completó sin errores,
                // para que un run interrumpido se reintente más adelante.
                Cache::put($hecho, 1, now()->addDay());
            }

            return $codigo === 0;
        } catch (\Throwable $e) {
            Log::warning('Falló el cierre automático diario: ' . $e->getMessage());

            return false;
        } finally {
            Cache::forget($candado);
        }
    }
}
