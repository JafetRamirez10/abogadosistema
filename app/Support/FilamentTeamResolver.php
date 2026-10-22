<?php

declare(strict_types=1);

namespace App\Support;

use Filament\Facades\Filament;
use Spatie\Permission\Contracts\PermissionsTeamResolver;
use Throwable;

/**
 * Resuelve el "team" de Spatie a partir del tenant actual de Filament.
 *
 * El problema con el resolver por defecto es el orden: si `setPermissionsTeamId()`
 * se llama después de que Filament ya evaluó algún policy, la comprobación de
 * permisos se hace con team = null y devuelve 403 aunque el usuario tenga el rol.
 * Derivando el team del tenant en el momento de la consulta, el orden deja de
 * importar.
 */
class FilamentTeamResolver implements PermissionsTeamResolver
{
    private int|string|null $explicit = null;

    public function getPermissionsTeamId(): int|string|null
    {
        if ($this->explicit !== null) {
            return $this->explicit;
        }

        try {
            return Filament::getTenant()?->getKey();
        } catch (Throwable) {
            // Sin panel activo (consola, seeders, queues)
            return null;
        }
    }

    public function setPermissionsTeamId($id): void
    {
        $this->explicit = $id;
    }
}
