<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EstadoMedidaCautelar;
use App\Enums\TipoMedidaCautelar;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $despacho_id
 * @property int $expediente_id
 * @property TipoMedidaCautelar $tipo
 * @property EstadoMedidaCautelar $estado
 * @property CarbonInterface $fecha_inicio
 * @property CarbonInterface|null $fecha_revision
 * @property CarbonInterface|null $fecha_vencimiento
 * @property-read bool $requiere_atencion
 */
class MedidaCautelar extends DespachoScopedModel
{
    protected $table = 'medidas_cautelares';

    /** @var list<string> */
    protected $fillable = [
        'despacho_id',
        'expediente_id',
        'tipo',
        'estado',
        'fecha_inicio',
        'fecha_revision',
        'fecha_vencimiento',
        'autoridad',
        'descripcion',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoMedidaCautelar::class,
            'estado' => EstadoMedidaCautelar::class,
            'fecha_inicio' => 'date',
            'fecha_revision' => 'date',
            'fecha_vencimiento' => 'date',
        ];
    }

    /** @return BelongsTo<Expediente, $this> */
    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    /** @return Attribute<bool, never> */
    protected function requiereAtencion(): Attribute
    {
        return Attribute::get(function (): bool {
            if ($this->estado !== EstadoMedidaCautelar::Vigente) {
                return false;
            }

            $hoy = now('America/Costa_Rica')->startOfDay();

            if ($this->fecha_vencimiento !== null && $this->fecha_vencimiento->lte($hoy->copy()->addDays(7))) {
                return true;
            }

            if ($this->fecha_revision !== null && $this->fecha_revision->lte($hoy->copy()->addDays(7))) {
                return true;
            }

            return false;
        });
    }
}
