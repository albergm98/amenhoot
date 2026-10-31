<?php

namespace App\Support;

use App\Models\Opcion;
use App\Models\Partida;
use App\Models\Pregunta;
use Illuminate\Support\Collection;

/**
 * Baraja las respuestas de cada pregunta de forma estable por partida,
 * para que anfitrión y jugadores vean el mismo orden (y no siempre la A correcta).
 */
final class OrdenOpciones
{
    /**
     * @return Collection<int, Opcion>
     */
    public static function paraPartida(Partida $partida, Pregunta $pregunta): Collection
    {
        $opciones = $pregunta->relationLoaded('opciones')
            ? $pregunta->opciones
            : $pregunta->opciones()->get();

        $semilla = crc32($partida->id.'-'.$pregunta->id);

        return $opciones
            ->values()
            ->sortBy(fn ($opcion): int => crc32($semilla.'-'.$opcion->id))
            ->values();
    }
}
