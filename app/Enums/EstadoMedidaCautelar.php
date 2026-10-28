<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoMedidaCautelar: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case Vigente = 'vigente';
    case Vencida = 'vencida';
    case Revocada = 'revocada';
    case Cumplida = 'cumplida';

    public function label(): string
    {
        return match ($this) {
            self::Vigente => 'Vigente',
            self::Vencida => 'Vencida',
            self::Revocada => 'Revocada',
            self::Cumplida => 'Cumplida',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Vigente => 'warning',
            self::Vencida => 'danger',
            self::Revocada => 'gray',
            self::Cumplida => 'success',
        };
    }
}
