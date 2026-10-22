<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Hora;
use App\Models\User;

class HoraPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ver_montos');
    }

    public function view(User $user, Hora $hora): bool
    {
        return $user->can('ver_montos') && $this->sameDespacho($user, $hora);
    }

    public function create(User $user): bool
    {
        return $user->can('ver_montos');
    }

    public function update(User $user, Hora $hora): bool
    {
        return $user->can('ver_montos') && $this->sameDespacho($user, $hora);
    }

    public function delete(User $user, Hora $hora): bool
    {
        return $user->can('ver_montos')
            && $this->sameDespacho($user, $hora)
            && $user->hasAnyRole(['admin', 'abogado']);
    }

    public function restore(User $user, Hora $hora): bool
    {
        return $this->delete($user, $hora);
    }

    public function forceDelete(User $user, Hora $hora): bool
    {
        return $user->hasRole('admin') && $this->sameDespacho($user, $hora);
    }

    private function sameDespacho(User $user, Hora $hora): bool
    {
        return $user->despachos()->whereKey($hora->despacho_id)->exists();
    }
}
