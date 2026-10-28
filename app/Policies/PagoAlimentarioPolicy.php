<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PagoAlimentario;
use App\Models\User;

class PagoAlimentarioPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    public function view(User $user, PagoAlimentario $pago): bool
    {
        return $this->sameDespacho($user, $pago);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    public function update(User $user, PagoAlimentario $pago): bool
    {
        return $this->sameDespacho($user, $pago)
            && $user->hasAnyRole(['admin', 'abogado']);
    }

    public function delete(User $user, PagoAlimentario $pago): bool
    {
        return $this->sameDespacho($user, $pago)
            && $user->hasRole('admin');
    }

    private function sameDespacho(User $user, PagoAlimentario $pago): bool
    {
        return $user->despachos()->whereKey($pago->despacho_id)->exists();
    }
}
