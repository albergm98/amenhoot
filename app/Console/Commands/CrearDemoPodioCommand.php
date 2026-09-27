<?php

namespace App\Console\Commands;

use App\Estados\EstadoPartida;
use App\Models\Cuestionario;
use App\Models\Jugador;
use App\Models\Opcion;
use App\Models\Partida;
use App\Models\Pregunta;
use App\Models\User;
use Illuminate\Console\Command;

final class CrearDemoPodioCommand extends Command
{
    protected $signature = 'amenhoot:demo-podio';

    protected $description = 'Crea una partida finalizada con ranking de prueba para ver el podio';

    public function handle(): int
    {
        $anfitrion = User::query()->firstOrCreate(
            ['email' => 'anfitrion@stellar.test'],
            ['name' => 'Anfitrión', 'password' => 'password'],
        );

        $cuestionario = Cuestionario::query()->create([
            'user_id' => $anfitrion->id,
            'titulo' => 'Demo podio Amenhoot',
            'descripcion' => 'Partida de prueba para ver la clasificación final',
        ]);

        $pregunta = Pregunta::query()->create([
            'cuestionario_id' => $cuestionario->id,
            'enunciado' => '¿Pregunta demo?',
            'segundos_limite' => 20,
            'orden' => 0,
        ]);

        foreach (['A', 'B', 'C', 'D'] as $indice => $letra) {
            Opcion::query()->create([
                'pregunta_id' => $pregunta->id,
                'texto' => "Opción {$letra}",
                'es_correcta' => $indice === 0,
                'orden' => $indice,
            ]);
        }

        $partida = Partida::query()->create([
            'cuestionario_id' => $cuestionario->id,
            'pin' => Partida::generarPin(),
            'estado' => EstadoPartida::Finalizada,
            'modo_equipos' => false,
        ]);

        $apodos = ['Joseja', 'Manu', 'Pablo', 'Lucía', 'Hugo', 'Sofía', 'Leo', 'Emma'];
        $puntos = [9800, 8400, 7200, 6100, 4800, 3500, 2200, 900];

        foreach ($apodos as $indice => $apodo) {
            Jugador::query()->create([
                'partida_id' => $partida->id,
                'apodo' => $apodo,
                'token' => Jugador::generarToken(),
                'puntuacion' => $puntos[$indice],
                'racha' => fake()->numberBetween(0, 4),
            ]);
        }

        $url = url("/partidas/{$partida->id}");

        $this->info('Partida demo lista.');
        $this->line('Anfitrión: anfitrion@stellar.test / password');
        $this->line("URL: {$url}");

        return self::SUCCESS;
    }
}
