<?php

namespace App\Http\Controllers;

use App\Estados\EstadoPartida;
use App\Events\PartidaFinalizada;
use App\Events\PartidaPorEmpezar;
use App\Events\PreguntaIniciada;
use App\Http\Requests\CrearPartidaRequest;
use App\Models\Cuestionario;
use App\Models\Equipo;
use App\Models\Partida;
use App\Models\Pregunta;
use App\Models\Respuesta;
use App\Services\CierrePregunta;
use App\Support\Anfitrion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class PartidaController extends Controller
{
    public function store(CrearPartidaRequest $request, Cuestionario $cuestionario): RedirectResponse
    {
        $this->asegurarAnfitrion($cuestionario);
        abort_if($cuestionario->preguntas()->count() === 0, 422, 'El cuestionario no tiene preguntas.');

        $partida = DB::transaction(function () use ($request, $cuestionario) {
            $partida = Partida::query()->create([
                'cuestionario_id' => $cuestionario->id,
                'pin' => Partida::generarPin(),
                'estado' => EstadoPartida::EsperandoJugadores,
                'modo_equipos' => $request->boolean('modo_equipos'),
            ]);

            if ($partida->modo_equipos) {
                foreach ($request->validated('equipos', []) as $equipo) {
                    $partida->equipos()->create([
                        'nombre' => $equipo['nombre'],
                        'color' => $equipo['color'],
                    ]);
                }
            }

            return $partida;
        });

        return redirect()->route('partidas.show', $partida);
    }

    public function show(Partida $partida, CierrePregunta $cierre): Response
    {
        $partida->load([
            'cuestionario.preguntas.opciones',
            'jugadores.equipo',
            'equipos.jugadores',
            'preguntaActual.opciones',
        ]);

        $this->asegurarAnfitrion($partida->cuestionario);

        return Inertia::render('Anfitrion/Partida', [
            'partida' => $this->serializarPartidaAnfitrion($partida, $cierre),
        ]);
    }

    public function prepararInicio(Partida $partida): RedirectResponse
    {
        $this->asegurarAnfitrion($partida->cuestionario);
        abort_unless($partida->estado === EstadoPartida::EsperandoJugadores, 422);
        abort_if($partida->jugadores()->count() === 0, 422, 'No hay jugadores.');

        event(new PartidaPorEmpezar($partida, 5));

        return back();
    }

    public function iniciarPregunta(Partida $partida): RedirectResponse
    {
        $this->asegurarAnfitrion($partida->cuestionario);
        abort_unless(in_array($partida->estado, [
            EstadoPartida::EsperandoJugadores,
            EstadoPartida::MostrandoResultados,
        ], true), 422);

        $siguiente = $this->siguientePregunta($partida);
        abort_if($siguiente === null, 422, 'No quedan preguntas.');

        $siguiente->load('opciones');

        $partida->update([
            'estado' => EstadoPartida::MostrandoPregunta,
            'pregunta_actual_id' => $siguiente->id,
            'pregunta_iniciada_en' => now(),
        ]);

        event(new PreguntaIniciada($partida->fresh(), $siguiente));

        return back();
    }

    public function cerrarPregunta(Partida $partida, CierrePregunta $cierre): RedirectResponse
    {
        $this->asegurarAnfitrion($partida->cuestionario);
        $cierre->ejecutar($partida);

        return back();
    }

    public function finalizar(Partida $partida): RedirectResponse
    {
        $this->asegurarAnfitrion($partida->cuestionario);
        abort_unless(in_array($partida->estado, [
            EstadoPartida::MostrandoResultados,
            EstadoPartida::EsperandoJugadores,
        ], true), 422);

        $partida->load(['jugadores.equipo', 'equipos.jugadores']);

        $podio = $partida->jugadores
            ->sortByDesc('puntuacion')
            ->values()
            ->map(fn ($jugador) => [
                'id' => $jugador->id,
                'apodo' => $jugador->apodo,
                'puntuacion' => $jugador->puntuacion,
                'racha' => $jugador->racha,
                'equipo' => $jugador->equipo?->nombre,
            ]);

        $podioEquipos = null;
        if ($partida->modo_equipos) {
            $podioEquipos = $partida->equipos
                ->map(fn (Equipo $equipo) => [
                    'id' => $equipo->id,
                    'nombre' => $equipo->nombre,
                    'color' => $equipo->color,
                    'puntuacion' => $equipo->puntuacionMedia(),
                ])
                ->sortByDesc('puntuacion')
                ->values();
        }

        $partida->update([
            'estado' => EstadoPartida::Finalizada,
            'pregunta_actual_id' => null,
            'pregunta_iniciada_en' => null,
        ]);

        event(new PartidaFinalizada($partida->fresh(), $podio, $podioEquipos));

        return back();
    }

    private function siguientePregunta(Partida $partida): ?Pregunta
    {
        $preguntas = $partida->cuestionario->preguntas()->orderBy('orden')->get();

        if ($partida->pregunta_actual_id === null) {
            return $preguntas->first();
        }

        $indiceActual = $preguntas->search(fn (Pregunta $p) => $p->id === $partida->pregunta_actual_id);

        if ($indiceActual === false) {
            return $preguntas->first();
        }

        return $preguntas->get($indiceActual + 1);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializarPartidaAnfitrion(Partida $partida, CierrePregunta $cierre): array
    {
        $mostrandoResultados = $partida->estado === EstadoPartida::MostrandoResultados;
        $pregunta = $partida->preguntaActual;

        return [
            'id' => $partida->id,
            'pin' => $partida->pin,
            'estado' => $partida->estado->value,
            'modo_equipos' => $partida->modo_equipos,
            'pregunta_iniciada_en' => $partida->pregunta_iniciada_en?->toIso8601String(),
            'cuestionario' => [
                'id' => $partida->cuestionario->id,
                'titulo' => $partida->cuestionario->titulo,
                'total_preguntas' => $partida->cuestionario->preguntas->count(),
            ],
            'pregunta_actual' => $pregunta ? [
                'id' => $pregunta->id,
                'enunciado' => $pregunta->enunciado,
                'segundos_limite' => $pregunta->segundos_limite,
                'orden' => $pregunta->orden,
                'opciones' => $pregunta->opciones->map(fn ($o) => [
                    'id' => $o->id,
                    'texto' => $o->texto,
                    'orden' => $o->orden,
                    'es_correcta' => $mostrandoResultados ? $o->es_correcta : null,
                ]),
            ] : null,
            'conteo_opciones' => $mostrandoResultados && $pregunta ? $cierre->conteo($pregunta) : [],
            'clasificacion' => $mostrandoResultados ? $cierre->clasificacion($partida) : [],
            'total_respuestas' => $pregunta
                ? Respuesta::query()->where('pregunta_id', $pregunta->id)->count()
                : 0,
            'jugadores' => $partida->jugadores->map(fn ($j) => [
                'id' => $j->id,
                'apodo' => $j->apodo,
                'puntuacion' => $j->puntuacion,
                'racha' => $j->racha,
                'equipo_id' => $j->equipo_id,
                'equipo' => $j->equipo?->nombre,
            ]),
            'equipos' => $partida->equipos->map(fn ($e) => [
                'id' => $e->id,
                'nombre' => $e->nombre,
                'color' => $e->color,
                'jugadores' => $e->jugadores->pluck('apodo'),
                'puntuacion' => $e->puntuacionMedia(),
            ]),
            'podio' => $partida->estado === EstadoPartida::Finalizada
                ? $partida->jugadores
                    ->sortByDesc('puntuacion')
                    ->values()
                    ->map(fn ($j) => [
                        'id' => $j->id,
                        'apodo' => $j->apodo,
                        'puntuacion' => $j->puntuacion,
                        'racha' => $j->racha,
                        'equipo' => $j->equipo?->nombre,
                    ])
                : [],
            'podio_equipos' => $partida->estado === EstadoPartida::Finalizada && $partida->modo_equipos
                ? $partida->equipos
                    ->map(fn (Equipo $equipo) => [
                        'id' => $equipo->id,
                        'nombre' => $equipo->nombre,
                        'color' => $equipo->color,
                        'puntuacion' => $equipo->puntuacionMedia(),
                    ])
                    ->sortByDesc('puntuacion')
                    ->values()
                : null,
            'quedan_preguntas' => $this->siguientePregunta($partida) !== null
                || ($partida->estado === EstadoPartida::EsperandoJugadores && $partida->cuestionario->preguntas->isNotEmpty()),
        ];
    }

    private function asegurarAnfitrion(Cuestionario $cuestionario): void
    {
        abort_unless($cuestionario->user_id === Anfitrion::usuario()->id, 403);
    }
}
