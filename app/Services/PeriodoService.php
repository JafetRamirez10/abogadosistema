<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\PeriodoData;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class PeriodoService
{
    public function para(CarbonInterface|string|null $fecha = null): PeriodoData
    {
        $referencia = $this->resolverFecha($fecha);

        $actualInicio = $referencia->startOfMonth();
        $actualFin = $referencia->endOfMonth();

        $mesAnterior = $referencia->subMonthNoOverflow();
        $anteriorInicio = $mesAnterior->startOfMonth();
        $anteriorFin = $mesAnterior->endOfMonth();

        return new PeriodoData(
            fechaReferencia: $referencia->startOfDay(),
            actualInicio: $actualInicio,
            actualFin: $actualFin,
            anteriorInicio: $anteriorInicio,
            anteriorFin: $anteriorFin,
        );
    }

    public function resolverFecha(CarbonInterface|string|null $fecha = null): CarbonImmutable
    {
        if ($fecha instanceof CarbonInterface) {
            return CarbonImmutable::parse($fecha->toDateTimeString(), 'America/Costa_Rica');
        }

        if (is_string($fecha) && filled($fecha)) {
            return CarbonImmutable::parse($fecha, 'America/Costa_Rica')->startOfDay();
        }

        return CarbonImmutable::now('America/Costa_Rica')->startOfDay();
    }
}
