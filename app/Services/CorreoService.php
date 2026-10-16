<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Part\TextPart;

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
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => $config['servidor'],
                'mail.mailers.smtp.port' => $config['puerto'],
                'mail.mailers.smtp.username' => $config['usuario'],
                'mail.mailers.smtp.password' => $config['password'],
                'mail.mailers.smtp.encryption' => 'tls',
                'mail.from.address' => $config['remitente'],
                'mail.from.name' => $config['nombreRemitente'],
            ]);

            // Extraer imágenes de la firma y reemplazar con placeholders temporales
            $imagenesFirma = $this->extraerImagenesFirma($htmlCuerpo);
            $htmlConPlaceholders = $htmlCuerpo;
            foreach ($imagenesFirma as $i => $img) {
                $htmlConPlaceholders = str_replace(
                    'src="' . $img['src'] . '"',
                    'src="cid:FIRMA_PLACEHOLDER_' . $i . '"',
                    $htmlConPlaceholders
                );
            }

            Mail::html($htmlConPlaceholders, function ($message) use ($destinatarios, $asunto, $htmlConPlaceholders, $imagenesFirma) {
                $message->subject($asunto);
                foreach ($destinatarios as $dest) {
                    $message->to($dest);
                }

                // Embeber imágenes de la firma como adjuntos inline (CID)
                $htmlFinal = $htmlConPlaceholders;
                foreach ($imagenesFirma as $i => $img) {
                    $cid = $message->embed($img['absoluta']);
                    $htmlFinal = str_replace('cid:FIRMA_PLACEHOLDER_' . $i, $cid, $htmlFinal);
                }

                if ($htmlFinal !== $htmlConPlaceholders) {
                    $message->getSymfonyMessage()->setBody(new TextPart($htmlFinal, 'utf-8', 'html'));
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

    /**
     * Obtiene el HTML de la firma de correo configurada.
     * Limpia wrappers de Outlook (html/head/body, <style> de Word).
     * Devuelve string vacío si no hay firma configurada.
     */
    public function firma(): string
    {
        $valor = DB::table('tblConfiguracionSistema')
            ->where('activo', true)
            ->where('clave', 'CorreoFirma')
            ->value('valor');

        if (empty($valor)) {
            return '';
        }

        $firma = $this->limpiarFirmaOutlook($valor);

        return $firma;
    }

    /**
     * Limpia el HTML de una firma de Outlook para uso en correos HTML:
     * - Elimina etiquetas <html>, <head>, <body> y sus atributos
     * - Elimina bloques <style> completos
     * - Elimina comentarios condicionales de Outlook/Word
     * - Elimina etiquetas VML (<v:*>) y <o:*>, <w:*>, <m:*>
     * - Convierte <v:shape> y <v:imagedata> a <img> estándar
     */
    private function limpiarFirmaOutlook(string $html): string
    {
        $firma = $html;

        $firma = preg_replace('/<!--\[if[^\]]*\]>.*?<!\[endif\]-->/is', '', $firma);
        $firma = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $firma);
        $firma = preg_replace('/<head[^>]*>.*?<\/head>/is', '', $firma);
        $firma = preg_replace('/<\/?html[^>]*>/i', '', $firma);
        $firma = preg_replace('/<\/?body[^>]*>/i', '', $firma);
        $firma = preg_replace('/<v:shape[^>]*>.*?<\/v:shape>/is', '', $firma);
        $firma = preg_replace('/<v:shapetype[^>]*>.*?<\/v:shapetype>/is', '', $firma);
        $firma = preg_replace('/<v:imagedata[^>]*>/i', '', $firma);
        $firma = preg_replace('/<\/?v:[a-z]+[^>]*>/i', '', $firma);
        $firma = preg_replace('/<\/?o:[a-z]+[^>]*>/i', '', $firma);
        $firma = preg_replace('/<\/?w:[a-z]+[^>]*>/i', '', $firma);
        $firma = preg_replace('/<\/?m:[a-z]+[^>]*>/i', '', $firma);
        $firma = preg_replace('/<link[^>]*>/i', '', $firma);
        $firma = preg_replace('/<meta[^>]*>/i', '', $firma);
        $firma = preg_replace('/<!--.*?-->/s', '', $firma);

        $firma = trim($firma);

        return $firma;
    }

    /**
     * Extrae las rutas de imágenes de la firma (src="firmas/...") del HTML.
     * Resuelve las rutas absolutas usando la configuración CorreoFirmaRuta.
     * Devuelve un array con ['src' => ruta original, 'absoluta' => ruta absoluta al archivo].
     */
    private function extraerImagenesFirma(string $html): array
    {
        $rutaBase = DB::table('tblConfiguracionSistema')
            ->where('activo', true)
            ->where('clave', 'CorreoFirmaRuta')
            ->value('valor');

        if (empty($rutaBase)) {
            return [];
        }

        $rutaBase = rtrim($rutaBase, '/\\');

        preg_match_all('/src="(firmas\/[^"]+)"/i', $html, $matches);

        $imagenes = [];
        foreach ($matches[1] as $rutaRelativa) {
            $nombreArchivo = basename($rutaRelativa);
            $pathAbsoluta = base_path($rutaBase . DIRECTORY_SEPARATOR . $nombreArchivo);
            if (file_exists($pathAbsoluta)) {
                $imagenes[] = ['src' => $rutaRelativa, 'absoluta' => $pathAbsoluta];
            }
        }

        return $imagenes;
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
