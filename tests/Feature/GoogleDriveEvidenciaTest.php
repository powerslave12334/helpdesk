<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Departamento;
use App\Models\Rol;
use App\Models\RolesUsuario;
use App\Models\Ticket;
use App\Models\TicketEvidencia;
use App\Models\TipoEmpleado;
use App\Models\TipoSoporte;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Evidencias de tickets subidas a Google Drive.
 *
 * Regresión: en producción las imágenes no se subían (permisos de
 * public/evidencias, storage:link ausente y límites de php.ini). Ahora la
 * evidencia se sube a Drive por OAuth2 y en tblTicketEvidencia solo queda
 * el fileId; si Drive falla no se registra nada y se muestra el motivo.
 *
 * No hay red real: todo se responde con Http::fake.
 */
class GoogleDriveEvidenciaTest extends TestCase
{
    use RefreshDatabase;

    /** Carpeta de evidencias locales que este test llega a crear. */
    private const CARPETA_LOCAL = 'evidencias/tickets/99999';

    /** Usuario con rol Root (bypass de permisos) para pasar permiso:tickets. */
    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        // Google Drive no se usa en esta versión: las evidencias se guardan
        // en public/evidencias/. Se conserva la spec por si se retoma Drive.
        $this->markTestSkipped('Google Drive deshabilitado temporalmente (guardado local).');

        $this->usuario = $this->usuarioRoot();

        config([
            'services.google_drive.client_id' => 'client-id-prueba',
            'services.google_drive.client_secret' => 'client-secret-prueba',
            'services.google_drive.refresh_token' => 'refresh-token-prueba',
            'services.google_drive.folder_id' => 'carpeta-prueba',
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(public_path(self::CARPETA_LOCAL));

        parent::tearDown();
    }

    /** Simula a Google: token, subida de archivo y permiso "cualquiera con el link". */
    private function fakeDriveOk(string $fileId = 'archivo-drive-1'): void
    {
        Http::preventStrayRequests();
        Http::fake(function ($request) use ($fileId) {
            $url = $request->url();

            if (str_contains($url, 'oauth2.googleapis.com/token')) {
                return Http::response(['access_token' => 'access-token-prueba', 'expires_in' => 3600]);
            }

            if (str_contains($url, '/upload/drive/v3/files')) {
                return Http::response(['id' => $fileId], 201);
            }

            if (str_contains($url, '/permissions')) {
                return Http::response(['id' => $fileId, 'type' => 'anyone', 'role' => 'reader']);
            }

            return Http::response(['error' => ['message' => 'recurso no encontrado']], 404);
        });
    }

    /** Simula a Google con la subida rechazada (403). */
    private function fakeDriveFalla(int $estado = 403): void
    {
        Http::preventStrayRequests();
        Http::fake(function ($request) use ($estado) {
            $url = $request->url();

            if (str_contains($url, 'oauth2.googleapis.com/token')) {
                return Http::response(['access_token' => 'access-token-prueba', 'expires_in' => 3600]);
            }

            if (str_contains($url, '/upload/drive/v3/files')) {
                return Http::response(['error' => ['message' => 'No se puede acceder a la carpeta']], $estado);
            }

            return Http::response(['id' => 'archivo-drive-1'], 200);
        });
    }

    private function usuarioRoot(): User
    {
        $rol = Rol::create([
            'nombre' => 'Root',
            'activo' => true,
            'fechaAlta' => now(),
        ]);

        $depto = Departamento::create([
            'nombre' => 'TI',
            'activo' => true,
            'fechaAlta' => now(),
        ]);

        $area = Area::create([
            'nombre' => 'Soporte TI',
            'activo' => true,
            'fechaAlta' => now(),
        ]);

        $tipoEmpleado = TipoEmpleado::create([
            'nombre' => 'Administrativo',
            'activo' => true,
            'fechaAlta' => now(),
        ]);

        $usuario = User::create([
            'nombre' => 'Drive',
            'app' => 'Prueba',
            'apm' => 'Test',
            'correo' => 'drive@test.local',
            'id_Depto' => $depto->id_Depto,
            'id_Area' => $area->id_Area,
            'id_TipoEmpleado' => $tipoEmpleado->id_TipoEmpleado,
            'esTecnico' => false,
            'activo' => true,
            'fechaAlta' => now(),
        ]);

        RolesUsuario::create([
            'id_Usuario' => $usuario->id_Usuario,
            'id_Rol' => $rol->id_Rol,
            'fechaAlta' => now(),
            'activo' => true,
        ]);

        return $usuario;
    }

    /** Envia la petición como el usuario de prueba, con IdUsuario en sesión. */
    private function comoUsuario(): static
    {
        return $this->actingAs($this->usuario)->withSession([
            'IdUsuario' => $this->usuario->id_Usuario,
            'NombreUsuario' => $this->usuario->nombreCompleto(),
        ]);
    }

    private function crearTicket(): Ticket
    {
        $tipoSoporte = TipoSoporte::create([
            'descripcion' => 'Soporte TI',
            'fechaAlta' => now(),
            'activo' => true,
            'id_Area' => Area::first()->id_Area,
        ]);

        return Ticket::create([
            'id_Usuario' => $this->usuario->id_Usuario,
            'id_TipoSoporte' => $tipoSoporte->id_TipoSoporte,
            'asunto' => 'Monitor sin imagen',
            'problema' => 'El monitor no enciende.',
            'fechaAlta' => now(),
            'activo' => true,
        ]);
    }

    public function test_al_agregar_evidencia_se_sube_a_drive_y_queda_el_file_id(): void
    {
        $this->fakeDriveOk();
        $ticket = $this->crearTicket();

        $respuesta = $this->comoUsuario()->post('/tickets/evidencia', [
            'id_Ticket' => $ticket->id_Ticket,
            'evidencia' => UploadedFile::fake()->image('falla.jpg', 200, 200),
        ]);

        $respuesta->assertOk()->assertJson(['success' => true]);

        $evidencia = TicketEvidencia::where('id_Ticket', $ticket->id_Ticket)->firstOrFail();

        $this->assertSame('archivo-drive-1', $evidencia->driveFileId);
        $this->assertSame('image/jpeg', $evidencia->tipoContenido);
        $this->assertSame('jpg', $evidencia->extension);
        $this->assertGreaterThan(0, $evidencia->tamanioBytes);
        // La ruta guardada es la de Drive, no una ruta del servidor.
        $this->assertSame('https://drive.google.com/file/d/archivo-drive-1/view', $evidencia->rutaArchivo);
        $this->assertSame('https://drive.google.com/file/d/archivo-drive-1/view', $evidencia->url);
        $this->assertSame('https://lh3.googleusercontent.com/d/archivo-drive-1', $evidencia->urlImagen);
    }

    public function test_crear_ticket_con_evidencia_la_sube_a_drive(): void
    {
        $this->fakeDriveOk('archivo-inicial');
        $ticket = $this->crearTicket();

        $respuesta = $this->comoUsuario()->post('/tickets', [
            'tipoTicket' => 'falla_tecnica',
            'asunto' => 'Falla con evidencia inicial',
            'id_TipoSoporte' => $ticket->id_TipoSoporte,
            'problema' => 'No enciende la impresora.',
            'evidencia' => UploadedFile::fake()->image('inicial.png', 120, 120),
        ]);

        $respuesta->assertRedirect(route('tickets.index'));

        $nuevo = Ticket::where('asunto', 'Falla con evidencia inicial')->firstOrFail();
        $evidencia = TicketEvidencia::where('id_Ticket', $nuevo->id_Ticket)->firstOrFail();

        $this->assertSame('archivo-inicial', $evidencia->driveFileId);
        $this->assertSame('image/png', $evidencia->tipoContenido);
    }

    public function test_si_drive_falla_al_agregar_evidencia_se_muestra_el_motivo_y_no_se_registra(): void
    {
        $this->fakeDriveFalla();
        $ticket = $this->crearTicket();

        $respuesta = $this->comoUsuario()->post('/tickets/evidencia', [
            'id_Ticket' => $ticket->id_Ticket,
            'evidencia' => UploadedFile::fake()->image('falla.jpg', 100, 100),
        ]);

        $respuesta->assertStatus(502)
            ->assertJson(['success' => false])
            ->assertJsonPath('error', fn ($error) => str_contains($error, '403'));

        $this->assertSame(0, TicketEvidencia::count());
    }

    public function test_si_drive_falla_al_crear_ticket_el_ticket_se_crea_con_aviso(): void
    {
        $this->fakeDriveFalla();
        $ticket = $this->crearTicket();

        $respuesta = $this->comoUsuario()->post('/tickets', [
            'tipoTicket' => 'falla_tecnica',
            'asunto' => 'Falla con Drive caído',
            'id_TipoSoporte' => $ticket->id_TipoSoporte,
            'problema' => 'No enciende la impresora.',
            'evidencia' => UploadedFile::fake()->image('falla.jpg', 100, 100),
        ]);

        $respuesta->assertRedirect(route('tickets.index'))
            ->assertSessionHas('error', fn ($error) => str_contains($error, 'creado, pero la evidencia no se pudo subir'));

        $this->assertNotNull(Ticket::where('asunto', 'Falla con Drive caído')->first());
        $this->assertSame(0, TicketEvidencia::count());
    }

    public function test_sin_configuracion_de_drive_se_responde_sin_hacer_peticiones(): void
    {
        // Ninguna petición falsificada: si el servicio intentara hablar con
        // Google, preventStrayRequests() lanzaría una excepción.
        Http::preventStrayRequests();
        config(['services.google_drive.folder_id' => null]);

        $ticket = $this->crearTicket();

        $respuesta = $this->comoUsuario()->post('/tickets/evidencia', [
            'id_Ticket' => $ticket->id_Ticket,
            'evidencia' => UploadedFile::fake()->image('falla.jpg', 100, 100),
        ]);

        $respuesta->assertStatus(502)
            ->assertJson(['success' => false])
            ->assertJsonPath('error', fn ($error) => str_contains($error, 'no está configurado'));

        $this->assertSame(0, TicketEvidencia::count());
    }

    public function test_ver_evidencia_redirige_a_drive_cuando_tiene_file_id(): void
    {
        $ticket = $this->crearTicket();

        $evidencia = TicketEvidencia::create([
            'id_Ticket' => $ticket->id_Ticket,
            'nombreArchivo' => '123_falla.jpg',
            'rutaArchivo' => 'https://drive.google.com/file/d/archivo-drive-9/view',
            'driveFileId' => 'archivo-drive-9',
            'extension' => 'jpg',
            'tipoContenido' => 'image/jpeg',
            'tamanioBytes' => 1024,
            'id_UsuarioAlta' => $this->usuario->id_Usuario,
            'fechaAlta' => now(),
            'activo' => true,
        ]);

        $this->comoUsuario()
            ->get('/tickets/evidencia/'.$evidencia->id_TicketEvidencia)
            ->assertRedirect('https://drive.google.com/file/d/archivo-drive-9/view');
    }

    public function test_ver_evidencia_local_antigua_sigue_funcionando(): void
    {
        $ticket = $this->crearTicket();
        File::ensureDirectoryExists(public_path(self::CARPETA_LOCAL));
        File::put(public_path(self::CARPETA_LOCAL.'/antigua.jpg'), 'contenido-de-prueba');

        $evidencia = TicketEvidencia::create([
            'id_Ticket' => $ticket->id_Ticket,
            'nombreArchivo' => 'antigua.jpg',
            'rutaArchivo' => self::CARPETA_LOCAL.'/antigua.jpg',
            'driveFileId' => null,
            'extension' => 'jpg',
            'tipoContenido' => 'image/jpeg',
            'tamanioBytes' => 18,
            'id_UsuarioAlta' => $this->usuario->id_Usuario,
            'fechaAlta' => now(),
            'activo' => true,
        ]);

        // Sin fileId el enlace apunta a la ruta local de la app.
        $this->assertSame('/'.self::CARPETA_LOCAL.'/antigua.jpg', $evidencia->url);

        $this->comoUsuario()
            ->get('/tickets/evidencia/'.$evidencia->id_TicketEvidencia)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_el_json_del_ticket_incluye_los_enlaces_de_drive(): void
    {
        $this->fakeDriveOk('archivo-galeria');
        $ticket = $this->crearTicket();

        $this->comoUsuario()->post('/tickets/evidencia', [
            'id_Ticket' => $ticket->id_Ticket,
            'evidencia' => UploadedFile::fake()->image('galeria.png', 100, 100),
        ])->assertOk();

        $this->comoUsuario()
            ->get('/tickets/'.$ticket->id_Ticket)
            ->assertOk()
            ->assertJsonPath('ticket.evidencias.0.driveFileId', 'archivo-galeria')
            ->assertJsonPath('ticket.evidencias.0.url', 'https://drive.google.com/file/d/archivo-galeria/view')
            ->assertJsonPath('ticket.evidencias.0.urlImagen', 'https://lh3.googleusercontent.com/d/archivo-galeria');
    }
}
