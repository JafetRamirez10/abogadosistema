<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Moneda;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $despacho_id
 * @property int $expediente_id
 * @property string $cuota_mensual
 * @property Moneda $moneda
 * @property int $dia_vencimiento
 * @property bool $incluye_aguinaldo
 * @property bool $incluye_salario_escolar
 * @property string|null $monto_aguinaldo
 * @property string|null $monto_salario_escolar
 * @property CarbonInterface $fecha_inicio
 * @property bool $activa
 */
class PensionAlimentaria extends DespachoScopedModel
{
    protected $table = 'pensiones_alimentarias';

    /** @var list<string> */
    protected $fillable = [
        'despacho_id',
        'expediente_id',
        'cuota_mensual',
        'moneda',
        'dia_vencimiento',
        'incluye_aguinaldo',
        'incluye_salario_escolar',
        'monto_aguinaldo',
        'monto_salario_escolar',
        'fecha_inicio',
        'activa',
        'notas',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cuota_mensual' => 'decimal:2',
            'moneda' => Moneda::class,
            'dia_vencimiento' => 'integer',
            'incluye_aguinaldo' => 'boolean',
            'incluye_salario_escolar' => 'boolean',
            'monto_aguinaldo' => 'decimal:2',
            'monto_salario_escolar' => 'decimal:2',
            'fecha_inicio' => 'date',
            'activa' => 'boolean',
        ];
    }

    /** @return BelongsTo<Expediente, $this> */
    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    /** @return HasMany<CuotaAlimentaria, $this> */
    public function cuotas(): HasMany
    {
        return $this->hasMany(CuotaAlimentaria::class);
    }

    /** @return HasMany<PagoAlimentario, $this> */
    public function pagos(): HasMany
    {
        return $this->hasMany(PagoAlimentario::class);
    }
}
