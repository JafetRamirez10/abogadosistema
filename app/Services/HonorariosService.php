<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Moneda;
use App\Models\Expediente;

final class HonorariosService
{
    public function totalPagado(Expediente $expediente, ?Moneda $moneda = null): string
    {
        $moneda ??= $expediente->moneda;

        $total = $expediente->pagos()
            ->where('moneda', $moneda)
            ->sum('monto');

        return number_format((float) $total, 2, '.', '');
    }

    public function saldoPendiente(Expediente $expediente, ?Moneda $moneda = null): string
    {
        $moneda ??= $expediente->moneda;
        $honorario = (float) ($expediente->honorario_pactado ?? 0);
        $pagado = (float) $this->totalPagado($expediente, $moneda);

        return number_format($honorario - $pagado, 2, '.', '');
    }

    public function horasTotales(Expediente $expediente): float
    {
        $minutos = (int) $expediente->horas()->sum('minutos');

        return round($minutos / 60, 2);
    }
}
