<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Departamento;
use App\Models\Rol;
use App\Models\RolesUsuario;
use App\Models\TipoEmpleado;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Acceso a los reportes KPI (FO-SGC-08): solo roles Administrador y Root.
 * Los empleados no ven los botones ni pueden descargar por URL directa.
 */
class KpiPermisoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * El dashboard y los reportes KPI usan funciones MySQL (MONTH,
     * TIMESTAMPDIFF, DATE_FORMAT); sqlite no las trae, así que se registran
     * aquí para poder probarlos en el suite.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $pdo = DB::connection()->getPdo();

        $pdo->sqliteCreateFunction(
            'MONTH',
            fn ($fecha): int => (int) date('n', strtotime((string) $fecha)),
            1
        );

        $pdo->sqliteCreateFunction(
            'TIMESTAMPDIFF',
            function ($unidad, $desde, $hasta): int {
                $segundos = strtotime((string) $hasta) - strtotime((string) $desde);

                return match (strtoupper((string) $unidad)) {
                    'MINUTE' => intdiv($segundos, 60),
                    'HOUR' => intdiv($segundos, 3600),
                    'DAY' => intdiv($segundos, 86400),
                    default => $segundos,
                };
            },
            3
        );

        $pdo->sqliteCreateFunction(
            'DATE_FORMAT',
            fn ($fecha, $formato): string => date(
                strtr((string) $formato, ['%Y' => 'Y', '%m' => 'm', '%d' => 'd']),
                strtotime((string) $fecha)
            ),
            2
        );

        $this->crearTablasSinMigracion();
    }

    /**
     * tblModulosSistemas y tblSubmodulosSistemas no tienen migración (se
     * crearon fuera de Laravel, ver DATABASE_SCHEMA.md); el reporte KPI
     * Desarrollo hace JOIN con ellas, así que se recrean aquí para sqlite.
     */
    private function crearTablasSinMigracion(): void
    {
        if (! Schema::hasTable('tblModulosSistemas')) {
            Schema::create('tblModulosSistemas', function (Blueprint $table) {
                $table->id('id_ModulosSistemas');
                $table->string('nombre', 50);
                $table->unsignedBigInteger('id_sistemas');
                $table->boolean('enabled');
            });
        }

        if (! Schema::hasTable('tblSubmodulosSistemas')) {
            Schema::create('tblSubmodulosSistemas', function (Blueprint $table) {
                $table->id('id_SubmodulosSistemas');
                $table->string('nombre', 50);
                $table->unsignedBigInteger('id_ModulosSistemas');
                $table->boolean('enabled');
            });
        }
    }

    /** Crea un usuario con el rol indicado, en el área dada. */
    private function usuarioConRol(string $nombreRol, ?string $nombreArea = null): User
    {
        $rol = Rol::create([
            'nombre' => $nombreRol,
            'activo' => true,
            'fechaAlta' => now(),
        ]);

        $depto = Departamento::create([
            'nombre' => 'TI',
            'activo' => true,
            'fechaAlta' => now(),
        ]);

        $area = Area::create([
            'nombre' => $nombreArea ?? 'Operaciones',
            'activo' => true,
            'fechaAlta' => now(),
        ]);

        $tipoEmpleado = TipoEmpleado::create([
            'nombre' => 'Administrativo',
            'activo' => true,
            'fechaAlta' => now(),
        ]);

        $usuario = User::create([
            'nombre' => 'Kpi',
            'app' => 'Prueba',
            'apm' => 'Test',
            'correo' => strtolower($nombreRol).'@test.local',
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

    /** Rutas de descarga de los tres reportes KPI. */
    private function rutasKpi(): array
    {
        return [
            'dashboard.reporte-kpi',
            'dashboard.reporte-kpi-desarrollo',
            'dashboard.reporte-kpi-gps',
        ];
    }

    public function test_empleado_no_ve_botones_de_descarga_kpi_en_el_dashboard(): void
    {
        // Empleado en el área que antes habilitaba los KPI: ya no debe verlos.
        $this->comoUsuario($this->usuarioConRol('Empleado', 'Soporte TI'))
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Descargar KPI', false);
    }

    public function test_empleado_recibe_403_en_las_tres_rutas_de_descarga(): void
    {
        $usuario = $this->usuarioConRol('Empleado', 'Soporte TI');

        foreach ($this->rutasKpi() as $ruta) {
            $this->comoUsuario($usuario)
                ->get(route($ruta))
                ->assertForbidden();
        }
    }

    public function test_administrador_ve_los_botones_de_descarga_kpi(): void
    {
        $this->comoUsuario($this->usuarioConRol('Administrador'))
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Descargar KPI', false);
    }

    public function test_root_puede_descargar_los_reportes_kpi(): void
    {
        foreach ($this->rutasKpi() as $ruta) {
            $this->comoUsuario($this->usuarioConRol('Root'))
                ->get(route($ruta))
                ->assertOk();
        }
    }

    public function test_tecnico_sin_rol_admin_no_descarga_kpi(): void
    {
        $usuario = $this->usuarioConRol('Técnico', 'Soporte TI');

        foreach ($this->rutasKpi() as $ruta) {
            $this->comoUsuario($usuario)
                ->get(route($ruta))
                ->assertForbidden();
        }
    }
}
