<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarCuestionarioRequest;
use App\Models\Cuestionario;
use App\Models\Pregunta;
use App\Support\Anfitrion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class CuestionarioController extends Controller
{
    public function index(): Response
    {
        $cuestionarios = Cuestionario::query()
            ->where('user_id', Anfitrion::usuario()->id)
            ->withCount('preguntas')
            ->latest()
            ->get();

        return Inertia::render('Anfitrion/Cuestionarios/Index', [
            'cuestionarios' => $cuestionarios,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Anfitrion/Cuestionarios/Editar', [
            'cuestionario' => null,
        ]);
    }

    public function store(GuardarCuestionarioRequest $request): RedirectResponse
    {
        $cuestionario = DB::transaction(function () use ($request) {
            $cuestionario = Cuestionario::query()->create([
                'user_id' => Anfitrion::usuario()->id,
                'titulo' => $request->validated('titulo'),
                'descripcion' => $request->validated('descripcion'),
            ]);

            $this->guardarPreguntas($cuestionario, $request->validated('preguntas'));

            return $cuestionario;
        });

        return redirect()->route('cuestionarios.index')
            ->with('exito', 'Cuestionario creado.');
    }

    public function edit(Cuestionario $cuestionario): Response
    {
        $this->asegurarAnfitrion($cuestionario);
        $cuestionario->load(['preguntas.opciones']);

        return Inertia::render('Anfitrion/Cuestionarios/Editar', [
            'cuestionario' => $cuestionario,
        ]);
    }

    public function update(GuardarCuestionarioRequest $request, Cuestionario $cuestionario): RedirectResponse
    {
        $this->asegurarAnfitrion($cuestionario);

        DB::transaction(function () use ($request, $cuestionario): void {
            $cuestionario->update([
                'titulo' => $request->validated('titulo'),
                'descripcion' => $request->validated('descripcion'),
            ]);

            $cuestionario->preguntas()->each(function (Pregunta $pregunta): void {
                $pregunta->opciones()->delete();
                $pregunta->delete();
            });

            $this->guardarPreguntas($cuestionario, $request->validated('preguntas'));
        });

        return redirect()->route('cuestionarios.index')
            ->with('exito', 'Cuestionario actualizado.');
    }

    public function destroy(Cuestionario $cuestionario): RedirectResponse
    {
        $this->asegurarAnfitrion($cuestionario);
        $cuestionario->delete();

        return redirect()->route('cuestionarios.index')
            ->with('exito', 'Cuestionario eliminado.');
    }

    /**
     * @param  array<int, array{enunciado: string, segundos_limite: int, opciones: array<int, array{texto: string, es_correcta: bool}>}>  $preguntas
     */
    private function guardarPreguntas(Cuestionario $cuestionario, array $preguntas): void
    {
        foreach ($preguntas as $orden => $datosPregunta) {
            $pregunta = $cuestionario->preguntas()->create([
                'enunciado' => $datosPregunta['enunciado'],
                'segundos_limite' => $datosPregunta['segundos_limite'],
                'orden' => $orden,
            ]);

            foreach ($datosPregunta['opciones'] as $ordenOpcion => $datosOpcion) {
                $pregunta->opciones()->create([
                    'texto' => $datosOpcion['texto'],
                    'es_correcta' => (bool) $datosOpcion['es_correcta'],
                    'orden' => $ordenOpcion,
                ]);
            }
        }
    }

    private function asegurarAnfitrion(Cuestionario $cuestionario): void
    {
        abort_unless($cuestionario->user_id === Anfitrion::usuario()->id, 403);
    }
}
