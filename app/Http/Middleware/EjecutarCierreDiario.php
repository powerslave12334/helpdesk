<?php

namespace App\Http\Middleware;

use App\Services\ProgramadorCierreDiario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EjecutarCierreDiario
{
    /**
     * Revisa en cada petición web si ya tocó el cierre automático
     * de tickets inactivos (6:00 AM) y aún no se ejecuta hoy.
     * Costo cuando ya corrió: un Cache::get.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!app()->environment('testing')) {
            try {
                app(ProgramadorCierreDiario::class)->ejecutarSiCorresponde();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('No se pudo revisar el cierre diario: ' . $e->getMessage());
            }
        }

        return $next($request);
    }
}
