<?php

namespace App\Console\Commands;

use App\Models\Cuestionario;
use App\Support\NormasAmenhoot;
use App\Support\VoiceStudioCliente;
use Illuminate\Console\Command;

final class GenerarNarracionAmenhootCommand extends Command
{
    protected $signature = 'amenhoot:generar-narracion
        {--cuestionario= : ID del cuestionario}
        {--titulo= : Buscar cuestionario por título (parcial)}
        {--solo-normas : Solo genera normas.wav}
        {--sin-normas : No regenera normas.wav}
        {--voz= : ID de voz VoiceStudio (override de config)}';

    protected $description = 'Genera WAV de normas y enunciados con la API local de VoiceStudio';

    public function handle(VoiceStudioCliente $cliente): int
    {
        $voz = $this->option('voz') ? (string) $this->option('voz') : null;
        $directorioBase = (string) config('amenhoot.audio.directorio');

        if (! is_dir($directorioBase) && ! mkdir($directorioBase, 0755, true) && ! is_dir($directorioBase)) {
            $this->error("No se pudo crear {$directorioBase}");

            return self::FAILURE;
        }

        if (! $cliente->disponible()) {
            $this->error("VoiceStudio no responde en {$cliente->base()}. Abre la app y vuelve a intentarlo.");

            return self::FAILURE;
        }

        $this->info('VoiceStudio OK · voz='.($voz ?: config('amenhoot.voicestudio.voice')));

        $opciones = array_filter(['voz' => $voz]);

        if (! $this->option('sin-normas')) {
            if (! $cliente->sintetizar(NormasAmenhoot::textoNarracion(), $directorioBase.DIRECTORY_SEPARATOR.'normas.wav', $opciones)) {
                $this->error('Falló normas.wav · '.($cliente->ultimoError() ?? ''));

                return self::FAILURE;
            }
            $this->line('✓ normas.wav');
        }

        if ($this->option('solo-normas')) {
            $this->info('Listo (solo normas).');

            return self::SUCCESS;
        }

        $cuestionario = $this->resolverCuestionario();
        if ($cuestionario === null) {
            $this->error('No hay cuestionario. Usa --cuestionario=ID o --titulo=Fisioterapia.');

            return self::FAILURE;
        }

        $directorio = $directorioBase.DIRECTORY_SEPARATOR.'c'.$cuestionario->id;
        if (! is_dir($directorio) && ! mkdir($directorio, 0755, true) && ! is_dir($directorio)) {
            $this->error("No se pudo crear {$directorio}");

            return self::FAILURE;
        }

        $this->info("Cuestionario #{$cuestionario->id} · {$cuestionario->titulo}");

        $preguntas = $cuestionario->preguntas()->orderBy('orden')->get(['id', 'enunciado', 'orden']);
        foreach ($preguntas as $pregunta) {
            $archivo = $directorio.DIRECTORY_SEPARATOR.'pregunta-'.$pregunta->orden.'.wav';
            if (! $cliente->sintetizar($pregunta->enunciado, $archivo, $opciones)) {
                $this->error("Falló pregunta-{$pregunta->orden}.wav · ".($cliente->ultimoError() ?? ''));

                return self::FAILURE;
            }
            $this->line("✓ c{$cuestionario->id}/pregunta-{$pregunta->orden}.wav · {$pregunta->enunciado}");
        }

        $this->info("Listo: {$preguntas->count()} enunciados en {$directorio}");

        return self::SUCCESS;
    }

    private function resolverCuestionario(): ?Cuestionario
    {
        $id = $this->option('cuestionario');
        if ($id) {
            return Cuestionario::query()->find((int) $id);
        }

        $titulo = $this->option('titulo');
        if (is_string($titulo) && $titulo !== '') {
            return Cuestionario::query()
                ->where('titulo', 'like', '%'.$titulo.'%')
                ->latest('id')
                ->first();
        }

        return Cuestionario::query()
            ->where('titulo', 'Cosas de la vida 🎉')
            ->latest('id')
            ->first()
            ?? Cuestionario::query()->latest('id')->first();
    }
}
