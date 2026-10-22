<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Materia: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case Civil = 'civil';
    case Laboral = 'laboral';
    case Familia = 'familia';
    case Penal = 'penal';
    case ContenciosoAdministrativo = 'contencioso_administrativo';
    case Comercial = 'comercial';
    case Agrario = 'agrario';
    case Notarial = 'notarial';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Civil => 'Civil',
            self::Laboral => 'Laboral',
            self::Familia => 'Familia',
            self::Penal => 'Penal',
            self::ContenciosoAdministrativo => 'Contencioso administrativo',
            self::Comercial => 'Comercial',
            self::Agrario => 'Agrario',
            self::Notarial => 'Notarial',
            self::Otro => 'Otro',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Civil => 'info',
            self::Laboral => 'warning',
            self::Familia => 'pink',
            self::Penal => 'danger',
            self::ContenciosoAdministrativo => 'purple',
            self::Comercial => 'success',
            self::Agrario => 'lime',
            self::Notarial => 'gray',
            self::Otro => 'gray',
        };
    }
}
