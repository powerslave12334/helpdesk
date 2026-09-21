<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CorreoService
{
    public function enviarCorreo(string $destinatario, string $asunto, string $htmlCuerpo): array
    {
        if (empty($destinatario)) {
            return ['ok' => false, 'mensaje' => 'No se recibió destinatario.'];
        }

        $config = $this->obtenerConfiguracion();
        if (!$config['activo']) {
            return ['ok' => false, 'mensaje' => 'El envío de correos está desactivado.'];
        }

        if (empty($config['servidor']) || empty($config['remitente']) || empty($config['usuario']) || empty($config['password'])) {
            return ['ok' => false, 'mensaje' => 'La configuración de correo está incompleta.'];
        }

        try {
            $destinatarios = $this->separarDestinatarios($destinatario);
            if (empty($destinatarios)) {
                return ['ok' => false, 'mensaje' => 'No se encontraron destinatarios válidos.'];
            }

            config([
                'mail.mailers.smtp.host' => $config['servidor'],
                'mail.mailers.smtp.port' => $config['puerto'],
                'mail.mailers.smtp.username' => $config['usuario'],
                'mail.mailers.smtp.password' => $config['password'],
                'mail.mailers.smtp.encryption' => 'tls',
                'mail.from.address' => $config['remitente'],
                'mail.from.name' => $config['nombreRemitente'],
            ]);

            Mail::raw($htmlCuerpo, function ($message) use ($destinatarios, $asunto) {
                $message->subject($asunto);
                foreach ($destinatarios as $dest) {
                    $message->to($dest);
                }
            });

            return ['ok' => true, 'mensaje' => 'Correo enviado correctamente.'];
        } catch (\Exception $e) {
            return ['ok' => false, 'mensaje' => $e->getMessage()];
        }
    }

    private function obtenerConfiguracion(): array
    {
        $claves = [
            'CorreoNotificacionesActivo',
            'CorreoServidorSmtp',
            'CorreoPuertoSmtp',
            'CorreoRemitente',
            'CorreoUsuario',
            'CorreoPassword',
            'CorreoNombreRemitente',
        ];

        $registros = DB::table('tblConfiguracionSistema')
            ->where('activo', true)
            ->whereIn('clave', $claves)
            ->pluck('valor', 'clave')
            ->toArray();

        return [
            'activo' => $this->obtenerBool($registros, 'CorreoNotificacionesActivo', false),
            'servidor' => $registros['CorreoServidorSmtp'] ?? '',
            'puerto' => (int) ($registros['CorreoPuertoSmtp'] ?? 587),
            'remitente' => $registros['CorreoRemitente'] ?? '',
            'usuario' => $registros['CorreoUsuario'] ?? '',
            'password' => $registros['CorreoPassword'] ?? '',
            'nombreRemitente' => $registros['CorreoNombreRemitente'] ?? 'Help Desk',
        ];
    }

    private function separarDestinatarios(string $destinatario): array
    {
        return array_unique(array_filter(array_map('trim', preg_split('/[;,]/', $destinatario))));
    }

    private function obtenerBool(array $valores, string $clave, bool $default): bool
    {
        $valor = $valores[$clave] ?? '';
        if (empty($valor)) return $default;
        return $valor === '1' || strtolower($valor) === 'true' || strtolower($valor) === 'si';
    }
}
