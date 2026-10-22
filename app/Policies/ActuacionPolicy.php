<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Actuacion;
use App\Models\User;

class ActuacionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    public function view(User $user, Actuacion $actuacion): bool
    {
        return $this->sameDespacho($user, $actuacion);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    public function update(User $user, Actuacion $actuacion): bool
    {
        return $this->sameDespacho($user, $actuacion)
            && $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    public function delete(User $user, Actuacion $actuacion): bool
    {
        return $this->sameDespacho($user, $actuacion)
            && $user->hasAnyRole(['admin', 'abogado']);
    }

    public function restore(User $user, Actuacion $actuacion): bool
    {
        return $this->delete($user, $actuacion);
    }

    public function forceDelete(User $user, Actuacion $actuacion): bool
    {
        return $user->hasRole('admin') && $this->sameDespacho($user, $actuacion);
    }

    private function sameDespacho(User $user, Actuacion $actuacion): bool
    {
        return $user->despachos()->whereKey($actuacion->despacho_id)->exists()
            && $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }
}
