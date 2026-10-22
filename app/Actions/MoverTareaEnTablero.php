<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EstadoTarea;
use App\Models\Tarea;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MoverTareaEnTablero
{
    /**
     * @param  list<int|string>  $ordenIds  IDs en el orden final de la columna destino
     */
    public function handle(Tarea $tarea, EstadoTarea $nuevoEstado, User $user, array $ordenIds = []): Tarea
    {
        Gate::forUser($user)->authorize('update', $tarea);

        return DB::transaction(function () use ($tarea, $nuevoEstado, $ordenIds): Tarea {
            $ids = array_values(array_unique(array_map(
                static fn (int|string $id): int => (int) $id,
                $ordenIds
            )));

            if ($ids === []) {
                $ids = [(int) $tarea->getKey()];
            } elseif (! in_array((int) $tarea->getKey(), $ids, true)) {
                array_unshift($ids, (int) $tarea->getKey());
            }

            $completadaAt = $nuevoEstado === EstadoTarea::Completada
                ? ($tarea->completada_at ?? now('America/Costa_Rica'))
                : null;

            foreach ($ids as $index => $id) {
                $attributes = [
                    'estado' => $nuevoEstado,
                    'orden' => $index,
                ];

                if ($id === (int) $tarea->getKey()) {
                    $attributes['completada_at'] = $completadaAt;
                }

                Tarea::query()->whereKey($id)->update($attributes);
            }

            return $tarea->refresh();
        });
    }
}
