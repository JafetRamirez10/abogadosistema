<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConceptoCuotaAlimentaria;
use App\Enums\EstadoCuotaAlimentaria;
use App\Enums\Moneda;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $despacho_id
 * @property int $expediente_id
 * @property int $pension_alimentaria_id
 * @property ConceptoCuotaAlimentaria $concepto
 * @property string|null $periodo
 * @property string $monto
 * @property string $monto_pagado
 * @property Moneda $moneda
 * @property CarbonInterface $vence_el
 * @property EstadoCuotaAlimentaria $estado
 * @property-read string $saldo
 * @property-read bool $en_mora
 */
class CuotaAlimentaria extends DespachoScopedModel
{
    protected $table = 'cuotas_alimentarias';

    /** @var list<string> */
    protected $fillable = [
        'despacho_id',
        'expediente_id',
        'pension_alimentaria_id',
        'concepto',
        'periodo',
        'monto',
        'monto_pagado',
        'moneda',
        'vence_el',
        'estado',
        'notas',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'concepto' => ConceptoCuotaAlimentaria::class,
            'monto' => 'decimal:2',
            'monto_pagado' => 'decimal:2',
            'moneda' => Moneda::class,
            'vence_el' => 'date',
            'estado' => EstadoCuotaAlimentaria::class,
        ];
    }

    /** @return BelongsTo<PensionAlimentaria, $this> */
    public function pension(): BelongsTo
    {
        return $this->belongsTo(PensionAlimentaria::class, 'pension_alimentaria_id');
    }

    /** @return BelongsTo<Expediente, $this> */
    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    /** @return HasMany<PagoAlimentario, $this> */
    public function pagos(): HasMany
    {
        return $this->hasMany(PagoAlimentario::class);
    }

    /** @return Attribute<string, never> */
    protected function saldo(): Attribute
    {
        return Attribute::get(function (): string {
            $saldo = (float) $this->monto - (float) $this->monto_pagado;

            return number_format(max(0, $saldo), 2, '.', '');
        });
    }

    /** @return Attribute<bool, never> */
    protected function enMora(): Attribute
    {
        return Attribute::get(function (): bool {
            if (in_array($this->estado, [EstadoCuotaAlimentaria::Pagada, EstadoCuotaAlimentaria::Condonada], true)) {
                return false;
            }

            return $this->vence_el->lt(now('America/Costa_Rica')->startOfDay());
        });
    }

    public function recalcularEstado(): void
    {
        if ($this->estado === EstadoCuotaAlimentaria::Condonada) {
            return;
        }

        $pagado = (float) $this->monto_pagado;
        $monto = (float) $this->monto;

        if ($pagado <= 0) {
            $this->estado = EstadoCuotaAlimentaria::Pendiente;
        } elseif ($pagado + 0.009 >= $monto) {
            $this->estado = EstadoCuotaAlimentaria::Pagada;
            $this->monto_pagado = number_format($monto, 2, '.', '');
        } else {
            $this->estado = EstadoCuotaAlimentaria::Parcial;
        }

        $this->save();
    }
}
