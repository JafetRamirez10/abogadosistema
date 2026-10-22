<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TipoHonorario: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case Fijo = 'fijo';
    case PorHora = 'por_hora';
    case Porcentaje = 'porcentaje';

    public function label(): string
    {
        return match ($this) {
            self::Fijo => 'Fijo',
            self::PorHora => 'Por hora',
            self::Porcentaje => 'Porcentaje',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Fijo => 'success',
            self::PorHora => 'info',
            self::Porcentaje => 'warning',
        };
    }
}
