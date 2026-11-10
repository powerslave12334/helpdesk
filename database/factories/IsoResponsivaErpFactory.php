<?php

namespace Database\Factories;

use App\Models\IsoResponsivaErp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IsoResponsivaErp>
 */
class IsoResponsivaErpFactory extends Factory
{
    protected $model = IsoResponsivaErp::class;

    /**
     * Definición del estado base.
     *
     * id_Usuario no se define aquí: el modelo User no tiene factory utilizable
     * (la UserFactory es la del esqueleto de Laravel y no aplica a tblUsuario),
     * por lo que siempre se debe pasar el id del creador:
     *   IsoResponsivaErp::factory()->create(['id_Usuario' => $usuario->id_Usuario]);
     */
    public function definition(): array
    {
        return [
            'fecha' => now()->toDateString(),
            'nombreEmpleado' => fake()->name(),
            'puesto' => fake()->jobTitle(),
            'correo' => fake()->unique()->safeEmail(),
            'usuarioErp' => fake()->unique()->userName(),
            'contrasenaErp' => 'Temp-'.fake()->numerify('####'),
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
