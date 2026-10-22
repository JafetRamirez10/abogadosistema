<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RolParte;
use Database\Factories\ParteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $despacho_id
 * @property int $expediente_id
 * @property string $nombre
 * @property RolParte $rol
 */
class Parte extends DespachoScopedModel
{
    /** @use HasFactory<ParteFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'despacho_id',
        'expediente_id',
        'nombre',
        'cedula',
        'rol',
        'abogado_contrario',
        'contacto',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cedula' => 'encrypted',
            'rol' => RolParte::class,
        ];
    }

    /** @return BelongsTo<Expediente, $this> */
    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }
}
