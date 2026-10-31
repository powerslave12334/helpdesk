<?php

namespace App\Services;

/**
 * Plantilla HTML de los correos del Help Desk.
 *
 * Estructura: stub oscuro con folio (eco de la sidebar), línea de acento
 * azul, saludo, tabla de detalle con hairlines, bloque opcional y CTA.
 * Todos los estilos van inline (compatibilidad con clientes de correo);
 * ancho de tarjeta 560px que colapsa a 100% en pantallas chicas.
 */
class CorreoHtml
{
    private const FONT = "Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif";

    /**
     * @param array{
     *     etiqueta: string,
     *     folio?: string|null,
     *     saludo: string,
     *     saludoPrefijo?: string,      // default 'Hola'
     *     firma?: string|null,         // HTML crudo de firma (ya limpio de Word/VML)
     *     intro: string,               // HTML permitido (ya escapado por el llamador)
     *     filas?: array<string, string|int|null>,
     *     bloqueTitulo?: string|null,
     *     bloque?: string|null,        // HTML ya escapado por el llamador
     *     cta?: array{texto: string, url: string}|null,
     *     pie?: string
     * } $datos
     */
    public static function plantilla(array $datos): string
    {
        $folio = $datos['folio'] ?? null;
        $izquierdo = $folio
            ? '<span style="font-size:28px; font-weight:700; letter-spacing:-0.02em; color:#ffffff; line-height:1;">'
                . e($folio) . '</span>'
            : '<span style="font-size:19px; font-weight:700; letter-spacing:-0.01em; color:#ffffff; line-height:1;">'
                . 'HelpDesk' . '</span>';

        $filasHtml = '';
        foreach ($datos['filas'] ?? [] as $label => $valor) {
            $filasHtml .= '
                <tr>
                    <td style="padding:9px 16px 9px 0; border-bottom:1px solid #e2e8f0; color:#64748b; font-size:13px; width:155px; vertical-align:top;">'
                        . e((string) $label) . '</td>
                    <td style="padding:9px 0; border-bottom:1px solid #e2e8f0; color:#0f172a; font-size:13px; font-weight:500; vertical-align:top;">'
                        . e((string) $valor) . '</td>
                </tr>';
        }
        $filasHtml = $filasHtml !== ''
            ? '
            <tr>
                <td style="padding:18px 28px 4px;">
                    <table role="presentation" width="100%" style="width:100%; border-collapse:collapse; font-family:' . self::FONT . ';">
                        ' . $filasHtml . '
                    </table>
                </td>
            </tr>'
            : '';

        $bloqueHtml = '';
        if (!empty($datos['bloque'])) {
            $titulo = isset($datos['bloqueTitulo'])
                ? '<p style="margin:0 0 8px; font-size:13px; font-weight:600; color:#0f172a;">' . e($datos['bloqueTitulo']) . '</p>'
                : '';
            $bloqueHtml = '
            <tr>
                <td style="padding:16px 28px 0; font-family:' . self::FONT . ';">
                    ' . $titulo . '
                    <div style="border-left:3px solid #2563eb; background:#f8fafc; padding:12px 16px; border-radius:0 6px 6px 0; font-size:14px; color:#334155; line-height:1.55;">'
                        . $datos['bloque'] . '
                    </div>
                </td>
            </tr>';
        }

        $ctaHtml = '';
        if (!empty($datos['cta']['url'])) {
            $ctaHtml = '
            <tr>
                <td style="padding:22px 28px 26px;">
                    <table role="presentation" style="border-collapse:collapse;">
                        <tr>
                            <td style="background:#2563eb; border-radius:6px;">
                                <a href="' . e($datos['cta']['url']) . '" style="display:inline-block; padding:11px 20px; font-family:' . self::FONT . '; font-size:14px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:6px; white-space:nowrap;">'
                                    . e($datos['cta']['texto']) . '</a>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>';
        }

        $pie = $datos['pie'] ?? 'Mensaje automático de Help Desk. No respondas a este correo.';
        $prefijoSaludo = $datos['saludoPrefijo'] ?? 'Hola';

        $firmaHtml = '';
        if (!empty($datos['firma'])) {
            $firmaHtml = '
            <tr>
                <td style="padding:14px 28px 22px; font-family:' . self::FONT . ';">
                    <div style="border-top:1px solid #e2e8f0; padding-top:14px;">' . $datos['firma'] . '</div>
                </td>
            </tr>';
        }

        return '
        <table role="presentation" width="100%" style="width:100%; margin:0; padding:0; background:#f1f5f9; border-collapse:collapse;">
            <tr>
                <td align="center" style="padding:24px 12px;">
                    <table role="presentation" width="560" style="width:560px; max-width:560px; background:#ffffff; border:1px solid #e2e8f0; border-collapse:collapse;">
                        <tr>
                            <td style="background:#0f172a; padding:18px 28px;">
                                <table role="presentation" width="100%" style="width:100%; border-collapse:collapse;">
                                    <tr>
                                        <td style="font-family:' . self::FONT . '; vertical-align:middle;">' . $izquierdo . '</td>
                                        <td align="right" style="font-family:' . self::FONT . '; font-size:13px; color:#94a3b8; vertical-align:middle;">'
                                            . e($datos['etiqueta']) . '</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td style="background:#2563eb; height:3px; font-size:0; line-height:0;">&nbsp;</td>
                        </tr>
                        <tr>
                            <td style="padding:22px 28px 6px; font-family:' . self::FONT . '; font-size:14px; color:#0f172a; line-height:1.55;">
                                <p style="margin:0 0 8px;">' . e($prefijoSaludo) . ' <strong>' . e($datos['saludo']) . '</strong>,</p>
                                <p style="margin:0;">' . $datos['intro'] . '</p>
                            </td>
                        </tr>
                        ' . $filasHtml . $bloqueHtml . $ctaHtml . '
                        <tr>
                            <td style="padding:14px 28px; border-top:1px solid #e2e8f0; font-family:' . self::FONT . '; font-size:12px; color:#64748b; line-height:1.5;">'
                                . $pie . '</td>
                        </tr>
                        ' . $firmaHtml . '
                    </table>
                </td>
            </tr>
        </table>';
    }
}
