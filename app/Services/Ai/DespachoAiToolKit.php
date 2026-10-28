<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Actions\CrearExpediente;
use App\DTOs\ExpedienteData;
use App\Enums\EstadoExpediente;
use App\Enums\EstadoTarea;
use App\Enums\Materia;
use App\Enums\Moneda;
use App\Enums\PrioridadTarea;
use App\Enums\TipoHonorario;
use App\Models\Cliente;
use App\Models\Expediente;
use App\Models\Tarea;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Herramientas del asistente con acceso controlado al despacho.
 * Crear/consultar: ejecución inmediata.
 * Actualizar/eliminar: requieren confirmación humana previa.
 */
final class DespachoAiToolKit
{
    /** @return list<string> */
    public function toolsQueRequierenConfirmacion(): array
    {
        return [
            'actualizar_tarea',
            'eliminar_tarea',
            'actualizar_expediente',
            'eliminar_expediente',
        ];
    }

    public function requiereConfirmacion(string $tool): bool
    {
        return in_array($tool, $this->toolsQueRequierenConfirmacion(), true);
    }

    /**
     * Esquemas OpenAI/DeepSeek compatible.
     *
     * @return list<array<string, mixed>>
     */
    public function schemas(): array
    {
        return [
            $this->fn('buscar_clientes', 'Busca clientes del despacho por nombre o correo.', [
                'type' => 'object',
                'properties' => [
                    'q' => ['type' => 'string', 'description' => 'Texto a buscar'],
                ],
                'required' => ['q'],
            ]),
            $this->fn('buscar_expedientes', 'Busca expedientes/casos por número o título.', [
                'type' => 'object',
                'properties' => [
                    'q' => ['type' => 'string'],
                ],
                'required' => ['q'],
            ]),
            $this->fn('buscar_tareas', 'Busca tareas por título o descripción.', [
                'type' => 'object',
                'properties' => [
                    'q' => ['type' => 'string'],
                    'estado' => ['type' => 'string', 'enum' => ['pendiente', 'en_progreso', 'completada']],
                ],
                'required' => ['q'],
            ]),
            $this->fn('crear_expediente', 'Crea un expediente/caso nuevo. Requiere cliente_id existente.', [
                'type' => 'object',
                'properties' => [
                    'cliente_id' => ['type' => 'integer'],
                    'titulo' => ['type' => 'string'],
                    'materia' => ['type' => 'string', 'enum' => array_map(fn (Materia $m) => $m->value, Materia::cases())],
                    'numero_interno' => ['type' => 'string', 'description' => 'Opcional; si no se envía se genera'],
                    'descripcion' => ['type' => 'string'],
                    'juzgado' => ['type' => 'string'],
                ],
                'required' => ['cliente_id', 'titulo', 'materia'],
            ]),
            $this->fn('crear_tarea', 'Crea una tarea en el tablero Kanban.', [
                'type' => 'object',
                'properties' => [
                    'titulo' => ['type' => 'string'],
                    'descripcion' => ['type' => 'string'],
                    'expediente_id' => ['type' => 'integer'],
                    'asignada_a' => ['type' => 'integer', 'description' => 'User id; default el usuario actual'],
                    'prioridad' => ['type' => 'string', 'enum' => ['alta', 'media', 'baja']],
                    'vence_el' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                    'estado' => ['type' => 'string', 'enum' => ['pendiente', 'en_progreso', 'completada']],
                ],
                'required' => ['titulo'],
            ]),
            $this->fn('actualizar_tarea', 'Actualiza campos de una tarea. REQUIERE confirmación del usuario antes de ejecutarse.', [
                'type' => 'object',
                'properties' => [
                    'tarea_id' => ['type' => 'integer'],
                    'titulo' => ['type' => 'string'],
                    'descripcion' => ['type' => 'string'],
                    'prioridad' => ['type' => 'string', 'enum' => ['alta', 'media', 'baja']],
                    'estado' => ['type' => 'string', 'enum' => ['pendiente', 'en_progreso', 'completada']],
                    'vence_el' => ['type' => 'string'],
                ],
                'required' => ['tarea_id'],
            ]),
            $this->fn('eliminar_tarea', 'Elimina una tarea. REQUIERE confirmación del usuario antes de ejecutarse.', [
                'type' => 'object',
                'properties' => [
                    'tarea_id' => ['type' => 'integer'],
                ],
                'required' => ['tarea_id'],
            ]),
            $this->fn('actualizar_expediente', 'Actualiza un expediente. REQUIERE confirmación del usuario.', [
                'type' => 'object',
                'properties' => [
                    'expediente_id' => ['type' => 'integer'],
                    'titulo' => ['type' => 'string'],
                    'descripcion' => ['type' => 'string'],
                    'estado' => ['type' => 'string', 'enum' => array_map(fn (EstadoExpediente $e) => $e->value, EstadoExpediente::cases())],
                    'juzgado' => ['type' => 'string'],
                ],
                'required' => ['expediente_id'],
            ]),
            $this->fn('eliminar_expediente', 'Elimina (soft delete) un expediente. REQUIERE confirmación del usuario.', [
                'type' => 'object',
                'properties' => [
                    'expediente_id' => ['type' => 'integer'],
                ],
                'required' => ['expediente_id'],
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{ok: bool, message: string, data?: mixed}
     */
    public function execute(string $tool, array $args, User $user, int $despachoId): array
    {
        try {
            return match ($tool) {
                'buscar_clientes' => $this->buscarClientes($args, $user, $despachoId),
                'buscar_expedientes' => $this->buscarExpedientes($args, $user, $despachoId),
                'buscar_tareas' => $this->buscarTareas($args, $user, $despachoId),
                'crear_expediente' => $this->crearExpediente($args, $user, $despachoId),
                'crear_tarea' => $this->crearTarea($args, $user, $despachoId),
                'actualizar_tarea' => $this->actualizarTarea($args, $user, $despachoId),
                'eliminar_tarea' => $this->eliminarTarea($args, $user, $despachoId),
                'actualizar_expediente' => $this->actualizarExpediente($args, $user, $despachoId),
                'eliminar_expediente' => $this->eliminarExpediente($args, $user, $despachoId),
                default => ['ok' => false, 'message' => "Herramienta desconocida: {$tool}"],
            };
        } catch (ValidationException $e) {
            return ['ok' => false, 'message' => 'Validación: '.implode(' ', $e->validator->errors()->all())];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Error: '.$e->getMessage()];
        }
    }

    /**
     * Texto claro para pedir confirmación al humano.
     *
     * @param  array<string, mixed>  $args
     */
    public function resumenConfirmacion(string $tool, array $args, int $despachoId): string
    {
        return match ($tool) {
            'eliminar_tarea' => $this->resumenEliminarTarea($args, $despachoId),
            'actualizar_tarea' => $this->resumenActualizarTarea($args, $despachoId),
            'eliminar_expediente' => $this->resumenEliminarExpediente($args, $despachoId),
            'actualizar_expediente' => $this->resumenActualizarExpediente($args, $despachoId),
            default => "Se ejecutará «{$tool}» con: ".json_encode($args, JSON_UNESCAPED_UNICODE),
        };
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    private function fn(string $name, string $description, array $parameters): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => $description,
                'parameters' => $parameters,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{ok: bool, message: string, data?: mixed}
     */
    private function buscarClientes(array $args, User $user, int $despachoId): array
    {
        if (! Gate::forUser($user)->allows('viewAny', Cliente::class)) {
            return ['ok' => false, 'message' => 'Sin permiso para ver clientes.'];
        }

        $q = trim((string) ($args['q'] ?? ''));
        $rows = Cliente::query()
            ->where('despacho_id', $despachoId)
            ->when($q !== '', fn ($query) => $query->where(function ($inner) use ($q): void {
                $inner->where('nombre', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            }))
            ->orderBy('nombre')
            ->limit(10)
            ->get(['id', 'nombre', 'tipo', 'email']);

        return [
            'ok' => true,
            'message' => $rows->isEmpty() ? 'Sin clientes.' : 'Clientes encontrados.',
            'data' => $rows->map(fn (Cliente $c): array => [
                'id' => $c->id,
                'nombre' => $c->nombre,
                'tipo' => $c->tipo->value,
                'email' => $c->email,
            ])->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{ok: bool, message: string, data?: mixed}
     */
    private function buscarExpedientes(array $args, User $user, int $despachoId): array
    {
        if (! Gate::forUser($user)->allows('viewAny', Expediente::class)) {
            return ['ok' => false, 'message' => 'Sin permiso para ver expedientes.'];
        }

        $q = trim((string) ($args['q'] ?? ''));
        $rows = Expediente::query()
            ->where('despacho_id', $despachoId)
            ->when($q !== '', fn ($query) => $query->where(function ($inner) use ($q): void {
                $inner->where('titulo', 'like', "%{$q}%")
                    ->orWhere('numero_interno', 'like', "%{$q}%");
            }))
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'numero_interno', 'titulo', 'materia', 'estado']);

        return [
            'ok' => true,
            'message' => $rows->isEmpty() ? 'Sin expedientes.' : 'Expedientes encontrados.',
            'data' => $rows->map(fn (Expediente $e): array => [
                'id' => $e->id,
                'numero_interno' => $e->numero_interno,
                'titulo' => $e->titulo,
                'materia' => $e->materia->value,
                'estado' => $e->estado->value,
            ])->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{ok: bool, message: string, data?: mixed}
     */
    private function buscarTareas(array $args, User $user, int $despachoId): array
    {
        if (! Gate::forUser($user)->allows('viewAny', Tarea::class)) {
            return ['ok' => false, 'message' => 'Sin permiso para ver tareas.'];
        }

        $q = trim((string) ($args['q'] ?? ''));
        $estado = $args['estado'] ?? null;

        $rows = Tarea::query()
            ->where('despacho_id', $despachoId)
            ->when($q !== '', fn ($query) => $query->where(function ($inner) use ($q): void {
                $inner->where('titulo', 'like', "%{$q}%")
                    ->orWhere('descripcion', 'like', "%{$q}%");
            }))
            ->when(is_string($estado) && $estado !== '', fn ($query) => $query->where('estado', $estado))
            ->orderByDesc('id')
            ->limit(15)
            ->get(['id', 'titulo', 'estado', 'prioridad', 'vence_el', 'expediente_id']);

        return [
            'ok' => true,
            'message' => $rows->isEmpty() ? 'Sin tareas.' : 'Tareas encontradas.',
            'data' => $rows->map(fn (Tarea $t): array => [
                'id' => $t->id,
                'titulo' => $t->titulo,
                'estado' => $t->estado->value,
                'prioridad' => $t->prioridad->value,
                'vence_el' => $t->vence_el?->toDateString(),
                'expediente_id' => $t->expediente_id,
            ])->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{ok: bool, message: string, data?: mixed}
     */
    private function crearExpediente(array $args, User $user, int $despachoId): array
    {
        if (! Gate::forUser($user)->allows('create', Expediente::class)) {
            return ['ok' => false, 'message' => 'Sin permiso para crear expedientes.'];
        }

        $clienteId = (int) ($args['cliente_id'] ?? 0);
        $cliente = Cliente::query()->where('despacho_id', $despachoId)->find($clienteId);
        if ($cliente === null) {
            return ['ok' => false, 'message' => 'cliente_id inválido en este despacho. Buscá el cliente primero.'];
        }

        $numero = trim((string) ($args['numero_interno'] ?? ''));
        if ($numero === '') {
            $numero = 'EXP-'.now('America/Costa_Rica')->format('Y').'-'.str_pad((string) (Expediente::query()->where('despacho_id', $despachoId)->count() + 1), 3, '0', STR_PAD_LEFT);
        }

        $expediente = app(CrearExpediente::class)->handle(ExpedienteData::fromArray([
            'despacho_id' => $despachoId,
            'cliente_id' => $clienteId,
            'responsable_id' => $user->getKey(),
            'numero_interno' => $numero,
            'titulo' => (string) $args['titulo'],
            'materia' => (string) $args['materia'],
            'fecha_inicio' => now('America/Costa_Rica')->toDateString(),
            'tipo_honorario' => TipoHonorario::Fijo->value,
            'moneda' => Moneda::CRC->value,
            'estado' => EstadoExpediente::Pendiente->value,
            'descripcion' => isset($args['descripcion']) ? (string) $args['descripcion'] : null,
            'juzgado' => isset($args['juzgado']) ? (string) $args['juzgado'] : null,
        ]));

        return [
            'ok' => true,
            'message' => "Expediente creado: {$expediente->numero_interno} (id {$expediente->id}).",
            'data' => [
                'id' => $expediente->id,
                'numero_interno' => $expediente->numero_interno,
                'titulo' => $expediente->titulo,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{ok: bool, message: string, data?: mixed}
     */
    private function crearTarea(array $args, User $user, int $despachoId): array
    {
        if (! Gate::forUser($user)->allows('create', Tarea::class)) {
            return ['ok' => false, 'message' => 'Sin permiso para crear tareas.'];
        }

        $asignada = (int) ($args['asignada_a'] ?? $user->getKey());
        if (! User::query()->whereKey($asignada)->whereHas('despachos', fn ($q) => $q->whereKey($despachoId))->exists()) {
            return ['ok' => false, 'message' => 'asignada_a no pertenece al despacho.'];
        }

        $expedienteId = isset($args['expediente_id']) ? (int) $args['expediente_id'] : null;
        if ($expedienteId !== null) {
            $ok = Expediente::query()->where('despacho_id', $despachoId)->whereKey($expedienteId)->exists();
            if (! $ok) {
                return ['ok' => false, 'message' => 'expediente_id inválido.'];
            }
        }

        $estado = EstadoTarea::tryFrom((string) ($args['estado'] ?? 'pendiente')) ?? EstadoTarea::Pendiente;
        $prioridad = PrioridadTarea::tryFrom((string) ($args['prioridad'] ?? 'media')) ?? PrioridadTarea::Media;
        $orden = (int) Tarea::query()->where('despacho_id', $despachoId)->where('estado', $estado)->max('orden') + 1;

        $tarea = Tarea::query()->create([
            'despacho_id' => $despachoId,
            'expediente_id' => $expedienteId,
            'asignada_a' => $asignada,
            'creada_por' => $user->getKey(),
            'titulo' => mb_substr((string) $args['titulo'], 0, 255),
            'descripcion' => isset($args['descripcion']) ? (string) $args['descripcion'] : null,
            'prioridad' => $prioridad,
            'estado' => $estado,
            'vence_el' => $args['vence_el'] ?? null,
            'orden' => $orden,
            'completada_at' => $estado === EstadoTarea::Completada ? now('America/Costa_Rica') : null,
        ]);

        return [
            'ok' => true,
            'message' => "Tarea creada #{$tarea->id}: {$tarea->titulo}",
            'data' => ['id' => $tarea->id, 'titulo' => $tarea->titulo, 'estado' => $tarea->estado->value],
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{ok: bool, message: string, data?: mixed}
     */
    private function actualizarTarea(array $args, User $user, int $despachoId): array
    {
        $tarea = Tarea::query()->where('despacho_id', $despachoId)->find((int) ($args['tarea_id'] ?? 0));
        if ($tarea === null) {
            return ['ok' => false, 'message' => 'Tarea no encontrada.'];
        }
        Gate::forUser($user)->authorize('update', $tarea);

        $payload = [];
        foreach (['titulo', 'descripcion', 'vence_el'] as $field) {
            if (array_key_exists($field, $args) && $args[$field] !== null) {
                $payload[$field] = $args[$field];
            }
        }
        if (isset($args['prioridad'])) {
            $payload['prioridad'] = PrioridadTarea::from((string) $args['prioridad']);
        }
        if (isset($args['estado'])) {
            $estado = EstadoTarea::from((string) $args['estado']);
            $payload['estado'] = $estado;
            $payload['completada_at'] = $estado === EstadoTarea::Completada
                ? ($tarea->completada_at ?? now('America/Costa_Rica'))
                : null;
        }

        $tarea->update($payload);

        return ['ok' => true, 'message' => "Tarea #{$tarea->id} actualizada.", 'data' => ['id' => $tarea->id]];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{ok: bool, message: string, data?: mixed}
     */
    private function eliminarTarea(array $args, User $user, int $despachoId): array
    {
        $tarea = Tarea::query()->where('despacho_id', $despachoId)->find((int) ($args['tarea_id'] ?? 0));
        if ($tarea === null) {
            return ['ok' => false, 'message' => 'Tarea no encontrada.'];
        }
        Gate::forUser($user)->authorize('delete', $tarea);
        $id = $tarea->id;
        $titulo = $tarea->titulo;
        $tarea->delete();

        return ['ok' => true, 'message' => "Tarea #{$id} «{$titulo}» eliminada."];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{ok: bool, message: string, data?: mixed}
     */
    private function actualizarExpediente(array $args, User $user, int $despachoId): array
    {
        $expediente = Expediente::query()->where('despacho_id', $despachoId)->find((int) ($args['expediente_id'] ?? 0));
        if ($expediente === null) {
            return ['ok' => false, 'message' => 'Expediente no encontrado.'];
        }
        Gate::forUser($user)->authorize('update', $expediente);

        $payload = [];
        foreach (['titulo', 'descripcion', 'juzgado'] as $field) {
            if (array_key_exists($field, $args) && $args[$field] !== null) {
                $payload[$field] = $args[$field];
            }
        }
        if (isset($args['estado'])) {
            $payload['estado'] = EstadoExpediente::from((string) $args['estado']);
        }

        $expediente->update($payload);

        return ['ok' => true, 'message' => "Expediente {$expediente->numero_interno} actualizado.", 'data' => ['id' => $expediente->id]];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{ok: bool, message: string, data?: mixed}
     */
    private function eliminarExpediente(array $args, User $user, int $despachoId): array
    {
        $expediente = Expediente::query()->where('despacho_id', $despachoId)->find((int) ($args['expediente_id'] ?? 0));
        if ($expediente === null) {
            return ['ok' => false, 'message' => 'Expediente no encontrado.'];
        }
        Gate::forUser($user)->authorize('delete', $expediente);
        $num = $expediente->numero_interno;
        $expediente->delete();

        return ['ok' => true, 'message' => "Expediente {$num} eliminado (borrado lógico)."];
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function resumenEliminarTarea(array $args, int $despachoId): string
    {
        $tarea = Tarea::query()->where('despacho_id', $despachoId)->find((int) ($args['tarea_id'] ?? 0));
        if ($tarea === null) {
            return 'Se intentará eliminar una tarea inexistente (id '.($args['tarea_id'] ?? '?').').';
        }

        return "Se va a ELIMINAR la tarea #{$tarea->id} «{$tarea->titulo}» (estado: {$tarea->estado->value}). Esta acción no se puede deshacer fácilmente.";
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function resumenActualizarTarea(array $args, int $despachoId): string
    {
        $tarea = Tarea::query()->where('despacho_id', $despachoId)->find((int) ($args['tarea_id'] ?? 0));
        $cambios = collect($args)->except('tarea_id')->all();

        if ($tarea === null) {
            return 'Se intentará modificar una tarea inexistente.';
        }

        return 'Se va a MODIFICAR la tarea #'.$tarea->id.' «'.$tarea->titulo.'». Cambios propuestos: '
            .json_encode($cambios, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function resumenEliminarExpediente(array $args, int $despachoId): string
    {
        $e = Expediente::query()->where('despacho_id', $despachoId)->find((int) ($args['expediente_id'] ?? 0));
        if ($e === null) {
            return 'Se intentará eliminar un expediente inexistente.';
        }

        return "Se va a ELIMINAR (borrado lógico) el expediente {$e->numero_interno} — «{$e->titulo}».";
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function resumenActualizarExpediente(array $args, int $despachoId): string
    {
        $e = Expediente::query()->where('despacho_id', $despachoId)->find((int) ($args['expediente_id'] ?? 0));
        $cambios = collect($args)->except('expediente_id')->all();
        if ($e === null) {
            return 'Se intentará modificar un expediente inexistente.';
        }

        return "Se va a MODIFICAR el expediente {$e->numero_interno} — «{$e->titulo}». Cambios: "
            .json_encode($cambios, JSON_UNESCAPED_UNICODE);
    }
}
