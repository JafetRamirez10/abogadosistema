<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Semaforo: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case Verde = 'verde';
    case Amarillo = 'amarillo';
    case Rojo = 'rojo';
    case Vencido = 'vencido';

    public function label(): string
    {
        return match ($this) {
            self::Verde => 'Verde',
            self::Amarillo => 'Amarillo',
            self::Rojo => 'Rojo',
            self::Vencido => 'Vencido',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Verde => 'success',
            self::Amarillo => 'warning',
            self::Rojo => 'danger',
            self::Vencido => 'gray',
        };
    }

    public function badge(int $dias): string
    {
        if ($dias === 0) {
            return $this->label().' · hoy';
        }

        $abs = abs($dias);
        $unidad = $abs === 1 ? 'día' : 'días';

        return $this->label().' · '.$abs.' '.$unidad;
    }
}
