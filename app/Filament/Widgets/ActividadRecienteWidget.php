<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Activity;
use App\Models\Cliente;
use App\Models\Despacho;
use App\Models\Documento;
use App\Models\Evento;
use App\Models\Expediente;
use App\Models\Tarea;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class ActividadRecienteWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'filament.widgets.actividad-reciente';

    protected static ?int $sort = 6;

    /** @var int|string|array<string, int|null> */
    protected int|string|array $columnSpan = [
        'md' => 12,
        'lg' => 7,
    ];

    public static function canView(): bool
    {
        return auth()->check();
    }

    /**
     * @return Collection<int, array{activity: Activity, icon: string, label: string}>
     */
    public function getActividades(): Collection
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Despacho) {
            return collect();
        }

        $user = auth()->user();

        if ($user === null) {
            return collect();
        }

        return Activity::query()
            ->with(['causer', 'subject'])
            ->forDespacho($tenant)
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->filter(function (Activity $activity) use ($user): bool {
                $subject = $activity->subject;

                if (! $subject instanceof Model) {
                    return (int) $activity->despacho_id === (int) Filament::getTenant()?->getKey();
                }

                return Gate::forUser($user)->allows('view', $subject);
            })
            ->take(8)
            ->values()
            ->map(fn (Activity $activity): array => [
                'activity' => $activity,
                'icon' => $this->iconoPara($activity),
                'label' => $this->etiquetaPara($activity),
            ]);
    }

    private function iconoPara(Activity $activity): string
    {
        $subject = $activity->subject_type;

        return match ($subject) {
            Cliente::class => 'heroicon-o-user',
            Expediente::class => 'heroicon-o-folder-open',
            Documento::class => 'heroicon-o-document-text',
            Evento::class => 'heroicon-o-calendar-days',
            Tarea::class => 'heroicon-o-clipboard-document-check',
            default => match ($activity->event) {
                'created' => 'heroicon-o-plus-circle',
                'updated' => 'heroicon-o-pencil-square',
                'deleted' => 'heroicon-o-trash',
                default => 'heroicon-o-bell',
            },
        };
    }

    private function etiquetaPara(Activity $activity): string
    {
        $evento = $activity->event ?? $activity->description;
        $tipo = class_basename((string) $activity->subject_type) ?: 'Registro';
        $causerModel = $activity->causer;
        $causer = $causerModel instanceof User ? $causerModel->name : 'Sistema';

        return trim(sprintf('%s · %s · %s', $tipo, $evento, $causer));
    }
}
