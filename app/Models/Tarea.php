<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EstadoTarea;
use App\Enums\PrioridadTarea;
use Carbon\CarbonInterface;
use Database\Factories\TareaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $despacho_id
 * @property int|null $expediente_id
 * @property int $asignada_a
 * @property int $creada_por
 * @property string $descripcion
 * @property CarbonInterface|null $vence_el
 * @property PrioridadTarea $prioridad
 * @property EstadoTarea $estado
 * @property CarbonInterface|null $completada_at
 */
class Tarea extends DespachoScopedModel
{
    /** @use HasFactory<TareaFactory> */
    use HasFactory;

    use LogsActivity;

    /** @var list<string> */
    protected $fillable = [
        'despacho_id',
        'expediente_id',
        'asignada_a',
        'creada_por',
        'descripcion',
        'vence_el',
        'prioridad',
        'estado',
        'completada_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'vence_el' => 'date',
            'prioridad' => PrioridadTarea::class,
            'estado' => EstadoTarea::class,
            'completada_at' => 'datetime',
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
     * @param  Builder<Tarea>  $query
     * @return Builder<Tarea>
     */
    public function scopePendientesDe(Builder $query, User $user): Builder
    {
        return $query
            ->where('asignada_a', $user->getKey())
            ->where('estado', EstadoTarea::Pendiente);
    }

    /**
     * @param  Builder<Tarea>  $query
     * @return Builder<Tarea>
     */
    public function scopeVencidas(Builder $query): Builder
    {
        $hoy = now('America/Costa_Rica')->toDateString();

        return $query
            ->where('estado', EstadoTarea::Pendiente)
            ->whereNotNull('vence_el')
            ->whereDate('vence_el', '<', $hoy);
    }

    /** @return BelongsTo<Expediente, $this> */
    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    /** @return BelongsTo<User, $this> */
    public function asignada(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignada_a');
    }

    /** @return BelongsTo<User, $this> */
    public function creadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creada_por');
    }
}
