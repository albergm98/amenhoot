<?php

namespace App\Services;

use App\Models\Jugador;
use App\Models\Pregunta;

final readonly class CalculadoraPuntos
{
    public function calcular(Pregunta $pregunta, int $milisegundos, bool $esCorrecta, int $rachaActual): array
    {
        if (! $esCorrecta) {
            return [
                'puntos' => 0,
                'racha' => 0,
                'bonus_racha' => 0,
            ];
        }

        $limiteMs = max(1, $pregunta->segundos_limite * 1000);
        $milisegundos = max(0, min($milisegundos, $limiteMs));
        $puntosBase = (int) round(1000 * (1 - ($milisegundos / $limiteMs) / 2));
        $puntosBase = max(500, min(1000, $puntosBase));

        $nuevaRacha = $rachaActual + 1;
        $bonusRacha = min(500, ($nuevaRacha - 1) * 100);

        return [
            'puntos' => $puntosBase + $bonusRacha,
            'racha' => $nuevaRacha,
            'bonus_racha' => $bonusRacha,
        ];
    }

    public function aplicar(Jugador $jugador, Pregunta $pregunta, int $milisegundos, bool $esCorrecta): array
    {
        $resultado = $this->calcular($pregunta, $milisegundos, $esCorrecta, $jugador->racha);

        $jugador->update([
            'puntuacion' => $jugador->puntuacion + $resultado['puntos'],
            'racha' => $resultado['racha'],
        ]);

        return $resultado;
    }
}
