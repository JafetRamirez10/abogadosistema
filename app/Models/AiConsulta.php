<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TipoConsultaIa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $despacho_id
 * @property int|null $expediente_id
 * @property int $user_id
 * @property TipoConsultaIa $tipo
 * @property string $prompt_hash
 * @property string $respuesta
 * @property int|null $tokens
 */
class AiConsulta extends DespachoScopedModel
{
    protected $table = 'ai_consultas';

    /** @var list<string> */
    protected $fillable = [
        'despacho_id',
        'expediente_id',
        'user_id',
        'tipo',
        'prompt_hash',
        'respuesta',
        'tokens',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoConsultaIa::class,
            'respuesta' => 'encrypted',
            'tokens' => 'integer',
        ];
    }

    /** @return BelongsTo<Expediente, $this> */
    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
