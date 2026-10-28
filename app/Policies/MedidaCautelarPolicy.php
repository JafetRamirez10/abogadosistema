<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MedidaCautelar;
use App\Models\User;

class MedidaCautelarPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    public function view(User $user, MedidaCautelar $medida): bool
    {
        return $this->sameDespacho($user, $medida);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    public function update(User $user, MedidaCautelar $medida): bool
    {
        return $this->sameDespacho($user, $medida)
            && $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    public function delete(User $user, MedidaCautelar $medida): bool
    {
        return $this->sameDespacho($user, $medida)
            && $user->hasAnyRole(['admin', 'abogado']);
    }

    private function sameDespacho(User $user, MedidaCautelar $medida): bool
    {
        return $user->despachos()->whereKey($medida->despacho_id)->exists();
    }
}
