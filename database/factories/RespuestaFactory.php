<?php

namespace Database\Factories;

use App\Models\Jugador;
use App\Models\Opcion;
use App\Models\Pregunta;
use App\Models\Respuesta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Respuesta>
 */
class RespuestaFactory extends Factory
{
    protected $model = Respuesta::class;

    public function definition(): array
    {
        return [
            'jugador_id' => Jugador::factory(),
            'pregunta_id' => Pregunta::factory(),
            'opcion_id' => Opcion::factory(),
            'milisegundos' => fake()->numberBetween(500, 15000),
            'es_correcta' => false,
            'puntos' => 0,
        ];
    }
}
