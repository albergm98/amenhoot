<?php

namespace App\Events;

use App\Models\Partida;
use App\Models\Pregunta;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class PreguntaCerrada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Collection<int, array{opcion_id: int, total: int}>  $conteoOpciones
     * @param  Collection<int, array{id: int, apodo: string, puntuacion: int, racha: int}>  $clasificacion
     */
    public function __construct(
        public Partida $partida,
        public Pregunta $pregunta,
        public int $opcionCorrectaId,
        public Collection $conteoOpciones,
        public Collection $clasificacion,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('partida.'.$this->partida->pin),
        ];
    }

    public function broadcastAs(): string
    {
        return 'pregunta.cerrada';
    }

    public function broadcastWith(): array
    {
        return [
            'pregunta_id' => $this->pregunta->id,
            'opcion_correcta_id' => $this->opcionCorrectaId,
            'conteo_opciones' => $this->conteoOpciones->values(),
            'clasificacion' => $this->clasificacion->values(),
        ];
    }
}
