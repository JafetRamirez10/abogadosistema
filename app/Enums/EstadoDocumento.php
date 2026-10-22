<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoDocumento: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case PendienteRevision = 'pendiente_revision';
    case PendienteFirma = 'pendiente_firma';
    case PendienteAprobacion = 'pendiente_aprobacion';
    case Aprobado = 'aprobado';

    public function label(): string
    {
        return match ($this) {
            self::PendienteRevision => 'Pendiente de revisión',
            self::PendienteFirma => 'Pendiente de firma',
            self::PendienteAprobacion => 'Pendiente de aprobación',
            self::Aprobado => 'Aprobado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PendienteRevision => 'warning',
            self::PendienteFirma => 'info',
            self::PendienteAprobacion => 'purple',
            self::Aprobado => 'success',
        };
    }
}
