<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Models\TicketComentario;
use App\Models\Estatus;
use Carbon\Carbon;

class CerrarTicketsInactivos extends Command
{
    protected $signature = 'tickets:cerrar-inactivos {--dias=4 : Días sin comentarios para cerrar}';
    protected $description = 'Cierra automáticamente tickets inactivos, identificando de quién es la inactividad (usuario o técnico)';

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
        $tickets = Ticket::with('estatus')
            ->where('activo', true)
            ->where('id_Estatus', '!=', $estatusCerrado->id_Estatus)
            ->whereNotNull('id_Estatus')
            ->get();

        $this->info("Tickets activos (no cerrados): " . count($tickets));
        $this->newLine();

        $cerrados = 0;
        $porUsuario = 0;
        $porTecnico = 0;

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
                $responsable = $this->responsableInactividad($ticket);
                $etiqueta = $responsable === 'tecnico' ? 'técnico' : 'usuario';

                // Cerrar el ticket
                $ticket->update([
                    'id_Estatus' => $estatusCerrado->id_Estatus,
                    'fechaCierre' => now(),
                ]);

                // Registrar comentario automático
                TicketComentario::create([
                    'id_Ticket' => $ticket->id_Ticket,
                    'comentario' => "Ticket cerrado automáticamente por inactividad del {$etiqueta} ({$diasInactivo} días sin comentarios).",
                    'fechaComentario' => now(),
                    'id_Usuario' => 1, // Sistema
                    'id_Estatus' => $estatusCerrado->id_Estatus,
                    'fechaAlta' => now(),
                    'activo' => true,
                ]);

                $this->line("   ✅ Ticket #{$ticket->id_Ticket} - {$ticket->asunto}");
                $this->line("      Inactividad del {$etiqueta} — última actividad: {$fechaUltimaActividad->format('d/m/Y H:i')} ({$diasInactivo} días)");

                try {
                    app(\App\Services\TicketNotificacionService::class)
                        ->notificarCierreAutomatico($ticket, $responsable, $diasInactivo);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('No se notificó el cierre del Ticket #' . $ticket->id_Ticket . ': ' . $e->getMessage());
                }

                $cerrados++;
                if ($responsable === 'tecnico') {
                    $porTecnico++;
                } else {
                    $porUsuario++;
                }
            }
        }

        $this->newLine();
        $this->info("=== PROCESO COMPLETADO ===");
        $this->info("Tickets cerrados: {$cerrados} ({$porUsuario} por inactividad del usuario, {$porTecnico} por inactividad del técnico)");

        return 0;
    }

    /**
     * A la quién le toca el ticket cuando quedó inactivo:
     *  - Resuelto / Pendiente de usuario → usuario (espera su visto bueno o respuesta).
     *  - Último comentario del solicitante → técnico (el usuario ya habló).
     *  - Último comentario del soporte/admin o sin comentarios → usuario.
     * Los comentarios automáticos (gestión, cierres) no cuentan para atribuir.
     */
    private function responsableInactividad(Ticket $ticket): string
    {
        if (in_array($ticket->estatus?->descripcion, ['Resuelto', 'Pendiente de usuario'], true)) {
            return 'usuario';
        }

        $ultimoSustantivo = TicketComentario::where('id_Ticket', $ticket->id_Ticket)
            ->where('activo', true)
            ->orderByDesc('fechaComentario')
            ->get()
            ->first(fn ($c) => !$this->esComentarioAutomatico($c->comentario));

        if (!$ultimoSustantivo) {
            return 'usuario';
        }

        return ((int) $ultimoSustantivo->id_Usuario === (int) $ticket->id_Usuario)
            ? 'tecnico'
            : 'usuario';
    }

    private function esComentarioAutomatico(string $texto): bool
    {
        return (bool) preg_match('/^(gesti[oó]n actualizada|ticket cerrado)/i', trim($texto));
    }
}
