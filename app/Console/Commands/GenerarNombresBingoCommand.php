<?php

namespace App\Console\Commands;

use App\Support\CatalogoPersonas;
use App\Support\VoiceStudioCliente;
use Illuminate\Console\Command;

final class GenerarNombresBingoCommand extends Command
{
    /**
     * Texto enviado al TTS. El fichero sigue siendo {clave}.wav.
     *
     * @var array<string, string>
     */
    private const PRONUNCIACION = [
        'edu' => 'Edu',
        'eva' => 'Eva',
        'manu' => 'Má-nu',
        'sara' => 'Sá-ra',
    ];

    /** Nombres sin WAV: el cliente los dice con speechSynthesis. */
    private const SIN_WAV = [
        'luis',
    ];

    /**
     * Instructs válidos de OmniVoice (solo etiquetas EN/ZH permitidas).
     *
     * @var array<string, string>
     */
    private const INSTRUCT = [
        'edu' => 'portuguese accent, male, young adult',
        'eva' => 'portuguese accent, female, young adult',
    ];

    /**
     * Frases de juego → texto a sintetizar.
     *
     * @var array<string, string>
     */
    private const FRASES = [
        'bingo' => 'Han cantado bingo',
        'linea' => 'Han cantado línea',
        'continuar' => 'Continuamos',
    ];

    protected $signature = 'bingo:generar-nombres
        {--voz= : ID de voz VoiceStudio (override de config)}
        {--solo=* : Claves a regenerar (nombres o frases: bingo linea continuar)}
        {--solo-frases : Solo genera bingo, línea y continuar}';

    protected $description = 'Genera WAV de nombres y frases del bingo con VoiceStudio';

    public function handle(VoiceStudioCliente $cliente): int
    {
        $voz = $this->option('voz') ? (string) $this->option('voz') : null;
        $solo = collect($this->option('solo'))
            ->filter()
            ->map(fn (string $clave): string => mb_strtolower($clave))
            ->values()
            ->all();
        $directorio = (string) config('bingo.audio.directorio');

        if (! is_dir($directorio) && ! mkdir($directorio, 0755, true) && ! is_dir($directorio)) {
            $this->error("No se pudo crear {$directorio}");

            return self::FAILURE;
        }

        if (! $cliente->disponible()) {
            $this->error("VoiceStudio no responde en {$cliente->base()}. Abre la app y vuelve a intentarlo.");

            return self::FAILURE;
        }

        $generarFrases = $this->option('solo-frases')
            || $solo === []
            || collect($solo)->intersect(array_keys(self::FRASES))->isNotEmpty();

        $generarNombres = ! $this->option('solo-frases')
            && ($solo === [] || collect($solo)->diff(array_keys(self::FRASES))->isNotEmpty());

        if ($generarNombres) {
            $personas = collect(CatalogoPersonas::todas())
                ->when(
                    $solo !== [],
                    fn ($coleccion) => $coleccion->whereIn('clave', collect($solo)->diff(array_keys(self::FRASES))->all()),
                )
                ->reject(fn (array $persona): bool => in_array($persona['clave'], self::SIN_WAV, true))
                ->values()
                ->all();

            $omitidos = collect($solo)->intersect(self::SIN_WAV);
            if ($omitidos->isNotEmpty()) {
                $this->warn('Sin WAV (síntesis del navegador): '.$omitidos->implode(', '));
            }

            if ($personas === [] && $solo !== [] && ! $generarFrases) {
                if ($omitidos->isNotEmpty() && collect($solo)->diff(array_keys(self::FRASES))->diff(self::SIN_WAV)->isEmpty()) {
                    return self::SUCCESS;
                }
                $this->error('Ninguna clave coincide con --solo.');

                return self::FAILURE;
            }

            if ($personas !== []) {
                $this->info('VoiceStudio OK · '.count($personas).' nombres');
                foreach ($personas as $persona) {
                    if (! $this->sintetizarClave($cliente, $directorio, $persona['clave'], self::PRONUNCIACION[$persona['clave']] ?? $persona['nombre'], $voz, self::INSTRUCT[$persona['clave']] ?? null)) {
                        return self::FAILURE;
                    }
                    $extra = isset(self::PRONUNCIACION[$persona['clave']]) ? ' ('.self::PRONUNCIACION[$persona['clave']].')' : '';
                    $extra .= isset(self::INSTRUCT[$persona['clave']]) ? ' [pt]' : '';
                    $this->line("✓ {$persona['clave']}.wav · {$persona['nombre']}{$extra}");
                }
            }
        }

        if ($generarFrases) {
            $frases = collect(self::FRASES)
                ->when(
                    $solo !== [] && ! $this->option('solo-frases'),
                    fn ($coleccion) => $coleccion->only($solo),
                );

            if ($frases->isEmpty()) {
                $this->warn('Sin frases que generar.');
            } else {
                $this->info('Frases · '.$frases->count());
                foreach ($frases as $clave => $texto) {
                    if (! $this->sintetizarClave($cliente, $directorio, $clave, $texto, $voz)) {
                        return self::FAILURE;
                    }
                    $this->line("✓ {$clave}.wav · {$texto}");
                }
            }
        }

        $this->info("Listo en {$directorio}");

        return self::SUCCESS;
    }

    private function sintetizarClave(
        VoiceStudioCliente $cliente,
        string $directorio,
        string $clave,
        string $texto,
        ?string $voz,
        ?string $instruct = null,
    ): bool {
        $archivo = $directorio.DIRECTORY_SEPARATOR.$clave.'.wav';
        $ok = $cliente->sintetizar($texto, $archivo, array_filter([
            'voz' => $voz,
            'instruct' => $instruct,
        ]));
        if (! $ok) {
            $this->error("Falló: {$clave} · ".($cliente->ultimoError() ?? 'sin detalle'));
        }

        return $ok;
    }
}
