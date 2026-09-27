<?php

namespace App\Models;

use Database\Factories\RespuestaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $jugador_id
 * @property int $pregunta_id
 * @property int $opcion_id
 * @property int $milisegundos
 * @property bool $es_correcta
 * @property int $puntos
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'jugador_id',
    'pregunta_id',
    'opcion_id',
    'milisegundos',
    'es_correcta',
    'puntos',
])]
class Respuesta extends Model
{
    /** @use HasFactory<RespuestaFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'milisegundos' => 'integer',
            'es_correcta' => 'boolean',
            'puntos' => 'integer',
        ];
    }

    public function jugador(): BelongsTo
    {
        return $this->belongsTo(Jugador::class);
    }

    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(Pregunta::class);
    }

    public function opcion(): BelongsTo
    {
        return $this->belongsTo(Opcion::class);
    }
}
