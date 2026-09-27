<?php

use App\Estados\EstadoPartida;
use App\Models\Cuestionario;
use App\Models\Equipo;
use App\Models\Jugador;
use App\Models\Partida;
use App\Models\User;

beforeEach(function () {
    $this->anfitrion = User::factory()->create();
});

function crearPartidaEsperando(bool $conEquipos = false): Partida
{
    $partida = Partida::factory()
        ->for(
            Cuestionario::factory()->for(test()->anfitrion),
        )
        ->state(['modo_equipos' => $conEquipos])
        ->create();

    if ($conEquipos) {
        Equipo::factory()->for($partida)->create(['nombre' => 'Estrellas']);
        Equipo::factory()->for($partida)->create(['nombre' => 'Cometas']);
    }

    return $partida->fresh('equipos');
}

test('un jugador puede unirse con pin y apodo', function () {
    $partida = crearPartidaEsperando();

    $this->post(route('jugador.unirse.guardar'), [
        'pin' => $partida->pin,
        'apodo' => 'Nova',
    ])->assertRedirect(route('jugador.partida', $partida));

    expect(Jugador::query()->where('apodo', 'Nova')->exists())->toBeTrue();
});

test('pin invalido rechaza el acceso', function () {
    $this->post(route('jugador.unirse.guardar'), [
        'pin' => '000000',
        'apodo' => 'Nova',
    ])->assertSessionHasErrors('pin');
});

test('apodo repetido se rechaza', function () {
    $partida = crearPartidaEsperando();
    Jugador::factory()->for($partida)->create(['apodo' => 'Nova']);

    $this->post(route('jugador.unirse.guardar'), [
        'pin' => $partida->pin,
        'apodo' => 'nova',
    ])->assertSessionHasErrors('apodo');
});

test('no se puede unir si la partida ya empezo', function () {
    $partida = crearPartidaEsperando();
    $partida->update(['estado' => EstadoPartida::MostrandoPregunta]);

    $this->post(route('jugador.unirse.guardar'), [
        'pin' => $partida->pin,
        'apodo' => 'Nova',
    ])->assertSessionHasErrors('pin');
});

test('en modo equipos hay que elegir equipo', function () {
    $partida = crearPartidaEsperando(true);

    $this->post(route('jugador.unirse.guardar'), [
        'pin' => $partida->pin,
        'apodo' => 'Nova',
    ])->assertSessionHasErrors('equipo_id');

    $this->post(route('jugador.unirse.guardar'), [
        'pin' => $partida->pin,
        'apodo' => 'Nova',
        'equipo_id' => $partida->equipos->first()->id,
    ])->assertRedirect(route('jugador.partida', $partida));
});
