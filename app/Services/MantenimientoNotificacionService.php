<?php

namespace App\Services;

use App\Models\EquipoUsuario;
use App\Models\Mantenimiento;
use App\Models\Notificacion;
use App\Models\Usuario;
use Illuminate\Support\Facades\Log;

class MantenimientoNotificacionService
{
    public function __construct(private CorreoService $correoService)
    {
    }

    /**
     * Notifica al usuario dueño del equipo por campana in-app y correo.
     * Nunca lanza excepciones: cualquier fallo se registra en log
     * para no interrumpir la creación del mantenimiento.
     */
    public function notificarUsuarioEquipo(Mantenimiento $mantenimiento): void
    {
        $mantenimiento->loadMissing(['equipo', 'tecnico', 'estatus']);

        $asignacion = EquipoUsuario::where('id_Equipo', $mantenimiento->id_Equipo)
            ->where('activo', true)
            ->with('usuario')
            ->first();

        $usuario = $asignacion?->usuario;
        if (!$usuario) {
            Log::info("No se notificó el mantenimiento #{$mantenimiento->id_Mantenimiento}: el equipo {$mantenimiento->id_Equipo} no tiene usuario asignado activo.");
            return;
        }

        $mensaje = "Mantenimiento {$mantenimiento->tipoMantenimiento} programado para el equipo {$this->nombreEquipo($mantenimiento)} el {$mantenimiento->fechaProgramada->format('d/m/Y H:i')}.";

        try {
            Notificacion::create([
                'id_UsuarioDestino' => $usuario->id_Usuario,
                'Mensaje' => mb_substr($mensaje, 0, 500),
                'Tipo' => 'mantenimiento_programado',
                'UrlAccion' => route('inventario.mantenimientos.index'),
                'Leido' => false,
                'FechaCreacion' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('No se pudo crear la notificación in-app del mantenimiento #' . $mantenimiento->id_Mantenimiento . ': ' . $e->getMessage());
        }

        try {
            if (empty($usuario->correo)) {
                return;
            }
            $resultado = $this->correoService->enviarCorreo(
                $usuario->correo,
                'Help Desk - Mantenimiento programado #' . $mantenimiento->id_Mantenimiento,
                $this->htmlCorreo($mantenimiento, $usuario)
            );
            if (!$resultado['ok']) {
                Log::warning('No se envió el correo del mantenimiento #' . $mantenimiento->id_Mantenimiento . ': ' . $resultado['mensaje']);
            }
        } catch (\Throwable $e) {
            Log::warning('Error al enviar el correo del mantenimiento #' . $mantenimiento->id_Mantenimiento . ': ' . $e->getMessage());
        }
    }

    private function htmlCorreo(Mantenimiento $mantenimiento, Usuario $usuario): string
    {
        $fecha = $mantenimiento->fechaProgramada instanceof \Carbon\Carbon
            ? $mantenimiento->fechaProgramada->format('d/m/Y H:i')
            : $mantenimiento->fechaProgramada;
        $tipo = mb_strtolower($mantenimiento->tipoMantenimiento);

        $intro = 'Como parte del programa de mantenimiento ' . e($tipo) . ' de la empresa, te confirmo que la revisión de tu equipo está agendada para el día <strong>' . e($fecha) . '</strong>.';

        $bloque = '
            <p style="margin:0 0 8px;"><strong>Detalles de la atención:</strong></p>
            <ul style="margin:0 0 16px; padding-left:18px; line-height:1.6;">
                <li>Duración estimada: 2 horas.</li>
                <li>Actividades a realizar: Limpieza física interna y externa del equipo, revisión de componentes, optimización del sistema operativo y actualizaciones de seguridad.</li>
            </ul>
            <p style="margin:0 0 8px;"><strong>Para agilizar el servicio, te pido apoyarme con los siguientes puntos antes de la hora acordada:</strong></p>
            <ul style="margin:0; padding-left:18px; line-height:1.6;">
                <li>Asegúrate de guardar tus documentos de trabajo en el servidor o nube corporativa de la empresa.</li>
                <li>Cierra los programas y aplicaciones que estés utilizando.</li>
                <li>Ten despejada tu área de trabajo para facilitar el acceso al equipo.</li>
            </ul>';

        return CorreoHtml::plantilla([
            'etiqueta' => 'Mantenimiento programado',
            'folio' => '#' . $mantenimiento->id_Mantenimiento,
            'saludoPrefijo' => 'Estimado/a',
            'saludo' => $usuario->nombreCompleto(),
            'intro' => $intro,
            'bloque' => $bloque,
            'cta' => [
                'texto' => 'Ver calendario',
                'url' => route('inventario.mantenimientos.index'),
            ],
            'pie' => 'Quedo atento en caso de que requieras ajustar la fecha u hora por cuestiones de agenda.<br>Saludos cordiales',
            'firma' => $this->correoService->firma(),
        ]);
    }

    private function nombreEquipo(Mantenimiento $mantenimiento): string
    {
        $equipo = $mantenimiento->equipo;
        if (!$equipo) {
            return 'Equipo #' . $mantenimiento->id_Equipo;
        }

        $nombre = trim(($equipo->nombreEquipo ?? '') . ' ' . ($equipo->marca ?? '') . ' ' . ($equipo->modelo ?? ''));

        return $nombre !== '' ? $nombre : 'Equipo #' . $mantenimiento->id_Equipo;
    }
}
