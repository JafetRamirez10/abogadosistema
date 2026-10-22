<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Plantilla;
use App\Models\User;

class PlantillaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('gestionar_plantillas');
    }

    public function view(User $user, Plantilla $plantilla): bool
    {
        return $this->sameDespacho($user, $plantilla) && $user->can('gestionar_plantillas');
    }

    public function create(User $user): bool
    {
        return $user->can('gestionar_plantillas');
    }

    public function update(User $user, Plantilla $plantilla): bool
    {
        return $this->sameDespacho($user, $plantilla) && $user->can('gestionar_plantillas');
    }

    public function delete(User $user, Plantilla $plantilla): bool
    {
        return $this->sameDespacho($user, $plantilla) && $user->can('gestionar_plantillas');
    }

    public function restore(User $user, Plantilla $plantilla): bool
    {
        return $this->delete($user, $plantilla);
    }

    public function forceDelete(User $user, Plantilla $plantilla): bool
    {
        return $this->delete($user, $plantilla);
    }

    private function sameDespacho(User $user, Plantilla $plantilla): bool
    {
        return $user->despachos()->whereKey($plantilla->despacho_id)->exists();
    }
}
