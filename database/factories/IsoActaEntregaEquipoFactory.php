<?php

namespace Database\Factories;

use App\Models\IsoActaEntrega;
use App\Models\IsoActaEntregaEquipo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IsoActaEntregaEquipo>
 */
class IsoActaEntregaEquipoFactory extends Factory
{
    protected $model = IsoActaEntregaEquipo::class;

    /**
     * Definición del estado base.
     *
     * id_ActaEntrega no se define aquí: siempre se debe pasar el acta padre
     * (directamente o con ->for($acta)):
     *   $acta->equipos()->create([...])  // vía relación (recomendado)
     *   IsoActaEntregaEquipo::factory()->create(['id_ActaEntrega' => $acta->id_ActaEntrega]);
     */
    public function definition(): array
    {
        return [
            'cantidad' => fake()->numberBetween(1, 3),
            'descripcion' => fake()->randomElement(['Laptop', 'Monitor', 'Teclado', 'Mouse', 'Teléfono']),
            'marca' => fake()->randomElement(['Dell', 'HP', 'Lenovo', 'Samsung', 'Cisco']),
            'modelo' => fake()->randomElement(['Latitude 5420', 'ProBook 440', 'ThinkPad E14', 'LED 24"']),
            'noSerie' => strtoupper(fake()->bothify('??########')),
            'condiciones' => fake()->randomElement(IsoActaEntrega::CONDICIONES),
        ];
    }

    /** Renglón sin marca/modelo/no. de serie (campos opcionales). */
    public function sinDetalles(): static
    {
        return $this->state(fn (array $attributes): array => [
            'marca' => null,
            'modelo' => null,
            'noSerie' => null,
        ]);
    }
}
