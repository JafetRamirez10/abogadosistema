<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TipoCliente: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case Fisica = 'fisica';
    case Juridica = 'juridica';

    public function label(): string
    {
        return match ($this) {
            self::Fisica => 'Persona física',
            self::Juridica => 'Persona jurídica',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Fisica => 'info',
            self::Juridica => 'primary',
        };
    }
}
