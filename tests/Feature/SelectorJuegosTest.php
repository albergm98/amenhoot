<?php

use App\Support\CatalogoBingo;

beforeEach(function () {
    $this->withoutVite();
});

test('la portada responde ok', function () {
    $this->get(route('home'))->assertOk();
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
            ->has('imagenes.0.etiqueta'));
});

test('bingo agrupa sara en la misma columna', function () {
    $imagenes = collect(CatalogoBingo::imagenes());
    $saras = $imagenes->filter(fn (array $ficha) => $ficha['columna'] === 'sara');

    expect($saras->isNotEmpty())->toBeTrue()
        ->and($saras->pluck('etiqueta')->unique()->all())->toBe(['Sara']);
});
