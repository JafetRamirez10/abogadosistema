<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DespachoFactory;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Despacho extends Model implements HasName
{
    /** @use HasFactory<DespachoFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'nombre',
        'slug',
        'cedula_juridica',
        'telefono',
        'email',
        'direccion',
    ];

    protected static function booted(): void
    {
        static::creating(function (Despacho $despacho): void {
            if (filled($despacho->slug)) {
                return;
            }

            $base = Str::slug($despacho->nombre) ?: 'despacho';
            $slug = $base;
            $counter = 1;

            while (static::query()->where('slug', $slug)->exists()) {
                $slug = $base.'-'.$counter;
                $counter++;
            }

            $despacho->slug = $slug;
        });
    }

    public function getFilamentName(): string
    {
        return $this->nombre;
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /** @return HasMany<Cliente, $this> */
    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }

    /** @return HasMany<Expediente, $this> */
    public function expedientes(): HasMany
    {
        return $this->hasMany(Expediente::class);
    }

    /** @return HasMany<Plantilla, $this> */
    public function plantillas(): HasMany
    {
        return $this->hasMany(Plantilla::class);
    }

    /** @return HasMany<Documento, $this> */
    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class);
    }
}
