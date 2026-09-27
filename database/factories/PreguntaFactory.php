<?php

namespace Database\Factories;

use App\Models\Cuestionario;
use App\Models\Pregunta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pregunta>
 */
class PreguntaFactory extends Factory
{
    protected $model = Pregunta::class;

    public function definition(): array
    {
        return [
            'cuestionario_id' => Cuestionario::factory(),
            'enunciado' => fake()->sentence().'?',
            'segundos_limite' => 20,
            'orden' => 0,
        ];
    }
}
