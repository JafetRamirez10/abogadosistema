<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Despacho;
use App\Models\User;

class DespachoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Despacho $despacho): bool
    {
        return $user->despachos()->whereKey($despacho->getKey())->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Despacho $despacho): bool
    {
        return $user->despachos()->whereKey($despacho->getKey())->exists();
    }

    public function delete(User $user, Despacho $despacho): bool
    {
        return $user->can('gestionar_usuarios');
    }
}
