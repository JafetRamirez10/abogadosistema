<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TipoActuacion;
use Carbon\CarbonInterface;
use Database\Factories\ActuacionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $despacho_id
 * @property int $expediente_id
 * @property int $user_id
 * @property CarbonInterface $fecha
 * @property TipoActuacion $tipo
 * @property string $descripcion
 * @property int|null $documento_id
 */
class Actuacion extends DespachoScopedModel
{
    /** @use HasFactory<ActuacionFactory> */
    use HasFactory;

    protected $table = 'actuaciones';

    /** @var list<string> */
    protected $fillable = [
        'despacho_id',
        'expediente_id',
        'user_id',
        'fecha',
        'tipo',
        'descripcion',
        'documento_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'tipo' => TipoActuacion::class,
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

    /** @return BelongsTo<Documento, $this> */
    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class);
    }
}
