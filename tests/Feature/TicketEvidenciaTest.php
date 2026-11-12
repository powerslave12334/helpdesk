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
use Tests\TestCase;

/**
 * Subida de evidencias en el módulo de tickets.
 *
 * Regresión: addEvidence() y store() leían getMimeType()/getSize() después
 * de move(). Symfony no actualiza la ruta temporal del UploadedFile al
 * moverlo, así que lanzaban "file does not exist" (HTTP 500): el archivo
 * quedaba huérfano en disco y nunca se registraba en tblTicketEvidencia,
 * por lo que la galería seguía vacía.
 */
class TicketEvidenciaTest extends TestCase
{
    use RefreshDatabase;

    /** Usuario con rol Root (bypass de permisos) para pasar permiso:tickets. */
    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = $this->usuarioRoot();
    }

    /** Borra las carpetas de evidencias que dejó el test en public/. */
    protected function tearDown(): void
    {
        foreach (Ticket::pluck('id_Ticket') as $idTicket) {
            File::deleteDirectory(public_path('evidencias/tickets/'.$idTicket));
        }

        parent::tearDown();
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
            'nombre' => 'Evidencia',
            'app' => 'Prueba',
            'apm' => 'Test',
            'correo' => 'evidencia@test.local',
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

    public function test_la_evidencia_se_registra_en_bd_y_el_archivo_queda_en_disco(): void
    {
        $ticket = $this->crearTicket();

        $respuesta = $this->comoUsuario()->post('/tickets/evidencia', [
            'id_Ticket' => $ticket->id_Ticket,
            'evidencia' => UploadedFile::fake()->image('falla.jpg', 200, 200),
        ]);

        $respuesta->assertOk()->assertJson(['success' => true]);

        $evidencia = TicketEvidencia::where('id_Ticket', $ticket->id_Ticket)->firstOrFail();

        $this->assertSame('image/jpeg', $evidencia->tipoContenido);
        $this->assertSame('jpg', $evidencia->extension);
        $this->assertGreaterThan(0, $evidencia->tamanioBytes);
        $this->assertFileExists(public_path($evidencia->rutaArchivo));
    }

    public function test_se_rechaza_un_archivo_que_no_es_imagen_ni_documento(): void
    {
        $ticket = $this->crearTicket();

        $respuesta = $this->comoUsuario()
            ->withHeader('Accept', 'application/json')
            ->post('/tickets/evidencia', [
                'id_Ticket' => $ticket->id_Ticket,
                'evidencia' => UploadedFile::fake()->create('peligroso.exe', 10),
            ]);

        $respuesta->assertStatus(422)->assertJsonValidationErrors('evidencia');
        $this->assertSame(0, TicketEvidencia::count());
    }

    public function test_crear_ticket_con_evidencia_inicial_no_falla(): void
    {
        $tipoSoporte = TipoSoporte::create([
            'descripcion' => 'Soporte TI',
            'fechaAlta' => now(),
            'activo' => true,
            'id_Area' => Area::first()->id_Area,
        ]);

        $respuesta = $this->comoUsuario()->post('/tickets', [
            'tipoTicket' => 'falla_tecnica',
            'asunto' => 'Falla con evidencia inicial',
            'id_TipoSoporte' => $tipoSoporte->id_TipoSoporte,
            'problema' => 'No enciende la impresora.',
            'evidencia' => UploadedFile::fake()->image('inicial.png', 120, 120),
        ]);

        $respuesta->assertRedirect(route('tickets.index'));

        $ticket = Ticket::where('asunto', 'Falla con evidencia inicial')->firstOrFail();
        $evidencia = TicketEvidencia::where('id_Ticket', $ticket->id_Ticket)->firstOrFail();

        $this->assertSame('image/png', $evidencia->tipoContenido);
        $this->assertFileExists(public_path($evidencia->rutaArchivo));
    }

    /**
     * Cuando la validación del alta falla (p. ej. imagen que PHP rechaza),
     * los mensajes deben verse en la página: antes quedaban invisibles y
     * parecía que "no pasaba nada" al subir la evidencia.
     */
    public function test_los_errores_de_validacion_se_muestran_en_la_pagina_de_tickets(): void
    {
        $this->comoUsuario()->post('/tickets', [
            'tipoTicket' => 'falla_tecnica',
            'asunto' => '',
            'id_TipoSoporte' => 1,
            'problema' => '',
        ])->assertSessionHasErrors(['asunto', 'problema']);

        // El navegador reenvía la cookie de sesión en la petición de la
        // redirección; sin ella el test genera un id nuevo y el store, que
        // persiste en memoria, reconstruye el ViewErrorBag vacío.
        $idSesion = session()->getId();

        // Segunda petición con la MISMA sesión: volver a llamar withSession()
        // reemplazaría el arreglo completo y perdería los errores flasheados.
        $respuesta = $this
            ->withCookie(config('session.cookie'), $idSesion)
            ->get('/tickets');

        $respuesta
            ->assertOk()
            ->assertSee('No se pudo guardar');
    }
}
