<?php

namespace Database\Factories;

use App\Models\Cuestionario;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cuestionario>
 */
class CuestionarioFactory extends Factory
{
    protected $model = Cuestionario::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'titulo' => fake()->sentence(3),
            'descripcion' => fake()->optional()->sentence(),
        ];
    }
}
