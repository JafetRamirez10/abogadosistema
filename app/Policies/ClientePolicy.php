<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Cliente;
use App\Models\User;

class ClientePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->belongsToCurrentDespacho($user);
    }

    public function view(User $user, Cliente $cliente): bool
    {
        return $this->sameDespacho($user, $cliente);
    }

    public function create(User $user): bool
    {
        return $this->belongsToCurrentDespacho($user);
    }

    public function update(User $user, Cliente $cliente): bool
    {
        return $this->sameDespacho($user, $cliente)
            && ($user->hasRole('admin') || $user->hasRole('abogado'));
    }

    public function delete(User $user, Cliente $cliente): bool
    {
        return $this->sameDespacho($user, $cliente) && $user->hasRole('admin');
    }

    public function restore(User $user, Cliente $cliente): bool
    {
        return $this->delete($user, $cliente);
    }

    public function forceDelete(User $user, Cliente $cliente): bool
    {
        return $this->delete($user, $cliente);
    }

    private function belongsToCurrentDespacho(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    private function sameDespacho(User $user, Cliente $cliente): bool
    {
        return $user->despachos()->whereKey($cliente->despacho_id)->exists()
            && $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }
}
