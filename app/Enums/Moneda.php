<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Moneda: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case CRC = 'CRC';
    case USD = 'USD';

    public function label(): string
    {
        return match ($this) {
            self::CRC => 'Colones (CRC)',
            self::USD => 'Dólares (USD)',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::CRC => 'success',
            self::USD => 'info',
        };
    }
}
