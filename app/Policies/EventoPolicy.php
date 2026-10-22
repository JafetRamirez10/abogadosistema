<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Evento;
use App\Models\User;

class EventoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    public function view(User $user, Evento $evento): bool
    {
        return $this->sameDespacho($user, $evento);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    public function update(User $user, Evento $evento): bool
    {
        return $this->sameDespacho($user, $evento)
            && $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    public function delete(User $user, Evento $evento): bool
    {
        return $this->sameDespacho($user, $evento)
            && $user->hasAnyRole(['admin', 'abogado']);
    }

    public function restore(User $user, Evento $evento): bool
    {
        return $this->delete($user, $evento);
    }

    public function forceDelete(User $user, Evento $evento): bool
    {
        return $user->hasRole('admin') && $this->sameDespacho($user, $evento);
    }

    private function sameDespacho(User $user, Evento $evento): bool
    {
        return $user->despachos()->whereKey($evento->despacho_id)->exists()
            && $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }
}
