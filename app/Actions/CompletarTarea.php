<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EstadoTarea;
use App\Models\Tarea;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CompletarTarea
{
    public function handle(Tarea $tarea, User $user): Tarea
    {
        Gate::forUser($user)->authorize('update', $tarea);

        if ((int) $tarea->asignada_a !== (int) $user->getKey()) {
            throw new AuthorizationException('Solo la persona asignada puede completar esta tarea.');
        }

        if ($tarea->estado === EstadoTarea::Completada) {
            throw ValidationException::withMessages([
                'tarea' => 'La tarea ya está completada.',
            ]);
        }

        $tarea->update([
            'estado' => EstadoTarea::Completada,
            'completada_at' => now('America/Costa_Rica'),
        ]);

        return $tarea->refresh();
    }
}
