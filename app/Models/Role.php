<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Rol de Spatie con relación al despacho (tenant).
 *
 * Spatie maneja los roles por equipo usando la columna definida en
 * permission.team_foreign_key (`despacho_id`). Filament, al scopear el
 * RoleResource de FilamentShield al tenant, necesita una relación Eloquent
 * llamada `despacho`; Spatie no la trae, así que la agregamos acá.
 */
class Role extends SpatieRole
{
    /** @var list<string> */
    protected $fillable = [
        'name',
        'guard_name',
        'despacho_id',
    ];

    public function despacho(): BelongsTo
    {
        return $this->belongsTo(
            Despacho::class,
            config('permission.column_names.team_foreign_key', 'despacho_id'),
        );
    }
}
