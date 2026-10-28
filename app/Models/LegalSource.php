<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $codigo
 * @property string $tipo
 * @property string $titulo
 * @property string|null $materia
 * @property bool $es_demo
 */
class LegalSource extends Model
{
    protected $fillable = [
        'codigo',
        'tipo',
        'titulo',
        'materia',
        'origen',
        'external_id',
        'vigente_desde',
        'vigente_hasta',
        'es_demo',
        'metadata',
        'indexado_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'es_demo' => 'boolean',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
            'metadata' => 'array',
            'indexado_at' => 'datetime',
        ];
    }

    /** @return HasMany<LegalChunk, $this> */
    public function chunks(): HasMany
    {
        return $this->hasMany(LegalChunk::class);
    }
}
