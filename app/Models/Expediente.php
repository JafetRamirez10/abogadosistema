<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EstadoExpediente;
use App\Enums\Materia;
use App\Enums\Moneda;
use App\Enums\TipoHonorario;
use App\Services\HonorariosService;
use Database\Factories\ExpedienteFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $despacho_id
 * @property int $cliente_id
 * @property int $responsable_id
 * @property string $numero_interno
 * @property string|null $numero_judicial
 * @property string $titulo
 * @property Materia $materia
 * @property EstadoExpediente $estado
 * @property string|null $juzgado
 * @property Moneda $moneda
 * @property string|null $honorario_pactado
 * @property-read string $total_pagado
 * @property-read string $saldo_pendiente
 * @property-read float $horas_totales
 */
class Expediente extends DespachoScopedModel
{
    /** @use HasFactory<ExpedienteFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'despacho_id',
        'cliente_id',
        'responsable_id',
        'numero_interno',
        'numero_judicial',
        'titulo',
        'materia',
        'estado',
        'juzgado',
        'fecha_inicio',
        'cuantia',
        'moneda',
        'tipo_honorario',
        'honorario_pactado',
        'porcentaje_honorario',
        'descripcion',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'materia' => Materia::class,
            'estado' => EstadoExpediente::class,
            'fecha_inicio' => 'date',
            'cuantia' => 'decimal:2',
            'moneda' => Moneda::class,
            'tipo_honorario' => TipoHonorario::class,
            'honorario_pactado' => 'decimal:2',
            'porcentaje_honorario' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /** @return BelongsTo<Cliente, $this> */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /** @return BelongsTo<User, $this> */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    /** @return HasMany<Parte, $this> */
    public function partes(): HasMany
    {
        return $this->hasMany(Parte::class);
    }

    /** @return HasMany<Actuacion, $this> */
    public function actuaciones(): HasMany
    {
        return $this->hasMany(Actuacion::class);
    }

    /** @return HasMany<Evento, $this> */
    public function eventos(): HasMany
    {
        return $this->hasMany(Evento::class);
    }

    /** @return HasMany<Documento, $this> */
    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class);
    }

    /** @return HasMany<Hora, $this> */
    public function horas(): HasMany
    {
        return $this->hasMany(Hora::class);
    }

    /** @return HasMany<Pago, $this> */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    /** @return HasMany<Tarea, $this> */
    public function tareas(): HasMany
    {
        return $this->hasMany(Tarea::class);
    }

    /** @return HasMany<AiConsulta, $this> */
    public function aiConsultas(): HasMany
    {
        return $this->hasMany(AiConsulta::class);
    }

    /** @return Attribute<string, never> */
    protected function totalPagado(): Attribute
    {
        return Attribute::get(
            fn (): string => app(HonorariosService::class)->totalPagado($this),
        );
    }

    /** @return Attribute<string, never> */
    protected function saldoPendiente(): Attribute
    {
        return Attribute::get(
            fn (): string => app(HonorariosService::class)->saldoPendiente($this),
        );
    }

    /** @return Attribute<float, never> */
    protected function horasTotales(): Attribute
    {
        return Attribute::get(
            fn (): float => app(HonorariosService::class)->horasTotales($this),
        );
    }
}
