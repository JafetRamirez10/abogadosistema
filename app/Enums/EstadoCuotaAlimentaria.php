<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoCuotaAlimentaria: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case Pendiente = 'pendiente';
    case Parcial = 'parcial';
    case Pagada = 'pagada';
    case Condonada = 'condonada';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Parcial => 'Pago parcial',
            self::Pagada => 'Pagada',
            self::Condonada => 'Condonada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendiente => 'warning',
            self::Parcial => 'info',
            self::Pagada => 'success',
            self::Condonada => 'gray',
        };
    }
}
