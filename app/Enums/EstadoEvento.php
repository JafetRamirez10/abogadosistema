<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoEvento: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case Pendiente = 'pendiente';
    case Cumplido = 'cumplido';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Cumplido => 'Cumplido',
            self::Cancelada => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendiente => 'warning',
            self::Cumplido => 'success',
            self::Cancelada => 'gray',
        };
    }
}
