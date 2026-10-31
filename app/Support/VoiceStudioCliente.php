<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Throwable;

final class VoiceStudioCliente
{
    private ?string $ultimoError = null;

    public function disponible(): bool
    {
        try {
            $respuesta = Http::connectTimeout(3)
                ->timeout(min(10, $this->timeout()))
                ->get($this->base().'/v1/models');

            return $respuesta->successful();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param  array{voz?: string|null, instruct?: string|null}  $opciones
     */
    public function sintetizar(string $texto, string $destino, array $opciones = []): bool
    {
        try {
            $respuesta = Http::connectTimeout(5)
                ->timeout($this->timeout())
                ->accept('audio/wav')
                ->post($this->base().'/v1/audio/speech', array_filter([
                    'model' => (string) config('amenhoot.voicestudio.model'),
                    'input' => $texto,
                    'voice' => $opciones['voz'] ?? (string) config('amenhoot.voicestudio.voice'),
                    'response_format' => 'wav',
                    'language' => 'es',
                    'instruct' => $opciones['instruct'] ?? null,
                    'speed' => $opciones['speed'] ?? 0.9,
                ], fn ($valor) => $valor !== null));
        } catch (Throwable $e) {
            $this->ultimoError = $e->getMessage();

            return false;
        }

        if (! $respuesta->successful()) {
            $this->ultimoError = $respuesta->status().': '.$respuesta->body();

            return false;
        }

        $this->ultimoError = null;

        return file_put_contents($destino, $respuesta->body()) !== false;
    }

    public function ultimoError(): ?string
    {
        return $this->ultimoError;
    }

    public function base(): string
    {
        return rtrim((string) config('amenhoot.voicestudio.url'), '/');
    }

    public function timeout(): int
    {
        return (int) config('amenhoot.voicestudio.timeout');
    }
}
