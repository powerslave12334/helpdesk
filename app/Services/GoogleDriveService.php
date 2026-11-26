<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Sube evidencias de tickets a Google Drive con la API REST (OAuth2).
 *
 * Implementado sobre el HTTP client de Laravel: no agrega dependencias a
 * composer. El scope es drive.file, así que solo se accede a los archivos
 * que esta aplicación sube.
 */
class GoogleDriveService
{
    private const URL_TOKEN = 'https://oauth2.googleapis.com/token';

    private const URL_AUTH = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const URL_UPLOAD = 'https://www.googleapis.com/upload/drive/v3/files';

    private const URL_PERMISSIONS = 'https://www.googleapis.com/drive/v3/files/{id}/permissions';

    /** Clave de caché del access token (Google los emite por 3600 s). */
    private const CACHE_KEY = 'google_drive.access_token';

    /** Segundos de caché del access token: 50 minutos. */
    private const TTL_TOKEN = 3000;

    /** Segundos de espera de la subida (archivos de hasta 10 MB). */
    private const TIMEOUT_SUBIDA = 120;

    /** Indica si las credenciales están completas en .env. */
    public function configurado(): bool
    {
        foreach (['client_id', 'client_secret', 'refresh_token', 'folder_id'] as $clave) {
            if (! filled(config('services.google_drive.'.$clave))) {
                return false;
            }
        }

        return true;
    }

    /** URL de consentimiento que debe abrir el usuario (una sola vez). */
    public function urlDeConsentimiento(): string
    {
        return self::URL_AUTH.'?'.http_build_query([
            'client_id' => config('services.google_drive.client_id'),
            'redirect_uri' => config('services.google_drive.redirect_uri'),
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'scope' => config('services.google_drive.scope'),
        ]);
    }

    /**
     * Intercambia el código de consentimiento por un refresh token.
     *
     * @throws GoogleDriveException
     */
    public function intercambiarCodigo(string $codigo): string
    {
        $respuesta = $this->solicitarToken([
            'code' => $codigo,
            'grant_type' => 'authorization_code',
            'redirect_uri' => config('services.google_drive.redirect_uri'),
        ]);

        $refreshToken = $respuesta->json('refresh_token');

        if (! filled($refreshToken)) {
            throw new GoogleDriveException(
                'Google no devolvió refresh token: revoca el acceso de la app en myaccount.google.com/permissions y repite el consentimiento.'
            );
        }

        return $refreshToken;
    }

    /**
     * Escribe (o reemplaza) GOOGLE_DRIVE_REFRESH_TOKEN en el archivo .env.
     *
     * @throws GoogleDriveException
     */
    public function guardarRefreshToken(string $refreshToken): void
    {
        $archivo = base_path('.env');
        $linea = 'GOOGLE_DRIVE_REFRESH_TOKEN='.$refreshToken;

        if (is_file($archivo)) {
            $contenido = file_get_contents($archivo);

            if (preg_match('/^GOOGLE_DRIVE_REFRESH_TOKEN=.*$/m', $contenido)) {
                $contenido = preg_replace('/^GOOGLE_DRIVE_REFRESH_TOKEN=.*$/m', $linea, $contenido, 1);
            } else {
                $contenido = rtrim($contenido).PHP_EOL.$linea.PHP_EOL;
            }
        } else {
            $contenido = $linea.PHP_EOL;
        }

        if (file_put_contents($archivo, $contenido) === false) {
            throw new GoogleDriveException('No se pudo escribir en '.$archivo.': agrega la línea manualmente.');
        }
    }

    /**
     * Sube el archivo a la carpeta de Drive, lo deja con permiso
     * "cualquiera con el link" (lector) y devuelve su fileId.
     *
     * @throws GoogleDriveException
     */
    public function subirArchivo(UploadedFile $archivo, string $nombreArchivo): string
    {
        if (! $this->configurado()) {
            throw new GoogleDriveException(
                'Google Drive no está configurado en .env (GOOGLE_DRIVE_CLIENT_ID, GOOGLE_DRIVE_CLIENT_SECRET, GOOGLE_DRIVE_REFRESH_TOKEN y GOOGLE_DRIVE_FOLDER_ID).'
            );
        }

        $fileId = $this->subirContenido($archivo, $nombreArchivo);

        try {
            $this->hacerPublico($fileId);
        } catch (GoogleDriveException $e) {
            // Sin permiso "cualquiera con el link" el archivo no sirve en la
            // galería: se borra para no dejar huérfanos en Drive.
            $this->borrar($fileId);

            throw $e;
        }

        return $fileId;
    }

    /** Enlace de vista del archivo (para abrir en otra pestaña). */
    public function urlVista(string $fileId): string
    {
        return 'https://drive.google.com/file/d/'.$fileId.'/view';
    }

    /** Enlace directo del contenido, útil como src de una etiqueta <img>. */
    public function urlContenido(string $fileId): string
    {
        return 'https://lh3.googleusercontent.com/d/'.$fileId;
    }

    /**
     * POST multipart a upload/drive/v3/files: la primera parte es el JSON
     * con los metadatos y la segunda es el archivo.
     *
     * @throws GoogleDriveException
     */
    private function subirContenido(UploadedFile $archivo, string $nombreArchivo): string
    {
        $metadatos = json_encode([
            'name' => $nombreArchivo,
            'parents' => [config('services.google_drive.folder_id')],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $respuesta = Http::withToken($this->accessToken())
                ->connectTimeout(10)
                ->timeout(self::TIMEOUT_SUBIDA)
                ->attach('metadata', $metadatos, null, [
                    'Content-Type' => 'application/json; charset=UTF-8',
                ])
                ->attach('file', $archivo->get(), $nombreArchivo, [
                    'Content-Type' => $archivo->getMimeType() ?: 'application/octet-stream',
                ])
                ->post(self::URL_UPLOAD.'?uploadType=multipart&fields=id');
        } catch (ConnectionException $e) {
            throw new GoogleDriveException($this->mensajeDeConexion($e));
        }

        $fileId = $respuesta->json('id');

        if ($respuesta->failed() || ! filled($fileId)) {
            throw new GoogleDriveException($this->mensajeDeError($respuesta, 'subir la evidencia'));
        }

        return $fileId;
    }

    /**
     * Deja el archivo visible con el link (type=anyone, role=reader).
     *
     * @throws GoogleDriveException
     */
    private function hacerPublico(string $fileId): void
    {
        try {
            $respuesta = Http::withToken($this->accessToken())
                ->connectTimeout(10)
                ->timeout(30)
                ->asJson()
                ->post(str_replace('{id}', $fileId, self::URL_PERMISSIONS), [
                    'type' => 'anyone',
                    'role' => 'reader',
                ]);
        } catch (ConnectionException $e) {
            throw new GoogleDriveException($this->mensajeDeConexion($e));
        }

        if ($respuesta->failed()) {
            throw new GoogleDriveException($this->mensajeDeError($respuesta, 'compartir la evidencia'));
        }
    }

    /** Borra un archivo de Drive (best effort: si falla, no se reporta). */
    private function borrar(string $fileId): void
    {
        try {
            Http::withToken($this->accessToken())
                ->connectTimeout(10)
                ->timeout(30)
                ->delete('https://www.googleapis.com/drive/v3/files/'.$fileId);
        } catch (\Throwable) {
            // Mejor esfuerzo: un archivo sin permiso no rompe nada.
        }
    }

    /** Access token obtenido con el refresh token, cacheado por 50 minutos. */
    private function accessToken(): string
    {
        $token = Cache::get(self::CACHE_KEY);

        if (filled($token)) {
            return $token;
        }

        $respuesta = $this->solicitarToken([
            'grant_type' => 'refresh_token',
            'refresh_token' => config('services.google_drive.refresh_token'),
        ]);

        $token = $respuesta->json('access_token');

        if (! filled($token)) {
            throw new GoogleDriveException('Google no devolvió un token de acceso válido.');
        }

        $vida = min((int) $respuesta->json('expires_in', 3600) - 300, self::TTL_TOKEN);
        Cache::put(self::CACHE_KEY, $token, max($vida, 60));

        return $token;
    }

    /**
     * POST a oauth2.googleapis.com/token.
     *
     * @param  array<string, string>  $parametros
     *
     * @throws GoogleDriveException
     */
    private function solicitarToken(array $parametros): Response
    {
        try {
            $respuesta = Http::connectTimeout(5)
                ->timeout(20)
                ->asForm()
                ->post(self::URL_TOKEN, array_merge([
                    'client_id' => config('services.google_drive.client_id'),
                    'client_secret' => config('services.google_drive.client_secret'),
                ], $parametros));
        } catch (ConnectionException $e) {
            throw new GoogleDriveException($this->mensajeDeConexion($e));
        }

        if ($respuesta->failed()) {
            throw new GoogleDriveException($this->mensajeDeError($respuesta, 'obtener el token de Google'));
        }

        return $respuesta;
    }

    private function mensajeDeConexion(ConnectionException $e): string
    {
        return 'No se pudo conectar con Google Drive (googleapis.com): '.$e->getMessage();
    }

    /** Traduce el estado HTTP de Google a un mensaje entendible. */
    private function mensajeDeError(Response $respuesta, string $accion): string
    {
        $detalle = $respuesta->json('error.message');

        $mensaje = match (true) {
            $respuesta->status() === 401 => 'Google rechazó las credenciales (401): ejecuta "php artisan drive:auth" para renovar el token.',
            $respuesta->status() === 403 => 'Google no permitió '.$accion.' (403): revisa el acceso a la carpeta de Drive.',
            $respuesta->status() === 404 => 'No se encontró la carpeta de destino en Google Drive (404): revisa GOOGLE_DRIVE_FOLDER_ID.',
            $respuesta->status() === 429 => 'Google limitó las peticiones (429): vuelve a intentarlo en unos minutos.',
            default => 'Google devolvió un error ('.$respuesta->status().') al '.$accion.'.',
        };

        return filled($detalle) ? $mensaje.' '.$detalle : $mensaje;
    }
}
