<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Moneda;

final class Dinero
{
    public static function formatear(float|int|string $monto, Moneda|string $moneda = Moneda::CRC): string
    {
        $valor = (float) $monto;
        $monedaEnum = $moneda instanceof Moneda ? $moneda : Moneda::from($moneda);

        return match ($monedaEnum) {
            Moneda::CRC => '₡'.number_format($valor, 0, ',', '.'),
            Moneda::USD => '$'.number_format($valor, 2, '.', ','),
        };
    }
}
