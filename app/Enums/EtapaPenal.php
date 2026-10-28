<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EtapaPenal: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case Investigacion = 'investigacion';
    case Imputacion = 'imputacion';
    case EtapaIntermedia = 'etapa_intermedia';
    case Juicio = 'juicio';
    case Recursos = 'recursos';
    case Ejecucion = 'ejecucion';
    case Archivado = 'archivado';

    public function label(): string
    {
        return match ($this) {
            self::Investigacion => 'Investigación',
            self::Imputacion => 'Imputación',
            self::EtapaIntermedia => 'Etapa intermedia',
            self::Juicio => 'Juicio',
            self::Recursos => 'Recursos',
            self::Ejecucion => 'Ejecución',
            self::Archivado => 'Archivado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Investigacion => 'gray',
            self::Imputacion => 'warning',
            self::EtapaIntermedia => 'info',
            self::Juicio => 'danger',
            self::Recursos => 'purple',
            self::Ejecucion => 'success',
            self::Archivado => 'gray',
        };
    }
}
