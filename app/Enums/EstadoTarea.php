<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoTarea: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case Pendiente = 'pendiente';
    case Completada = 'completada';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Completada => 'Completada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendiente => 'warning',
            self::Completada => 'success',
        };
    }
}
