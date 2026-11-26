<?php

namespace App\Console\Commands;

use App\Services\GoogleDriveException;
use App\Services\GoogleDriveService;
use Illuminate\Console\Command;

/**
 * Autoriza la app con Google Drive y guarda el refresh token en .env.
 *
 * Uso:
 *   1. php artisan drive:auth            -> imprime la URL de consentimiento
 *   2. Abrir la URL, autorizar y copiar el código
 *   3. php artisan drive:auth --code="..." -> guarda GOOGLE_DRIVE_REFRESH_TOKEN
 *
 * El paso 3 también se puede hacer por el navegador con la ruta
 * /oauth/drive/callback (solo disponible en local).
 */
class DriveAuth extends Command
{
    protected $signature = 'drive:auth {--code= : Código de consentimiento intercambiado por el refresh token}';

    protected $description = 'Autoriza el acceso a Google Drive y guarda el refresh token en .env';

    public function handle(GoogleDriveService $drive): int
    {
        if (! filled(config('services.google_drive.client_id')) || ! filled(config('services.google_drive.client_secret'))) {
            $this->error('Faltan GOOGLE_DRIVE_CLIENT_ID y GOOGLE_DRIVE_CLIENT_SECRET en .env.');

            return self::FAILURE;
        }

        if ($codigo = $this->option('code')) {
            return $this->guardar($drive, $codigo);
        }

        $this->info('1. Abre esta URL en tu navegador (inicia sesión con la cuenta que ve la carpeta de Drive):');
        $this->newLine();
        $this->line('   '.$drive->urlDeConsentimiento());
        $this->newLine();
        $this->info('2. Autoriza la app y copia el código de autorización.');
        $this->info('3. Ejecuta: php artisan drive:auth --code="CODIGO"');
        $this->newLine();
        $this->comment('   (o abre '.config('services.google_drive.redirect_uri').'?code=CODIGO)');

        return self::SUCCESS;
    }

    /** @throws GoogleDriveException */
    private function guardar(GoogleDriveService $drive, string $codigo): int
    {
        try {
            $refreshToken = $drive->intercambiarCodigo($codigo);
            $drive->guardarRefreshToken($refreshToken);
        } catch (GoogleDriveException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Refresh token guardado en .env (GOOGLE_DRIVE_REFRESH_TOKEN).');
        $this->comment('Ejecuta "php artisan config:clear" y luego sube una evidencia de prueba.');

        return self::SUCCESS;
    }
}
