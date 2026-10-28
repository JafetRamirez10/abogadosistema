<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $legal_source_id
 * @property string|null $referencia
 * @property string|null $titulo
 * @property string $contenido
 * @property string|null $materia
 * @property array<int, string>|null $keywords
 */
class LegalChunk extends Model
{
    protected $fillable = [
        'legal_source_id',
        'referencia',
        'external_id',
        'titulo',
        'contenido',
        'materia',
        'keywords',
        'orden',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'metadata' => 'array',
            'orden' => 'integer',
        ];
    }

    /** @return BelongsTo<LegalSource, $this> */
    public function source(): BelongsTo
    {
        return $this->belongsTo(LegalSource::class, 'legal_source_id');
    }
}
