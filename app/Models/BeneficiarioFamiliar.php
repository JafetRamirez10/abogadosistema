<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $despacho_id
 * @property int $expediente_id
 * @property string $nombre
 * @property CarbonInterface|null $fecha_nacimiento
 * @property string|null $parentesco
 * @property bool $es_menor
 */
class BeneficiarioFamiliar extends DespachoScopedModel
{
    protected $table = 'beneficiarios_familiares';

    /** @var list<string> */
    protected $fillable = [
        'despacho_id',
        'expediente_id',
        'nombre',
        'fecha_nacimiento',
        'parentesco',
        'es_menor',
        'cedula',
        'notas',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'es_menor' => 'boolean',
            'cedula' => 'encrypted',
        ];
    }

    /** @return BelongsTo<Expediente, $this> */
    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }
}
