<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EstadoEvento;
use App\Enums\Semaforo;
use Carbon\CarbonInterface;

final class SemaforoService
{
    public static function para(CarbonInterface $fecha, EstadoEvento $estado): Semaforo
    {
        if ($estado !== EstadoEvento::Pendiente) {
            return Semaforo::Verde;
        }

        $dias = self::diasHasta($fecha);

        if ($dias < 0) {
            return Semaforo::Vencido;
        }

        if ($dias < 2) {
            return Semaforo::Rojo;
        }

        if ($dias <= 5) {
            return Semaforo::Amarillo;
        }

        return Semaforo::Verde;
    }

    public static function diasHasta(CarbonInterface $fecha): int
    {
        $hoy = now('America/Costa_Rica')->startOfDay();
        $diaEvento = $fecha->toImmutable()->timezone('America/Costa_Rica')->startOfDay();

        return (int) $hoy->diffInDays($diaEvento, false);
    }

    public static function badge(CarbonInterface $fecha, EstadoEvento $estado): string
    {
        $semaforo = self::para($fecha, $estado);
        $dias = self::diasHasta($fecha);

        return $semaforo->badge($dias);
    }
}
