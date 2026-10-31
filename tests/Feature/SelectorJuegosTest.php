<?php

use App\Support\CatalogoBingo;
use App\Support\CatalogoPersonas;

beforeEach(function () {
    $this->withoutVite();
});

test('la portada responde ok y trae collage del bingo', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Welcome')
            ->has('fotosCollage')
            ->where('fotosCollage.0', fn ($url) => is_string($url) && str_contains($url, '/assets/img/bingo/')));
});

test('amenhoot inicio responde ok', function () {
    $this->get(route('amenhoot.inicio'))->assertOk();
});

test('bingo sala responde ok y trae imagenes', function () {
    $this->get(route('bingo.sala'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Bingo/Sala')
            ->has('imagenes')
            ->where('imagenes.0.id', 1)
            ->has('imagenes.0.url')
            ->has('imagenes.0.columna')
            ->has('imagenes.0.etiqueta')
            ->where('prioridad', []));
});

test('bingo sala amañada trae prioridad en /bingo/1', function () {
    $this->get(route('bingo.sala', ['codigo' => 1]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Bingo/Sala')
            ->has('prioridad', 14)
            ->where('prioridad.0', 'paula10.jpeg'));
});

test('bingo agrupa sara en la misma columna', function () {
    $imagenes = collect(CatalogoBingo::imagenes());
    $saras = $imagenes->filter(fn (array $ficha) => $ficha['columna'] === 'sara');

    expect($saras->isNotEmpty())->toBeTrue()
        ->and($saras->pluck('etiqueta')->unique()->all())->toBe(['Sara']);
});

test('amenhoot reutiliza las personas del bingo', function () {
    expect(CatalogoPersonas::todas())->toHaveCount(10);
});
