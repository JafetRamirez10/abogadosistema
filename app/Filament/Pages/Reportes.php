<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\EstadoCuotaAlimentaria;
use App\Models\CuotaAlimentaria;
use App\Models\MedidaCautelar;
use App\Enums\EstadoMedidaCautelar;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class Reportes extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Reportes';

    protected static ?string $title = 'Reportes';

    protected static ?int $navigationSort = 7;

    protected static string $view = 'filament.pages.reportes';

    /**
     * @return Collection<int, CuotaAlimentaria>
     */
    public function cuotasEnMora(): Collection
    {
        $hoy = now('America/Costa_Rica')->toDateString();

        return CuotaAlimentaria::query()
            ->with(['expediente', 'pension'])
            ->whereIn('estado', [
                EstadoCuotaAlimentaria::Pendiente,
                EstadoCuotaAlimentaria::Parcial,
            ])
            ->whereDate('vence_el', '<', $hoy)
            ->orderBy('vence_el')
            ->limit(50)
            ->get();
    }

    /**
     * @return Collection<int, MedidaCautelar>
     */
    public function medidasPorRevisar(): Collection
    {
        $limite = now('America/Costa_Rica')->addDays(7)->toDateString();

        return MedidaCautelar::query()
            ->with('expediente')
            ->where('estado', EstadoMedidaCautelar::Vigente)
            ->where(function ($q) use ($limite): void {
                $q->whereDate('fecha_revision', '<=', $limite)
                    ->orWhereDate('fecha_vencimiento', '<=', $limite);
            })
            ->orderBy('fecha_revision')
            ->limit(50)
            ->get();
    }
}
