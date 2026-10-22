<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PrioridadTarea: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case Alta = 'alta';
    case Media = 'media';
    case Baja = 'baja';

    public function label(): string
    {
        return match ($this) {
            self::Alta => 'Alta',
            self::Media => 'Media',
            self::Baja => 'Baja',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Alta => 'danger',
            self::Media => 'warning',
            self::Baja => 'success',
        };
    }
}
