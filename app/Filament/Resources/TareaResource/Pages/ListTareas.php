<?php

declare(strict_types=1);

namespace App\Filament\Resources\TareaResource\Pages;

use App\Actions\MoverTareaEnTablero;
use App\Enums\EstadoTarea;
use App\Enums\PrioridadTarea;
use App\Filament\Resources\TareaResource;
use App\Models\Tarea;
use App\Models\User;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Throwable;

class ListTareas extends Page
{
    protected static string $resource = TareaResource::class;

    protected static string $view = 'filament.resources.tarea-resource.pages.tablero-tareas';

    protected static ?string $title = 'Tablero de tareas';

    #[Url]
    public string $filtroAsignada = 'todas';

    #[Url]
    public string $columnaActiva = 'pendiente';

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nueva tarea')
                ->model(Tarea::class)
                ->authorize(fn (): bool => Gate::allows('create', Tarea::class))
                ->modalHeading('Nueva tarea')
                ->modalWidth(MaxWidth::ThreeExtraLarge)
                ->modal()
                ->createAnother(false)
                ->form($this->tareaFormSchema())
                ->mutateFormDataUsing(function (array $data): array {
                    $data['creada_por'] = auth()->id();
                    $data['estado'] = $data['estado'] ?? EstadoTarea::Pendiente->value;
                    $data['orden'] = (int) Tarea::query()
                        ->where('estado', $data['estado'])
                        ->max('orden') + 1;

                    if ($data['estado'] === EstadoTarea::Completada->value
                        || $data['estado'] === EstadoTarea::Completada) {
                        $data['completada_at'] = now('America/Costa_Rica');
                    } else {
                        $data['completada_at'] = null;
                    }

                    return $data;
                })
                ->successNotificationTitle('Tarea creada'),
        ];
    }

    public function editTareaAction(): Action
    {
        return Action::make('editTarea')
            ->label('Editar tarea')
            ->modalHeading(fn (array $arguments): string => 'Editar tarea')
            ->modalWidth(MaxWidth::ThreeExtraLarge)
            ->modalSubmitActionLabel('Guardar')
            ->fillForm(function (array $arguments): array {
                $tarea = Tarea::query()->findOrFail($arguments['tareaId']);

                Gate::authorize('update', $tarea);

                return [
                    'descripcion' => $tarea->descripcion,
                    'asignada_a' => $tarea->asignada_a,
                    'expediente_id' => $tarea->expediente_id,
                    'vence_el' => $tarea->vence_el,
                    'prioridad' => $tarea->prioridad,
                    'estado' => $tarea->estado,
                ];
            })
            ->form($this->tareaFormSchema())
            ->action(function (array $data, array $arguments): void {
                $tarea = Tarea::query()->findOrFail($arguments['tareaId']);
                Gate::authorize('update', $tarea);

                $estado = $data['estado'] ?? $tarea->estado;
                if ($estado instanceof EstadoTarea) {
                    $estadoEnum = $estado;
                } else {
                    $estadoEnum = EstadoTarea::from((string) $estado);
                }

                $data['completada_at'] = $estadoEnum === EstadoTarea::Completada
                    ? ($tarea->completada_at ?? now('America/Costa_Rica'))
                    : null;

                $tarea->update($data);

                Notification::make()
                    ->title('Tarea actualizada')
                    ->success()
                    ->send();
            });
    }

    public function abrirTarea(int $tareaId): void
    {
        $tarea = Tarea::query()->findOrFail($tareaId);
        Gate::authorize('view', $tarea);

        $this->mountAction('editTarea', [
            'tareaId' => $tareaId,
        ]);
    }

    /**
     * @return array<int, Forms\Components\Component>
     */
    protected function tareaFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Detalle')
                ->schema([
                    Forms\Components\Textarea::make('descripcion')
                        ->label('Descripción')
                        ->placeholder('Escribí el detalle de la tarea…')
                        ->required()
                        ->rows(10)
                        ->autosize()
                        ->maxLength(10000)
                        ->columnSpanFull()
                        ->extraInputAttributes([
                            'class' => 'min-h-[14rem] text-base leading-relaxed',
                        ]),
                ]),
            Forms\Components\Section::make('Asignación')
                ->schema([
                    Forms\Components\Grid::make(['default' => 1, 'md' => 2])
                        ->schema([
                            Forms\Components\Select::make('asignada_a')
                                ->label('Asignada a')
                                ->options(fn (): array => User::query()
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->required()
                                ->default(fn (): mixed => auth()->id()),
                            Forms\Components\Select::make('expediente_id')
                                ->label('Expediente')
                                ->relationship('expediente', 'numero_interno')
                                ->searchable()
                                ->preload()
                                ->nullable(),
                            Forms\Components\DatePicker::make('vence_el')
                                ->label('Vence el')
                                ->native(false),
                            Forms\Components\Select::make('prioridad')
                                ->label('Prioridad')
                                ->options(PrioridadTarea::class)
                                ->default(PrioridadTarea::Media)
                                ->required(),
                            Forms\Components\Select::make('estado')
                                ->label('Columna')
                                ->options(EstadoTarea::class)
                                ->default(EstadoTarea::Pendiente)
                                ->required(),
                        ]),
                ]),
        ];
    }

    /**
     * @return array<string, Collection<int, Tarea>>
     */
    public function getColumnas(): array
    {
        $query = $this->tareasBaseQuery()
            ->with(['asignada', 'expediente'])
            ->orderBy('orden')
            ->orderBy('id');

        /** @var Collection<int, Tarea> $tareas */
        $tareas = $query->get();

        $columnas = [];

        foreach (EstadoTarea::columnasTablero() as $estado) {
            $columnas[$estado->value] = $tareas
                ->where('estado', $estado)
                ->values();
        }

        return $columnas;
    }

    /**
     * @return Builder<Tarea>
     */
    protected function tareasBaseQuery(): Builder
    {
        $query = Tarea::query();

        if ($this->filtroAsignada === 'mias' && auth()->id()) {
            $query->where('asignada_a', auth()->id());
        }

        return $query;
    }

    /**
     * @param  list<int|string>  $ordenIds
     */
    public function moverTarea(int $tareaId, string $estado, array $ordenIds = []): void
    {
        $user = auth()->user();
        abort_if($user === null, 403);

        $nuevoEstado = EstadoTarea::tryFrom($estado);
        abort_if($nuevoEstado === null, 422);

        $tarea = Tarea::query()->findOrFail($tareaId);

        try {
            app(MoverTareaEnTablero::class)->handle($tarea, $nuevoEstado, $user, $ordenIds);
            $this->columnaActiva = $nuevoEstado->value;
        } catch (AuthorizationException $exception) {
            Notification::make()
                ->title('No autorizado')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('No se pudo mover la tarea')
                ->danger()
                ->send();
        }
    }

    public function eliminarTarea(int $tareaId): void
    {
        $user = auth()->user();
        abort_if($user === null, 403);

        $tarea = Tarea::query()->findOrFail($tareaId);
        Gate::forUser($user)->authorize('delete', $tarea);
        $tarea->delete();

        Notification::make()
            ->title('Tarea eliminada')
            ->success()
            ->send();
    }

    public function colorPrioridad(PrioridadTarea $prioridad): string
    {
        return match ($prioridad) {
            PrioridadTarea::Alta => 'danger',
            PrioridadTarea::Media => 'warning',
            PrioridadTarea::Baja => 'success',
        };
    }

    public function resumenDescripcion(string $descripcion): string
    {
        $linea = preg_replace('/\s+/', ' ', trim($descripcion)) ?? '';

        if (mb_strlen($linea) <= 120) {
            return $linea;
        }

        return mb_substr($linea, 0, 117).'…';
    }
}
