<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Departamento;
use App\Models\IsoRecepcionEquipo;
use App\Models\Rol;
use App\Models\RolesUsuario;
use App\Models\TipoEmpleado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RecepcionEquipoTest extends TestCase
{
    use RefreshDatabase;

    /** Datos válidos de captura (grupos de checkboxes incluidos). */
    private function datosValidos(): array
    {
        return [
            'fecha' => '2026-11-11',
            'motivoEntrega' => 'Cambio de Activo',
            'tipoActivo' => ['Computadora', 'Laptop'],
            'marca' => 'Dell',
            'modelo' => 'Latitude 5420',
            'noSerie' => 'DL123456',
            'imei' => '123456789012345',
            'numeroTelefonico' => '5512345678',
            'accesorios' => ['Cargador', 'Mouse Inalámbrico'],
            'descripcionEntrega' => 'Se recibe equipo en buenas condiciones',
            'aplicacionGastos' => 'Con cargo a Alcomex',
            'gastosReparacion' => 0,
            'revisadoPor' => 'Juan Jefe Sistemas',
            'entregadoPor' => 'Ana Entrega Todo',
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
            'apm' => 'IT03',
            'correo' => 'fo.it03@test.local',
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

    /** Crea un documento activo (convierte las selecciones múltiples a CSV). */
    private function documento(User $creador, array $datos = []): IsoRecepcionEquipo
    {
        $datos = array_merge($this->datosValidos(), $datos);

        // La BD guarda las selecciones múltiples como lista por comas.
        $datos['tipoActivo'] = implode(', ', (array) $datos['tipoActivo']);
        $datos['accesorios'] = implode(', ', (array) $datos['accesorios']);

        return IsoRecepcionEquipo::factory()->create([
            ...$datos,
            'id_Usuario' => $creador->id_Usuario,
        ]);
    }

    /** PNG de 1x1 en data-URI, válido para las reglas del FormRequest. */
    private function firmaEjemplo(string $contenido = 'firma'): string
    {
        return 'data:image/png;base64,'.base64_encode($contenido);
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get('/iso9001/FO-IT-03')->assertRedirect(route('login'));
    }

    public function test_usuario_sin_submodulo_iso9001_es_redirigido_al_dashboard(): void
    {
        $this->comoUsuario($this->usuarioConRol('Empleado'))
            ->get('/iso9001/FO-IT-03')
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');
    }

    public function test_lista_muestra_la_tabla_con_el_boton_de_nuevo_documento(): void
    {
        $admin = $this->administrador();
        $documento = $this->documento($admin);

        $this->comoUsuario($admin)
            ->get('/iso9001/FO-IT-03')
            ->assertOk()
            ->assertSee('Nuevo documento')
            ->assertSee($documento->folio)
            ->assertSee('Cambio de Activo')
            ->assertSee('DL123456');
    }

    public function test_formulario_muestra_los_grupos_y_los_valores_por_defecto(): void
    {
        $this->comoUsuario($this->administrador())
            ->get('/iso9001/FO-IT-03/nuevo')
            ->assertOk()
            ->assertSee('name="fecha"', false)
            ->assertSee('name="motivoEntrega"', false)
            ->assertSee('name="tipoActivo[]"', false)
            ->assertSee('name="accesorios[]"', false)
            ->assertSee('name="aplicacionGastos"', false)
            ->assertSee('name="gastosReparacion"', false)
            ->assertSee('name="revisadoPor"', false)
            ->assertSee('name="entregadoPor"', false)
            // Catálogos de los grupos de checkboxes.
            ->assertSee('Baja de Personal')
            ->assertSee('Kit inalambrico Teclado')
            ->assertSee('Sin Accesorios')
            ->assertSee('Con cargo a Cliente')
            // Valores por defecto del formato oficial.
            ->assertSee('Se recibe equipo en buenas condiciones')
            ->assertSee('value="0"', false);
    }

    public function test_guardar_documento_valido_lo_regresa_a_la_lista(): void
    {
        $admin = $this->administrador();

        $this->comoUsuario($admin)
            ->post('/iso9001/FO-IT-03', $this->datosValidos())
            ->assertRedirect(route('iso9001.documentos', ['clave' => 'FO-IT-03']))
            ->assertSessionHas('success');

        $documento = IsoRecepcionEquipo::query()->sole();

        $this->assertTrue($documento->fecha->isSameDay('2026-11-11'));
        $this->assertSame('Cambio de Activo', $documento->motivoEntrega);
        $this->assertSame('Computadora, Laptop', $documento->tipoActivo);
        $this->assertSame('Dell', $documento->marca);
        $this->assertSame('Latitude 5420', $documento->modelo);
        $this->assertSame('DL123456', $documento->noSerie);
        $this->assertSame('123456789012345', $documento->imei);
        $this->assertSame('Cargador, Mouse Inalámbrico', $documento->accesorios);
        $this->assertSame('Se recibe equipo en buenas condiciones', $documento->descripcionEntrega);
        $this->assertSame('Con cargo a Alcomex', $documento->aplicacionGastos);
        $this->assertSame('0.00', $documento->gastosReparacion);
        $this->assertSame('Juan Jefe Sistemas', $documento->revisadoPor);
        $this->assertSame('Ana Entrega Todo', $documento->entregadoPor);
        $this->assertSame($admin->id_Usuario, $documento->id_Usuario);
        $this->assertTrue($documento->activo);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', (string) $documento->huella);
    }

    public function test_las_selecciones_multiples_se_guardan_como_listas(): void
    {
        $admin = $this->administrador();

        $this->comoUsuario($admin)
            ->post('/iso9001/FO-IT-03', $this->datosValidos())
            ->assertRedirect();

        $documento = IsoRecepcionEquipo::query()->sole();

        $this->assertSame(['Computadora', 'Laptop'], $documento->tiposActivos());
        $this->assertSame(['Cargador', 'Mouse Inalámbrico'], $documento->accesoriosMarcados());
    }

    public function test_guardar_con_motivo_fuera_del_catalogo_muestra_error(): void
    {
        $datos = $this->datosValidos();
        $datos['motivoEntrega'] = 'Motivo Inventado';

        $this->comoUsuario($this->administrador())
            ->post('/iso9001/FO-IT-03', $datos)
            ->assertSessionHasErrors('motivoEntrega');

        $this->assertDatabaseCount('tblIsoRecepcionEquipo', 0);
    }

    public function test_guardar_con_tipo_de_activo_fuera_del_catalogo_muestra_error(): void
    {
        $datos = $this->datosValidos();
        $datos['tipoActivo'][] = 'Tablet Inventada';

        $this->comoUsuario($this->administrador())
            ->post('/iso9001/FO-IT-03', $datos)
            ->assertSessionHasErrors('tipoActivo.2');

        $this->assertDatabaseCount('tblIsoRecepcionEquipo', 0);
    }

    public function test_guardar_con_accesorio_fuera_del_catalogo_muestra_error(): void
    {
        $datos = $this->datosValidos();
        $datos['accesorios'] = ['Base de carga'];

        $this->comoUsuario($this->administrador())
            ->post('/iso9001/FO-IT-03', $datos)
            ->assertSessionHasErrors('accesorios.0');

        $this->assertDatabaseCount('tblIsoRecepcionEquipo', 0);
    }

    public function test_guardar_sin_selecciones_muestra_errores(): void
    {
        $datos = $this->datosValidos();
        unset($datos['motivoEntrega'], $datos['tipoActivo'], $datos['accesorios'], $datos['aplicacionGastos']);

        $this->comoUsuario($this->administrador())
            ->post('/iso9001/FO-IT-03', $datos)
            ->assertSessionHasErrors([
                'motivoEntrega', 'tipoActivo', 'accesorios', 'aplicacionGastos',
            ]);

        $this->assertDatabaseCount('tblIsoRecepcionEquipo', 0);
    }

    public function test_guardar_con_campos_vacios_muestra_errores(): void
    {
        $this->comoUsuario($this->administrador())
            ->post('/iso9001/FO-IT-03', [])
            ->assertSessionHasErrors([
                'fecha', 'marca', 'modelo', 'noSerie', 'descripcionEntrega',
                'gastosReparacion', 'revisadoPor', 'entregadoPor',
            ]);

        $this->assertDatabaseCount('tblIsoRecepcionEquipo', 0);
    }

    public function test_imei_y_numero_telefonico_vacios_se_guardan_como_null(): void
    {
        $datos = $this->datosValidos();
        $datos['imei'] = '';
        $datos['numeroTelefonico'] = '   ';

        $this->comoUsuario($this->administrador())
            ->post('/iso9001/FO-IT-03', $datos)
            ->assertRedirect();

        $documento = IsoRecepcionEquipo::query()->sole();

        $this->assertNull($documento->imei);
        $this->assertNull($documento->numeroTelefonico);
    }

    public function test_gastos_reparacion_admite_decimales(): void
    {
        $datos = $this->datosValidos();
        $datos['gastosReparacion'] = 1500.5;

        $this->comoUsuario($this->administrador())
            ->post('/iso9001/FO-IT-03', $datos)
            ->assertRedirect();

        $this->assertSame('1500.50', IsoRecepcionEquipo::query()->sole()->gastosReparacion);
    }

    public function test_pdf_se_genera_bajo_demanda(): void
    {
        $admin = $this->administrador();
        $documento = $this->documento($admin);

        $this->comoUsuario($admin)
            ->get('/iso9001/FO-IT-03/'.$documento->id_RecepcionEquipo.'/pdf')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_pdf_con_descarga_sirve_el_archivo(): void
    {
        $admin = $this->administrador();
        $documento = $this->documento($admin);

        $this->comoUsuario($admin)
            ->get('/iso9001/FO-IT-03/'.$documento->id_RecepcionEquipo.'/pdf?descargar=1')
            ->assertOk()
            ->assertDownload($documento->folio.'.pdf');
    }

    public function test_pdf_de_documento_inexistente_devuelve_404(): void
    {
        $this->comoUsuario($this->administrador())
            ->get('/iso9001/FO-IT-03/9999/pdf')
            ->assertNotFound();
    }

    public function test_pdf_es_fiel_al_formato_oficial(): void
    {
        $admin = $this->administrador();
        $firma = $this->firmaEjemplo();
        $documento = $this->documento($admin, [
            'firmaRevision' => $firma,
            'fechaFirmaRevision' => now()->format('Y-m-d H:i:s'),
        ]);

        $html = view('iso9001.formatos.recepcion_equipo_pdf', [
            'documento' => $documento,
            'cabeceraBase64' => 'data:image/png;base64,dGVzdA==',
        ])->render();

        // Encabezados y secciones del .xlsx.
        $this->assertStringContainsString('FECHA: 11/11/2026', $html);
        $this->assertStringContainsString('Motivo de la Entrega:', $html);
        $this->assertStringContainsString('Tipo de activo entregado:', $html);
        $this->assertStringContainsString('Identificacion del Activo:', $html);
        $this->assertStringContainsString('Accesorios del Activo:', $html);
        $this->assertStringContainsString('Descripcion de la Entrega :', $html);
        $this->assertStringContainsString('Aplicacion de Gastos de Reparación:', $html);
        $this->assertStringContainsString('Gastos de Reparación:', $html);

        // Opciones de todos los grupos de checkboxes.
        $this->assertStringContainsString('Baja de Personal', $html);
        $this->assertStringContainsString('Kit inalambrico Teclado', $html);
        $this->assertStringContainsString('Sin Accesorios', $html);
        $this->assertStringContainsString('Con cargo a Alcomex', $html);

        // Datos capturados y valores por defecto.
        $this->assertStringContainsString('Marca: Dell', $html);
        $this->assertStringContainsString('Numero de serie: DL123456', $html);
        $this->assertStringContainsString('Se recibe equipo en buenas condiciones', $html);
        $this->assertStringContainsString('$ 0', $html);

        // Casillas marcadas para las opciones seleccionadas.
        $this->assertStringContainsString('casilla marcada', $html);

        // Firmas, roles fijos del formato y huella.
        $this->assertStringContainsString('Realiza Revisión:', $html);
        $this->assertStringContainsString('Entrega Activo:', $html);
        $this->assertStringContainsString('Jefe de Sistemas', $html);
        $this->assertStringContainsString('Persona', $html);
        $this->assertStringContainsString('Juan Jefe Sistemas', $html);
        $this->assertStringContainsString($firma, $html);
        $this->assertStringContainsString('Fecha de firma', $html);
        $this->assertStringContainsString((string) $documento->huella, $html);

        // Documento sin firmar: conserva el formato en blanco.
        $pendiente = $this->documento($admin);
        $htmlBlanco = view('iso9001.formatos.recepcion_equipo_pdf', [
            'documento' => $pendiente,
            'cabeceraBase64' => '',
        ])->render();

        $this->assertStringNotContainsString('Fecha de firma', $htmlBlanco);
        $this->assertStringNotContainsString('data:image/png', $htmlBlanco);
    }

    public function test_eliminar_hace_borrado_logico(): void
    {
        $admin = $this->administrador();
        $documento = $this->documento($admin);

        $this->comoUsuario($admin)
            ->delete('/iso9001/FO-IT-03/'.$documento->id_RecepcionEquipo)
            ->assertRedirect();

        $this->assertDatabaseHas('tblIsoRecepcionEquipo', [
            'id_RecepcionEquipo' => $documento->id_RecepcionEquipo,
            'activo' => false,
        ]);
    }

    public function test_documento_eliminado_desaparece_de_la_lista_y_su_pdf_da_404(): void
    {
        $admin = $this->administrador();
        $documento = $this->documento($admin, ['activo' => false]);

        $this->comoUsuario($admin)
            ->get('/iso9001/FO-IT-03')
            ->assertOk()
            ->assertDontSee($documento->folio);

        $this->comoUsuario($admin)
            ->get('/iso9001/FO-IT-03/'.$documento->id_RecepcionEquipo.'/pdf')
            ->assertNotFound();
    }

    public function test_firmar_guarda_revision_y_entrega_con_su_fecha_y_huella(): void
    {
        $admin = $this->administrador();
        $documento = $this->documento($admin);
        $firma = $this->firmaEjemplo();

        $this->comoUsuario($admin)
            ->post('/iso9001/FO-IT-03/'.$documento->id_RecepcionEquipo.'/firmar', [
                'firmaRevision' => $firma,
                'firmaEntrega' => $this->firmaEjemplo('entrega'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $documento->refresh();

        $this->assertSame($firma, $documento->firmaRevision);
        $this->assertNotNull($documento->fechaFirmaRevision);
        $this->assertNotNull($documento->fechaFirmaEntrega);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', (string) $documento->huella);
        $this->assertTrue($documento->estaFirmado());
    }

    public function test_firmar_sin_ningun_lienzo_muestra_error(): void
    {
        $admin = $this->administrador();
        $documento = $this->documento($admin);

        $this->comoUsuario($admin)
            ->post('/iso9001/FO-IT-03/'.$documento->id_RecepcionEquipo.'/firmar', [
                'firmaRevision' => null,
                'firmaEntrega' => null,
            ])
            ->assertSessionHasErrors(['firmaRevision', 'firmaEntrega']);

        $this->assertFalse($documento->fresh()->estaFirmado());
    }

    public function test_re_firmar_sobrescribe_la_firma_y_actualiza_la_huella(): void
    {
        $admin = $this->administrador();
        $documento = $this->documento($admin, [
            'firmaRevision' => $this->firmaEjemplo('vieja'),
            'fechaFirmaRevision' => now()->format('Y-m-d H:i:s'),
        ]);
        $huellaPrevia = $documento->huella;
        $firmaNueva = $this->firmaEjemplo('nueva');

        $this->comoUsuario($admin)
            ->post('/iso9001/FO-IT-03/'.$documento->id_RecepcionEquipo.'/firmar', [
                'firmaRevision' => $firmaNueva,
            ])
            ->assertRedirect();

        $documento->refresh();

        $this->assertSame($firmaNueva, $documento->firmaRevision);
        $this->assertNotSame($huellaPrevia, $documento->huella);
        $this->assertNull($documento->firmaEntrega, 'La firma de entrega no enviada debe conservarse.');
    }

    public function test_documento_firmado_no_se_puede_eliminar(): void
    {
        $admin = $this->administrador();
        $documento = $this->documento($admin, [
            'firmaEntrega' => $this->firmaEjemplo(),
            'fechaFirmaEntrega' => now()->format('Y-m-d H:i:s'),
        ]);

        $this->comoUsuario($admin)
            ->delete('/iso9001/FO-IT-03/'.$documento->id_RecepcionEquipo)
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertTrue($documento->fresh()->activo, 'El documento firmado debe seguir activo.');
    }

    public function test_lista_muestra_estado_botones_y_modal_de_firma(): void
    {
        $admin = $this->administrador();
        $this->documento($admin, [
            'firmaRevision' => $this->firmaEjemplo(),
            'fechaFirmaRevision' => now()->format('Y-m-d H:i:s'),
        ]);
        $this->documento($admin);

        $this->comoUsuario($admin)
            ->get('/iso9001/FO-IT-03')
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
        $documento = $this->documento($admin, [
            'firmaRevision' => $this->firmaEjemplo(),
            'fechaFirmaRevision' => now()->format('Y-m-d H:i:s'),
        ]);

        $releido = $documento->fresh();

        $this->assertSame($documento->huella, $releido->huella);
        $this->assertSame($releido->huella, $releido->calcularHuella());
    }

    public function test_huella_incluye_los_datos_del_documento(): void
    {
        $admin = $this->administrador();
        $documento = $this->documento($admin);

        // Alteración directa en BD (sin pasar por el modelo → sin recalcular).
        DB::table('tblIsoRecepcionEquipo')
            ->where('id_RecepcionEquipo', $documento->id_RecepcionEquipo)
            ->update(['entregadoPor' => 'Nombre Alterado']);

        $this->comoUsuario($admin)
            ->get('/iso9001/verificar?folio='.$documento->folio)
            ->assertOk()
            ->assertSee('Huella alterada');
    }

    public function test_verificar_folio_vigente(): void
    {
        $admin = $this->administrador();
        $documento = $this->documento($admin, [
            'firmaRevision' => $this->firmaEjemplo(),
            'fechaFirmaRevision' => now()->format('Y-m-d H:i:s'),
        ]);

        $this->comoUsuario($admin)
            ->get('/iso9001/verificar?folio='.$documento->folio)
            ->assertOk()
            ->assertSee('Huella vigente')
            ->assertSee((string) $documento->huella, false)
            ->assertSee('Motivo de la entrega')
            ->assertSee('Firma de revisión');
    }

    public function test_verificar_detecta_alteracion_de_datos(): void
    {
        $admin = $this->administrador();
        $documento = $this->documento($admin);

        // Alteración directa en BD (sin pasar por el modelo → sin recalcular).
        DB::table('tblIsoRecepcionEquipo')
            ->where('id_RecepcionEquipo', $documento->id_RecepcionEquipo)
            ->update(['marca' => 'Marca Alterada']);

        $this->comoUsuario($admin)
            ->get('/iso9001/verificar?folio='.$documento->folio)
            ->assertOk()
            ->assertSee('Huella alterada');
    }

    public function test_verificar_folio_inexistente(): void
    {
        $this->comoUsuario($this->administrador())
            ->get('/iso9001/verificar?folio=FO-IT-03-9999')
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
        // id_RecepcionEquipo en su cláusula SET.
        $consultas = [];
        DB::listen(function ($consulta) use (&$consultas): void {
            $consultas[] = $consulta->sql;
        });

        $admin = $this->administrador();

        $this->comoUsuario($admin)
            ->post('/iso9001/FO-IT-03', $this->datosValidos())
            ->assertRedirect();

        $documento = IsoRecepcionEquipo::query()->sole();

        $this->comoUsuario($admin)
            ->post('/iso9001/FO-IT-03/'.$documento->id_RecepcionEquipo.'/firmar', [
                'firmaRevision' => $this->firmaEjemplo(),
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
                'id_recepcionequipo',
                $set,
                "El UPDATE no debe incluir la columna identidad en el SET: {$sql}"
            );
        }

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', (string) $documento->fresh()->huella);
    }

    public function test_firmar_documento_inexistente_devuelve_404(): void
    {
        $this->comoUsuario($this->administrador())
            ->post('/iso9001/FO-IT-03/9999/firmar', [
                'firmaRevision' => $this->firmaEjemplo(),
            ])
            ->assertNotFound();
    }

    public function test_invitado_no_puede_firmar(): void
    {
        $this->post('/iso9001/FO-IT-03/1/firmar', [
            'firmaRevision' => $this->firmaEjemplo(),
        ])->assertRedirect(route('login'));
    }
}
