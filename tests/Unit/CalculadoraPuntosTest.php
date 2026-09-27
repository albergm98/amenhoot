<?php

use App\Models\Pregunta;
use App\Services\CalculadoraPuntos;

test('puntos base entre 500 y 1000', function () {
    $calc = new CalculadoraPuntos;
    $pregunta = new Pregunta(['segundos_limite' => 10]);

    expect($calc->calcular($pregunta, 0, true, 0)['puntos'])->toBe(1000)
        ->and($calc->calcular($pregunta, 10000, true, 0)['puntos'])->toBe(500)
        ->and($calc->calcular($pregunta, 5000, false, 2)['puntos'])->toBe(0)
        ->and($calc->calcular($pregunta, 5000, false, 2)['racha'])->toBe(0);
});
