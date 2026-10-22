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

if (! function_exists('asset_version')) {
    /**
     * Versión de assets para cache-busting (?v=…).
     * Subí ASSET_VERSION en .env tras cambios de CSS/JS.
     */
    function asset_version(): string
    {
        return (string) config('app.asset_version', '1');
    }
}

if (! function_exists('asset_v')) {
    /**
     * Igual que asset(), pero con ?v=ASSET_VERSION (o &v= si ya hay query).
     */
    function asset_v(string $path, ?bool $secure = null): string
    {
        $url = asset($path, $secure);
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.'v='.rawurlencode(asset_version());
    }
}
