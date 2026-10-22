<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tarea;
use App\Models\User;

class TareaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    public function view(User $user, Tarea $tarea): bool
    {
        return $this->sameDespacho($user, $tarea);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    public function update(User $user, Tarea $tarea): bool
    {
        return $this->sameDespacho($user, $tarea)
            && $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    public function delete(User $user, Tarea $tarea): bool
    {
        return $this->sameDespacho($user, $tarea)
            && $user->hasAnyRole(['admin', 'abogado']);
    }

    public function restore(User $user, Tarea $tarea): bool
    {
        return $this->delete($user, $tarea);
    }

    public function forceDelete(User $user, Tarea $tarea): bool
    {
        return $user->hasRole('admin') && $this->sameDespacho($user, $tarea);
    }

    private function sameDespacho(User $user, Tarea $tarea): bool
    {
        return $user->despachos()->whereKey($tarea->despacho_id)->exists()
            && $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }
}
