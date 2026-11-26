<?php

namespace App\Http\Controllers;

use App\Services\GoogleDriveException;
use App\Services\GoogleDriveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Callback del flujo OAuth de Google Drive (solo local).
 *
 * La app nunca debe exponer este endpoint en producción: intercambia el
 * código de consentimiento por el refresh token y lo escribe en .env.
 * En producción el refresh token se copia a mano desde el .env local.
 */
class GoogleDriveAuthController extends Controller
{
    public function callback(Request $request, GoogleDriveService $drive): Response|RedirectResponse
    {
        if (! app()->isLocal() || ! in_array($request->host(), ['localhost', '127.0.0.1'], true)) {
            abort(403);
        }

        $error = $request->query('error');

        if (filled($error)) {
            return $this->pagina('Google no autorizó el acceso', (string) $error);
        }

        $codigo = $request->query('code');

        if (! filled($codigo)) {
            return $this->pagina('Falta el código', 'No llegó el parámetro code en la URL.');
        }

        try {
            $drive->guardarRefreshToken($drive->intercambiarCodigo((string) $codigo));
        } catch (GoogleDriveException $e) {
            return $this->pagina('No se pudo guardar el token', $e->getMessage());
        }

        return $this->pagina(
            'Refresh token guardado',
            'Se escribió GOOGLE_DRIVE_REFRESH_TOKEN en .env. Ejecuta "php artisan config:clear" y sube una evidencia de prueba.'
        );
    }

    private function pagina(string $titulo, string $detalle): Response
    {
        $esc = fn (string $texto): string => e($texto);

        return response(
            '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>'.$esc($titulo).'</title></head>'
            .'<body style="font-family: sans-serif; max-width: 640px; margin: 60px auto; line-height: 1.6">'
            .'<h1 style="font-size: 22px">'.$esc($titulo).'</h1><p>'.$esc($detalle).'</p>'
            .'<p>Puedes cerrar esta pestaña.</p></body></html>',
            200,
            ['Content-Type' => 'text/html; charset=UTF-8']
        );
    }
}
