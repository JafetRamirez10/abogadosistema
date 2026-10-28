<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BeneficiarioFamiliar;
use App\Models\User;

class BeneficiarioFamiliarPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    public function view(User $user, BeneficiarioFamiliar $beneficiario): bool
    {
        return $this->sameDespacho($user, $beneficiario);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    public function update(User $user, BeneficiarioFamiliar $beneficiario): bool
    {
        return $this->sameDespacho($user, $beneficiario)
            && $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    public function delete(User $user, BeneficiarioFamiliar $beneficiario): bool
    {
        return $this->sameDespacho($user, $beneficiario)
            && $user->hasAnyRole(['admin', 'abogado']);
    }

    private function sameDespacho(User $user, BeneficiarioFamiliar $beneficiario): bool
    {
        return $user->despachos()->whereKey($beneficiario->despacho_id)->exists();
    }
}
