<?php

namespace App\Services;

use App\Estados\EstadoPartida;
use App\Events\PreguntaCerrada;
use App\Models\Partida;
use App\Models\Pregunta;
use App\Models\Respuesta;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class CierrePregunta
{
    public function ejecutar(Partida $partida): bool
    {
        $cerrada = DB::transaction(function () use ($partida): ?array {
            $bloqueada = Partida::query()->lockForUpdate()->find($partida->id);

            if ($bloqueada === null
                || $bloqueada->estado !== EstadoPartida::MostrandoPregunta
                || $bloqueada->pregunta_actual_id === null) {
                return null;
            }

            $pregunta = Pregunta::query()->with('opciones')->findOrFail($bloqueada->pregunta_actual_id);
            $opcionCorrecta = $pregunta->opciones->firstWhere('es_correcta', true);

            $bloqueada->update([
                'estado' => EstadoPartida::MostrandoResultados,
                'pregunta_iniciada_en' => null,
            ]);

            return [$bloqueada->fresh(), $pregunta, $opcionCorrecta?->id ?? 0];
        });

        if ($cerrada === null) {
            return false;
        }

        [$partidaCerrada, $pregunta, $opcionCorrectaId] = $cerrada;

        event(new PreguntaCerrada(
            $partidaCerrada,
            $pregunta,
            $opcionCorrectaId,
            $this->conteo($pregunta),
            $this->clasificacion($partidaCerrada),
        ));

        return true;
    }

    /**
     * @return Collection<int, array{opcion_id: int, texto: string, total: int, es_correcta: bool}>
     */
    public function conteo(Pregunta $pregunta): Collection
    {
        return $pregunta->opciones->map(fn ($opcion) => [
            'opcion_id' => $opcion->id,
            'texto' => $opcion->texto,
            'total' => Respuesta::query()
                ->where('pregunta_id', $pregunta->id)
                ->where('opcion_id', $opcion->id)
                ->count(),
            'es_correcta' => $opcion->es_correcta,
        ]);
    }

    /**
     * @return Collection<int, array{id: int, apodo: string, puntuacion: int, racha: int}>
     */
    public function clasificacion(Partida $partida): Collection
    {
        return $partida->jugadores()
            ->orderByDesc('puntuacion')
            ->orderBy('id')
            ->get(['id', 'apodo', 'puntuacion', 'racha'])
            ->map(fn ($jugador) => [
                'id' => $jugador->id,
                'apodo' => $jugador->apodo,
                'puntuacion' => $jugador->puntuacion,
                'racha' => $jugador->racha,
            ])
            ->values();
    }
}
