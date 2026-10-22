<?php

declare(strict_types=1);

use App\Enums\Moneda;
use App\Support\Dinero;

if (! function_exists('formatear_monto')) {
    function formatear_monto(float|int|string $monto, Moneda|string $moneda = Moneda::CRC): string
    {
        return Dinero::formatear($monto, $moneda);
    }
}
