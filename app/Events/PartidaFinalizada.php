<?php

namespace App\Events;

use App\Models\Partida;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class PartidaFinalizada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Collection<int, array{id: int, apodo: string, puntuacion: int, racha: int, equipo?: string|null}>  $podio
     * @param  Collection<int, array{id: int, nombre: string, color: string, puntuacion: int}>|null  $podioEquipos
     */
    public function __construct(
        public Partida $partida,
        public Collection $podio,
        public ?Collection $podioEquipos = null,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('partida.'.$this->partida->pin),
        ];
    }

    public function broadcastAs(): string
    {
        return 'partida.finalizada';
    }

    public function broadcastWith(): array
    {
        return [
            'podio' => $this->podio->values(),
            'podio_equipos' => $this->podioEquipos?->values(),
        ];
    }
}
