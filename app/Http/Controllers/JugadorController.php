<?php

namespace App\Http\Controllers;

use App\Estados\EstadoPartida;
use App\Events\JugadorUnido;
use App\Events\RespuestaRecibida;
use App\Http\Requests\ResponderPreguntaRequest;
use App\Http\Requests\UnirsePartidaRequest;
use App\Models\Jugador;
use App\Models\Opcion;
use App\Models\Partida;
use App\Models\Respuesta;
use App\Services\CalculadoraPuntos;
use App\Services\CierrePregunta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class JugadorController extends Controller
{
    public function formularioUnirse(Request $request): Response
    {
        return Inertia::render('Jugador/Unirse', [
            'pinInicial' => $request->query('pin'),
        ]);
    }

    public function unirse(UnirsePartidaRequest $request): RedirectResponse
    {
        $partida = Partida::query()
            ->with('equipos')
            ->where('pin', $request->validated('pin'))
            ->first();

        if ($partida === null) {
            return back()->withErrors(['pin' => 'PIN no válido.'])->onlyInput('pin', 'apodo');
        }

        if ($partida->estado !== EstadoPartida::EsperandoJugadores) {
            return back()->withErrors(['pin' => 'La partida ya ha empezado.'])->onlyInput('pin', 'apodo');
        }

        $apodoExiste = $partida->jugadores()
            ->whereRaw('LOWER(apodo) = ?', [mb_strtolower($request->validated('apodo'))])
            ->exists();

        if ($apodoExiste) {
            return back()->withErrors(['apodo' => 'Ese apodo ya está en uso.'])->onlyInput('pin', 'apodo');
        }

        $equipoId = $request->validated('equipo_id');
        if ($partida->modo_equipos) {
            if ($equipoId === null || ! $partida->equipos->contains('id', $equipoId)) {
                return back()->withErrors(['equipo_id' => 'Elige un equipo.'])->onlyInput('pin', 'apodo');
            }
        } else {
            $equipoId = null;
        }

        $jugador = $partida->jugadores()->create([
            'apodo' => $request->validated('apodo'),
            'equipo_id' => $equipoId,
            'token' => Jugador::generarToken(),
        ]);

        event(new JugadorUnido($partida, $jugador));

        return redirect()
            ->route('jugador.partida', $partida)
            ->withCookie(cookie('jugador_token', $jugador->token, 60 * 24, httpOnly: true, raw: true));
    }

    public function partida(Request $request, Partida $partida, CierrePregunta $cierre): Response
    {
        $jugador = $this->jugadorDesdeCookie($request, $partida);
        abort_if($jugador === null, 403);

        $partida->load(['cuestionario.preguntas', 'equipos', 'preguntaActual.opciones', 'jugadores']);

        $respuestaActual = null;
        if ($partida->pregunta_actual_id) {
            $respuestaActual = Respuesta::query()
                ->where('jugador_id', $jugador->id)
                ->where('pregunta_id', $partida->pregunta_actual_id)
                ->first();
        }

        $mostrandoPregunta = $partida->estado === EstadoPartida::MostrandoPregunta;
        $mostrandoResultados = $partida->estado === EstadoPartida::MostrandoResultados;
        $pregunta = $partida->preguntaActual;

        return Inertia::render('Jugador/Partida', [
            'partida' => [
                'id' => $partida->id,
                'pin' => $partida->pin,
                'estado' => $partida->estado->value,
                'modo_equipos' => $partida->modo_equipos,
                'pregunta_iniciada_en' => $partida->pregunta_iniciada_en?->toIso8601String(),
                'total_preguntas' => $partida->cuestionario->preguntas->count(),
                'pregunta_actual' => $pregunta && ($mostrandoPregunta || $mostrandoResultados)
                    ? [
                        'id' => $pregunta->id,
                        'enunciado' => $pregunta->enunciado,
                        'segundos_limite' => $pregunta->segundos_limite,
                        'orden' => $pregunta->orden,
                        'opciones' => $pregunta->opciones->map(fn ($o) => [
                            'id' => $o->id,
                            'texto' => $o->texto,
                            'orden' => $o->orden,
                        ]),
                    ]
                    : null,
                'conteo_opciones' => $mostrandoResultados && $pregunta ? $cierre->conteo($pregunta) : [],
                'clasificacion' => $mostrandoResultados ? $cierre->clasificacion($partida) : [],
            ],
            'jugador' => [
                'id' => $jugador->id,
                'apodo' => $jugador->apodo,
                'puntuacion' => $jugador->puntuacion,
                'racha' => $jugador->racha,
                'equipo_id' => $jugador->equipo_id,
            ],
            'respuesta_actual' => $respuestaActual ? [
                'opcion_id' => $respuestaActual->opcion_id,
                'es_correcta' => $respuestaActual->es_correcta,
                'puntos' => $respuestaActual->puntos,
            ] : null,
            'equipos' => $partida->equipos->map(fn ($e) => [
                'id' => $e->id,
                'nombre' => $e->nombre,
                'color' => $e->color,
            ]),
        ]);
    }

    public function responder(
        ResponderPreguntaRequest $request,
        Partida $partida,
        CalculadoraPuntos $calculadora,
        CierrePregunta $cierre,
    ): JsonResponse {
        $jugador = $this->jugadorDesdeCookie($request, $partida);
        abort_if($jugador === null, 403);
        abort_unless($partida->estado === EstadoPartida::MostrandoPregunta, 422, 'No hay pregunta activa.');
        abort_if($partida->pregunta_actual_id === null || $partida->pregunta_iniciada_en === null, 422);

        $pregunta = $partida->preguntaActual()->with('opciones')->firstOrFail();
        $opcion = Opcion::query()->findOrFail($request->validated('opcion_id'));
        abort_unless($opcion->pregunta_id === $pregunta->id, 422);

        $yaRespondio = Respuesta::query()
            ->where('jugador_id', $jugador->id)
            ->where('pregunta_id', $pregunta->id)
            ->exists();

        if ($yaRespondio) {
            return response()->json(['mensaje' => 'Ya has respondido.'], 422);
        }

        $milisegundos = (int) abs(now()->diffInMilliseconds($partida->pregunta_iniciada_en));
        $limiteMs = $pregunta->segundos_limite * 1000;

        if ($milisegundos > $limiteMs) {
            return response()->json(['mensaje' => 'Tiempo agotado.'], 422);
        }

        $resultado = $calculadora->aplicar($jugador, $pregunta, $milisegundos, $opcion->es_correcta);

        Respuesta::query()->create([
            'jugador_id' => $jugador->id,
            'pregunta_id' => $pregunta->id,
            'opcion_id' => $opcion->id,
            'milisegundos' => $milisegundos,
            'es_correcta' => $opcion->es_correcta,
            'puntos' => $resultado['puntos'],
        ]);

        $totalRespuestas = Respuesta::query()
            ->where('pregunta_id', $pregunta->id)
            ->whereIn('jugador_id', $partida->jugadores()->pluck('id'))
            ->count();

        event(new RespuestaRecibida($partida, $totalRespuestas));

        if ($totalRespuestas >= $partida->jugadores()->count()) {
            $cierre->ejecutar($partida);
        }

        $jugador->refresh();

        return response()->json([
            'es_correcta' => $opcion->es_correcta,
            'puntos' => $resultado['puntos'],
            'bonus_racha' => $resultado['bonus_racha'],
            'racha' => $jugador->racha,
            'puntuacion' => $jugador->puntuacion,
        ]);
    }

    public function equiposPorPin(string $pin): JsonResponse
    {
        $partida = Partida::query()->with('equipos')->where('pin', $pin)->first();

        if ($partida === null || $partida->estado !== EstadoPartida::EsperandoJugadores) {
            return response()->json(['equipos' => [], 'modo_equipos' => false]);
        }

        return response()->json([
            'modo_equipos' => $partida->modo_equipos,
            'equipos' => $partida->equipos->map(fn ($e) => [
                'id' => $e->id,
                'nombre' => $e->nombre,
                'color' => $e->color,
            ]),
        ]);
    }

    private function jugadorDesdeCookie(Request $request, Partida $partida): ?Jugador
    {
        $token = $request->header('X-Jugador-Token')
            ?? $request->cookie('jugador_token');

        if (! is_string($token) || $token === '') {
            return null;
        }

        return Jugador::query()
            ->where('partida_id', $partida->id)
            ->where('token', $token)
            ->first();
    }
}
