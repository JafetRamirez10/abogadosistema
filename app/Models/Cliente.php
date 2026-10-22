<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TipoCliente;
use App\Support\Cedula;
use Database\Factories\ClienteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $despacho_id
 * @property TipoCliente $tipo
 * @property string $nombre
 * @property string|null $cedula
 * @property string|null $direccion
 */
class Cliente extends DespachoScopedModel
{
    /** @use HasFactory<ClienteFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'despacho_id',
        'tipo',
        'nombre',
        'cedula',
        'cedula_hash',
        'telefono',
        'email',
        'direccion',
        'notas',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoCliente::class,
            'cedula' => 'encrypted',
            'telefono' => 'encrypted',
            'direccion' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Cliente $cliente): void {
            if (! filled($cliente->cedula)) {
                return;
            }

            $cliente->cedula = Cedula::normalizar((string) $cliente->cedula);
            $cliente->cedula_hash = Cedula::hash((string) $cliente->cedula);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['tipo', 'nombre', 'email', 'notas', 'despacho_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function cedulaEnmascarada(): string
    {
        return Cedula::enmascarar($this->cedula);
    }

    /** @return HasMany<Expediente, $this> */
    public function expedientes(): HasMany
    {
        return $this->hasMany(Expediente::class);
    }
}
