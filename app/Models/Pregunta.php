<?php

namespace App\Models;

use Database\Factories\PreguntaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $cuestionario_id
 * @property string $enunciado
 * @property int $segundos_limite
 * @property int $orden
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['cuestionario_id', 'enunciado', 'segundos_limite', 'orden'])]
class Pregunta extends Model
{
    /** @use HasFactory<PreguntaFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'segundos_limite' => 'integer',
            'orden' => 'integer',
        ];
    }

    public function cuestionario(): BelongsTo
    {
        return $this->belongsTo(Cuestionario::class);
    }

    public function opciones(): HasMany
    {
        return $this->hasMany(Opcion::class)->orderBy('orden');
    }
}
