<?php

namespace App\Models;

use Database\Factories\JugadorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $partida_id
 * @property int|null $equipo_id
 * @property string $apodo
 * @property string $token
 * @property int $puntuacion
 * @property int $racha
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['partida_id', 'equipo_id', 'apodo', 'token', 'puntuacion', 'racha'])]
#[Hidden(['token'])]
class Jugador extends Model
{
    /** @use HasFactory<JugadorFactory> */
    use HasFactory;

    protected $table = 'jugadores';

    protected function casts(): array
    {
        return [
            'puntuacion' => 'integer',
            'racha' => 'integer',
        ];
    }

    public function partida(): BelongsTo
    {
        return $this->belongsTo(Partida::class);
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(Respuesta::class);
    }

    public static function generarToken(): string
    {
        return Str::random(64);
    }
}
