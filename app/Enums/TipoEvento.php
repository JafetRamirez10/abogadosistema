<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TipoEvento: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case Audiencia = 'audiencia';
    case Vencimiento = 'vencimiento';
    case Cita = 'cita';
    case Recordatorio = 'recordatorio';

    public function label(): string
    {
        return match ($this) {
            self::Audiencia => 'Audiencia',
            self::Vencimiento => 'Vencimiento',
            self::Cita => 'Cita',
            self::Recordatorio => 'Recordatorio',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Audiencia => 'danger',
            self::Vencimiento => 'warning',
            self::Cita => 'info',
            self::Recordatorio => 'gray',
        };
    }
}
