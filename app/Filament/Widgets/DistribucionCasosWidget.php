<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\EstadoExpediente;
use App\Models\Expediente;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Gate;

class DistribucionCasosWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Distribución de casos';

    protected static ?int $sort = 5;

    /** @var int|string|array<string, int|null> */
    protected int|string|array $columnSpan = [
        'md' => 12,
        'lg' => 5,
    ];

    public static function canView(): bool
    {
        return Gate::allows('viewAny', Expediente::class);
    }

    protected function getData(): array
    {
        $counts = Expediente::query()
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $labels = [];
        $data = [];
        $colors = [];

        foreach (EstadoExpediente::cases() as $estado) {
            $labels[] = $estado->getLabel();
            $data[] = (int) ($counts[$estado->value] ?? 0);
            $colors[] = match ($estado) {
                EstadoExpediente::EnProgreso => '#2563EB',
                EstadoExpediente::Pendiente => '#F59E0B',
                EstadoExpediente::EnRevision => '#7C3AED',
                EstadoExpediente::Cerrado => '#22C55E',
            };
        }

        return [
            'datasets' => [
                [
                    'label' => 'Casos',
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
