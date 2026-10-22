<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RolParte: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case Actor = 'actor';
    case Demandado = 'demandado';
    case Tercero = 'tercero';
    case Testigo = 'testigo';
    case Perito = 'perito';
    case ApoderadoContrario = 'apoderado_contrario';

    public function label(): string
    {
        return match ($this) {
            self::Actor => 'Actor',
            self::Demandado => 'Demandado',
            self::Tercero => 'Tercero',
            self::Testigo => 'Testigo',
            self::Perito => 'Perito',
            self::ApoderadoContrario => 'Apoderado contrario',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Actor => 'success',
            self::Demandado => 'danger',
            self::Tercero => 'warning',
            self::Testigo => 'info',
            self::Perito => 'purple',
            self::ApoderadoContrario => 'gray',
        };
    }
}
