<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EstadoEvento;
use App\Enums\Semaforo;
use App\Enums\TipoEvento;
use App\Services\SemaforoService;
use Carbon\CarbonInterface;
use Database\Factories\EventoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $despacho_id
 * @property int|null $expediente_id
 * @property int $responsable_id
 * @property TipoEvento $tipo
 * @property string $titulo
 * @property CarbonInterface $inicio
 * @property CarbonInterface|null $fin
 * @property string|null $lugar
 * @property int $dias_aviso
 * @property EstadoEvento $estado
 * @property CarbonInterface|null $alerta_enviada_at
 * @property string|null $notas
 * @property-read Semaforo $semaforo
 * @property-read string $semaforo_badge
 */
class Evento extends DespachoScopedModel
{
    /** @use HasFactory<EventoFactory> */
    use HasFactory;

    use LogsActivity;

    /** @var list<string> */
    protected $fillable = [
        'despacho_id',
        'expediente_id',
        'responsable_id',
        'tipo',
        'titulo',
        'inicio',
        'fin',
        'lugar',
        'dias_aviso',
        'estado',
        'alerta_enviada_at',
        'notas',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoEvento::class,
            'inicio' => 'datetime',
            'fin' => 'datetime',
            'dias_aviso' => 'integer',
            'estado' => EstadoEvento::class,
            'alerta_enviada_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /** @return Attribute<Semaforo, never> */
    protected function semaforo(): Attribute
    {
        return Attribute::get(
            fn (): Semaforo => SemaforoService::para($this->inicio, $this->estado),
        );
    }

    /** @return Attribute<string, never> */
    protected function semaforoBadge(): Attribute
    {
        return Attribute::get(
            fn (): string => SemaforoService::badge($this->inicio, $this->estado),
        );
    }

    /**
     * @param  Builder<Evento>  $query
     * @return Builder<Evento>
     */
    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado', EstadoEvento::Pendiente);
    }

    /**
     * @param  Builder<Evento>  $query
     * @return Builder<Evento>
     */
    public function scopeDeHoy(Builder $query): Builder
    {
        $hoy = now('America/Costa_Rica');

        return $query->whereDate('inicio', $hoy->toDateString());
    }

    /**
     * @param  Builder<Evento>  $query
     * @return Builder<Evento>
     */
    public function scopeEstaSemana(Builder $query): Builder
    {
        $inicio = now('America/Costa_Rica')->startOfWeek();
        $fin = now('America/Costa_Rica')->endOfWeek();

        return $query->whereBetween('inicio', [$inicio, $fin]);
    }

    /** @return BelongsTo<Expediente, $this> */
    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    /** @return BelongsTo<User, $this> */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
}
