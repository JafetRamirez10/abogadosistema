<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Expediente;
use App\Models\User;

class ExpedientePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    public function view(User $user, Expediente $expediente): bool
    {
        return $this->sameDespacho($user, $expediente);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'abogado']);
    }

    public function update(User $user, Expediente $expediente): bool
    {
        if (! $this->sameDespacho($user, $expediente)) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('abogado')) {
            return (int) $expediente->responsable_id === (int) $user->getKey();
        }

        return false;
    }

    public function delete(User $user, Expediente $expediente): bool
    {
        return $this->sameDespacho($user, $expediente) && $user->hasRole('admin');
    }

    public function restore(User $user, Expediente $expediente): bool
    {
        return $this->delete($user, $expediente);
    }

    public function forceDelete(User $user, Expediente $expediente): bool
    {
        return $this->delete($user, $expediente);
    }

    private function sameDespacho(User $user, Expediente $expediente): bool
    {
        return $user->despachos()->whereKey($expediente->despacho_id)->exists()
            && $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }
}
