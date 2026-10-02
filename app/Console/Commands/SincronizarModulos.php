<?php

namespace App\Console\Commands;

use App\Support\Modulos;
use Illuminate\Console\Command;

class SincronizarModulos extends Command
{
    protected $signature = 'modulos:sync';
    protected $description = 'Refleja config/modulos.php en tblModulos y tblSubModulos (catálogo en código)';

    public function handle()
    {
        $this->line('=== SINCRONIZACIÓN DE CATÁLOGO DE MÓDULOS ===');

        $resumen = Modulos::sync();

        $this->line(sprintf(
            'Módulos: %d creados, %d actualizados, %d desactivados',
            $resumen['modulosCreados'],
            $resumen['modulosActualizados'],
            $resumen['modulosDesactivados']
        ));
        $this->line(sprintf(
            'Submódulos: %d creados, %d actualizados, %d eliminados',
            $resumen['submodulosCreados'],
            $resumen['submodulosActualizados'],
            $resumen['submodulosEliminados']
        ));

        return 0;
    }
}
