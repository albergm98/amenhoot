<?php

namespace App\Events;

use App\Models\Partida;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RespuestaRecibida implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Partida $partida, public int $totalRespuestas) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('anfitrion.partida.'.$this->partida->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'respuesta.recibida';
    }

    public function broadcastWith(): array
    {
        return [
            'total_respuestas' => $this->totalRespuestas,
            'total_jugadores' => $this->partida->jugadores()->count(),
        ];
    }
}
