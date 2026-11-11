<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Departamento;
use App\Models\IsoResponsivaErp;
use App\Models\Rol;
use App\Models\RolesUsuario;
use App\Models\TipoEmpleado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResponsivaErpTest extends TestCase
{
    use RefreshDatabase;

    /** Datos válidos de captura (los 6 campos del formato). */
    private function datosValidos(): array
    {
        return [
            'fecha' => '2026-11-09',
            'nombreEmpleado' => 'Juan Pérez López',
            'puesto' => 'Analista de Sistemas',
            'correo' => 'juan.perez@empresa.com',
            'usuarioErp' => 'jperez',
            'contrasenaErp' => 'Secreta-123',
        ];
    }

    /** Crea un usuario con el rol indicado (sin permisos sobre ISO 9001). */
    private function usuarioConRol(string $rolNombre): User
    {
        $rol = Rol::create([
            'nombre' => $rolNombre,
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
            'nombre' => 'Prueba',
            'app' => 'FO',
            'apm' => 'IT01',
            'correo' => 'fo.it01@test.local',
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

    /** Usuario administrador (acceso total a los módulos). */
    private function administrador(): User
    {
        return $this->usuarioConRol('Administrador');
    }

    /** Envia la petición como el usuario indicado, con IdUsuario en sesión. */
    private function comoUsuario(User $usuario): static
    {
        return $this->actingAs($usuario)->withSession([
            'IdUsuario' => $usuario->id_Usuario,
            'NombreUsuario' => $usuario->nombreCompleto(),
        ]);
    }

    /** Crea un documento activo y regresa su id. */
    private function documento(User $creador, array $datos = []): IsoResponsivaErp
    {
        return IsoResponsivaErp::factory()->create([
            ...$this->datosValidos(),
            ...$datos,
            'id_Usuario' => $creador->id_Usuario,
        ]);
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get('/iso9001/FO-IT-01')->assertRedirect(route('login'));
    }

    public function test_usuario_sin_submodulo_iso9001_es_redirigido_al_dashboard(): void
    {
        $this->comoUsuario($this->usuarioConRol('Empleado'))
            ->get('/iso9001/FO-IT-01')
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');
    }

    public function test_lista_muestra_la_tabla_con_el_boton_de_nuevo_documento(): void
    {
        $admin = $this->administrador();
        $documento = $this->documento($admin);

        $this->comoUsuario($admin)
            ->get('/iso9001/FO-IT-01')
            ->assertOk()
            ->assertSee('Nuevo documento')
            ->assertSee($documento->folio)
            ->assertSee('Juan Pérez López')
            ->assertSee('jperez');
    }

    public function test_lista_no_muestra_la_contrasena_erp(): void
    {
        $admin = $this->administrador();
        $this->documento($admin);

        $this->comoUsuario($admin)
            ->get('/iso9001/FO-IT-01')
            ->assertOk()
            ->assertDontSee('Secreta-123');
    }

    public function test_formulario_muestra_los_seis_campos(): void
    {
        $this->comoUsuario($this->administrador())
            ->get('/iso9001/FO-IT-01/nuevo')
            ->assertOk()
            ->assertSee('name="fecha"', false)
            ->assertSee('name="nombreEmpleado"', false)
            ->assertSee('name="puesto"', false)
            ->assertSee('name="correo"', false)
            ->assertSee('name="usuarioErp"', false)
            ->assertSee('name="contrasenaErp"', false);
    }

    public function test_guardar_documento_valido_lo_regresa_a_la_lista(): void
    {
        $admin = $this->administrador();

        $this->comoUsuario($admin)
            ->post('/iso9001/FO-IT-01', $this->datosValidos())
            ->assertRedirect(route('iso9001.documentos', ['clave' => 'FO-IT-01']))
            ->assertSessionHas('success');

        $documento = IsoResponsivaErp::query()->sole();

        $this->assertSame('Juan Pérez López', $documento->nombreEmpleado);
        $this->assertSame('Analista de Sistemas', $documento->puesto);
        $this->assertSame('juan.perez@empresa.com', $documento->correo);
        $this->assertSame('jperez', $documento->usuarioErp);
        $this->assertTrue($documento->fecha->isSameDay('2026-11-09'));
        $this->assertSame($admin->id_Usuario, $documento->id_Usuario);
        $this->assertTrue($documento->activo);
    }

    public function test_guardar_con_campos_vacios_muestra_errores_de_los_seis(): void
    {
        $this->comoUsuario($this->administrador())
            ->post('/iso9001/FO-IT-01', [])
            ->assertSessionHasErrors([
                'fecha', 'nombreEmpleado', 'puesto', 'correo', 'usuarioErp', 'contrasenaErp',
            ]);

        $this->assertDatabaseCount('tblIsoResponsivaErp', 0);
    }

    public function test_alta_y_firmado_no_incluyen_la_columna_identidad_en_el_update(): void
    {
        // SQL Server rechaza escribir columnas identidad (MySQL las tolera):
        // el UPDATE de la huella generado tras el alta NO debe incluir
        // id_ResponsivaErp en su cláusula SET.
        $consultas = [];
        DB::listen(function ($consulta) use (&$consultas): void {
            $consultas[] = $consulta->sql;
        });

        $admin = $this->administrador();

        $this->comoUsuario($admin)
            ->post('/iso9001/FO-IT-01', $this->datosValidos())
            ->assertRedirect();

        $documento = IsoResponsivaErp::query()->sole();

        $this->comoUsuario($admin)
            ->post('/iso9001/FO-IT-01/'.$documento->id_ResponsivaErp.'/firmar', [
                'firmaEmpleado' => $this->firmaEjemplo(),
            ])
            ->assertRedirect();

        $actualizaciones = array_values(array_filter(
            $consultas,
            fn (string $sql): bool => str_starts_with(strtolower($sql), 'update ')
        ));

        $this->assertNotSame([], $actualizaciones, 'El alta/firmado debe generar el UPDATE de la huella.');

        foreach ($actualizaciones as $sql) {
            $set = explode(' where ', strtolower($sql))[0];

            $this->assertStringNotContainsString(
                'id_responsivaerp',
                $set,
                "El UPDATE no debe incluir la columna identidad en el SET: {$sql}"
            );
        }

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', (string) $documento->fresh()->huella);
    }

    public function test_contrasena_se_guarda_cifrada_y_se_lee_desencriptada(): void
    {
        $admin = $this->administrador();
        $documento = $this->documento($admin);

        $enBruto = DB::table('tblIsoResponsivaErp')
            ->where('id_ResponsivaErp', $documento->id_ResponsivaErp)
            ->value('contrasenaErp');

        $this->assertNotSame('Secreta-123', $enBruto);
        $this->assertSame('Secreta-123', $documento->fresh()->contrasenaErp);
    }

    public function test_pdf_se_genera_bajo_demanda(): void
    {
        $admin = $this->administrador();
        $documento = $this->documento($admin);

        $this->comoUsuario($admin)
            ->get('/iso9001/FO-IT-01/'.$documento->id_ResponsivaErp.'/pdf')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_pdf_con_descarga_sirve_el_archivo(): void
    {
        $admin = $this->administrador();
        $documento = $this->documento($admin);

        $this->comoUsuario($admin)
            ->get('/iso9001/FO-IT-01/'.$documento->id_ResponsivaErp.'/pdf?descargar=1')
            ->assertOk()
            ->assertDownload($documento->folio.'.pdf');
    }

    public function test_pdf_de_documento_inexistente_devuelve_404(): void
    {
        $this->comoUsuario($this->administrador())
            ->get('/iso9001/FO-IT-01/9999/pdf')
            ->assertNotFound();
    }

    public function test_eliminar_hace_borrado_logico(): void
    {
        $admin = $this->administrador();
        $documento = $this->documento($admin);

        $this->comoUsuario($admin)
            ->delete('/iso9001/FO-IT-01/'.$documento->id_ResponsivaErp)
            ->assertRedirect();

        $this->assertDatabaseHas('tblIsoResponsivaErp', [
            'id_ResponsivaErp' => $documento->id_ResponsivaErp,
            'activo' => false,
        ]);
    }

    public function test_documento_eliminado_desaparece_de_la_lista_y_su_pdf_da_404(): void
    {
        $admin = $this->administrador();
        $documento = $this->documento($admin, ['activo' => false]);

        $this->comoUsuario($admin)
            ->get('/iso9001/FO-IT-01')
            ->assertOk()
            ->assertDontSee($documento->folio);

        $this->comoUsuario($admin)
            ->get('/iso9001/FO-IT-01/'.$documento->id_ResponsivaErp.'/pdf')
            ->assertNotFound();
    }

    /** PNG de 1x1 en data-URI, válido para las reglas del FormRequest. */
    private function firmaEjemplo(string $contenido = 'firma'): string
    {
        return 'data:image/png;base64,'.base64_encode($contenido);
    }

    public function test_firmar_guarda_empleado_y_ti_con_su_fecha_y_huella(): void
    {
        $admin = $this->administrador();
        $documento = $this->documento($admin);
        $firma = $this->firmaEjemplo();

        $this->comoUsuario($admin)
            ->post('/iso9001/FO-IT-01/'.$documento->id_ResponsivaErp.'/firmar', [
                'firmaEmpleado' => $firma,
                'firmaTi' => $this->firmaEjemplo('firma-ti'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $documento->refresh();

        $this->assertSame($firma, $documento->firmaEmpleado);
        $this->assertNotNull($documento->fechaFirmaEmpleado);
        $this->assertNotNull($documento->fechaFirmaTi);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', (string) $documento->huella);
        $this->assertTrue($documento->estaFirmado());
    }

    public function test_firmar_sin_ningun_lienzo_muestra_error(): void
    {
        $admin = $this->administrador();
        $documento = $this->documento($admin);

        $this->comoUsuario($admin)
            ->post('/iso9001/FO-IT-01/'.$documento->id_ResponsivaErp.'/firmar', [
                'firmaEmpleado' => null,
                'firmaTi' => null,
            ])
            ->assertSessionHasErrors(['firmaEmpleado', 'firmaTi']);

        $this->assertFalse($documento->fresh()->estaFirmado());
    }

    public function test_re_firmar_sobrescribe_la_firma_y_actualiza_la_huella(): void
    {
        $admin = $this->administrador();
        $documento = $this->documento($admin, [
            'firmaEmpleado' => $this->firmaEjemplo('vieja'),
            'fechaFirmaEmpleado' => now()->format('Y-m-d H:i:s'),
        ]);
        $huellaPrevia = $documento->huella;
        $firmaNueva = $this->firmaEjemplo('nueva');

        $this->comoUsuario($admin)
            ->post('/iso9001/FO-IT-01/'.$documento->id_ResponsivaErp.'/firmar', [
                'firmaEmpleado' => $firmaNueva,
            ])
            ->assertRedirect();

        $documento->refresh();

        $this->assertSame($firmaNueva, $documento->firmaEmpleado);
        $this->assertNotSame($huellaPrevia, $documento->huella);
        $this->assertNull($documento->firmaTi, 'La firma de TI no enviada debe conservarse.');
    }

    public function test_documento_firmado_no_se_puede_eliminar(): void
    {
        $admin = $this->administrador();
        $documento = $this->documento($admin, [
            'firmaEmpleado' => $this->firmaEjemplo(),
            'fechaFirmaEmpleado' => now()->format('Y-m-d H:i:s'),
        ]);

        $this->comoUsuario($admin)
            ->delete('/iso9001/FO-IT-01/'.$documento->id_ResponsivaErp)
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertTrue($documento->fresh()->activo, 'El documento firmado debe seguir activo.');
    }

    public function test_lista_muestra_estado_botones_y_modal_de_firma(): void
    {
        $admin = $this->administrador();
        $this->documento($admin, [
            'firmaEmpleado' => $this->firmaEjemplo(),
            'fechaFirmaEmpleado' => now()->format('Y-m-d H:i:s'),
        ]);
        $this->documento($admin);

        $this->comoUsuario($admin)
            ->get('/iso9001/FO-IT-01')
            ->assertOk()
            ->assertSee('Firmado')
            ->assertSee('Pendiente')
            ->assertSee('Documento firmado: no se puede eliminar', false)
            ->assertSee('modalFirma', false)
            ->assertSee('cdn.jsdelivr.net/npm/signature_pad', false);
    }

    public function test_pdf_imprime_firmas_fecha_y_huella(): void
    {
        $admin = $this->administrador();
        $firma = $this->firmaEjemplo();
        $documento = $this->documento($admin, [
            'firmaEmpleado' => $firma,
            'fechaFirmaEmpleado' => now()->format('Y-m-d H:i:s'),
        ]);

        $html = view('iso9001.formatos.responsiva_erp_pdf', [
            'documento' => $documento,
            'cabeceraBase64' => 'data:image/png;base64,dGVzdA==',
        ])->render();

        $this->assertStringContainsString($firma, $html);
        $this->assertStringContainsString($documento->huella, $html);
        $this->assertStringContainsString('Fecha de firma', $html);

        // Documento sin firmar: conserva el formato en blanco.
        $pendiente = $this->documento($admin);
        $htmlBlanco = view('iso9001.formatos.responsiva_erp_pdf', [
            'documento' => $pendiente,
            'cabeceraBase64' => '',
        ])->render();

        $this->assertStringNotContainsString('Fecha de firma', $htmlBlanco);
        $this->assertStringContainsString('Nombre y Firma', $htmlBlanco);
    }

    public function test_huella_se_mantiene_estable_al_regenerar(): void
    {
        $admin = $this->administrador();
        $documento = $this->documento($admin, [
            'firmaEmpleado' => $this->firmaEjemplo(),
            'fechaFirmaEmpleado' => now()->format('Y-m-d H:i:s'),
        ]);

        // La contraseña se cifra con IV distinto en cada escritura: la huella
        // debe usar el valor descifrado para no cambiar entre lecturas.
        $releido = $documento->fresh();

        $this->assertSame($documento->huella, $releido->huella);
        $this->assertSame($releido->huella, $releido->calcularHuella());
    }

    public function test_verificar_folio_vigente(): void
    {
        $admin = $this->administrador();
        $documento = $this->documento($admin, [
            'firmaEmpleado' => $this->firmaEjemplo(),
            'fechaFirmaEmpleado' => now()->format('Y-m-d H:i:s'),
        ]);

        $this->comoUsuario($admin)
            ->get('/iso9001/verificar?folio='.$documento->folio)
            ->assertOk()
            ->assertSee('Huella vigente')
            ->assertSee($documento->huella, false);
    }

    public function test_verificar_detecta_alteracion_de_datos(): void
    {
        $admin = $this->administrador();
        $documento = $this->documento($admin);

        // Alteración directa en BD (sin pasar por el modelo → sin recalcular).
        DB::table('tblIsoResponsivaErp')
            ->where('id_ResponsivaErp', $documento->id_ResponsivaErp)
            ->update(['nombreEmpleado' => 'Nombre Alterado']);

        $this->comoUsuario($admin)
            ->get('/iso9001/verificar?folio='.$documento->folio)
            ->assertOk()
            ->assertSee('Huella alterada');
    }

    public function test_verificar_folio_inexistente(): void
    {
        $this->comoUsuario($this->administrador())
            ->get('/iso9001/verificar?folio=FO-IT-01-9999')
            ->assertOk()
            ->assertSee('No se encontr');
    }

    public function test_verificar_sin_folio_muestra_solo_el_formulario(): void
    {
        $this->comoUsuario($this->administrador())
            ->get('/iso9001/verificar')
            ->assertOk()
            ->assertSee('Verificación de firma')
            ->assertDontSee('Huella vigente')
            ->assertDontSee('Huella alterada');
    }

    public function test_firmar_documento_inexistente_devuelve_404(): void
    {
        $this->comoUsuario($this->administrador())
            ->post('/iso9001/FO-IT-01/9999/firmar', [
                'firmaEmpleado' => $this->firmaEjemplo(),
            ])
            ->assertNotFound();
    }

    public function test_invitado_no_puede_firmar(): void
    {
        $this->post('/iso9001/FO-IT-01/1/firmar', [
            'firmaEmpleado' => $this->firmaEjemplo(),
        ])->assertRedirect(route('login'));
    }
}
