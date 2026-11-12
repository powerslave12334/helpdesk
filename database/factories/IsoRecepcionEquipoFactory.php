<?php

namespace Database\Factories;

use App\Models\IsoRecepcionEquipo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IsoRecepcionEquipo>
 */
class IsoRecepcionEquipoFactory extends Factory
{
    protected $model = IsoRecepcionEquipo::class;

    /**
     * Definición del estado base.
     *
     * id_Usuario no se define aquí: el modelo User no tiene factory utilizable
     * (la UserFactory es la del esqueleto de Laravel y no aplica a tblUsuario),
     * por lo que siempre se debe pasar el id del creador:
     *   IsoRecepcionEquipo::factory()->create(['id_Usuario' => $usuario->id_Usuario]);
     */
    public function definition(): array
    {
        return [
            'fecha' => now()->toDateString(),
            'motivoEntrega' => fake()->randomElement(IsoRecepcionEquipo::MOTIVOS),
            'tipoActivo' => implode(', ', fake()->randomElements(IsoRecepcionEquipo::TIPOS_ACTIVO, 2)),
            'marca' => fake()->randomElement(['Dell', 'HP', 'Lenovo', 'Samsung', 'Cisco']),
            'modelo' => fake()->randomElement(['Latitude 5420', 'ProBook 440', 'ThinkPad E14', 'Galaxy A15']),
            'noSerie' => strtoupper(fake()->bothify('??########')),
            'imei' => fake()->numerify('###############'),
            'numeroTelefonico' => fake()->numerify('##########'),
            'accesorios' => implode(', ', fake()->randomElements(IsoRecepcionEquipo::ACCESORIOS, 2)),
            'descripcionEntrega' => IsoRecepcionEquipo::DESCRIPCION_DEFECTO,
            'aplicacionGastos' => fake()->randomElement(IsoRecepcionEquipo::APLICACION_GASTOS),
            'gastosReparacion' => 0,
            'revisadoPor' => fake()->name(),
            'entregadoPor' => fake()->name(),
            'fechaCreacion' => now(),
            'activo' => true,
        ];
    }

    /** Documento borrado lógicamente. */
    public function inactivo(): static
    {
        return $this->state(fn (array $attributes): array => ['activo' => false]);
    }
}
