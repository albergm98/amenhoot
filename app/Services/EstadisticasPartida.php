<?php

namespace App\Services;

use App\Models\Jugador;
use App\Models\Partida;
use App\Models\Respuesta;
use Illuminate\Support\Collection;

final readonly class EstadisticasPartida
{
    /**
     * @return array{
     *     resumen: array{
     *         jugadores: int,
     *         preguntas_totales: int,
     *         preguntas_con_respuestas: int,
     *         respuestas: int,
     *         aciertos: int,
     *         fallos: int,
     *         porcentaje_acierto: float,
     *         tiempo_medio_ms: int|null,
     *         puntos_repartidos: int,
     *         duracion_segundos: int|null
     *     },
     *     destacados: array{
     *         mas_rapido: array{id: int, apodo: string, milisegundos: int}|null,
     *         mas_preciso: array{id: int, apodo: string, porcentaje: float, correctas: int}|null,
     *         mayor_puntuacion: array{id: int, apodo: string, puntuacion: int}|null,
     *         mayor_racha: array{id: int, apodo: string, racha: int}|null
     *     },
     *     jugadores: list<array{
     *         id: int,
     *         apodo: string,
     *         puntuacion: int,
     *         correctas: int,
     *         fallos: int,
     *         sin_responder: int,
     *         porcentaje_acierto: float,
     *         tiempo_medio_ms: int|null,
     *         mas_rapida_ms: int|null,
     *         racha_maxima: int,
     *         puntos_ganados: int
     *     }>
     * }
     */
    public function para(Partida $partida): array
    {
        $partida->loadMissing(['jugadores', 'cuestionario.preguntas']);

        $preguntas = $partida->cuestionario->preguntas->sortBy('orden')->values();
        $totalPreguntas = $preguntas->count();
        $idsPreguntas = $preguntas->pluck('id');

        $respuestas = Respuesta::query()
            ->whereIn('jugador_id', $partida->jugadores->pluck('id'))
            ->whereIn('pregunta_id', $idsPreguntas)
            ->get();

        $porJugador = $partida->jugadores
            ->map(fn (Jugador $jugador) => $this->estadisticasJugador(
                $jugador,
                $respuestas->where('jugador_id', $jugador->id)->values(),
                $totalPreguntas,
            ))
            ->sortByDesc('puntuacion')
            ->values()
            ->all();

        $aciertos = (int) $respuestas->where('es_correcta', true)->count();
        $fallos = (int) $respuestas->where('es_correcta', false)->count();
        $totalRespuestas = $respuestas->count();

        $masRapida = $respuestas->sortBy('milisegundos')->first();
        $masRapido = null;
        if ($masRapida !== null) {
            $jugadorRapido = $partida->jugadores->firstWhere('id', $masRapida->jugador_id);
            $masRapido = $jugadorRapido ? [
                'id' => $jugadorRapido->id,
                'apodo' => $jugadorRapido->apodo,
                'milisegundos' => $masRapida->milisegundos,
            ] : null;
        }

        $masPreciso = collect($porJugador)
            ->filter(fn (array $j) => ($j['correctas'] + $j['fallos']) > 0)
            ->sortByDesc(fn (array $j) => [$j['porcentaje_acierto'], $j['correctas']])
            ->first();

        $mayorPuntuacion = collect($porJugador)->first();
        $mayorRacha = collect($porJugador)->sortByDesc('racha_maxima')->first();

        $primera = $respuestas->min('created_at');
        $ultima = $respuestas->max('created_at');
        $duracion = ($primera && $ultima)
            ? max(0, $primera->diffInSeconds($ultima))
            : null;

        return [
            'resumen' => [
                'jugadores' => $partida->jugadores->count(),
                'preguntas_totales' => $totalPreguntas,
                'preguntas_con_respuestas' => $respuestas->pluck('pregunta_id')->unique()->count(),
                'respuestas' => $totalRespuestas,
                'aciertos' => $aciertos,
                'fallos' => $fallos,
                'porcentaje_acierto' => $totalRespuestas > 0
                    ? round(($aciertos / $totalRespuestas) * 100, 1)
                    : 0.0,
                'tiempo_medio_ms' => $totalRespuestas > 0
                    ? (int) round($respuestas->avg('milisegundos'))
                    : null,
                'puntos_repartidos' => (int) $respuestas->sum('puntos'),
                'duracion_segundos' => $duracion,
            ],
            'destacados' => [
                'mas_rapido' => $masRapido,
                'mas_preciso' => $masPreciso ? [
                    'id' => $masPreciso['id'],
                    'apodo' => $masPreciso['apodo'],
                    'porcentaje' => $masPreciso['porcentaje_acierto'],
                    'correctas' => $masPreciso['correctas'],
                ] : null,
                'mayor_puntuacion' => $mayorPuntuacion ? [
                    'id' => $mayorPuntuacion['id'],
                    'apodo' => $mayorPuntuacion['apodo'],
                    'puntuacion' => $mayorPuntuacion['puntuacion'],
                ] : null,
                'mayor_racha' => $mayorRacha && $mayorRacha['racha_maxima'] > 0 ? [
                    'id' => $mayorRacha['id'],
                    'apodo' => $mayorRacha['apodo'],
                    'racha' => $mayorRacha['racha_maxima'],
                ] : null,
            ],
            'jugadores' => $porJugador,
        ];
    }

    /**
     * @param  Collection<int, Respuesta>  $respuestas
     * @return array{
     *     id: int,
     *     apodo: string,
     *     puntuacion: int,
     *     correctas: int,
     *     fallos: int,
     *     sin_responder: int,
     *     porcentaje_acierto: float,
     *     tiempo_medio_ms: int|null,
     *     mas_rapida_ms: int|null,
     *     racha_maxima: int,
     *     puntos_ganados: int
     * }
     */
    private function estadisticasJugador(Jugador $jugador, Collection $respuestas, int $totalPreguntas): array
    {
        $correctas = (int) $respuestas->where('es_correcta', true)->count();
        $fallos = (int) $respuestas->where('es_correcta', false)->count();
        $contestadas = $correctas + $fallos;

        return [
            'id' => $jugador->id,
            'apodo' => $jugador->apodo,
            'puntuacion' => $jugador->puntuacion,
            'correctas' => $correctas,
            'fallos' => $fallos,
            'sin_responder' => max(0, $totalPreguntas - $contestadas),
            'porcentaje_acierto' => $contestadas > 0
                ? round(($correctas / $contestadas) * 100, 1)
                : 0.0,
            'tiempo_medio_ms' => $contestadas > 0
                ? (int) round($respuestas->avg('milisegundos'))
                : null,
            'mas_rapida_ms' => $contestadas > 0
                ? (int) $respuestas->min('milisegundos')
                : null,
            'racha_maxima' => $this->rachaMaxima($respuestas),
            'puntos_ganados' => (int) $respuestas->sum('puntos'),
        ];
    }

    /**
     * @param  Collection<int, Respuesta>  $respuestas
     */
    private function rachaMaxima(Collection $respuestas): int
    {
        $maxima = 0;
        $actual = 0;

        foreach ($respuestas->sortBy('pregunta_id') as $respuesta) {
            if ($respuesta->es_correcta) {
                $actual++;
                $maxima = max($maxima, $actual);
                continue;
            }
            $actual = 0;
        }

        return $maxima;
    }
}
