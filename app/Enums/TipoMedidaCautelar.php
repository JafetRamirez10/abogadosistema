<?php

declare(strict_types=1);

namespace App\Enums;

use App\Concerns\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TipoMedidaCautelar: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case PrisionPreventiva = 'prision_preventiva';
    case ImpedimentoSalida = 'impedimento_salida';
    case FirmaPeriodica = 'firma_periodica';
    case Caucion = 'caucion';
    case ArrestoDomiciliario = 'arresto_domiciliario';
    case Otra = 'otra';

    public function label(): string
    {
        return match ($this) {
            self::PrisionPreventiva => 'Prisión preventiva',
            self::ImpedimentoSalida => 'Impedimento de salida',
            self::FirmaPeriodica => 'Firma periódica',
            self::Caucion => 'Caución',
            self::ArrestoDomiciliario => 'Arresto domiciliario',
            self::Otra => 'Otra medida',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PrisionPreventiva => 'danger',
            self::ImpedimentoSalida => 'warning',
            self::FirmaPeriodica => 'info',
            self::Caucion => 'success',
            self::ArrestoDomiciliario => 'purple',
            self::Otra => 'gray',
        };
    }
}
