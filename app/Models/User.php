<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Moneda;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use LogsActivity;
    use Notifiable;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'email',
        'telefono',
        'tarifa_hora',
        'moneda_tarifa',
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'tarifa_hora' => 'decimal:2',
            'moneda_tarifa' => Moneda::class,
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /** @return Collection<int, Despacho> */
    public function getTenants(Panel $panel): Collection
    {
        return $this->despachos;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->despachos()->whereKey($tenant->getKey())->exists();
    }

    /** @return BelongsToMany<Despacho, $this> */
    public function despachos(): BelongsToMany
    {
        return $this->belongsToMany(Despacho::class)->withTimestamps();
    }

    /** @return HasMany<Expediente, $this> */
    public function expedientesResponsable(): HasMany
    {
        return $this->hasMany(Expediente::class, 'responsable_id');
    }

    public function hasTwoFactorEnabled(): bool
    {
        return filled($this->two_factor_secret) && filled($this->two_factor_confirmed_at);
    }

    public function enableTwoFactorAuthentication(): string
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();

        $this->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $this->generateRecoveryCodes(),
            'two_factor_confirmed_at' => null,
        ])->save();

        return $secret;
    }

    public function confirmTwoFactorAuthentication(string $code): bool
    {
        if (! filled($this->two_factor_secret)) {
            return false;
        }

        $google2fa = new Google2FA;

        if (! $google2fa->verifyKey($this->two_factor_secret, $code)) {
            return false;
        }

        $this->forceFill([
            'two_factor_confirmed_at' => now(),
        ])->save();

        return true;
    }

    public function disableTwoFactorAuthentication(): void
    {
        $this->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    /**
     * @return list<string>
     */
    protected function generateRecoveryCodes(): array
    {
        return Collection::times(8, fn (): string => strtoupper(bin2hex(random_bytes(5))))
            ->values()
            ->all();
    }
}
