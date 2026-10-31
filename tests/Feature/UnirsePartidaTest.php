<?php

use App\Estados\EstadoPartida;
use App\Models\Cuestionario;
use App\Models\Equipo;
use App\Models\Jugador;
use App\Models\Partida;
use App\Models\User;
use App\Support\CatalogoPersonas;

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

function primeraPersona(): array
{
    $persona = CatalogoPersonas::todas()[0] ?? null;
    expect($persona)->not->toBeNull();

    return $persona;
}

test('un jugador puede unirse eligiendo persona del bingo', function () {
    $partida = crearPartidaEsperando();
    $persona = primeraPersona();

    $this->post(route('jugador.unirse.guardar'), [
        'pin' => $partida->pin,
        'persona' => $persona['clave'],
    ])->assertRedirect(route('jugador.partida', $partida));

    $jugador = Jugador::query()->where('apodo', $persona['nombre'])->first();

    expect($jugador)->not->toBeNull()
        ->and($jugador->foto)->toBe($persona['foto']);
});

test('pin invalido rechaza el acceso', function () {
    $persona = primeraPersona();

    $this->post(route('jugador.unirse.guardar'), [
        'pin' => '000000',
        'persona' => $persona['clave'],
    ])->assertSessionHasErrors('pin');
});

test('persona repetida se rechaza', function () {
    $partida = crearPartidaEsperando();
    $persona = primeraPersona();
    Jugador::factory()->for($partida)->create([
        'apodo' => $persona['nombre'],
        'foto' => $persona['foto'],
    ]);

    $this->post(route('jugador.unirse.guardar'), [
        'pin' => $partida->pin,
        'persona' => $persona['clave'],
    ])->assertSessionHasErrors('persona');
});

test('no se puede unir si la partida ya empezo', function () {
    $partida = crearPartidaEsperando();
    $partida->update(['estado' => EstadoPartida::MostrandoPregunta]);
    $persona = primeraPersona();

    $this->post(route('jugador.unirse.guardar'), [
        'pin' => $partida->pin,
        'persona' => $persona['clave'],
    ])->assertSessionHasErrors('pin');
});

test('en modo equipos hay que elegir equipo', function () {
    $partida = crearPartidaEsperando(true);
    $persona = primeraPersona();

    $this->post(route('jugador.unirse.guardar'), [
        'pin' => $partida->pin,
        'persona' => $persona['clave'],
    ])->assertSessionHasErrors('equipo_id');

    $this->post(route('jugador.unirse.guardar'), [
        'pin' => $partida->pin,
        'persona' => $persona['clave'],
        'equipo_id' => $partida->equipos->first()->id,
    ])->assertRedirect(route('jugador.partida', $partida));
});

test('el formulario de unirse trae las personas del bingo', function () {
    $this->withoutVite()
        ->get(route('jugador.unirse'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Jugador/Unirse')
            ->has('personas')
            ->where('personas.0.clave', fn ($clave) => is_string($clave) && $clave !== '')
            ->has('personas.0.foto')
            ->has('personas.0.nombre'));
});

test('catalogo personas tiene diez nombres del bingo', function () {
    $personas = CatalogoPersonas::todas();
    $porClave = collect($personas)->keyBy('clave');

    expect($personas)->toHaveCount(10)
        ->and($porClave->keys()->all())
        ->toEqualCanonicalizing([
            'alberto', 'angie', 'edu', 'eva', 'joseja', 'luis', 'manu', 'pablo', 'paula', 'sara',
        ])
        ->and($porClave['alberto']['foto'])->toEndWith('/alberto3.jpeg')
        ->and($porClave['angie']['foto'])->toEndWith('/angie4.jpeg')
        ->and($porClave['joseja']['foto'])->toEndWith('/joseja3.jpeg')
        ->and($porClave['luis']['foto'])->toEndWith('/luis10.jpeg')
        ->and($porClave['manu']['foto'])->toEndWith('/manu9.jpeg')
        ->and($porClave['pablo']['foto'])->toEndWith('/pablo6.jpeg')
        ->and($porClave['paula']['foto'])->toEndWith('/paula6.jpeg')
        ->and($porClave['sara']['foto'])->toEndWith('/sara6.jpeg');
});
