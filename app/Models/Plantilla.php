<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FormatoSalidaPlantilla;
use App\Enums\Materia;
use Database\Factories\PlantillaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $despacho_id
 * @property string $nombre
 * @property Materia|null $materia
 * @property string $contenido
 * @property FormatoSalidaPlantilla $formato_salida
 */
class Plantilla extends DespachoScopedModel
{
    /** @use HasFactory<PlantillaFactory> */
    use HasFactory;

    use LogsActivity;

    /** @var list<string> */
    protected $fillable = [
        'despacho_id',
        'nombre',
        'materia',
        'contenido',
        'formato_salida',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'materia' => Materia::class,
            'formato_salida' => FormatoSalidaPlantilla::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /** @return HasMany<Documento, $this> */
    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class);
    }
}
