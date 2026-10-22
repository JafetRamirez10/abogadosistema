<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\ActividadRecienteWidget;
use App\Filament\Widgets\AudienciasProximasWidget;
use App\Filament\Widgets\CasosRecientesWidget;
use App\Filament\Widgets\DashboardStatsOverview;
use App\Filament\Widgets\DistribucionCasosWidget;
use App\Filament\Widgets\MisTareasWidget;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Panel de Control';

    protected static ?int $navigationSort = 1;

    public function getHeading(): string
    {
        return 'Panel de Control';
    }

    public function getSubheading(): ?string
    {
        return 'Resumen general del despacho';
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('fecha')
                    ->label('Fecha de referencia')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->default(now('America/Costa_Rica')->toDateString())
                    ->required(),
            ]);
    }

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            DashboardStatsOverview::class,
            CasosRecientesWidget::class,
            AudienciasProximasWidget::class,
            MisTareasWidget::class,
            DistribucionCasosWidget::class,
            ActividadRecienteWidget::class,
        ];
    }

    /**
     * @return int | string | array<string, int | string | null>
     */
    public function getColumns(): int|string|array
    {
        return 12;
    }
}
