<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoExpediente: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case EnProgreso = 'en_progreso';
    case Pendiente = 'pendiente';
    case EnRevision = 'en_revision';
    case Cerrado = 'cerrado';

    public function label(): string
    {
        return match ($this) {
            self::EnProgreso => 'En progreso',
            self::Pendiente => 'Pendiente',
            self::EnRevision => 'En revisión',
            self::Cerrado => 'Cerrado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EnProgreso => 'info',
            self::Pendiente => 'warning',
            self::EnRevision => 'purple',
            self::Cerrado => 'success',
        };
    }
}
