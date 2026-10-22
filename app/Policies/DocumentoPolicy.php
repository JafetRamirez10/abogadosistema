<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Documento;
use App\Models\User;

class DocumentoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    public function view(User $user, Documento $documento): bool
    {
        return $this->sameDespacho($user, $documento);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    public function update(User $user, Documento $documento): bool
    {
        return $this->sameDespacho($user, $documento)
            && $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    public function delete(User $user, Documento $documento): bool
    {
        return $this->sameDespacho($user, $documento)
            && $user->hasAnyRole(['admin', 'abogado']);
    }

    public function restore(User $user, Documento $documento): bool
    {
        return $this->delete($user, $documento);
    }

    public function forceDelete(User $user, Documento $documento): bool
    {
        return $user->hasRole('admin') && $this->sameDespacho($user, $documento);
    }

    public function download(User $user, Documento $documento): bool
    {
        return $this->view($user, $documento);
    }

    private function sameDespacho(User $user, Documento $documento): bool
    {
        return $user->despachos()->whereKey($documento->despacho_id)->exists()
            && $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }
}
