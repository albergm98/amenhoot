<?php

namespace Database\Factories;

use App\Models\Jugador;
use App\Models\Partida;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Jugador>
 */
class JugadorFactory extends Factory
{
    protected $model = Jugador::class;

    public function definition(): array
    {
        return [
            'partida_id' => Partida::factory(),
            'equipo_id' => null,
            'apodo' => fake()->unique()->userName(),
            'token' => Jugador::generarToken(),
            'puntuacion' => 0,
            'racha' => 0,
        ];
    }
}
