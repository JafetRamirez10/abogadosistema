<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Moneda;
use Carbon\CarbonInterface;
use Database\Factories\HoraFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $despacho_id
 * @property int $expediente_id
 * @property int $user_id
 * @property CarbonInterface $fecha
 * @property int $minutos
 * @property string $descripcion
 * @property string|null $tarifa
 * @property Moneda $moneda
 * @property bool $facturable
 */
class Hora extends DespachoScopedModel
{
    /** @use HasFactory<HoraFactory> */
    use HasFactory;

    use LogsActivity;

    /** @var list<string> */
    protected $fillable = [
        'despacho_id',
        'expediente_id',
        'user_id',
        'fecha',
        'minutos',
        'descripcion',
        'tarifa',
        'moneda',
        'facturable',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'minutos' => 'integer',
            'tarifa' => 'decimal:2',
            'moneda' => Moneda::class,
            'facturable' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
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
