<?php

namespace App\Events;

use App\Models\Partida;
use App\Models\Pregunta;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PreguntaIniciada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Partida $partida, public Pregunta $pregunta) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('partida.'.$this->partida->pin),
        ];
    }

    public function broadcastAs(): string
    {
        return 'pregunta.iniciada';
    }

    public function broadcastWith(): array
    {
        return [
            'pregunta' => [
                'id' => $this->pregunta->id,
                'enunciado' => $this->pregunta->enunciado,
                'segundos_limite' => $this->pregunta->segundos_limite,
                'orden' => $this->pregunta->orden,
                'opciones' => $this->pregunta->opciones->map(fn ($opcion) => [
                    'id' => $opcion->id,
                    'texto' => $opcion->texto,
                    'orden' => $opcion->orden,
                ])->values(),
            ],
            'pregunta_iniciada_en' => $this->partida->pregunta_iniciada_en?->toIso8601String(),
            'total_preguntas' => $this->partida->cuestionario->preguntas()->count(),
        ];
    }
}
