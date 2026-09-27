<?php

use App\Estados\EstadoPartida;
use App\Models\Cuestionario;
use App\Models\Jugador;
use App\Models\Opcion;
use App\Models\Partida;
use App\Models\Pregunta;
use App\Models\Respuesta;
use App\Models\User;
use App\Services\CalculadoraPuntos;

function montarPartidaConPregunta(User $anfitrion): array
{
    $cuestionario = Cuestionario::factory()->for($anfitrion)->create();
    $pregunta = Pregunta::factory()->for($cuestionario)->create([
        'segundos_limite' => 20,
        'orden' => 0,
    ]);
    $correcta = Opcion::factory()->for($pregunta)->correcta()->create(['orden' => 0, 'texto' => 'A']);
    Opcion::factory()->for($pregunta)->create(['orden' => 1, 'texto' => 'B']);
    Opcion::factory()->for($pregunta)->create(['orden' => 2, 'texto' => 'C']);
    Opcion::factory()->for($pregunta)->create(['orden' => 3, 'texto' => 'D']);

    $partida = Partida::factory()->for($cuestionario)->create([
        'estado' => EstadoPartida::MostrandoPregunta,
        'pregunta_actual_id' => $pregunta->id,
        'pregunta_iniciada_en' => now()->subMilliseconds(2000),
    ]);

    $jugador = Jugador::factory()->for($partida)->create(['apodo' => 'Ranger']);

    return compact('cuestionario', 'pregunta', 'correcta', 'partida', 'jugador');
}

test('responder suma puntos por velocidad y racha', function () {
    $anfitrion = User::factory()->create();
    ['partida' => $partida, 'jugador' => $jugador, 'correcta' => $correcta, 'pregunta' => $pregunta] = montarPartidaConPregunta($anfitrion);

    $this->withHeaders(['X-Jugador-Token' => $jugador->token])
        ->postJson(route('jugador.responder', $partida), ['opcion_id' => $correcta->id])
        ->assertOk()
        ->assertJsonPath('es_correcta', true);

    $jugador->refresh();
    expect($jugador->puntuacion)->toBeGreaterThanOrEqual(500)
        ->and($jugador->racha)->toBe(1)
        ->and(Respuesta::query()->where('jugador_id', $jugador->id)->count())->toBe(1);

    // Segunda pregunta para racha
    $pregunta2 = Pregunta::factory()->for($partida->cuestionario)->create(['orden' => 1, 'segundos_limite' => 20]);
    $correcta2 = Opcion::factory()->for($pregunta2)->correcta()->create(['orden' => 0]);
    Opcion::factory()->for($pregunta2)->count(3)->create();

    $partida->refresh()->update([
        'estado' => EstadoPartida::MostrandoPregunta,
        'pregunta_actual_id' => $pregunta2->id,
        'pregunta_iniciada_en' => now()->subMilliseconds(1000),
    ]);

    $this->withHeaders(['X-Jugador-Token' => $jugador->token])
        ->postJson(route('jugador.responder', $partida), ['opcion_id' => $correcta2->id])
        ->assertOk()
        ->assertJsonPath('racha', 2)
        ->assertJsonPath('bonus_racha', 100);

    $jugador->refresh();
    expect($jugador->racha)->toBe(2);
});

test('fallo reinicia la racha', function () {
    $anfitrion = User::factory()->create();
    ['partida' => $partida, 'jugador' => $jugador, 'pregunta' => $pregunta] = montarPartidaConPregunta($anfitrion);
    $jugador->update(['racha' => 3]);
    $incorrecta = $pregunta->opciones()->where('es_correcta', false)->first();

    $this->withHeaders(['X-Jugador-Token' => $jugador->token])
        ->postJson(route('jugador.responder', $partida), ['opcion_id' => $incorrecta->id])
        ->assertOk()
        ->assertJsonPath('es_correcta', false)
        ->assertJsonPath('racha', 0);

    expect($jugador->fresh()->racha)->toBe(0);
});

test('cuando responden todos se cierra la pregunta', function () {
    $anfitrion = User::factory()->create();
    ['partida' => $partida, 'jugador' => $jugador, 'correcta' => $correcta] = montarPartidaConPregunta($anfitrion);
    $segundo = Jugador::factory()->for($partida)->create(['apodo' => 'Cometa']);

    $this->withHeaders(['X-Jugador-Token' => $jugador->token])
        ->postJson(route('jugador.responder', $partida), ['opcion_id' => $correcta->id])
        ->assertOk();

    expect($partida->fresh()->estado)->toBe(EstadoPartida::MostrandoPregunta);

    $this->withHeaders(['X-Jugador-Token' => $segundo->token])
        ->postJson(route('jugador.responder', $partida), ['opcion_id' => $correcta->id])
        ->assertOk();

    expect($partida->fresh()->estado)->toBe(EstadoPartida::MostrandoResultados);
});

test('no se puede responder dos veces', function () {
    $anfitrion = User::factory()->create();
    ['partida' => $partida, 'jugador' => $jugador, 'correcta' => $correcta] = montarPartidaConPregunta($anfitrion);

    $this->withHeaders(['X-Jugador-Token' => $jugador->token])
        ->postJson(route('jugador.responder', $partida), ['opcion_id' => $correcta->id])
        ->assertOk();

    $this->withHeaders(['X-Jugador-Token' => $jugador->token])
        ->postJson(route('jugador.responder', $partida), ['opcion_id' => $correcta->id])
        ->assertStatus(422);
});

test('respuesta fuera de tiempo se rechaza', function () {
    $anfitrion = User::factory()->create();
    ['partida' => $partida, 'jugador' => $jugador, 'correcta' => $correcta, 'pregunta' => $pregunta] = montarPartidaConPregunta($anfitrion);

    $partida->update([
        'pregunta_iniciada_en' => now()->subSeconds($pregunta->segundos_limite + 2),
    ]);

    $this->withHeaders(['X-Jugador-Token' => $jugador->token])
        ->postJson(route('jugador.responder', $partida), ['opcion_id' => $correcta->id])
        ->assertStatus(422);
});

test('calculadora respeta rango 500-1000 y bonus de racha', function () {
    $pregunta = new Pregunta(['segundos_limite' => 20]);
    $calc = new CalculadoraPuntos;

    $rapido = $calc->calcular($pregunta, 0, true, 0);
    expect($rapido['puntos'])->toBe(1000)->and($rapido['racha'])->toBe(1);

    $lento = $calc->calcular($pregunta, 20000, true, 0);
    expect($lento['puntos'])->toBe(500);

    $conRacha = $calc->calcular($pregunta, 0, true, 4);
    expect($conRacha['bonus_racha'])->toBe(400)->and($conRacha['puntos'])->toBe(1400);

    $maxRacha = $calc->calcular($pregunta, 0, true, 10);
    expect($maxRacha['bonus_racha'])->toBe(500);
});
