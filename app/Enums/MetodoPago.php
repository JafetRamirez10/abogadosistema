<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MetodoPago: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case Efectivo = 'efectivo';
    case Transferencia = 'transferencia';
    case Sinpe = 'sinpe';
    case Tarjeta = 'tarjeta';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Efectivo => 'Efectivo',
            self::Transferencia => 'Transferencia',
            self::Sinpe => 'SINPE Móvil',
            self::Tarjeta => 'Tarjeta',
            self::Otro => 'Otro',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Efectivo => 'success',
            self::Transferencia => 'info',
            self::Sinpe => 'primary',
            self::Tarjeta => 'warning',
            self::Otro => 'gray',
        };
    }
}
