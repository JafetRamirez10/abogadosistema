<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoEscritoIa: string implements HasLabel
{
    case Demanda = 'demanda';
    case Contestacion = 'contestacion';
    case Recurso = 'recurso';
    case EscritoVarios = 'escrito_varios';
    case Memorial = 'memorial';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Demanda => 'Demanda',
            self::Contestacion => 'Contestación',
            self::Recurso => 'Recurso',
            self::EscritoVarios => 'Escrito de varios',
            self::Memorial => 'Memorial',
            self::Otro => 'Otro',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
