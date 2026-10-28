<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PensionAlimentaria;
use App\Models\User;

class PensionAlimentariaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    public function view(User $user, PensionAlimentaria $pension): bool
    {
        return $this->sameDespacho($user, $pension);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'abogado']);
    }

    public function update(User $user, PensionAlimentaria $pension): bool
    {
        return $this->sameDespacho($user, $pension)
            && $user->hasAnyRole(['admin', 'abogado']);
    }

    public function delete(User $user, PensionAlimentaria $pension): bool
    {
        return $this->sameDespacho($user, $pension)
            && $user->hasRole('admin');
    }

    private function sameDespacho(User $user, PensionAlimentaria $pension): bool
    {
        return $user->despachos()->whereKey($pension->despacho_id)->exists();
    }
}
