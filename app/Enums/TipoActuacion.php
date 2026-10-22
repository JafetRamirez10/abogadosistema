<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TipoActuacion: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case Escrito = 'escrito';
    case Resolucion = 'resolucion';
    case Audiencia = 'audiencia';
    case Notificacion = 'notificacion';
    case Nota = 'nota';

    public function label(): string
    {
        return match ($this) {
            self::Escrito => 'Escrito',
            self::Resolucion => 'Resolución',
            self::Audiencia => 'Audiencia',
            self::Notificacion => 'Notificación',
            self::Nota => 'Nota',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Escrito => 'info',
            self::Resolucion => 'purple',
            self::Audiencia => 'danger',
            self::Notificacion => 'warning',
            self::Nota => 'gray',
        };
    }
}
