<?php

namespace Database\Factories;

use App\Models\IsoActaEntrega;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IsoActaEntrega>
 */
class IsoActaEntregaFactory extends Factory
{
    protected $model = IsoActaEntrega::class;

    /**
     * Definición del estado base.
     *
     * id_Usuario no se define aquí: el modelo User no tiene factory utilizable
     * (la UserFactory es la del esqueleto de Laravel y no aplica a tblUsuario),
     * por lo que siempre se debe pasar el id del creador:
     *   IsoActaEntrega::factory()->create(['id_Usuario' => $usuario->id_Usuario]);
     *
     * Los renglones de equipos se crean aparte vía la relación equipos():
     *   $acta->equipos()->createMany([...]);
     */
    public function definition(): array
    {
        return [
            'fecha' => now()->toDateString(),
            'entregadoPor' => fake()->name(),
            'recibidoPor' => fake()->name(),
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
