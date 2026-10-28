<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoConsultaIa: string implements HasLabel
{
    case Resumen = 'resumen';
    case Borrador = 'borrador';
    case Chat = 'chat';

    public function label(): string
    {
        return match ($this) {
            self::Resumen => 'Resumen',
            self::Borrador => 'Borrador',
            self::Chat => 'Chat jurídico',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
