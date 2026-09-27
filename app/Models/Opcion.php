<?php

namespace App\Models;

use Database\Factories\OpcionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $pregunta_id
 * @property string $texto
 * @property bool $es_correcta
 * @property int $orden
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['pregunta_id', 'texto', 'es_correcta', 'orden'])]
class Opcion extends Model
{
    /** @use HasFactory<OpcionFactory> */
    use HasFactory;

    protected $table = 'opciones';

    protected function casts(): array
    {
        return [
            'es_correcta' => 'boolean',
            'orden' => 'integer',
        ];
    }

    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(Pregunta::class);
    }
}
