<?php

namespace App\Services;

use App\Models\Notificacion;
use App\Models\Ticket;
use App\Models\Usuario;
use Illuminate\Support\Facades\Log;

class TicketNotificacionService
{
    public function __construct(private CorreoService $correoService) {}

    /**
     * Notifica al técnico asignado por campana in-app y correo.
     * Nunca lanza excepciones: cualquier fallo se registra en log
     * para no interrumpir la creación/gestión del ticket.
     *
     * @param  string  $motivo  'creado' | 'reasignado'
     * @param  array<string, string>  $datosExtra  datos del form no persistidos (sistema, módulo, tipo desarrollo)
     */
    public function notificar(Ticket $ticket, Usuario $tecnico, string $motivo, array $datosExtra = []): void
    {
        $creado = $motivo === 'creado';
        // Tipo en snake_case minúscula, consistente con el vocabulario legado
        // de tblNotificaciones (ticket_asignado, comentario).
        $tipo = $creado ? 'ticket_asignado' : 'ticket_reasignado';
        $asunto = 'Ticket #'.$ticket->id_Ticket.($creado ? ' creado: ' : ' reasignado: ').$ticket->asunto;

        try {
            $this->crearNotificacionApp($ticket, $tecnico, $tipo, $asunto);
        } catch (\Throwable $e) {
            Log::warning('No se pudo crear la notificación in-app del '.$asunto.': '.$e->getMessage());
        }

        try {
            if (empty($tecnico->correo)) {
                return;
            }
            $resultado = $this->correoService->enviarCorreoAutomatica(
                $tecnico->correo,
                'Help Desk - '.($creado ? 'Nuevo ticket asignado' : 'Ticket reasignado').' #'.$ticket->id_Ticket,
                $this->htmlCorreo($ticket, $tecnico, $motivo, $datosExtra)
            );
            if (! $resultado['ok']) {
                Log::warning('No se envió el correo del '.$asunto.': '.$resultado['mensaje']);
            }
        } catch (\Throwable $e) {
            Log::warning('Error al enviar el correo del '.$asunto.': '.$e->getMessage());
        }
    }

    private function crearNotificacionApp(Ticket $ticket, Usuario $tecnico, string $tipo, string $mensaje): void
    {
        Notificacion::create([
            'id_UsuarioDestino' => $tecnico->id_Usuario,
            'Mensaje' => mb_substr($mensaje, 0, 500),
            'Tipo' => $tipo,
            'UrlAccion' => route('tickets.index'),
            'Leido' => false,
            'FechaCreacion' => now(),
        ]);
    }

    /**
     * Notifica el cierre automático por inactividad: campana in-app al
     * solicitante y al técnico. No envía correo.
     * Nunca lanza excepciones.
     *
     * @param  string  $responsable  'usuario' | 'tecnico' de quién fue la inactividad
     */
    public function notificarCierreAutomatico(Ticket $ticket, string $responsable, int $dias): void
    {
        $ticket->loadMissing(['usuario', 'tecnico']);
        $etiqueta = $responsable === 'tecnico' ? 'técnico' : 'usuario';

        $destinatarios = [];
        foreach ([$ticket->usuario, $ticket->tecnico] as $destinatario) {
            if ($destinatario && ! isset($destinatarios[$destinatario->id_Usuario])) {
                $destinatarios[$destinatario->id_Usuario] = $destinatario;
            }
        }

        $mensaje = "Ticket #{$ticket->id_Ticket} cerrado automáticamente por inactividad del {$etiqueta} ({$dias} días sin comentarios).";

        foreach ($destinatarios as $destinatario) {
            try {
                Notificacion::create([
                    'id_UsuarioDestino' => $destinatario->id_Usuario,
                    'Mensaje' => mb_substr($mensaje, 0, 500),
                    'Tipo' => 'ticket_cerrado',
                    'UrlAccion' => route('tickets.show', $ticket->id_Ticket),
                    'Leido' => false,
                    'FechaCreacion' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('No se pudo crear la notificación in-app del cierre del Ticket #'.$ticket->id_Ticket.': '.$e->getMessage());
            }
        }
    }

    private function htmlCorreo(Ticket $ticket, Usuario $tecnico, string $motivo, array $datosExtra): string
    {
        $ticket->loadMissing(['usuario', 'tecnico', 'tipoSoporte.area', 'equipo']);

        $creado = $motivo === 'creado';
        $nombreTecnico = $tecnico->nombreCompleto();
        $titular = $creado
            ? 'Se te ha asignado el siguiente ticket:'
            : 'El siguiente ticket ha sido reasignado a ti:';

        $filas = [
            'Asunto' => $ticket->asunto,
            'Tipo de ticket' => $ticket->etiquetaTipoTicket(),
            'Tipo de soporte' => trim(
                ($ticket->tipoSoporte?->descripcion ?? '')
                .($ticket->tipoSoporte?->area && $ticket->tipoSoporte->area->nombre !== $ticket->tipoSoporte->descripcion
                    ? ' ('.$ticket->tipoSoporte->area->nombre.')'
                    : '')
            ),
            'Solicitante' => $ticket->usuario?->nombreCompleto() ?? '—',
            'Equipo' => $ticket->equipo
                ? (implode(' - ', array_filter([
                    trim($ticket->equipo->nombreEquipo ?? ''),
                    trim(($ticket->equipo->marca ?? '').' '.($ticket->equipo->modelo ?? '')),
                ])) ?: 'Sin equipo')
                : 'Sin equipo',
            'Unidades GPS' => $ticket->vehiculosDelTicket()->isEmpty()
                ? null
                : $ticket->vehiculosDelTicket()
                    ->map(fn ($v) => $v->numeroUnidad.' ('.$v->tipoVehiculo.')')
                    ->implode(', '),
            'Problema GPS' => $ticket->problemaGps ?: null,
            'Fecha de alta' => $ticket->fechaAlta?->format('d/m/Y H:i') ?? '—',
            'Estatus' => $ticket->estatus?->descripcion ?? '—',
            'Técnico asignado' => $nombreTecnico,
        ];

        $filas = array_filter($filas, fn ($valor) => $valor !== null && $valor !== '');

        foreach ($datosExtra as $etiqueta => $valor) {
            if ($valor !== null && $valor !== '') {
                $filas[$etiqueta] = $valor;
            }
        }

        return CorreoHtml::plantilla([
            'etiqueta' => $creado ? 'Ticket asignado' : 'Ticket reasignado',
            'folio' => '#'.$ticket->id_Ticket,
            'saludo' => $nombreTecnico,
            'intro' => e($titular),
            'filas' => $filas,
            'bloqueTitulo' => 'Problema reportado',
            'bloque' => nl2br(e($ticket->problema)),
            'cta' => [
                'texto' => 'Ver ticket',
                'url' => route('tickets.show', $ticket->id_Ticket),
            ],
            'pie' => 'Mensaje automático de Help Desk. Responde desde el sistema para que quede registrado.',
            'firma' => $this->correoService->firma(),
        ]);
    }
}
