<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\EstadoEvento;
use App\Enums\TipoEvento;
use App\Filament\Pages\Agenda;
use App\Models\Evento;
use App\Services\PeriodoService;
use Carbon\CarbonImmutable;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class AudienciasProximasWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'filament.widgets.audiencias-proximas';

    protected static ?int $sort = 3;

    /** @var int|string|array<string, int|null> */
    protected int|string|array $columnSpan = [
        'md' => 12,
        'lg' => 3,
    ];

    public static function canView(): bool
    {
        return Gate::allows('viewAny', Evento::class);
    }

    /**
     * @return Collection<int, array{evento: Evento, etiqueta_fecha: string}>
     */
    public function getAudiencias(): Collection
    {
        $fecha = app(PeriodoService::class)->resolverFecha($this->filters['fecha'] ?? null);
        $hoy = $fecha->startOfDay();
        $manana = $hoy->addDay();

        return Evento::query()
            ->with(['expediente.cliente'])
            ->where('tipo', TipoEvento::Audiencia)
            ->where('estado', '!=', EstadoEvento::Cancelada)
            ->where('inicio', '>=', $hoy)
            ->orderBy('inicio')
            ->limit(6)
            ->get()
            ->map(fn (Evento $evento): array => [
                'evento' => $evento,
                'etiqueta_fecha' => $this->etiquetaFecha(
                    CarbonImmutable::parse($evento->inicio)->timezone('America/Costa_Rica'),
                    $hoy,
                    $manana,
                ),
            ]);
    }

    public function calendarioUrl(): string
    {
        return Agenda::getUrl();
    }

    private function etiquetaFecha(CarbonImmutable $inicio, CarbonImmutable $hoy, CarbonImmutable $manana): string
    {
        if ($inicio->isSameDay($hoy)) {
            return 'Hoy';
        }

        if ($inicio->isSameDay($manana)) {
            return 'Mañana';
        }

        return $inicio->format('d/m');
    }
}
