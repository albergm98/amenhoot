<?php

namespace App\Models;

use Database\Factories\EquipoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $partida_id
 * @property string $nombre
 * @property string $color
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['partida_id', 'nombre', 'color'])]
class Equipo extends Model
{
    /** @use HasFactory<EquipoFactory> */
    use HasFactory;

    public function partida(): BelongsTo
    {
        return $this->belongsTo(Partida::class);
    }

    public function jugadores(): HasMany
    {
        return $this->hasMany(Jugador::class);
    }

    public function puntuacionMedia(): int
    {
        $jugadores = $this->jugadores;

        if ($jugadores->isEmpty()) {
            return 0;
        }

        return (int) round($jugadores->avg('puntuacion'));
    }
}
