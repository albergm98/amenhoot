<?php

namespace Database\Factories;

use App\Estados\EstadoPartida;
use App\Models\Cuestionario;
use App\Models\Partida;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Partida>
 */
class PartidaFactory extends Factory
{
    protected $model = Partida::class;

    public function definition(): array
    {
        return [
            'cuestionario_id' => Cuestionario::factory(),
            'pin' => (string) fake()->unique()->numberBetween(100000, 999999),
            'estado' => EstadoPartida::EsperandoJugadores,
            'modo_equipos' => false,
            'pregunta_actual_id' => null,
            'pregunta_iniciada_en' => null,
        ];
    }

    public function conEquipos(): static
    {
        return $this->state(fn () => ['modo_equipos' => true]);
    }
}
