<?php

namespace App\Providers;

use App\Support\ConexionActiva;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Conmutador de BD del módulo de configuración: solo aplica en peticiones
        // web; en consola se conserva la conexión de .env para que comandos como
        // migrate no operen contra productivo por accidente.
        if (!$this->app->runningInConsole()) {
            $activa = ConexionActiva::actual();
            if ($activa !== config('database.default')) {
                config(['database.default' => $activa]);
            }
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
