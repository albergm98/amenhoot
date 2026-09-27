<?php

namespace Database\Factories;

use App\Models\Opcion;
use App\Models\Pregunta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Opcion>
 */
class OpcionFactory extends Factory
{
    protected $model = Opcion::class;

    public function definition(): array
    {
        return [
            'pregunta_id' => Pregunta::factory(),
            'texto' => fake()->words(3, true),
            'es_correcta' => false,
            'orden' => 0,
        ];
    }

    public function correcta(): static
    {
        return $this->state(fn () => ['es_correcta' => true]);
    }
}
