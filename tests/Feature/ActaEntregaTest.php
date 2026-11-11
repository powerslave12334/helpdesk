<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Departamento;
use App\Models\IsoActaEntrega;
use App\Models\IsoActaEntregaEquipo;
use App\Models\Rol;
use App\Models\RolesUsuario;
use App\Models\TipoEmpleado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActaEntregaTest extends TestCase
{
    use RefreshDatabase;

    /** Renglones válidos de la tabla dinámica de equipos (2 filas). */
    private function equiposValidos(): array
    {
        return [
            [
                'cantidad' => 1,
                'descripcion' => 'Laptop Dell',
                'marca' => 'Dell',
                'modelo' => 'Latitude 5420',
                'noSerie' => 'DL123456',
                'condiciones' => 'Usado en buen estado',
            ],
            [
                'cantidad' => 2,
                'descripcion' => 'Monitor Samsung',
                'marca' => 'Samsung',
                'modelo' => 'LED 24',
                'noSerie' => '',
                'condiciones' => 'Nuevo',
            ],
        ];
    }

    /** Datos válidos de captura (datos generales + renglones de equipos). */
    private function datosValidos(): array
    {
        return [
            'fecha' => '2026-11-11',
            'entregadoPor' => 'Alan Guzman Santos',
            'recibidoPor' => 'Valentin Loeza Arcos',
            'equipos' => $this->equiposValidos(),
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
            'apm' => 'IT02',
            'correo' => 'fo.it02@test.local',
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

    /** Crea un acta activa con sus renglones de equipos. */
    private function acta(User $creador, array $datos = []): IsoActaEntrega
    {
        $acta = IsoActaEntrega::factory()->create([
            ...$datos,
            'id_Usuario' => $creador->id_Usuario,
        ]);

        $acta->equipos()->createMany($this->equiposValidos());

        // El hook de cada renglón recalcula la huella en BD sobre otra
        // instancia; se relee para entregar el estado consistente.
        return $acta->fresh();
    }

    /** PNG de 1x1 en data-URI, válido para las reglas del FormRequest. */
    private function firmaEjemplo(string $contenido = 'firma'): string
    {
        return 'data:image/png;base64,'.base64_encode($contenido);
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get('/iso9001/FO-IT-02')->assertRedirect(route('login'));
    }

    public function test_usuario_sin_submodulo_iso9001_es_redirigido_al_dashboard(): void
    {
        $this->comoUsuario($this->usuarioConRol('Empleado'))
            ->get('/iso9001/FO-IT-02')
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');
    }

    public function test_lista_muestra_la_tabla_con_el_boton_de_nuevo_documento(): void
    {
        $admin = $this->administrador();
        $acta = $this->acta($admin);

        $this->comoUsuario($admin)
            ->get('/iso9001/FO-IT-02')
            ->assertOk()
            ->assertSee('Nuevo documento')
            ->assertSee($acta->folio)
            ->assertSee($acta->entregadoPor)
            ->assertSee($acta->recibidoPor)
            ->assertSee('2');
    }

    public function test_formulario_muestra_los_campos_y_el_catalogo_de_condiciones(): void
    {
        $this->comoUsuario($this->administrador())
            ->get('/iso9001/FO-IT-02/nuevo')
            ->assertOk()
            ->assertSee('name="fecha"', false)
            ->assertSee('name="entregadoPor"', false)
            ->assertSee('name="recibidoPor"', false)
            ->assertSee('name="equipos[__I__][descripcion]"', false)
            ->assertSee('id="btnAgregarEquipo"', false)
            ->assertSee('Usado en buen estado')
            ->assertSee('No funcional');
    }

    public function test_guardar_acta_valida_lo_regresa_a_la_lista(): void
    {
        $admin = $this->administrador();

        $this->comoUsuario($admin)
            ->post('/iso9001/FO-IT-02', $this->datosValidos())
            ->assertRedirect(route('iso9001.documentos', ['clave' => 'FO-IT-02']))
            ->assertSessionHas('success');

        $acta = IsoActaEntrega::query()->sole();

        $this->assertTrue($acta->fecha->isSameDay('2026-11-11'));
        $this->assertSame('Alan Guzman Santos', $acta->entregadoPor);
        $this->assertSame('Valentin Loeza Arcos', $acta->recibidoPor);
        $this->assertSame($admin->id_Usuario, $acta->id_Usuario);
        $this->assertTrue($acta->activo);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', (string) $acta->huella);
    }

    public function test_los_renglones_se_guardan_como_filas_independientes(): void
    {
        $admin = $this->administrador();

        $this->comoUsuario($admin)
            ->post('/iso9001/FO-IT-02', $this->datosValidos())
            ->assertRedirect();

        $acta = IsoActaEntrega::query()->sole();

        $this->assertDatabaseCount('tblIsoActaEntregaEquipo', 2);

        $equipos = $acta->equipos()->get();

        $this->assertCount(2, $equipos);
        $this->assertSame($acta->id_ActaEntrega, $equipos[0]->id_ActaEntrega);
        $this->assertSame(1, $equipos[0]->cantidad);
        $this->assertSame('Laptop Dell', $equipos[0]->descripcion);
        $this->assertSame('Dell', $equipos[0]->marca);
        $this->assertSame('Latitude 5420', $equipos[0]->modelo);
        $this->assertSame('DL123456', $equipos[0]->noSerie);
        $this->assertSame('Usado en buen estado', $equipos[0]->condiciones);
        $this->assertSame(2, $equipos[1]->cantidad);
        $this->assertSame('Nuevo', $equipos[1]->condiciones);
    }

    public function test_campos_opcionales_de_equipos_admiten_vacio(): void
    {
        $admin = $this->administrador();
        $datos = $this->datosValidos();
        $datos['equipos'] = [[
            'cantidad' => 1,
            'descripcion' => 'Mouse USB',
            'marca' => '',
            'modelo' => '',
            'noSerie' => '',
            'condiciones' => 'Nuevo',
        ]];

        $this->comoUsuario($admin)
            ->post('/iso9001/FO-IT-02', $datos)
            ->assertRedirect();

        $equipo = IsoActaEntregaEquipo::query()->sole();

        $this->assertSame('Mouse USB', $equipo->descripcion);
        $this->assertNull($equipo->marca);
        $this->assertNull($equipo->modelo);
        $this->assertNull($equipo->noSerie);
    }

    public function test_guardar_sin_renglones_de_equipos_muestra_error(): void
    {
        $datos = $this->datosValidos();
        unset($datos['equipos']);

        $this->comoUsuario($this->administrador())
            ->post('/iso9001/FO-IT-02', $datos)
            ->assertSessionHasErrors('equipos');

        $this->assertDatabaseCount('tblIsoActaEntrega', 0);
    }

    public function test_guardar_con_condicion_fuera_del_catalogo_muestra_error(): void
    {
        $datos = $this->datosValidos();
        $datos['equipos'][1]['condiciones'] = 'Como nuevo';

        $this->comoUsuario($this->administrador())
            ->post('/iso9001/FO-IT-02', $datos)
            ->assertSessionHasErrors(['equipos.1.condiciones']);

        $this->assertDatabaseCount('tblIsoActaEntrega', 0);
        $this->assertDatabaseCount('tblIsoActaEntregaEquipo', 0);
    }

    public function test_guardar_con_campos_vacios_muestra_errores(): void
    {
        $this->comoUsuario($this->administrador())
            ->post('/iso9001/FO-IT-02', [])
            ->assertSessionHasErrors([
                'fecha', 'entregadoPor', 'recibidoPor', 'equipos',
            ]);

        $this->assertDatabaseCount('tblIsoActaEntrega', 0);
    }

    public function test_pdf_se_genera_bajo_demanda(): void
    {
        $admin = $this->administrador();
        $acta = $this->acta($admin);

        $this->comoUsuario($admin)
            ->get('/iso9001/FO-IT-02/'.$acta->id_ActaEntrega.'/pdf')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_pdf_con_descarga_sirve_el_archivo(): void
    {
        $admin = $this->administrador();
        $acta = $this->acta($admin);

        $this->comoUsuario($admin)
            ->get('/iso9001/FO-IT-02/'.$acta->id_ActaEntrega.'/pdf?descargar=1')
            ->assertOk()
            ->assertDownload($acta->folio.'.pdf');
    }

    public function test_pdf_de_documento_inexistente_devuelve_404(): void
    {
        $this->comoUsuario($this->administrador())
            ->get('/iso9001/FO-IT-02/9999/pdf')
            ->assertNotFound();
    }

    public function test_pdf_es_fiel_al_formato_oficial(): void
    {
        $admin = $this->administrador();
        $firma = $this->firmaEjemplo();
        $acta = $this->acta($admin, [
            'entregadoPor' => 'Alan Guzman Santos',
            'firmaEntrega' => $firma,
            'fechaFirmaEntrega' => now()->format('Y-m-d H:i:s'),
        ]);

        $html = view('iso9001.formatos.acta_entrega_pdf', [
            'documento' => $acta,
            'cabeceraBase64' => 'data:image/png;base64,dGVzdA==',
        ])->render();

        // Datos generales y encabezados de la tabla del .xlsx.
        $this->assertStringContainsString('Fecha: '.$acta->fecha->format('d/m/Y'), $html);
        $this->assertStringContainsString('Con la presente acta se le hace entrega de lo siguiente:', $html);
        $this->assertStringContainsString('Cantidad', $html);
        $this->assertStringContainsString('No. De serie', $html);
        $this->assertStringContainsString('Condiciones', $html);

        // Renglones capturados.
        $this->assertStringContainsString('Laptop Dell', $html);
        $this->assertStringContainsString('DL123456', $html);
        $this->assertStringContainsString('Usado en buen estado', $html);

        // Texto legal fijo del formato.
        $this->assertStringContainsString('El trabajador manifiesta que:', $html);
        $this->assertStringContainsString('Autorizo expresamente a la empresa', $html);

        // Firmas, nombres y huella.
        $this->assertStringContainsString('Entregado por:', $html);
        $this->assertStringContainsString('Recibido por:', $html);
        $this->assertStringContainsString('Alan Guzman Santos', $html);
        $this->assertStringContainsString($firma, $html);
        $this->assertStringContainsString('Fecha de firma', $html);
        $this->assertStringContainsString((string) $acta->huella, $html);

        // Documento sin firmar: conserva el formato en blanco.
        $pendiente = $this->acta($admin);
        $htmlBlanco = view('iso9001.formatos.acta_entrega_pdf', [
            'documento' => $pendiente,
            'cabeceraBase64' => '',
        ])->render();

        $this->assertStringNotContainsString('Fecha de firma', $htmlBlanco);
        $this->assertStringNotContainsString('data:image/png', $htmlBlanco);
    }

    public function test_eliminar_hace_borrado_logico(): void
    {
        $admin = $this->administrador();
        $acta = $this->acta($admin);

        $this->comoUsuario($admin)
            ->delete('/iso9001/FO-IT-02/'.$acta->id_ActaEntrega)
            ->assertRedirect();

        $this->assertDatabaseHas('tblIsoActaEntrega', [
            'id_ActaEntrega' => $acta->id_ActaEntrega,
            'activo' => false,
        ]);
    }

    public function test_documento_eliminado_desaparece_de_la_lista_y_su_pdf_da_404(): void
    {
        $admin = $this->administrador();
        $acta = $this->acta($admin, ['activo' => false]);

        $this->comoUsuario($admin)
            ->get('/iso9001/FO-IT-02')
            ->assertOk()
            ->assertDontSee($acta->folio);

        $this->comoUsuario($admin)
            ->get('/iso9001/FO-IT-02/'.$acta->id_ActaEntrega.'/pdf')
            ->assertNotFound();
    }

    public function test_firmar_guarda_entrega_y_recepcion_con_su_fecha_y_huella(): void
    {
        $admin = $this->administrador();
        $acta = $this->acta($admin);
        $firma = $this->firmaEjemplo();

        $this->comoUsuario($admin)
            ->post('/iso9001/FO-IT-02/'.$acta->id_ActaEntrega.'/firmar', [
                'firmaEntrega' => $firma,
                'firmaRecepcion' => $this->firmaEjemplo('recepcion'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $acta->refresh();

        $this->assertSame($firma, $acta->firmaEntrega);
        $this->assertNotNull($acta->fechaFirmaEntrega);
        $this->assertNotNull($acta->fechaFirmaRecepcion);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', (string) $acta->huella);
        $this->assertTrue($acta->estaFirmado());
    }

    public function test_firmar_sin_ningun_lienzo_muestra_error(): void
    {
        $admin = $this->administrador();
        $acta = $this->acta($admin);

        $this->comoUsuario($admin)
            ->post('/iso9001/FO-IT-02/'.$acta->id_ActaEntrega.'/firmar', [
                'firmaEntrega' => null,
                'firmaRecepcion' => null,
            ])
            ->assertSessionHasErrors(['firmaEntrega', 'firmaRecepcion']);

        $this->assertFalse($acta->fresh()->estaFirmado());
    }

    public function test_re_firmar_sobrescribe_la_firma_y_actualiza_la_huella(): void
    {
        $admin = $this->administrador();
        $acta = $this->acta($admin, [
            'firmaEntrega' => $this->firmaEjemplo('vieja'),
            'fechaFirmaEntrega' => now()->format('Y-m-d H:i:s'),
        ]);
        $huellaPrevia = $acta->huella;
        $firmaNueva = $this->firmaEjemplo('nueva');

        $this->comoUsuario($admin)
            ->post('/iso9001/FO-IT-02/'.$acta->id_ActaEntrega.'/firmar', [
                'firmaEntrega' => $firmaNueva,
            ])
            ->assertRedirect();

        $acta->refresh();

        $this->assertSame($firmaNueva, $acta->firmaEntrega);
        $this->assertNotSame($huellaPrevia, $acta->huella);
        $this->assertNull($acta->firmaRecepcion, 'La firma de recepción no enviada debe conservarse.');
    }

    public function test_documento_firmado_no_se_puede_eliminar(): void
    {
        $admin = $this->administrador();
        $acta = $this->acta($admin, [
            'firmaEntrega' => $this->firmaEjemplo(),
            'fechaFirmaEntrega' => now()->format('Y-m-d H:i:s'),
        ]);

        $this->comoUsuario($admin)
            ->delete('/iso9001/FO-IT-02/'.$acta->id_ActaEntrega)
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertTrue($acta->fresh()->activo, 'El documento firmado debe seguir activo.');
    }

    public function test_lista_muestra_estado_botones_y_modal_de_firma(): void
    {
        $admin = $this->administrador();
        $this->acta($admin, [
            'firmaEntrega' => $this->firmaEjemplo(),
            'fechaFirmaEntrega' => now()->format('Y-m-d H:i:s'),
        ]);
        $this->acta($admin);

        $this->comoUsuario($admin)
            ->get('/iso9001/FO-IT-02')
            ->assertOk()
            ->assertSee('Firmado')
            ->assertSee('Pendiente')
            ->assertSee('Documento firmado: no se puede eliminar', false)
            ->assertSee('modalFirma', false)
            ->assertSee('cdn.jsdelivr.net/npm/signature_pad', false);
    }

    public function test_huella_se_mantiene_estable_al_regenerar(): void
    {
        $admin = $this->administrador();
        $acta = $this->acta($admin, [
            'firmaEntrega' => $this->firmaEjemplo(),
            'fechaFirmaEntrega' => now()->format('Y-m-d H:i:s'),
        ]);

        $releido = $acta->fresh();

        $this->assertSame($acta->huella, $releido->huella);
        $this->assertSame($releido->huella, $releido->calcularHuella());
    }

    public function test_huella_incluye_los_renglones_de_equipos(): void
    {
        $admin = $this->administrador();
        $acta = $this->acta($admin);

        // Alteración directa de un renglón en BD (sin pasar por el modelo
        // → sin recalcular la huella del acta).
        DB::table('tblIsoActaEntregaEquipo')
            ->where('id_ActaEquipo', $acta->equipos()->first()->id_ActaEquipo)
            ->update(['descripcion' => 'Equipo Alterado']);

        $this->comoUsuario($admin)
            ->get('/iso9001/verificar?folio='.$acta->folio)
            ->assertOk()
            ->assertSee('Huella alterada');
    }

    public function test_verificar_folio_vigente(): void
    {
        $admin = $this->administrador();
        $acta = $this->acta($admin, [
            'firmaEntrega' => $this->firmaEjemplo(),
            'fechaFirmaEntrega' => now()->format('Y-m-d H:i:s'),
        ]);

        $this->comoUsuario($admin)
            ->get('/iso9001/verificar?folio='.$acta->folio)
            ->assertOk()
            ->assertSee('Huella vigente')
            ->assertSee((string) $acta->huella, false)
            ->assertSee('Equipos en el acta')
            ->assertSee('Firma de entrega');
    }

    public function test_verificar_detecta_alteracion_de_datos(): void
    {
        $admin = $this->administrador();
        $acta = $this->acta($admin);

        // Alteración directa en BD (sin pasar por el modelo → sin recalcular).
        DB::table('tblIsoActaEntrega')
            ->where('id_ActaEntrega', $acta->id_ActaEntrega)
            ->update(['entregadoPor' => 'Nombre Alterado']);

        $this->comoUsuario($admin)
            ->get('/iso9001/verificar?folio='.$acta->folio)
            ->assertOk()
            ->assertSee('Huella alterada');
    }

    public function test_verificar_folio_inexistente(): void
    {
        $this->comoUsuario($this->administrador())
            ->get('/iso9001/verificar?folio=FO-IT-02-9999')
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

    public function test_alta_y_firmado_no_incluyen_la_columna_identidad_en_el_update(): void
    {
        // SQL Server rechaza escribir columnas identidad (MySQL las tolera):
        // el UPDATE de la huella generado tras el alta NO debe incluir
        // id_ActaEntrega en su cláusula SET.
        $consultas = [];
        DB::listen(function ($consulta) use (&$consultas): void {
            $consultas[] = $consulta->sql;
        });

        $admin = $this->administrador();

        $this->comoUsuario($admin)
            ->post('/iso9001/FO-IT-02', $this->datosValidos())
            ->assertRedirect();

        $acta = IsoActaEntrega::query()->sole();

        $this->comoUsuario($admin)
            ->post('/iso9001/FO-IT-02/'.$acta->id_ActaEntrega.'/firmar', [
                'firmaEntrega' => $this->firmaEjemplo(),
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
                'id_actaentrega',
                $set,
                "El UPDATE no debe incluir la columna identidad en el SET: {$sql}"
            );
        }

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', (string) $acta->fresh()->huella);
    }

    public function test_firmar_documento_inexistente_devuelve_404(): void
    {
        $this->comoUsuario($this->administrador())
            ->post('/iso9001/FO-IT-02/9999/firmar', [
                'firmaEntrega' => $this->firmaEjemplo(),
            ])
            ->assertNotFound();
    }

    public function test_invitado_no_puede_firmar(): void
    {
        $this->post('/iso9001/FO-IT-02/1/firmar', [
            'firmaEntrega' => $this->firmaEjemplo(),
        ])->assertRedirect(route('login'));
    }
}
