<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MetodoPago;
use App\Enums\Moneda;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $despacho_id
 * @property int $expediente_id
 * @property int $pension_alimentaria_id
 * @property int|null $cuota_alimentaria_id
 * @property string $monto
 * @property Moneda $moneda
 * @property CarbonInterface $fecha
 * @property MetodoPago $metodo
 */
class PagoAlimentario extends DespachoScopedModel
{
    protected $table = 'pagos_alimentarios';

    /** @var list<string> */
    protected $fillable = [
        'despacho_id',
        'expediente_id',
        'pension_alimentaria_id',
        'cuota_alimentaria_id',
        'monto',
        'moneda',
        'fecha',
        'metodo',
        'referencia',
        'nota',
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

    /** @return BelongsTo<PensionAlimentaria, $this> */
    public function pension(): BelongsTo
    {
        return $this->belongsTo(PensionAlimentaria::class, 'pension_alimentaria_id');
    }

    /** @return BelongsTo<CuotaAlimentaria, $this> */
    public function cuota(): BelongsTo
    {
        return $this->belongsTo(CuotaAlimentaria::class, 'cuota_alimentaria_id');
    }

    /** @return BelongsTo<Expediente, $this> */
    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }
}
