<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CuotaAlimentaria;
use App\Models\User;

class CuotaAlimentariaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    public function view(User $user, CuotaAlimentaria $cuota): bool
    {
        return $this->sameDespacho($user, $cuota);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    public function update(User $user, CuotaAlimentaria $cuota): bool
    {
        return $this->sameDespacho($user, $cuota)
            && $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    public function delete(User $user, CuotaAlimentaria $cuota): bool
    {
        return $this->sameDespacho($user, $cuota)
            && $user->hasAnyRole(['admin', 'abogado']);
    }

    private function sameDespacho(User $user, CuotaAlimentaria $cuota): bool
    {
        return $user->despachos()->whereKey($cuota->despacho_id)->exists();
    }
}
