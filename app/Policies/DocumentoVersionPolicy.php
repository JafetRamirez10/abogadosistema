<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DocumentoVersion;
use App\Models\User;

class DocumentoVersionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    public function view(User $user, DocumentoVersion $documentoVersion): bool
    {
        return $this->sameDespacho($user, $documentoVersion);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }

    public function update(User $user, DocumentoVersion $documentoVersion): bool
    {
        return $this->sameDespacho($user, $documentoVersion)
            && $user->hasAnyRole(['admin', 'abogado']);
    }

    public function delete(User $user, DocumentoVersion $documentoVersion): bool
    {
        return $this->sameDespacho($user, $documentoVersion)
            && $user->hasRole('admin');
    }

    public function download(User $user, DocumentoVersion $documentoVersion): bool
    {
        return $this->view($user, $documentoVersion);
    }

    private function sameDespacho(User $user, DocumentoVersion $documentoVersion): bool
    {
        return $user->despachos()->whereKey($documentoVersion->despacho_id)->exists()
            && $user->hasAnyRole(['admin', 'abogado', 'asistente']);
    }
}
