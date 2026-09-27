<?php

namespace App\Events;

use App\Models\Partida;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PartidaPorEmpezar implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Partida $partida,
        public int $segundos = 5,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('partida.'.$this->partida->pin),
        ];
    }

    public function broadcastAs(): string
    {
        return 'partida.por-empezar';
    }

    /**
     * @return array{segundos: int}
     */
    public function broadcastWith(): array
    {
        return [
            'segundos' => $this->segundos,
        ];
    }
}
