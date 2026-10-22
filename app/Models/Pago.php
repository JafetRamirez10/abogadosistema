<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MetodoPago;
use App\Enums\Moneda;
use Carbon\CarbonInterface;
use Database\Factories\PagoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $despacho_id
 * @property int $expediente_id
 * @property string $monto
 * @property Moneda $moneda
 * @property CarbonInterface $fecha
 * @property MetodoPago $metodo
 * @property string|null $referencia
 * @property string|null $nota
 * @property int $user_id
 */
class Pago extends DespachoScopedModel
{
    /** @use HasFactory<PagoFactory> */
    use HasFactory;

    use LogsActivity;

    /** @var list<string> */
    protected $fillable = [
        'despacho_id',
        'expediente_id',
        'monto',
        'moneda',
        'fecha',
        'metodo',
        'referencia',
        'nota',
        'user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'moneda' => Moneda::class,
            'fecha' => 'date',
            'metodo' => MetodoPago::class,
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
