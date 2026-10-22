<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Actions\CompletarTarea;
use App\Enums\EstadoTarea;
use App\Enums\PrioridadTarea;
use App\Filament\Resources\TareaResource;
use App\Models\Tarea;
use Filament\Notifications\Notification;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Throwable;

class MisTareasWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'filament.widgets.mis-tareas';

    protected static ?int $sort = 4;

    /** @var int|string|array<string, int|null> */
    protected int|string|array $columnSpan = [
        'md' => 12,
        'lg' => 3,
    ];

    public static function canView(): bool
    {
        return Gate::allows('viewAny', Tarea::class);
    }

    /**
     * @return Collection<int, Tarea>
     */
    public function getTareas(): Collection
    {
        $user = auth()->user();

        if ($user === null) {
            return collect();
        }

        return Tarea::query()
            ->with(['expediente'])
            ->pendientesDe($user)
            ->orderByRaw("CASE prioridad WHEN 'alta' THEN 1 WHEN 'media' THEN 2 ELSE 3 END")
            ->orderBy('vence_el')
            ->limit(8)
            ->get();
    }

    public function completar(int $tareaId): void
    {
        $user = auth()->user();

        abort_if($user === null, 403);

        $tarea = Tarea::query()->findOrFail($tareaId);

        try {
            app(CompletarTarea::class)->handle($tarea, $user);

            Notification::make()
                ->title('Tarea completada')
                ->success()
                ->send();
        } catch (AuthorizationException $exception) {
            Notification::make()
                ->title('No autorizado')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        } catch (ValidationException $exception) {
            Notification::make()
                ->title('No se pudo completar')
                ->body(collect($exception->errors())->flatten()->first() ?: 'Validación fallida.')
                ->warning()
                ->send();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Error al completar la tarea')
                ->danger()
                ->send();
        }
    }

    public function esVencida(Tarea $tarea): bool
    {
        if ($tarea->estado !== EstadoTarea::Pendiente || $tarea->vence_el === null) {
            return false;
        }

        return $tarea->vence_el->toDateString() < now('America/Costa_Rica')->toDateString();
    }

    public function colorPrioridad(PrioridadTarea $prioridad): string
    {
        return match ($prioridad) {
            PrioridadTarea::Alta => 'danger',
            PrioridadTarea::Media => 'warning',
            PrioridadTarea::Baja => 'success',
        };
    }

    public function verTodasUrl(): string
    {
        return TareaResource::getUrl('index');
    }
}
