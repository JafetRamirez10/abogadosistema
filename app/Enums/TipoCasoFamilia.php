<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TipoCasoFamilia: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case PensionAlimentaria = 'pension_alimentaria';
    case DivorcioSeparacion = 'divorcio_separacion';
    case GuardaCrianza = 'guarda_crianza';
    case InterrelacionFamiliar = 'interrelacion_familiar';
    case ViolenciaDomestica = 'violencia_domestica';
    case Filiacion = 'filiacion';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::PensionAlimentaria => 'Pensión alimentaria',
            self::DivorcioSeparacion => 'Divorcio / separación',
            self::GuardaCrianza => 'Guarda, crianza y educación',
            self::InterrelacionFamiliar => 'Régimen de interrelación',
            self::ViolenciaDomestica => 'Violencia doméstica',
            self::Filiacion => 'Filiación',
            self::Otro => 'Otro (familia)',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PensionAlimentaria => 'warning',
            self::DivorcioSeparacion => 'info',
            self::GuardaCrianza => 'success',
            self::InterrelacionFamiliar => 'primary',
            self::ViolenciaDomestica => 'danger',
            self::Filiacion => 'purple',
            self::Otro => 'gray',
        };
    }
}
