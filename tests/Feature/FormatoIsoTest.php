<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Departamento;
use App\Models\Rol;
use App\Models\RolesUsuario;
use App\Models\TipoEmpleado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormatoIsoTest extends TestCase
{
    use RefreshDatabase;

    /** Crea un usuario con rol administrador (ve todos los módulos). */
    private function administrador(): User
    {
        $rol = Rol::create([
            'nombre' => 'Administrador',
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
            'app' => 'ISO',
            'apm' => 'Test',
            'correo' => 'iso@test.local',
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

    /** Envia la petición como el usuario indicado, con IdUsuario en sesión. */
    private function comoUsuario(User $usuario): static
    {
        return $this->actingAs($usuario)->withSession([
            'IdUsuario' => $usuario->id_Usuario,
            'NombreUsuario' => $usuario->nombreCompleto(),
        ]);
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get('/iso9001')->assertRedirect(route('login'));
    }

    public function test_panel_muestra_los_18_formatos(): void
    {
        $this->comoUsuario($this->administrador())
            ->get('/iso9001')
            ->assertOk()
            ->assertSee('ISO 9001')
            ->assertSee('FO-IT-01')
            ->assertSee('Responsiva ERP')
            ->assertSee('FO-IT-21')
            ->assertSee('Inventario GPS');
    }

    public function test_panel_no_incluye_formatos_fuera_del_catalogo(): void
    {
        $this->comoUsuario($this->administrador())
            ->get('/iso9001')
            ->assertDontSee('FO-IT-10');
    }

    public function test_formato_habilitado_muestra_su_lista_de_documentos(): void
    {
        $this->comoUsuario($this->administrador())
            ->get('/iso9001/FO-IT-01')
            ->assertOk()
            ->assertSee('Responsiva ERP')
            ->assertSee('Nuevo documento')
            ->assertSee('Aún no se han generado documentos')
            ->assertDontSee('Formato en construcción');
    }

    public function test_formato_sin_controlador_muestra_el_marcador_de_construccion(): void
    {
        $this->comoUsuario($this->administrador())
            ->get('/iso9001/FO-IT-03')
            ->assertOk()
            ->assertSee('Recepción de equipo')
            ->assertSee('Formato en construcción');
    }

    public function test_formato_inexistente_devuelve_404(): void
    {
        $this->comoUsuario($this->administrador())
            ->get('/iso9001/FO-IT-99')
            ->assertNotFound();
    }

    public function test_guardar_en_formato_aun_no_habilitado_devuelve_404(): void
    {
        $this->comoUsuario($this->administrador())
            ->post('/iso9001/FO-IT-03')
            ->assertNotFound();
    }
}
