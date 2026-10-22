<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Pago;
use App\Models\User;

class PagoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ver_montos');
    }

    public function view(User $user, Pago $pago): bool
    {
        return $user->can('ver_montos') && $this->sameDespacho($user, $pago);
    }

    public function create(User $user): bool
    {
        return $user->can('ver_montos');
    }

    public function update(User $user, Pago $pago): bool
    {
        return $user->can('ver_montos') && $this->sameDespacho($user, $pago);
    }

    public function delete(User $user, Pago $pago): bool
    {
        return $user->can('ver_montos')
            && $this->sameDespacho($user, $pago)
            && $user->hasAnyRole(['admin', 'abogado']);
    }

    public function restore(User $user, Pago $pago): bool
    {
        return $this->delete($user, $pago);
    }

    public function forceDelete(User $user, Pago $pago): bool
    {
        return $user->hasRole('admin') && $this->sameDespacho($user, $pago);
    }

    private function sameDespacho(User $user, Pago $pago): bool
    {
        return $user->despachos()->whereKey($pago->despacho_id)->exists();
    }
}
