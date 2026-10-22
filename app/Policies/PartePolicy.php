<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Parte;
use App\Models\User;

class PartePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    public function view(User $user, Parte $parte): bool
    {
        return $this->sameDespacho($user, $parte);
    }

    public function create(User $user): bool
    {
        // Asistente puede crear partes (actuaciones/eventos/documentos en fases posteriores).
        return $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    public function update(User $user, Parte $parte): bool
    {
        return $this->sameDespacho($user, $parte)
            && $user->hasAnyRole(['admin', 'abogado']);
    }

    public function delete(User $user, Parte $parte): bool
    {
        return $this->sameDespacho($user, $parte)
            && $user->hasAnyRole(['admin', 'abogado']);
    }

    public function restore(User $user, Parte $parte): bool
    {
        return $this->delete($user, $parte);
    }

    public function forceDelete(User $user, Parte $parte): bool
    {
        return $user->hasRole('admin') && $this->sameDespacho($user, $parte);
    }

    private function sameDespacho(User $user, Parte $parte): bool
    {
        return $user->despachos()->whereKey($parte->despacho_id)->exists()
            && $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }
}
