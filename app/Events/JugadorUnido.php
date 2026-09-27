<?php

namespace App\Events;

use App\Models\Jugador;
use App\Models\Partida;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JugadorUnido implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Partida $partida, public Jugador $jugador) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('partida.'.$this->partida->pin),
        ];
    }

    public function broadcastAs(): string
    {
        return 'jugador.unido';
    }

    public function broadcastWith(): array
    {
        return [
            'jugador' => [
                'id' => $this->jugador->id,
                'apodo' => $this->jugador->apodo,
                'equipo_id' => $this->jugador->equipo_id,
                'puntuacion' => $this->jugador->puntuacion,
            ],
            'total_jugadores' => $this->partida->jugadores()->count(),
        ];
    }
}
