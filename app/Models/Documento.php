<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EstadoDocumento;
use Database\Factories\DocumentoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $despacho_id
 * @property int $expediente_id
 * @property string $nombre
 * @property int|null $plantilla_id
 * @property int $version_actual
 * @property EstadoDocumento $estado
 */
class Documento extends DespachoScopedModel
{
    /** @use HasFactory<DocumentoFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'despacho_id',
        'expediente_id',
        'nombre',
        'plantilla_id',
        'version_actual',
        'estado',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoDocumento::class,
            'version_actual' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePendientes(Builder $query): Builder
    {
        return $query->whereIn('estado', [
            EstadoDocumento::PendienteRevision,
            EstadoDocumento::PendienteFirma,
            EstadoDocumento::PendienteAprobacion,
        ]);
    }

    /** @return BelongsTo<Expediente, $this> */
    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    /** @return BelongsTo<Plantilla, $this> */
    public function plantilla(): BelongsTo
    {
        return $this->belongsTo(Plantilla::class);
    }

    /** @return HasMany<DocumentoVersion, $this> */
    public function versiones(): HasMany
    {
        return $this->hasMany(DocumentoVersion::class);
    }

    /** @return HasOne<DocumentoVersion, $this> */
    public function ultimaVersion(): HasOne
    {
        return $this->hasOne(DocumentoVersion::class)->latestOfMany('version');
    }
}
