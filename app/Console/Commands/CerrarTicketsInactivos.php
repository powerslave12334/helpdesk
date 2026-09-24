<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Ticket;
use App\Models\TicketComentario;
use App\Models\Estatus;
use Carbon\Carbon;

class CerrarTicketsInactivos extends Command
{
    protected $signature = 'tickets:cerrar-inactivos {--dias=3 : Días sin comentarios para cerrar}';
    protected $description = 'Cierra automáticamente tickets sin comentarios en los últimos N días';

    public function handle()
    {
        $dias = (int) $this->option('dias');
        $fechaLimite = Carbon::now()->subDays($dias);

        $this->line("=== CIERRE AUTOMÁTICO DE TICKETS INACTIVOS ===");
        $this->line("Buscando tickets sin comentarios desde: {$fechaLimite->format('d/m/Y H:i')}");
        $this->newLine();

        // Obtener estatus "Cerrado"
        $estatusCerrado = Estatus::where('descripcion', 'Cerrado')
            ->where('id_Modulos', 1)
            ->first();

        if (!$estatusCerrado) {
            $this->error('No se encontró el estatus "Cerrado" para tickets (id_Modulos=1)');
            return 1;
        }

        // Obtener tickets que NO están cerrados y están activos
        $tickets = Ticket::where('activo', true)
            ->where('id_Estatus', '!=', $estatusCerrado->id_Estatus)
            ->whereNotNull('id_Estatus')
            ->get();

        $this->info("Tickets activos (no cerrados): " . count($tickets));
        $this->newLine();

        $cerrados = 0;

        foreach ($tickets as $ticket) {
            // Obtener el último comentario del ticket
            $ultimoComentario = TicketComentario::where('id_Ticket', $ticket->id_Ticket)
                ->where('activo', true)
                ->orderBy('fechaComentario', 'desc')
                ->first();

            // Si no tiene comentarios, usar fecha de alta del ticket
            $fechaUltimaActividad = $ultimoComentario
                ? Carbon::parse($ultimoComentario->fechaComentario)
                : Carbon::parse($ticket->fechaAlta);

            // Si la última actividad es anterior a la fecha límite
            if ($fechaUltimaActividad->lt($fechaLimite)) {
                $diasInactivo = $fechaUltimaActividad->diffInDays(now());

                // Cerrar el ticket
                $ticket->update([
                    'id_Estatus' => $estatusCerrado->id_Estatus,
                    'fechaCierre' => now(),
                ]);

                // Registrar comentario automático
                TicketComentario::create([
                    'id_Ticket' => $ticket->id_Ticket,
                    'comentario' => "Ticket cerrado automáticamente por inactividad ({$diasInactivo} días sin comentarios).",
                    'fechaComentario' => now(),
                    'id_Usuario' => 1, // Sistema
                    'id_Estatus' => $estatusCerrado->id_Estatus,
                    'fechaAlta' => now(),
                    'activo' => true,
                ]);

                $this->line("   ✅ Ticket #{$ticket->id_Ticket} - {$ticket->asunto}");
                $this->line("      Última actividad: {$fechaUltimaActividad->format('d/m/Y H:i')} ({$diasInactivo} días)");

                $cerrados++;
            }
        }

        $this->newLine();
        $this->info("=== PROCESO COMPLETADO ===");
        $this->info("Tickets cerrados: {$cerrados}");

        return 0;
    }
}
