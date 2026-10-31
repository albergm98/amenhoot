<?php

use App\Support\Anfitrion;

beforeEach(function () {
    $this->withoutVite();
});

test('sin contraseña redirige al acceso de anfitrión', function () {
    $this->get(route('cuestionarios.index'))
        ->assertRedirect(route('anfitrion.acceso'));
});

test('contraseña incorrecta muestra error', function () {
    $this->from(route('anfitrion.acceso'))
        ->post(route('anfitrion.acceso.guardar'), ['clave' => '999999'])
        ->assertRedirect(route('anfitrion.acceso'))
        ->assertSessionHasErrors('clave')
        ->assertSessionMissing(Anfitrion::SESION_ACCESO);
});

test('contraseña correcta abre cuestionarios', function () {
    $this->post(route('anfitrion.acceso.guardar'), ['clave' => '000001'])
        ->assertRedirect(route('cuestionarios.index'))
        ->assertSessionHas(Anfitrion::SESION_ACCESO, true);

    $this->get(route('cuestionarios.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Anfitrion/Cuestionarios/Index'));
});
