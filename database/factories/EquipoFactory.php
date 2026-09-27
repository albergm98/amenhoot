<?php

namespace Database\Factories;

use App\Models\Equipo;
use App\Models\Partida;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipo>
 */
class EquipoFactory extends Factory
{
    protected $model = Equipo::class;

    public function definition(): array
    {
        return [
            'partida_id' => Partida::factory(),
            'nombre' => fake()->randomElement(['Estrellas', 'Cometas', 'Nebulosas', 'Pulsars']),
            'color' => fake()->hexColor(),
        ];
    }
}
