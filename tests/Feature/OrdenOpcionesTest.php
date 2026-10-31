<?php

use App\Models\Cuestionario;
use App\Models\Opcion;
use App\Models\Partida;
use App\Models\Pregunta;
use App\Models\User;
use App\Support\OrdenOpciones;

test('las opciones se barajan de forma estable por partida', function () {
    $anfitrion = User::factory()->create();
    $cuestionario = Cuestionario::factory()->for($anfitrion)->create();
    $pregunta = Pregunta::factory()->for($cuestionario)->create(['orden' => 0]);

    foreach (['A', 'B', 'C', 'D'] as $indice => $texto) {
        Opcion::factory()->for($pregunta)->create([
            'texto' => $texto,
            'orden' => $indice,
            'es_correcta' => $indice === 0,
        ]);
    }

    $pregunta->load('opciones');
    $partidaA = Partida::factory()->for($cuestionario)->create();
    $partidaB = Partida::factory()->for($cuestionario)->create();

    $ordenA1 = OrdenOpciones::paraPartida($partidaA, $pregunta)->pluck('texto')->all();
    $ordenA2 = OrdenOpciones::paraPartida($partidaA, $pregunta)->pluck('texto')->all();
    $ordenB = OrdenOpciones::paraPartida($partidaB, $pregunta)->pluck('texto')->all();
    $ordenBd = $pregunta->opciones->pluck('texto')->all();

    expect($ordenA1)->toBe($ordenA2)
        ->and($ordenA1)->toHaveCount(4)
        ->and(collect($ordenA1)->sort()->values()->all())->toBe(['A', 'B', 'C', 'D'])
        ->and($ordenA1 === $ordenB || $ordenA1 !== $ordenBd)->toBeTrue();
});
