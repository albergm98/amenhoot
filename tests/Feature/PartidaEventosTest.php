<?php

use App\Estados\EstadoPartida;
use App\Events\JugadorUnido;
use App\Events\PartidaFinalizada;
use App\Events\PreguntaCerrada;
use App\Events\PreguntaIniciada;
use App\Models\Cuestionario;
use App\Models\Equipo;
use App\Models\Jugador;
use App\Models\Opcion;
use App\Models\Partida;
use App\Models\Pregunta;
use App\Support\Anfitrion;
use Illuminate\Support\Facades\Event;

test('iniciar pregunta emite PreguntaIniciada', function () {
    Event::fake([PreguntaIniciada::class]);

    $cuestionario = Cuestionario::factory()->for(Anfitrion::usuario())->create();
    $pregunta = Pregunta::factory()->for($cuestionario)->create(['orden' => 0]);
    Opcion::factory()->for($pregunta)->correcta()->create();
    Opcion::factory()->for($pregunta)->count(3)->create();

    $partida = Partida::factory()->for($cuestionario)->create();
    Jugador::factory()->for($partida)->create();

    $this->post(route('partidas.iniciar-pregunta', $partida))
        ->assertRedirect();

    Event::assertDispatched(PreguntaIniciada::class);
    expect($partida->fresh()->estado)->toBe(EstadoPartida::MostrandoPregunta);
});

test('cerrar pregunta emite PreguntaCerrada', function () {
    Event::fake([PreguntaCerrada::class]);

    $cuestionario = Cuestionario::factory()->for(Anfitrion::usuario())->create();
    $pregunta = Pregunta::factory()->for($cuestionario)->create();
    Opcion::factory()->for($pregunta)->correcta()->create();
    Opcion::factory()->for($pregunta)->count(3)->create();

    $partida = Partida::factory()->for($cuestionario)->create([
        'estado' => EstadoPartida::MostrandoPregunta,
        'pregunta_actual_id' => $pregunta->id,
        'pregunta_iniciada_en' => now(),
    ]);

    $this->post(route('partidas.cerrar-pregunta', $partida))
        ->assertRedirect();

    Event::assertDispatched(PreguntaCerrada::class);
    expect($partida->fresh()->estado)->toBe(EstadoPartida::MostrandoResultados);
});

test('finalizar emite PartidaFinalizada con media de equipos', function () {
    Event::fake([PartidaFinalizada::class]);

    $cuestionario = Cuestionario::factory()->for(Anfitrion::usuario())->create();
    $partida = Partida::factory()->for($cuestionario)->create([
        'estado' => EstadoPartida::MostrandoResultados,
        'modo_equipos' => true,
    ]);
    $equipoA = Equipo::factory()->for($partida)->create(['nombre' => 'A']);
    $equipoB = Equipo::factory()->for($partida)->create(['nombre' => 'B']);
    Jugador::factory()->for($partida)->create(['equipo_id' => $equipoA->id, 'puntuacion' => 1000, 'apodo' => 'Uno']);
    Jugador::factory()->for($partida)->create(['equipo_id' => $equipoA->id, 'puntuacion' => 500, 'apodo' => 'Dos']);
    Jugador::factory()->for($partida)->create(['equipo_id' => $equipoB->id, 'puntuacion' => 800, 'apodo' => 'Tres']);

    $this->post(route('partidas.finalizar', $partida))
        ->assertRedirect();

    Event::assertDispatched(PartidaFinalizada::class, function (PartidaFinalizada $evento) {
        $mediaA = $evento->podioEquipos->firstWhere('nombre', 'A')['puntuacion'] ?? null;

        return $mediaA === 750;
    });
});

test('unirse emite JugadorUnido', function () {
    Event::fake([JugadorUnido::class]);

    $partida = Partida::factory()
        ->for(Cuestionario::factory()->for(Anfitrion::usuario()))
        ->create();

    $this->post(route('jugador.unirse.guardar'), [
        'pin' => $partida->pin,
        'apodo' => 'Eco',
    ])->assertRedirect();

    Event::assertDispatched(JugadorUnido::class);
});
