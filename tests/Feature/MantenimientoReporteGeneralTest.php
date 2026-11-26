<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Departamento;
use App\Models\Equipo;
use App\Models\Estatus;
use App\Models\Mantenimiento;
use App\Models\PeriodoMantenimiento;
use App\Models\Rol;
use App\Models\RolesUsuario;
use App\Models\TipoEmpleado;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MantenimientoReporteGeneralTest extends TestCase
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
            'app' => 'Mant',
            'apm' => 'Test',
            'correo' => 'mant@test.local',
            'id_Depto' => $depto->id_Depto,
            'id_Area' => $area->id_Area,
            'id_TipoEmpleado' => $tipoEmpleado->id_TipoEmpleado,
            'esTecnico' => true,
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

    /** Crea el equipo mínimo con su periodo de mantenimiento. */
    private function equipoConPeriodo(int $meses): Equipo
    {
        $estatus = Estatus::create([
            'descripcion' => 'Operativo',
            'fechaAlta' => now(),
            'activo' => true,
        ]);

        $tipoEquipo = TipoEquipo::create([
            'descripcion' => 'Computadora',
            'fechaAlta' => now(),
            'activo' => true,
        ]);

        $periodo = PeriodoMantenimiento::create([
            'descripcion' => 'Semestral',
            'meses' => $meses,
            'activo' => true,
        ]);

        return Equipo::create([
            'marca' => 'Dell',
            'modelo' => 'OptiPlex',
            'id_TipoEquipo' => $tipoEquipo->id_TipoEquipo,
            'id_Estatus' => $estatus->id_Estatus,
            'nombreEquipo' => 'EQ-PRUEBA',
            'id_PeriodoMantenimiento' => $periodo->id_PeriodoMantenimiento,
            'fechaAlta' => now(),
            'activo' => true,
            'esCompartido' => false,
        ]);
    }

    /** Crea un mantenimiento y lo regresa. */
    private function mantenimiento(Equipo $equipo, User $tecnico, ?string $fechaEjecucion): Mantenimiento
    {
        return Mantenimiento::create([
            'id_Equipo' => $equipo->id_Equipo,
            'id_UsuarioTecnico' => $tecnico->id_Usuario,
            'fechaProgramada' => '2026-01-15 10:00:00',
            'fechaEjecucion' => $fechaEjecucion,
            'tipoMantenimiento' => 'Preventivo',
            'observaciones' => 'Se realizó limpieza general.',
            'fechaAlta' => now(),
            'activo' => true,
        ]);
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get('/inventario/mantenimientos/reporte-general')
            ->assertRedirect(route('login'));
    }

    public function test_reporte_general_descarga_un_pdf(): void
    {
        $usuario = $this->administrador();
        $equipo = $this->equipoConPeriodo(6);
        $this->mantenimiento($equipo, $usuario, '2026-01-20 11:30:00');

        $response = $this->comoUsuario($usuario)
            ->get('/inventario/mantenimientos/reporte-general');

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString(
            'Reporte_General_Mantenimientos.pdf',
            (string) $response->headers->get('content-disposition')
        );
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
    }

    public function test_reporte_general_funciona_sin_mantenimientos_completados(): void
    {
        $usuario = $this->administrador();
        $equipo = $this->equipoConPeriodo(6);
        // Solo un mantenimiento pendiente (sin fecha de ejecución): no debe incluirse.
        $this->mantenimiento($equipo, $usuario, null);

        $response = $this->comoUsuario($usuario)
            ->get('/inventario/mantenimientos/reporte-general');

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
    }

    public function test_reporte_general_tolera_equipo_con_periodo_inexistente(): void
    {
        $usuario = $this->administrador();
        $equipo = $this->equipoConPeriodo(6);
        // Dato inconsistente: el FK apunta a un periodo que ya no existe.
        $equipo->forceFill(['id_PeriodoMantenimiento' => 99])->save();
        $this->mantenimiento($equipo, $usuario, '2026-01-20 11:30:00');

        $response = $this->comoUsuario($usuario)
            ->get('/inventario/mantenimientos/reporte-general');

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
    }

    public function test_vista_del_reporte_incluye_las_tres_secciones(): void
    {
        $usuario = $this->administrador();
        $equipo = $this->equipoConPeriodo(6);
        $mantenimiento = $this->mantenimiento($equipo, $usuario, '2026-01-20 11:30:00');

        $html = view('inventario.reporte_mantenimiento_general', [
            'logoBase64' => '',
            'mantenimientos' => collect([
                [
                    'mantenimiento' => $mantenimiento,
                    'nombreEquipo' => 'EQ-PRUEBA',
                    'usuarioAsignado' => 'Prueba Mant',
                    'area' => 'Soporte TI',
                    'tecnicoNombre' => 'Prueba Mant',
                    'periodoDescripcion' => 'Bimestral',
                    'meses' => 2,
                    'proximaFechaSugerida' => '20/03/2026',
                ],
            ]),
        ])->render();

        $this->assertStringContainsString('1. MANTENIMIENTOS REALIZADOS', $html);
        $this->assertStringContainsString('3. TIEMPO DE MANTENIMIENTO RECOMENDADO', $html);
        $this->assertStringContainsString('3. ACTIVIDADES REALIZADAS POR MANTENIMIENTO', $html);
        $this->assertStringContainsString('Cada 2 meses', $html);
        $this->assertStringContainsString('Bimestral', $html);
        $this->assertStringContainsString('EQ-PRUEBA', $html);
        $this->assertStringContainsString('20/03/2026', $html);
    }
}
