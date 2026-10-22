<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Cliente;
use App\Models\Documento;
use App\Models\Evento;
use App\Models\Expediente;
use App\Services\DashboardMetricsService;
use App\Services\PeriodoService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Gate;

class DashboardStatsOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected function getColumns(): int
    {
        return 4;
    }

    public static function canView(): bool
    {
        return Gate::allows('viewAny', Expediente::class)
            || Gate::allows('viewAny', Cliente::class)
            || Gate::allows('viewAny', Evento::class)
            || Gate::allows('viewAny', Documento::class);
    }

    protected function getStats(): array
    {
        $fecha = app(PeriodoService::class)->resolverFecha($this->filters['fecha'] ?? null);
        $resumen = app(DashboardMetricsService::class)->resumen($fecha);

        return [
            $this->stat(
                'Casos Activos',
                $resumen['casos_activos']['valor'],
                $resumen['casos_activos']['variacion'],
                'heroicon-o-folder-open',
                'primary',
            ),
            $this->stat(
                'Audiencias Hoy',
                $resumen['audiencias_hoy']['valor'],
                $resumen['audiencias_hoy']['variacion'],
                'heroicon-o-scale',
                'warning',
            ),
            $this->stat(
                'Clientes Totales',
                $resumen['clientes_totales']['valor'],
                $resumen['clientes_totales']['variacion'],
                'heroicon-o-users',
                'success',
            ),
            $this->stat(
                'Documentos Pendientes',
                $resumen['documentos_pendientes']['valor'],
                $resumen['documentos_pendientes']['variacion'],
                'heroicon-o-document-text',
                'danger',
            ),
        ];
    }

    private function stat(string $label, int $valor, ?float $variacion, string $icon, string $color): Stat
    {
        $stat = Stat::make($label, (string) $valor)
            ->icon($icon)
            ->color($color);

        if ($variacion === null) {
            return $stat->description('Sin período anterior comparable');
        }

        $signo = $variacion > 0 ? '+' : '';
        $trend = $variacion >= 0 ? 'up' : 'down';

        return $stat
            ->description($signo.number_format($variacion, 1).'% vs período anterior')
            ->descriptionIcon($trend === 'up' ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down');
    }
}
