<?php

namespace App\Models;

use App\Estados\EstadoPartida;
use Database\Factories\PartidaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $cuestionario_id
 * @property string $pin
 * @property EstadoPartida $estado
 * @property bool $modo_equipos
 * @property int|null $pregunta_actual_id
 * @property Carbon|null $pregunta_iniciada_en
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'cuestionario_id',
    'pin',
    'estado',
    'modo_equipos',
    'pregunta_actual_id',
    'pregunta_iniciada_en',
])]
class Partida extends Model
{
    /** @use HasFactory<PartidaFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'estado' => EstadoPartida::class,
            'modo_equipos' => 'boolean',
            'pregunta_iniciada_en' => 'datetime',
        ];
    }

    public function cuestionario(): BelongsTo
    {
        return $this->belongsTo(Cuestionario::class);
    }

    public function preguntaActual(): BelongsTo
    {
        return $this->belongsTo(Pregunta::class, 'pregunta_actual_id');
    }

    public function jugadores(): HasMany
    {
        return $this->hasMany(Jugador::class);
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }

    public static function generarPin(): string
    {
        do {
            $pin = (string) random_int(100000, 999999);
        } while (self::query()->where('pin', $pin)->exists());

        return $pin;
    }

    public function perteneceAlUsuario(User $usuario): bool
    {
        return $this->cuestionario->user_id === $usuario->id;
    }
}
